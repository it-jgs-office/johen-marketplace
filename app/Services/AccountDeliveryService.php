<?php

namespace App\Services;

use App\Mail\AccountCredentialsMail;
use App\Models\AccountOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AccountDeliveryService
{
    public function __construct(private BalanceService $balance) {}

    public function deliver(AccountOrder $accountOrder): bool
    {
        $lock = Cache::lock('account-credentials-delivery:'.$accountOrder->getKey(), 120);

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

        $accountOrder->update([
            'status' => 'processing',
            'credentials_email_attempted_at' => now(),
            'credentials_email_error' => null,
        ]);
        $this->balance->markAccountPaid($accountOrder);
        $accountOrder->listing?->update(['is_sold' => true]);

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
}
