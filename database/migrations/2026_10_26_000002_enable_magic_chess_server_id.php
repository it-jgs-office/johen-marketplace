<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('brands')
            ->whereRaw('LOWER(name) = ?', ['magic chess'])
            ->update(['requires_zone_id' => true]);
    }

    public function down(): void
    {
        DB::table('brands')
            ->whereRaw('LOWER(name) = ?', ['magic chess'])
            ->update(['requires_zone_id' => false]);
    }
};
