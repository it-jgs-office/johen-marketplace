<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class TopupPopularGameSeeder extends Seeder
{
    /**
     * Game populer bawaan harus menggunakan aset repository agar tetap tersedia
     * setelah deploy baru tanpa bergantung pada file upload dari mesin lokal.
     */
    private const GAMES = [
        [
            'name' => 'Call of Duty MOBILE',
            'category' => 'fps',
            'image' => 'assets/card-topup/call-of-duty-mobile.png',
            'thumbnail' => 'assets/card-topup/call-of-duty-mobile.png',
            'sort_order' => 1,
        ],
        [
            'name' => 'Magic Chess',
            'category' => 'strategy',
            'image' => 'assets/card-topup/magic-chess.png',
            'thumbnail' => 'assets/card-topup/magic-chess.png',
            'sort_order' => 2,
        ],
        [
            'name' => 'PUBG Mobile',
            'category' => 'battle-royale',
            'image' => 'assets/backround/bg-pubg.png',
            'thumbnail' => 'assets/card-topup/pubg-mobile.png',
            'sort_order' => 3,
        ],
        [
            'name' => 'Mobile Legends',
            'category' => 'moba',
            'image' => 'assets/backround/bg-ml.jpg',
            'thumbnail' => 'assets/card-topup/mobile-legends.png',
            'sort_order' => 4,
        ],
        [
            'name' => 'Free Fire',
            'category' => 'battle-royale',
            'image' => 'assets/backround/bg-ff.png',
            'thumbnail' => 'assets/card-topup/freefire.png',
            'sort_order' => 5,
        ],
        [
            'name' => 'Valorant',
            'category' => 'fps',
            'image' => 'assets/backround/bg-valo.png',
            'thumbnail' => 'assets/card-topup/valorant.png',
            'sort_order' => 6,
        ],
    ];

    public function run(): void
    {
        foreach (self::GAMES as $game) {
            $brand = Brand::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($game['name'])])
                ->first();

            if (! $brand) {
                $brand = new Brand(['name' => $game['name']]);
            }

            $brand->fill([
                'category' => $brand->category ?: $game['category'],
                'service_type' => 'topup',
                'catalog_group' => 'game',
                'is_active' => true,
                'is_topup_popular' => true,
                'sort_order' => $game['sort_order'],
            ]);

            if (blank($brand->thumbnail)) {
                $brand->thumbnail = $game['thumbnail'];
            }

            if (blank($brand->topup_popular_image)) {
                $brand->topup_popular_image = $game['image'];
            }

            $brand->save();
        }
    }
}
