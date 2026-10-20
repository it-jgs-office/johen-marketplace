<?php

namespace Database\Seeders;

use App\Models\CheckProvider;
use Illuminate\Database\Seeder;

/**
 * Provider pengecekan akun default per game.
 *
 * Jalur gratis diisi dari hasil verifikasi nyata:
 *  - Mobile Legends : gopay (MOBILE_LEGENDS, mengembalikan nickname asli) -> alu (jika key+IP whitelist).
 *  - PUBG Mobile    : gopay (PUBG_ID, mengembalikan nickname asli).
 *  - Genshin/HSR/ZZZ: enka.
 *
 * Provider gratis yang TIDAK berfungsi (isan: Free Fire hanya echo tanpa nama,
 * serta AoV/CoD/Valorant/Point Blank 404/400) sengaja dinonaktifkan. Game tanpa
 * jalur gratis memakai fallback Digiflazz berbayar (SKU diisi admin).
 *
 * Ini aditif: bila tabel kosong, sistem memakai konfigurasi statis lama.
 */
class CheckProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'brand_pattern' => 'mobile legends*',
                'label' => 'Mobile Legends',
                'free_type' => 'gopay',
                'free_config' => ['code' => 'MOBILE_LEGENDS'],
                'fallback_free_type' => 'alu',
                'fallback_free_config' => [],
                'digiflazz_fallback_enabled' => true,
                'digiflazz_sku' => env('DIGIFLAZZ_ML_USERNAME_SKU', 'usrnameml-johen'),
                'enabled' => true,
                'priority' => 1,
            ],
            [
                'brand_pattern' => 'pubg mobile*',
                'label' => 'PUBG Mobile',
                'free_type' => 'gopay',
                'free_config' => ['code' => 'PUBG_ID'],
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => false,
                'digiflazz_sku' => env('DIGIFLAZZ_PUBG_USERNAME_SKU'),
                'enabled' => true,
                'priority' => 2,
            ],
            [
                'brand_pattern' => 'genshin impact*',
                'label' => 'Genshin Impact',
                'free_type' => 'enka',
                'free_config' => ['path' => 'api/uid/{user_id}', 'nickname_path' => 'playerInfo.nickname'],
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => false,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 3,
            ],
            [
                'brand_pattern' => 'honkai star rail*',
                'label' => 'Honkai Star Rail',
                'free_type' => 'enka',
                'free_config' => ['path' => 'api/hsr/uid/{user_id}', 'nickname_path' => 'detailInfo.nickname'],
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => false,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 4,
            ],
            [
                'brand_pattern' => 'zenless zone zero*',
                'label' => 'Zenless Zone Zero',
                'free_type' => 'enka',
                'free_config' => ['path' => 'api/zzz/uid/{user_id}', 'nickname_path' => 'playerInfo.nickname'],
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => false,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 5,
            ],
            [
                'brand_pattern' => 'free fire*',
                'label' => 'Free Fire',
                'free_type' => 'none',
                'free_config' => null,
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => true,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 6,
            ],
            [
                'brand_pattern' => 'arena of valor*',
                'label' => 'Arena of Valor',
                'free_type' => 'none',
                'free_config' => null,
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => true,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 7,
            ],
            [
                'brand_pattern' => 'call of duty*',
                'label' => 'Call of Duty Mobile',
                'free_type' => 'none',
                'free_config' => null,
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => true,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 8,
            ],
            [
                'brand_pattern' => 'valorant*',
                'label' => 'Valorant',
                'free_type' => 'none',
                'free_config' => null,
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => true,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 9,
            ],
            [
                'brand_pattern' => 'point blank*',
                'label' => 'Point Blank',
                'free_type' => 'none',
                'free_config' => null,
                'fallback_free_type' => null,
                'fallback_free_config' => null,
                'digiflazz_fallback_enabled' => true,
                'digiflazz_sku' => null,
                'enabled' => true,
                'priority' => 10,
            ],
        ];

        foreach ($providers as $provider) {
            CheckProvider::updateOrCreate(
                ['brand_pattern' => $provider['brand_pattern']],
                $provider
            );
        }
    }
}
