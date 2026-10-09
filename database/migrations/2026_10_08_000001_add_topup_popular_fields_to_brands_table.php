<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->boolean('is_topup_popular')->default(false)->after('is_popular');
            $table->string('topup_popular_image')->nullable()->after('is_topup_popular');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['is_topup_popular', 'topup_popular_image']);
        });
    }
};
