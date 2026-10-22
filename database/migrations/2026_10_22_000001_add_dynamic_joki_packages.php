<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('estimated_duration', 80)->nullable()->after('type');
            $table->unsignedInteger('joki_sort_order')->default(0)->after('estimated_duration');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('service_type', 20)->default('topup')->after('category');
            $table->string('joki_login_method', 40)->nullable()->after('service_type');
            $table->text('joki_login_identifier')->nullable()->after('joki_login_method');
            $table->text('joki_login_password')->nullable()->after('joki_login_identifier');
            $table->text('joki_notes')->nullable()->after('joki_login_password');
        });

        $now = now();
        $brandName = DB::table('brands')->whereRaw('LOWER(name) LIKE ?', ['mobile legends%'])->value('name') ?: 'Mobile Legends';
        DB::table('brands')->where('name', $brandName)->update(['service_type' => 'both']);
        $packages = [
            ['joki-ml-epic-10', 'Paket 10 Star Epic + Bonus 1 Star', 60000, '12 jam', 'epic.png', 10],
            ['joki-ml-legend-10', 'Paket 10 Star Legend + Bonus 1 Star', 70000, '14 jam', 'legend.png', 20],
            ['joki-ml-mythic-10', 'Paket 10 Star Mythic + Bonus 1 Star', 175000, '1 hari', 'mythic.png', 30],
            ['joki-ml-honor-10', 'Paket 10 Star Honor + Bonus 1 Star', 195000, '1 hari', 'honor.png', 40],
            ['joki-ml-glory-10', 'Paket 10 Star Glory + Bonus 1 Star', 240000, '2 hari', 'glory.png', 50],
            ['joki-ml-immortal-10', 'Paket 10 Star Immortal + Bonus 1 Star', 265000, '2 hari', 'immortal.png', 60],
        ];

        foreach ($packages as [$sku, $name, $price, $duration, $icon, $sort]) {
            DB::table('products')->updateOrInsert(['buyer_sku_code' => $sku], [
                'brand' => $brandName,
                'category' => 'Games',
                'product_name' => $name,
                'photo' => 'assets/joki/mobile-legends/ranks/'.$icon,
                'price' => $price,
                'selling_price' => $price,
                'type' => 'joki',
                'estimated_duration' => $duration,
                'joki_sort_order' => $sort,
                'is_active' => true,
                'stock' => 9999,
                'region' => 'ID',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('products')->whereIn('buyer_sku_code', [
            'joki-ml-epic-10', 'joki-ml-legend-10', 'joki-ml-mythic-10',
            'joki-ml-honor-10', 'joki-ml-glory-10', 'joki-ml-immortal-10',
        ])->delete();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'joki_login_method', 'joki_login_identifier', 'joki_login_password', 'joki_notes']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['estimated_duration', 'joki_sort_order']);
        });
    }
};
