<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('success_email_sent_at')->nullable()->after('reconcile_status');
            $table->timestamp('failed_email_sent_at')->nullable()->after('success_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['success_email_sent_at', 'failed_email_sent_at']);
        });
    }
};
