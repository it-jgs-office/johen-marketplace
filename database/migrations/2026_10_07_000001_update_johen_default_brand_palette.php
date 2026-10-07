<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('event_themes')
            ->where('slug', 'default')
            ->update([
                'colors' => json_encode([
                    'primary' => '#2563eb',
                    'accent' => '#00d4ff',
                    'bg' => '#01203c',
                    'bg_soft' => '#052a48',
                    'card_bg' => '#0A1E50',
                    'text' => '#f0f4ff',
                    'text_on_primary' => '#ffffff',
                ], JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('event_themes')
            ->where('slug', 'default')
            ->update([
                'colors' => json_encode([
                    'primary' => '#7c3aed',
                    'accent' => '#f0c419',
                    'bg' => '#100821',
                    'bg_soft' => '#160b2c',
                    'card_bg' => '#1e1136',
                    'text' => '#f5f3fb',
                    'text_on_primary' => '#ffffff',
                ], JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }
};
