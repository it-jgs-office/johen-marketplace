<?php

namespace App\Services;

use App\Mail\AccountCredentialsMail;
use App\Models\AccountListing;
use App\Models\AccountOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountDeliveryService
{
    public function __construct(private BalanceService $balance) {}

    public function deliver(AccountOrder $accountOrder): bool
    {
        $lock = Cache::lock('account-stock-delivery:'.$accountOrder->account_listing_id, 120);

        try {
            return (bool) $lock->block(5, fn () => $this->deliverLocked($accountOrder));
        } catch (\Throwable $exception) {
            Log::error('Pengiriman data login akun gagal diproses', [
                'order_ref' => $accountOrder->order_ref,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function deliverLocked(AccountOrder $accountOrder): bool
    {
        $accountOrder = AccountOrder::with(['listing', 'user'])->find($accountOrder->getKey());

        if (! $accountOrder) {
            return false;
        }

        if ($accountOrder->credentials_email_sent_at) {
            if ($accountOrder->status !== 'success') {
                $accountOrder->update(['status' => 'success', 'credentials_email_error' => null]);
            }
            $accountOrder->listing?->update(['is_sold' => true]);
            $this->balance->markAccountDelivered($accountOrder->fresh());

            return true;
        }

        if (! in_array($accountOrder->status, ['pending', 'processing'], true)) {
            return false;
        }

        if ($accountOrder->credentials_email_error
            && $accountOrder->credentials_email_attempted_at?->isAfter(now()->subMinute())) {
            return false;
        }

        if (! data_get($accountOrder->gateway_extra, 'stock_claimed', false)) {
            $claimed = $this->claimStock($accountOrder);

            if ($claimed === null) {
                $accountOrder->update([
                    'status' => 'processing',
                    'credentials_email_attempted_at' => now(),
                    'credentials_email_error' => 'Status stok company belum dapat dikonfirmasi. Sistem akan mencoba kembali.',
                ]);

                return false;
            }

            if (! $claimed) {
                $extra = (array) $accountOrder->gateway_extra;
                $extra['stock_status'] = 'sold_out';
                $extra['requires_manual_refund'] = (float) $accountOrder->cash_amount > 0;
                $accountOrder->update([
                    'status' => 'sold_out',
                    'gateway_extra' => $extra,
                    'credentials_email_error' => 'Produk sudah terjual kepada pembeli lain.',
                ]);
                $this->balance->restorePoints($accountOrder, 'Produk akun sudah terjual; Johen Point dikembalikan');

                return false;
            }

            $extra = (array) $accountOrder->gateway_extra;
            $extra['stock_claimed'] = true;
            $extra['stock_claimed_at'] = now()->toIso8601String();
            $accountOrder->update(['gateway_extra' => $extra]);
        }

        $this->markCompetingOrdersSoldOut($accountOrder);

        $accountOrder->update([
            'status' => 'processing',
            'credentials_email_attempted_at' => now(),
            'credentials_email_error' => null,
        ]);
        $this->balance->markAccountPaid($accountOrder);
        $accountOrder->listing?->update(['is_sold' => true]);

        if ($accountOrder->listing?->source === 'jba_api'
            && ctype_digit((string) $accountOrder->listing->source_id)) {
            $latestCredentials = app(JbaApiClient::class)
                ->loginCredentials((int) $accountOrder->listing->source_id);

            if (blank($latestCredentials)) {
                $accountOrder->update([
                    'credentials_email_error' => 'Data login akun dari company belum dapat diambil. Sistem akan mencoba kembali.',
                ]);

                return false;
            }

            $accountOrder->listing->update(['login_credentials' => $latestCredentials]);
            $accountOrder->listing->refresh();
        }

        $recipient = trim((string) $accountOrder->customer_email);
        $credentials = trim((string) $accountOrder->listing?->login_credentials);

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || $credentials === '') {
            $error = $credentials === ''
                ? 'Data login akun belum diisi oleh admin.'
                : 'Alamat email penerima tidak valid.';
            $accountOrder->update(['credentials_email_error' => $error]);
            Log::warning('Data login akun belum dapat dikirim', [
                'order_ref' => $accountOrder->order_ref,
                'reason' => $error,
            ]);

            return false;
        }

        try {
            Mail::to($recipient)->send(new AccountCredentialsMail(
                $accountOrder,
                $credentials,
                business_info(),
            ));
        } catch (\Throwable $exception) {
            $accountOrder->update([
                'credentials_email_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            Log::error('Email data login akun gagal dikirim', [
                'order_ref' => $accountOrder->order_ref,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        $accountOrder->update([
            'status' => 'success',
            'credentials_email_sent_at' => now(),
            'credentials_email_error' => null,
        ]);
        $this->balance->markAccountDelivered($accountOrder->fresh());

        Log::info('Data login akun berhasil dikirim', [
            'order_ref' => $accountOrder->order_ref,
            'recipient' => $recipient,
        ]);

        return true;
    }

    private function claimStock(AccountOrder $accountOrder): ?bool
    {
        $listing = $accountOrder->listing;

        if (! $listing) {
            return false;
        }

        if ($listing->source === 'jba_api' && ctype_digit((string) $listing->source_id)) {
            return app(JbaApiClient::class)->claimSold((int) $listing->source_id, $accountOrder->order_ref);
        }

        return AccountListing::query()
            ->whereKey($listing->getKey())
            ->where('is_active', true)
            ->where('is_sold', false)
            ->update(['is_sold' => true]) === 1;
    }

    private function markCompetingOrdersSoldOut(AccountOrder $winner): void
    {
        AccountOrder::query()
            ->where('account_listing_id', $winner->account_listing_id)
            ->whereKeyNot($winner->getKey())
            ->where('status', 'pending')
            ->get()
            ->each(function (AccountOrder $order) use ($winner) {
                $extra = (array) $order->gateway_extra;
                $extra['stock_status'] = 'sold_out';
                $extra['sold_to_order_ref'] = $winner->order_ref;
                if (data_get($extra, 'payment_status') === 'SUCCESS') {
                    $extra['requires_manual_refund'] = true;
                }

                $order->update([
                    'status' => 'sold_out',
                    'gateway_extra' => $extra,
                    'credentials_email_error' => 'Produk sudah terjual kepada pembeli lain.',
                ]);
                $this->balance->restorePoints($order, 'Produk akun sudah terjual; Johen Point dikembalikan');
            });
    }
}
