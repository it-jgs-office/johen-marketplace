<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('brands', 'detail_bg')) {
            return;
        }

        $values = ['detail_bg' => null];

        if (Schema::hasColumn('brands', 'detail_bg_position')) {
            $values['detail_bg_position'] = 'center';
        }

        DB::table('brands')->update($values);
    }

    public function down(): void
    {
        // Sampul lama tidak dapat dipulihkan setelah seluruh brand memakai default.
    }
};
