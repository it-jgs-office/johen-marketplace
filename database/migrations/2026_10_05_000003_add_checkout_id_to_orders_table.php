<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order hasil checkout keranjang punya induk. Order top-up biasa
        // (Beli Sekarang / pesan inline) tetap punya checkout_id null.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('checkout_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkout_id');
        });
    }
};