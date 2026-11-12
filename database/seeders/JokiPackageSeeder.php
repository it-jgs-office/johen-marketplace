<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;

class JokiPackageSeeder extends Seeder
{
    public function run(): void
    {
        $brandName = Brand::whereRaw('LOWER(name) LIKE ?', ['mobile legends%'])->value('name') ?: 'Mobile Legends';
        Brand::where('name', $brandName)->update(['service_type' => 'both']);

        $packages = [
            ['joki-ml-gm', 'Joki GM / Star', 5000, 'epic.png', 10],
            ['joki-ml-epic-10', 'Joki Epic / Star', 7000, 'epic.png', 20],
            ['joki-ml-legend-10', 'Joki Legend / Star', 9000, 'legend.png', 30],
            ['joki-ml-grading', 'Joki Grading / Star', 14000, 'mythic.png', 40],
            ['joki-ml-mythic-10', 'Joki Mythic Low / Star', 14000, 'mythic.png', 50],
            ['joki-ml-honor-10', 'Joki Honor / Star', 15000, 'honor.png', 60],
            ['joki-ml-glory-10', 'Joki Glory / Star', 19000, 'glory.png', 70],
            ['joki-ml-immortal-10', 'Joki Immortal / Star', 22000, 'immortal.png', 80],
            ['joki-ml-full-grading', 'Joki Full Grading', 140000, 'mythic.png', 90],
        ];

        foreach ($packages as [$sku, $name, $price, $icon, $sort]) {
            Product::updateOrCreate(['buyer_sku_code' => $sku], [
                'brand' => $brandName,
                'category' => 'Games',
                'product_name' => $name,
                'photo' => 'assets/joki/mobile-legends/ranks/'.$icon,
                'price' => $price,
                'selling_price' => $price,
                'selling_price_override' => $price,
                'type' => 'joki',
                'estimated_duration' => null,
                'joki_sort_order' => $sort,
                'is_active' => true,
                'stock' => 9999,
                'region' => 'ID',
            ]);
        }
    }
}
