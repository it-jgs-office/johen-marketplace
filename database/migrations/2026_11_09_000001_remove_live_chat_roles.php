<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_live_chat_admin') && Schema::hasColumn('users', 'is_live_chat_cs')) {
            DB::table('users')
                ->where('is_admin', false)
                ->where(function ($query) {
                    $query->where('is_live_chat_admin', true)
                        ->orWhere('is_live_chat_cs', true);
                })
                ->delete();
        }

        Schema::dropIfExists('live_chat_operator_schedules');
        Schema::dropIfExists('live_chat_admins');
        Schema::dropIfExists('live_chat_operators');

        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['is_live_chat_admin', 'is_live_chat_cs'],
                fn (string $column) => Schema::hasColumn('users', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_live_chat_admin')) {
                $table->boolean('is_live_chat_admin')->default(false)->after('is_admin');
            }
            if (! Schema::hasColumn('users', 'is_live_chat_cs')) {
                $table->boolean('is_live_chat_cs')->default(false)->after('is_live_chat_admin');
            }
        });
    }
};
