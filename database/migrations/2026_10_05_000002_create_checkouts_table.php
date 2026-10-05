<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Induk dari beberapa `orders`. Satu checkout = satu charge gateway
        // untuk total gabungan, lalu setiap order anak diselesaikan sendiri
        // lewat TopupSettlementService (satu top-up Digiflazz per SKU).
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->string('checkout_ref')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable();

            // Kolom gateway sengaja diberi nama sama dengan `orders` supaya
            // PaymentGatewayService::runCharge() bisa dipakai tanpa perubahan.
            $table->string('gateway_invoice_id')->nullable()->index();
            $table->string('gateway_invoice_url')->nullable();
            $table->string('gateway_type')->nullable();
            $table->text('qr_string')->nullable();
            $table->string('va_number')->nullable();
            $table->string('payment_code')->nullable();
            $table->text('checkout_url')->nullable();
            $table->json('gateway_extra')->nullable();

            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('item_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};