<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('check_providers')) {
            return;
        }

        DB::table('check_providers')
            ->where('brand_pattern', 'free fire*')
            ->update([
                'free_type' => 'none',
                'free_config' => null,
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => false,
                'digiflazz_sku' => null,
                'updated_at' => now(),
            ]);

        Cache::forget('check_providers_all');
    }

    public function down(): void
    {
        // Pengecekan nickname Free Fire sengaja tidak dipulihkan.
    }
};
