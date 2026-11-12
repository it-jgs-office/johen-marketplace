<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class JokiPricingService
{
    public const PACKAGES = [
        'joki-ml-gm' => ['label' => 'GM', 'per_star' => true, 'gendong_fee' => 2000, 'vip_fee' => 5000, 'icon' => 'epic.png'],
        'joki-ml-epic-10' => ['label' => 'Epic', 'per_star' => true, 'gendong_fee' => 2000, 'vip_fee' => 5000, 'icon' => 'epic.png'],
        'joki-ml-legend-10' => ['label' => 'Legend', 'per_star' => true, 'gendong_fee' => 2000, 'vip_fee' => 5000, 'icon' => 'legend.png'],
        'joki-ml-grading' => ['label' => 'Grading', 'per_star' => true, 'gendong_fee' => 2000, 'vip_fee' => 5000, 'icon' => 'mythic.png'],
        'joki-ml-mythic-10' => ['label' => 'Mythic Low', 'per_star' => true, 'gendong_fee' => 2000, 'vip_fee' => 5000, 'icon' => 'mythic.png'],
        'joki-ml-honor-10' => ['label' => 'Honor', 'per_star' => true, 'gendong_fee' => 3000, 'vip_fee' => 5000, 'icon' => 'honor.png'],
        'joki-ml-glory-10' => ['label' => 'Glory', 'per_star' => true, 'gendong_fee' => 3000, 'vip_fee' => 7000, 'icon' => 'glory.png'],
        'joki-ml-immortal-10' => ['label' => 'Immortal', 'per_star' => true, 'gendong_fee' => 3000, 'vip_fee' => 7000, 'icon' => 'immortal.png'],
        'joki-ml-full-grading' => ['label' => 'Full Grading', 'per_star' => false, 'gendong_fee' => 0, 'vip_fee' => 0, 'icon' => 'mythic.png'],
    ];

    public static function configFor(Product $product): ?array
    {
        return self::PACKAGES[$product->buyer_sku_code] ?? null;
    }

    /** @return array{quantity:int,surcharge:int,notes:array<int,string>} */
    public static function quote(Product $product, int $stars, bool $gendong, int $vipGames, bool $noHeroRequest): array
    {
        $config = self::configFor($product);

        if (! $config) {
            throw ValidationException::withMessages(['product_id' => 'Paket joki tidak valid.']);
        }

        if (! $config['per_star']) {
            return ['quantity' => 1, 'surcharge' => 0, 'notes' => ['Paket '.$config['label']]];
        }

        $stars = max(1, min(99, $stars));
        $surcharge = 0;
        $notes = [$config['label'].': '.$stars.' star'];

        if ($gendong) {
            $fee = $config['gendong_fee'] * $stars;
            $surcharge += $fee;
            $notes[] = 'Joki gendong: +Rp'.number_format($fee, 0, ',', '.');
        }

        if ($vipGames > 0) {
            $fee = $config['vip_fee'] * $vipGames;
            $surcharge += $fee;
            $notes[] = 'VIP '.$vipGames.' game: +Rp'.number_format($fee, 0, ',', '.');
        }

        if ($noHeroRequest && $stars >= 10) {
            $notes[] = 'Bonus +1 star (tanpa request hero)';
        }

        return ['quantity' => $stars, 'surcharge' => $surcharge, 'notes' => $notes];
    }
}
