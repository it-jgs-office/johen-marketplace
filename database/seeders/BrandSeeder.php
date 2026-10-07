<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Mobile Legends', 'category' => 'moba', 'description' => 'Mobile Legends: Bang Bang'],
            ['name' => 'PUBG', 'category' => 'br', 'description' => 'PUBG Mobile'],
            ['name' => 'Free Fire', 'category' => 'br', 'description' => 'Free Fire'],
            ['name' => 'Roblox', 'category' => 'sandbox', 'description' => 'Roblox'],
            ['name' => 'Valorant', 'category' => 'fps', 'description' => 'Valorant'],
            ['name' => 'Honor of Kings', 'category' => 'moba', 'description' => 'Honor of Kings'],
            ['name' => 'Arena of Valor', 'category' => 'moba', 'description' => 'Arena of Valor'],
            ['name' => 'Point Blank', 'category' => 'fps', 'description' => 'Point Blank'],
            ['name' => 'Call of Duty', 'category' => 'fps', 'description' => 'Call of Duty Mobile'],
            ['name' => 'Arena Breakout', 'category' => 'fps', 'description' => 'Arena Breakout'],
            ['name' => 'Genshin Impact', 'category' => 'rpg', 'description' => 'Genshin Impact'],
            ['name' => 'Clash of Clans', 'category' => 'strategi', 'description' => 'Clash of Clans'],
            ['name' => 'Clash Royale', 'category' => 'strategi', 'description' => 'Clash Royale'],
            ['name' => 'Asphalt 9', 'category' => 'racing', 'description' => 'Asphalt 9'],
            ['name' => 'CrossFire', 'category' => 'fps', 'description' => 'CrossFire'],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['name' => $brand['name']],
                [
                    'category' => $brand['category'],
                    'description' => $brand['description'],
                ]
            );
        }
    }
}
