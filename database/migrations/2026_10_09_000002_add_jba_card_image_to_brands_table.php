<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('jba_card_image')->nullable()->after('thumbnail');
        });

        // Keep existing Jual Beli cards populated, then allow each image to
        // diverge from the Top Up thumbnail on subsequent admin edits.
        DB::table('brands')->whereNotNull('thumbnail')
            ->update(['jba_card_image' => DB::raw('thumbnail')]);
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('jba_card_image');
        });
    }
};
