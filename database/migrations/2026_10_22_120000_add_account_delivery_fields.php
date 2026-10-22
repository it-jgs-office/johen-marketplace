<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_listings', function (Blueprint $table) {
            $table->text('login_credentials')->nullable()->after('whatsapp');
        });

        Schema::table('account_orders', function (Blueprint $table) {
            $table->timestamp('credentials_email_attempted_at')->nullable()->after('status');
            $table->timestamp('credentials_email_sent_at')->nullable()->after('credentials_email_attempted_at');
            $table->text('credentials_email_error')->nullable()->after('credentials_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('account_orders', function (Blueprint $table) {
            $table->dropColumn(['credentials_email_attempted_at', 'credentials_email_sent_at', 'credentials_email_error']);
        });

        Schema::table('account_listings', function (Blueprint $table) {
            $table->dropColumn('login_credentials');
        });
    }
};
