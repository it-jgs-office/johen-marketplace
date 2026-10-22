<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\MediaStore;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Storage;

class JokiController extends Controller
{
    public function mobileLegends(PaymentGatewayService $gateway)
    {
        $brand = Brand::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) LIKE ?', ['mobile legends%'])
            ->firstOrFail();

        $packages = Product::query()
            ->where('is_active', true)
            ->where('type', 'joki')
            ->whereRaw('LOWER(brand) = ?', [mb_strtolower($brand->name)])
            ->orderBy('joki_sort_order')
            ->orderBy('selling_price')
            ->get();

        $paymentMethods = $gateway->filterAvailableMethods(
            PaymentMethod::where('is_active', true)->get()
        );

        $activeTheme = view()->shared('activeTheme');
        $bannerPaths = $activeTheme?->banner_image
            ? [$activeTheme->banner_image]
            : [
                SiteSetting::get('site_hero_banner'),
                SiteSetting::get('site_hero_banner_2'),
                SiteSetting::get('site_hero_banner_3'),
            ];

        $banners = collect($bannerPaths)->filter(function ($path) {
            if (! $path) {
                return false;
            }

            if (preg_match('#^https?://#i', $path)) {
                $host = parse_url($path, PHP_URL_HOST);
                $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

                if ($host && $host !== $appHost) {
                    return true;
                }
            }

            $normalizedPath = MediaStore::normalizePath($path);

            return $normalizedPath && (
                Storage::disk('public')->exists($normalizedPath)
                || MediaStore::find($normalizedPath) !== null
                || (str_starts_with($normalizedPath, 'assets/') && is_file(public_path($normalizedPath)))
            );
        })->map(fn ($path) => media_url($path))
            ->filter()
            ->unique()
            ->values();

        if ($banners->isEmpty()) {
            $banners->push(pwa_asset('assets/joki/mobile-legends/banner.png'));
        }

        $rankDefinitions = [
            'honor' => ['label' => 'Mythic Honor', 'icon' => 'honor.png'],
            'glory' => ['label' => 'Mythic Glory', 'icon' => 'glory.png'],
            'immortal' => ['label' => 'Mythic Immortal', 'icon' => 'immortal.png'],
            'mythic' => ['label' => 'Mythic', 'icon' => 'mythic.png'],
            'legend' => ['label' => 'Legend', 'icon' => 'legend.png'],
            'epic' => ['label' => 'Epic', 'icon' => 'epic.png'],
        ];

        $rankCalculator = $packages->map(function (Product $package) use ($rankDefinitions) {
            $name = mb_strtolower($package->product_name);
            $rankKey = collect(array_keys($rankDefinitions))->first(
                fn (string $key) => str_contains($name, $key)
            );

            if (! $rankKey) {
                return null;
            }

            $definition = $rankDefinitions[$rankKey];
            preg_match('/(\d+)\s*star/i', $package->product_name, $starMatch);
            $packageStars = max(1, (int) ($starMatch[1] ?? 1));

            return [
                'key' => $rankKey,
                'label' => $definition['label'],
                'icon' => $package->photo_url ?: pwa_asset('assets/joki/mobile-legends/ranks/'.$definition['icon']),
                'package_id' => $package->id,
                'package_name' => $package->product_name,
                'price_per_star' => (int) ceil((float) $package->selling_price / $packageStars),
            ];
        })->filter()->unique('key')->values();

        return view('joki.mobile-legends', compact(
            'brand',
            'packages',
            'paymentMethods',
            'banners',
            'rankCalculator'
        ));
    }
}
