<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\TopupSettlementService;
use Illuminate\Console\Command;

/**
 * Menutup celah order topup yang menggantung.
 *
 * Webhook Digiflazz tidak selalu sampai. Kalau tidak ada yang mencari status
 * akhir, order akan selamanya berstatus "processing": uang user sudah masuk ke
 * saldonya, tapi diamond tidak pernah masuk, dan tidak ada yang mengembalikan
 * saldonya.
 *
 * Command ini cadangan yang menutup celah tersebut:
 *   - status masih pending -> poll Digiflazz, terapkan kalau sudah final
 *   - lewat batas waktu     -> kembalikan saldo ke user secara otomatis
 */
class ReconcileDigiflazzOrders extends Command
{
    protected $signature = 'digiflazz:reconcile
                            {--limit= : Berapa order diproses sekali jalan}
                            {--refund-only : Lewati polling, langsung proses yang lewat batas waktu}';

    protected $description = 'Polling status topup Digiflazz yang menggantung dan kembalikan saldo otomatis bila lewat batas waktu';

    public function handle(TopupSettlementService $settlement): int
    {
        $grace = (int) config('digiflazz.reconcile.grace_minutes', 30);
        $providerErrorGrace = (int) config('digiflazz.reconcile.provider_error_grace_minutes', 10);
        $maxPolls = (int) config('digiflazz.reconcile.max_polls', 12);
        $limit = (int) ($this->option('limit') ?: config('digiflazz.reconcile.chunk', 50));
        $refundOnly = (bool) $this->option('refund-only');

        $orders = Order::query()
            ->where('status', 'processing')
            ->where('saldo_status', 'held')
            ->when($refundOnly, fn ($q) => $q->where(function ($q) use ($grace, $providerErrorGrace, $maxPolls) {
                $q->where(function ($providerError) use ($providerErrorGrace) {
                    $providerError->where('reconcile_status', 'provider_error')
                        ->where('provider_error_at', '<=', now()->subMinutes(max(1, $providerErrorGrace)));
                })->orWhere(function ($pending) use ($grace, $maxPolls) {
                    $pending->where('reconcile_status', '!=', 'provider_error')
                        ->where(function ($expired) use ($grace, $maxPolls) {
                            $expired->where('digiflazz_started_at', '<=', now()->subMinutes(max(1, $grace)))
                                ->orWhere('digiflazz_poll_count', '>=', $maxPolls);
                        });
                });
            }))
            ->orderBy('digiflazz_started_at')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada topup yang perlu direkonsiliasi.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Memeriksa %d topup (batas waktu %d menit, maks %d poll).',
            $orders->count(),
            $grace,
            $maxPolls
        ));

        $settled = 0;
        $refunded = 0;
        $stillPending = 0;
        $polled = 0;

        foreach ($orders as $order) {
            // Error provider diberi waktu khusus untuk diperbaiki dan di-retry
            // admin. Selama masa tunggu ini jangan polling otomatis karena
            // respons gagal berulang tidak boleh menggeser tenggat refund.
            if ($settlement->isWaitingForProviderErrorRetry($order)) {
                if (! $settlement->hasExceededProviderErrorGracePeriod($order)) {
                    $stillPending++;
                    continue;
                }

                $settlement->settleFailure($order, $settlement->providerErrorRefundReason($order));
                $refunded++;
                $this->line(sprintf(
                    '  <comment>%s</comment> -> error provider lewat %d menit, refund ke Johen Point',
                    $order->order_id,
                    $providerErrorGrace
                ));
                continue;
            }

            // 1. Tanya Digiflazz dulu. Selalu lebih baik memastikan topup
            //    benar-benar gagal daripada buru-buru mengembalikan saldo.
            if (! $refundOnly) {
                $polled++;
                $final = $settlement->poll($order);

                if ($final !== null) {
                    if ($final === 'provider_error') {
                        $stillPending++;
                        $this->line(sprintf(
                            '  <comment>%s</comment> -> error provider, menunggu retry admin atau refund otomatis',
                            $order->order_id
                        ));
                        continue;
                    }

                    $settled++;
                    $this->line(sprintf(
                        '  <info>%s</info> -> %s',
                        $order->order_id,
                        'topup berhasil, saldo terpakai'
                    ));

                    continue;
                }
            }

            $order->refresh();

            // 2. Masih pending. Sudah lewat batas waktu atau batas poll?
            $expired = $settlement->hasExceededGracePeriod($order)
                || $settlement->hasExceededPollLimit($order);

            if (! $expired) {
                $stillPending++;

                continue;
            }

            $settlement->settleFailure(
                $order,
                'Topup tidak selesai dalam batas waktu. Saldo dikembalikan otomatis.'
            );

            $refunded++;
            $this->line(sprintf(
                '  <comment>%s</comment> -> lewat batas waktu, saldo dikembalikan',
                $order->order_id
            ));
        }

        $this->newLine();
        $this->info(sprintf(
            'Selesai: %d berhasil, %d dikembalikan, %d masih diproses%s.',
            $settled,
            $refunded,
            $stillPending,
            $refundOnly ? '' : sprintf(' (poll ke Digiflazz: %d)', $polled)
        ));

        return self::SUCCESS;
    }
}
