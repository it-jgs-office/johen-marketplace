<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Sinkronkan daftar metode pembayaran dengan data lokal (idempotent).
     *
     * Production hanya mengaktifkan VA BSI, BNI, dan CIMB melalui DOKU.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'GoPay',
                'code' => 'gopay',
                'category' => 'ewallet',
                'photo' => 'payments/gopay.svg',
                'photo_light' => 'payments/gopay-dark.png',
                'is_active' => false,
            ],
            [
                'name' => 'Dana',
                'code' => 'dana',
                'category' => 'ewallet',
                'photo' => 'payments/dana.svg',
                'is_active' => false,
            ],
            [
                'name' => 'LinkAja',
                'code' => 'linkaja',
                'category' => 'ewallet',
                'photo' => null,
                'photo_light' => null,
                // LinkAja sudah discontinued di Xendit, nonaktifkan agar tidak muncul.
                'is_active' => false,
            ],
            [
                'name' => 'ShopeePay',
                'code' => 'shopeepay',
                'category' => 'ewallet',
                'photo' => 'payments/shopeepay.svg',
                'is_active' => false,
            ],
            [
                'name' => 'Ovo',
                'code' => 'ovo',
                'category' => 'ewallet',
                'photo' => 'payments/ovo.svg',
                'is_active' => false,
            ],
            [
                'name' => 'QRIS',
                'code' => 'qris',
                'category' => 'qris',
                'photo' => 'payments/qris.svg',
                'photo_light' => 'payments/qris-dark.png',
                'is_active' => false,
            ],
            [
                'name' => 'BCA Virtual Account',
                'code' => 'bca_va',
                'category' => 'va',
                'photo' => 'payments/bca.svg',
                'is_active' => false,
            ],
            [
                'name' => 'BRI Virtual Account',
                'code' => 'bri_va',
                'category' => 'va',
                'photo' => 'payments/bri.svg',
                'is_active' => false,
            ],
            [
                'name' => 'BNI Virtual Account',
                'code' => 'bni_va',
                'category' => 'va',
                'photo' => 'payments/bni.svg',
                'is_active' => true,
            ],
            [
                'name' => 'BSI Virtual Account',
                'code' => 'bsi_va',
                'category' => 'va',
                'photo' => null,
                'photo_light' => null,
                'is_active' => true,
            ],
            [
                'name' => 'CIMB Virtual Account',
                'code' => 'cimb_va',
                'category' => 'va',
                'photo' => null,
                'photo_light' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Mandiri Virtual Account',
                'code' => 'mandiri_va',
                'category' => 'va',
                'photo' => 'payments/mandiri.svg',
                'is_active' => false,
            ],
            [
                'name' => 'Permata Virtual Account',
                'code' => 'permata_va',
                'category' => 'va',
                'photo' => null,
                'photo_light' => null,
                'is_active' => false,
            ],
            [
                'name' => 'Alfamart',
                'code' => 'alfamart',
                'category' => 'convenience_store',
                'photo' => 'payments/alfamart.svg',
                'is_active' => false,
            ],
            [
                'name' => 'Indomaret',
                'code' => 'indomaret',
                'category' => 'convenience_store',
                'photo' => 'payments/indomaret.svg',
                'is_active' => false,
            ],
        ];

        $seededCodes = [];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method,
            );
            $seededCodes[] = $method['code'];
        }

        // Nonaktifkan metode yang tidak ada di daftar (agar tampilan konsisten
        // dengan lokal). Data tetap disimpan, hanya tidak ditampilkan.
        PaymentMethod::whereNotIn('code', $seededCodes)->update(['is_active' => false]);
    }
}
