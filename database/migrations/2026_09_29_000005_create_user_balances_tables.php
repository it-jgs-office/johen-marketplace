<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        // Buku besar saldo. Setiap perubahan saldo punya satu baris agar bisa
        // diaudit; `balance_after` menyimpan snapshot supaya mudah direkonsiliasi.
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->json('meta')->nullable();

            // Kunci idempotensi: satu aksi bisnis hanya boleh dicatat sekali
            // walau callback Ayolinx/Digiflazz terkirim berulang.
            $table->string('idempotency_key')->nullable()->unique();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('user_balances');
    }
};
