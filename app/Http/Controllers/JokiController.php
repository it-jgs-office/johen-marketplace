<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\MediaStore;
use App\Services\PaymentGatewayService;
use App\Services\JokiPricingService;
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
        $gatewayName = $gateway->providerLabel();

        $bannerPaths = [
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

        $rankCalculator = $packages->map(function (Product $package) {
            $config = JokiPricingService::configFor($package);
            if (! $config || ! $config['per_star']) {
                return null;
            }

            return [
                'key' => $package->buyer_sku_code,
                'label' => $config['label'],
                'icon' => $package->photo_url ?: pwa_asset('assets/joki/mobile-legends/ranks/'.$config['icon']),
                'package_id' => $package->id,
                'package_name' => $package->product_name,
                'price_per_star' => (int) $package->selling_price,
                'gendong_fee' => $config['gendong_fee'],
                'vip_fee' => $config['vip_fee'],
            ];
        })->filter()->values();

        return view('joki.mobile-legends', compact(
            'brand',
            'packages',
            'paymentMethods',
            'gatewayName',
            'banners',
            'rankCalculator'
        ));
    }
}
