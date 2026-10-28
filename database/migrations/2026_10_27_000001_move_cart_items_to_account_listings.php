<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedBigInteger('account_listing_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unique(['user_id', 'account_listing_id']);
            $table->index('account_listing_id');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'account_listing_id']);
            $table->dropIndex(['account_listing_id']);
            $table->dropColumn('account_listing_id');
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
