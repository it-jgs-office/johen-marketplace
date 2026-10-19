<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('provider_error_at')->nullable()->after('last_status_check_at');
            $table->text('provider_error_reason')->nullable()->after('provider_error_at');
            $table->index(['reconcile_status', 'provider_error_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['reconcile_status', 'provider_error_at']);
            $table->dropColumn(['provider_error_at', 'provider_error_reason']);
        });
    }
};
