<?php

namespace App\Http\Controllers;

use App\Models\AccountListing;
use App\Models\Brand;
use App\Services\ArticleApiClient;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $base = rtrim(config('app.url'), '/');
        if ($base === '' || $base === 'http://localhost') {
            $base = rtrim(config('app.sitemap_url', request()->root()), '/');
        }

        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0', 'freq' => 'daily'],
            ['loc' => $base.'/jual-beli-akun', 'priority' => '0.9', 'freq' => 'daily'],
            ['loc' => $base.'/cek-transaksi', 'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => $base.'/leaderboard', 'priority' => '0.4', 'freq' => 'daily'],
            ['loc' => $base.'/testimoni', 'priority' => '0.5', 'freq' => 'weekly'],
            ['loc' => $base.'/hubungi-kami', 'priority' => '0.6', 'freq' => 'monthly'],
            ['loc' => $base.'/faq', 'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => $base.'/syarat-ketentuan', 'priority' => '0.3', 'freq' => 'yearly'],
            ['loc' => $base.'/kebijakan-privasi', 'priority' => '0.3', 'freq' => 'yearly'],
            ['loc' => $base.'/artikel', 'priority' => '0.8', 'freq' => 'weekly'],
        ];

        foreach (app(ArticleApiClient::class)->allForSitemap() as $article) {
            $urls[] = [
                'loc' => $base.'/artikel/'.rawurlencode($article->slug),
                'priority' => $article->is_featured ? '0.8' : '0.7',
                'freq' => 'monthly',
                'lastmod' => $article->updated_at?->toAtomString(),
            ];
        }

        foreach (Brand::where('is_active', true)->orderBy('name')->get() as $brand) {
            $urls[] = [
                'loc' => $base.'/games/'.rawurlencode($brand->name),
                'priority' => '0.8',
                'freq' => 'weekly',
            ];
        }

        // Batasi agar sitemap tidak membengkak; listing tetap bisa dijelajahi
        // lewat halaman /jual-beli-akun/{game}.
        $listingUrls = AccountListing::where('is_active', true)
            ->where('is_sold', false)
            ->orderBy('updated_at', 'desc')
            ->limit(500)
            ->get();

        foreach ($listingUrls as $listing) {
            $urls[] = [
                'loc' => $base.'/jual-beli-akun/'.$listing->id,
                'priority' => '0.7',
                'freq' => 'weekly',
            ];
        }

        $xml = view('sitemap', compact('urls'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
