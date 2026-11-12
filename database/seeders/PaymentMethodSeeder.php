<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Sinkronkan hanya kanal pembayaran yang didukung Ayolinx.
     */
    public function run(): void
    {
        $methods = [
            ['name' => 'DANA', 'code' => 'dana', 'category' => 'ewallet', 'photo' => 'payments/dana.svg', 'is_active' => true],
            ['name' => 'QRIS', 'code' => 'qris', 'category' => 'qris', 'photo' => 'payments/qris.svg', 'photo_light' => 'payments/qris-dark.png', 'is_active' => true],
            ['name' => 'BCA Virtual Account', 'code' => 'bca_va', 'category' => 'va', 'photo' => 'payments/bca.svg', 'is_active' => true],
            ['name' => 'BRI Virtual Account', 'code' => 'bri_va', 'category' => 'va', 'photo' => 'payments/bri.svg', 'is_active' => true],
            ['name' => 'BNI Virtual Account', 'code' => 'bni_va', 'category' => 'va', 'photo' => 'payments/bni.svg', 'is_active' => true],
            ['name' => 'CIMB Virtual Account', 'code' => 'cimb_va', 'category' => 'va', 'photo' => null, 'photo_light' => null, 'is_active' => true],
            ['name' => 'Mandiri Virtual Account', 'code' => 'mandiri_va', 'category' => 'va', 'photo' => 'payments/mandiri.svg', 'is_active' => true],
            ['name' => 'BSS Virtual Account', 'code' => 'bss_va', 'category' => 'va', 'photo' => null, 'photo_light' => null, 'is_active' => true],
            ['name' => 'Alfamart', 'code' => 'alfamart', 'category' => 'convenience_store', 'photo' => 'payments/alfamart.svg', 'is_active' => true],
        ];

        $seededCodes = [];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['code' => $method['code']], $method);
            $seededCodes[] = $method['code'];
        }

        PaymentMethod::whereNotIn('code', $seededCodes)->update(['is_active' => false]);
    }
}
