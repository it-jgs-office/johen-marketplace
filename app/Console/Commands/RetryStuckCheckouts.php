<?php

namespace App\Console\Commands;

use App\Models\Checkout;
use App\Services\CheckoutSettlementService;
use Illuminate\Console\Command;

/**
 * Jaring pengaman untuk checkout keranjang.
 *
 * Settlement per item berjalan lewat queue. Kalau worker mati atau job hilang,
 * order anak akan menggantung di status `pending` padahal user sudah membayar
 * (dan kuota flash deal-nya sudah tertahan). Command ini memancing ulang
 * pekerjaan itu secara berkala.
 */
class RetryStuckCheckouts extends Command
{
    protected $signature = 'checkout:retry
                            {--limit=50 : Maksimal checkout yang diproses sekali jalan}';

    protected $description = 'Antrikan ulang settlement checkout yang order anaknya masih pending';

    public function handle(CheckoutSettlementService $checkoutSettlement): int
    {
        $limit = (int) $this->option('limit');

        $checkouts = Checkout::where('status', 'processing')
            ->whereHas('orders', fn ($q) => $q->where('status', 'pending'))
            ->orderBy('updated_at')
            ->limit(max(1, $limit))
            ->get();

        if ($checkouts->isEmpty()) {
            $this->info('Tidak ada checkout yang perlu diantrikan ulang.');

            return self::SUCCESS;
        }

        foreach ($checkouts as $checkout) {
            $checkoutSettlement->retryStuck($checkout);
        }

        $this->info("{$checkouts->count()} checkout diantrikan ulang.");

        return self::SUCCESS;
    }
}