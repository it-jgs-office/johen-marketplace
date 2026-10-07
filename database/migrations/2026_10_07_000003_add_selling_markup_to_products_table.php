<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('selling_markup_type', 16)->nullable()->after('selling_price_override');
            $table->decimal('selling_markup_value', 12, 2)->nullable()->after('selling_markup_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['selling_markup_type', 'selling_markup_value']);
        });
    }
};
