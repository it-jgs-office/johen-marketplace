<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        $this->seedTopupCardImages();
        $this->seedTopupCharacterImages();

        Brand::query()
            ->whereRaw('LOWER(name) = ?', ['magic chess'])
            ->update(['requires_zone_id' => true]);
    }

    /**
     * Daftarkan artwork bawaan untuk katalog top up. Artwork hanya diisi saat
     * thumbnail masih kosong, sehingga gambar yang sudah diganti lewat admin
     * tidak akan tertimpa ketika seeder dijalankan lagi.
     */
    private function seedTopupCardImages(): void
    {
        $directory = public_path('assets/card-topup');

        if (! is_dir($directory)) {
            return;
        }

        $brandsByKey = Brand::query()
            ->get()
            ->keyBy(fn (Brand $brand) => $this->brandKey($brand->name));

        $sortOrder = (int) Brand::max('sort_order');

        foreach (glob($directory.'/*.png') ?: [] as $file) {
            $filename = basename($file);
            $name = $this->nameFromFilename($filename);
            $key = $this->brandKey($name);
            $thumbnail = 'assets/card-topup/'.$filename;

            $brand = $brandsByKey->get($key);

            if (! $brand) {
                $brand = Brand::create([
                    'name' => $name,
                    'category' => 'Games',
                    'service_type' => 'topup',
                    'catalog_group' => 'game',
                    'is_active' => true,
                    'sort_order' => ++$sortOrder,
                ]);
                $brandsByKey->put($key, $brand);
            }

            if (blank($brand->thumbnail)) {
                $brand->update(['thumbnail' => $thumbnail]);
            }
        }
    }

    /**
     * Daftarkan karakter 3D bawaan untuk kartu top up tanpa menimpa gambar
     * karakter yang sudah diatur melalui admin.
     */
    private function seedTopupCharacterImages(): void
    {
        $directory = public_path('assets/card-topup/3d-karakter');

        if (! is_dir($directory)) {
            return;
        }

        $characterBrands = [
            'afk-journey.png' => 'AFK Journey',
            'age-of-empires.png' => 'Age of Empires Mobile',
            'airplane-chefs.png' => 'Airplane Chefs',
            'astra-kinghts-of-veda.png' => 'Astra Knights of Veda',
            'banishers.png' => 'Banishers Faiths Entwined',
            'bleach-soul-resonance.png' => 'Bleach Soul Resonance',
            'blue-protocol-star-ressonance.png' => 'Blue Protocol Star Resonance',
            'bottle-thought-the-heavens.png' => 'Battle Through the Heavens 3D Fight',
            'captain-tsubatsa-dream-team.png' => 'Captain Tsubasa Dream Team',
            'castle-duels.png' => 'Castle Duels',
            'cod-mobile.png' => 'Call of Duty MOBILE',
            'crystal-of-atlan.png' => 'Crystal of Atlan',
            'freefire.png' => 'Free Fire',
            'magic-chess.png' => 'Magic Chess',
            'mobile-legends.png' => 'Mobile Legends',
            'PUBG-Mobile.png' => 'PUBG Mobile',
            'Tsubasa-Ozora-removebg-preview.png' => 'Captain Tsubasa Ace',
            'valorant.png' => 'Valorant',
        ];

        $brandsByKey = Brand::query()
            ->get()
            ->keyBy(fn (Brand $brand) => $this->brandKey($brand->name));

        $sortOrder = (int) Brand::max('sort_order');

        foreach ($characterBrands as $filename => $name) {
            if (! is_file($directory.'/'.$filename)) {
                continue;
            }

            $key = $this->brandKey($name);
            $brand = $brandsByKey->get($key);

            if (! $brand) {
                $brand = Brand::create([
                    'name' => $name,
                    'category' => 'Games',
                    'service_type' => 'topup',
                    'catalog_group' => 'game',
                    'is_active' => true,
                    'sort_order' => ++$sortOrder,
                ]);
                $brandsByKey->put($key, $brand);
            }

            if (blank($brand->topup_character_image)) {
                $brand->update([
                    'topup_character_image' => 'assets/card-topup/3d-karakter/'.$filename,
                ]);
            }
        }
    }

    private function nameFromFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/\s*\(\d+\)$/', '', $name);

        return Str::of($name)
            ->replace('-', ' ')
            ->title()
            ->toString();
    }

    private function brandKey(string $name): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', mb_strtolower(trim($name)));
    }
}
