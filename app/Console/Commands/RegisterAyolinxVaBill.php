<?php

namespace App\Console\Commands;

use App\Services\AyolinxPaymentService;
use Illuminate\Console\Command;

class RegisterAyolinxVaBill extends Command
{
    protected $signature = 'ayolinx:register-va-bill
                            {reference : Referensi lokal order/checkout}
                            {virtual-account : Nomor VA hasil Create VA}
                            {trx-id : trxId yang dikirim saat Create VA}
                            {customer-no : customerNo yang dikirim saat Create VA}
                            {partner-service-id : partnerServiceId yang dikirim saat Create VA}';

    protected $description = 'Hubungkan VA Ayolinx yang sudah dibuat ke tagihan lokal untuk menerima callback pembayaran';

    public function handle(AyolinxPaymentService $payments): int
    {
        $payment = $payments->findByReference(trim((string) $this->argument('reference')));

        if (! $payment) {
            $this->error('Tagihan lokal tidak ditemukan.');

            return self::FAILURE;
        }

        if ($payment->status !== 'pending') {
            $this->error('Tagihan harus berstatus pending.');

            return self::FAILURE;
        }

        if ((float) $payment->cash_amount <= 0) {
            $this->error('Nominal cash_amount tagihan tidak valid.');

            return self::FAILURE;
        }

        $virtualAccountNo = trim((string) $this->argument('virtual-account'));
        $trxId = trim((string) $this->argument('trx-id'));
        $customerNo = trim((string) $this->argument('customer-no'));
        $partnerServiceId = trim((string) $this->argument('partner-service-id'));

        foreach (compact('virtualAccountNo', 'trxId', 'customerNo', 'partnerServiceId') as $value) {
            if ($value === '') {
                $this->error('Semua data VA wajib diisi.');

                return self::FAILURE;
            }
        }

        $existing = $payments->findByVirtualAccount($virtualAccountNo);

        if ($existing && ! $existing->is($payment)) {
            $this->error('Nomor VA sudah terhubung ke tagihan lain.');

            return self::FAILURE;
        }

        $extra = (array) $payment->gateway_extra;
        $extra['provider'] = 'ayolinx';
        $extra['integration'] = 'virtual_account';
        $extra['environment'] = config('ayolinx.is_production') ? 'production' : 'sandbox';
        $extra['partner_reference_no'] = $trxId;
        $extra['trx_id'] = $trxId;
        $extra['virtual_account_no'] = $virtualAccountNo;
        $extra['customer_no'] = $customerNo;
        $extra['partner_service_id'] = $partnerServiceId;
        $extra['expected_amount'] = (int) round((float) $payment->cash_amount);

        $payment->update([
            'va_number' => $virtualAccountNo,
            'gateway_extra' => $extra,
        ]);

        $this->info('Tagihan VA Ayolinx berhasil didaftarkan.');
        $this->line('Nominal callback wajib sama dengan cash_amount tagihan: '.number_format((float) $payment->cash_amount, 2, '.', ''));

        return self::SUCCESS;
    }
}
