<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('points_used', 15, 2)->default(0)->after('saldo_status');
            $table->string('points_status', 16)->default('none')->after('points_used');
            $table->decimal('cash_amount', 15, 2)->default(0)->after('points_status');
        });

        DB::table('orders')->where('cash_amount', 0)->update(['cash_amount' => DB::raw('price')]);

        Schema::table('checkouts', function (Blueprint $table) {
            $table->decimal('points_used', 15, 2)->default(0)->after('total');
            $table->decimal('cash_amount', 15, 2)->default(0)->after('points_used');
            $table->string('points_status', 16)->default('none')->after('cash_amount');
        });

        DB::table('checkouts')->where('cash_amount', 0)->update(['cash_amount' => DB::raw('total')]);

        Schema::table('account_orders', function (Blueprint $table) {
            $table->decimal('points_used', 15, 2)->default(0)->after('total_price');
            $table->string('points_status', 16)->default('none')->after('points_used');
            $table->decimal('cash_amount', 15, 2)->default(0)->after('points_status');
            $table->decimal('saldo_amount', 15, 2)->default(0)->after('cash_amount');
            $table->string('saldo_status', 16)->default('none')->after('saldo_amount');
            $table->timestamp('saldo_released_at')->nullable()->after('saldo_status');
        });

        DB::table('account_orders')->where('cash_amount', 0)->update(['cash_amount' => DB::raw('total_price')]);
        DB::table('account_orders')->where('status', 'success')->update([
            'saldo_amount' => DB::raw('total_price'),
            'saldo_status' => 'consumed',
        ]);

        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->foreignId('account_order_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->foreignId('checkout_id')->nullable()->after('account_order_id')->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->after('checkout_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('order_provider_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->string('provider_ref')->nullable();
            $table->string('status', 32)->nullable();
            $table->text('message')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('order_admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->text('reason')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->json('payload')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_admin_actions');
        Schema::dropIfExists('order_provider_attempts');

        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_id');
            $table->dropConstrainedForeignId('checkout_id');
            $table->dropConstrainedForeignId('account_order_id');
        });

        Schema::table('account_orders', fn (Blueprint $table) => $table->dropColumn([
            'points_used', 'points_status', 'cash_amount', 'saldo_amount', 'saldo_status', 'saldo_released_at',
        ]));
        Schema::table('checkouts', fn (Blueprint $table) => $table->dropColumn(['points_used', 'cash_amount', 'points_status']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['points_used', 'points_status', 'cash_amount']));
    }
};
