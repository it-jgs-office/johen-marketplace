<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $supported = [
            'qris', 'gopay',
            'bca_va', 'bni_va', 'bri_va', 'cimb_va', 'mandiri_va', 'permata_va',
            'alfamart', 'indomaret',
        ];

        DB::table('payment_methods')->update(['is_active' => false]);
        DB::table('payment_methods')->whereIn('code', $supported)->update(['is_active' => true]);
    }

    public function down(): void
    {
        // Status metode sebelumnya tidak dapat dipulihkan secara akurat.
    }
};
