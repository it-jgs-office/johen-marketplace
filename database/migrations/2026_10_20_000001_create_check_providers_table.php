<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_providers', function (Blueprint $table) {
            $table->id();
            // Pola nama brand wildcard, mis. "mobile legends*" (case-insensitive).
            $table->string('brand_pattern');
            $table->string('label')->nullable();

            // Provider gratis utama: alu / isan / gopay / enka / none
            $table->string('free_type')->default('none');
            $table->json('free_config')->nullable();

            // Provider gratis cadangan (opsional).
            $table->string('fallback_free_type')->nullable();
            $table->json('fallback_free_config')->nullable();

            // Fallback berbayar Digiflazz.
            $table->boolean('digiflazz_fallback_enabled')->default(false);
            $table->string('digiflazz_sku')->nullable();

            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->unique('brand_pattern');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_providers');
    }
};
