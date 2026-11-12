<?php

namespace App\Http\Controllers;

use App\Mail\AccountEmailTestMail;
use App\Models\AccountListing;
use App\Models\AccountOrder;
use App\Models\Brand;
use App\Models\Checkout;
use App\Models\ContactInquiry;
use App\Models\FlashDeal;
use App\Models\FlashSaleBanner;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Services\GameAccountService;
use App\Services\JbaApiClient;
use App\Services\ArticleApiClient;
use App\Services\PaymentGatewayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    private const EMAIL_CONFIRMATION_COOLDOWN_SECONDS = 60;

    private const JBA_GAME_SLUGS = [
        'mlbb' => 'Mobile Legends',
        'pubg' => 'PUBG Mobile',
        'efootball' => 'E-Football',
        'fcm' => 'FC Mobile',
        'ff' => 'Free Fire',
        'roblox' => 'Roblox',
        'valorant' => 'Valorant',
    ];

    private const JBA_REMOTE_GAME_SLUGS = [
        'ml' => 'mlbb',
        'pubg' => 'pubg',
        'fc_mobile' => 'fcm',
        'ff' => 'ff',
        'roblox' => 'roblox',
        'valorant' => 'valorant',
        'efootball' => 'efootball',
    ];

    private static function jbaPageData(?string $activeGame = null): array
    {
        $sourceListings = app(JbaApiClient::class)->listings();
        if ($activeGame) {
            $sourceListings = $sourceListings->where('game', $activeGame)->values();
        }

        $listings = $sourceListings
            ->sortBy(fn (AccountListing $listing) => sprintf(
                '%d|%s|%s',
                $listing->is_sold ? 1 : 0,
                mb_strtolower($listing->game),
                mb_strtolower($listing->product_name)
            ))
            ->values()
            ->groupBy('game');

        // Daftar awal untuk render server (SEO + fallback tanpa JavaScript).
        // Saat game aktif, tampilkan listing game tersebut; selain itu, ambil
        // sampel per game agar beranda tetap punya konten produk.
        $serverSource = $activeGame
            ? ($listings->get($activeGame) ?? collect())
            : $listings->flatMap(fn ($items) => $items->take(4));
        $serverTotal = $serverSource->count();
        $serverAvailable = $serverSource->where('is_sold', false)->count();
        $serverSold = $serverSource->where('is_sold', true)->count();
        $serverListings = $serverSource->take(12)->values();
        $clientListings = $activeGame
            ? $listings->map(fn ($gameListings) => $gameListings->map(fn (AccountListing $listing) => [
                'id' => $listing->id,
                'game' => $listing->game,
                'product_name' => $listing->product_name,
                'owner_name' => $listing->owner_name,
                'price' => $listing->price,
                'original_price' => $listing->original_price,
                'photo_url' => $listing->photo_url,
                'is_sold' => $listing->is_sold,
                'promo_type' => $listing->promo_type,
                'discount_percent' => $listing->discount_percent,
                'collector_tier' => $listing->collector_tier,
                'deal_type' => $listing->deal_type,
            ]))
            : collect();

        $popularGames = Brand::where('is_popular', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $testimonials = array_values(array_filter(static::getTestimonials(), fn ($t) => ($t['layanan'] ?? '') === 'jual-beli-akun'));

        $flashSaleBanners = FlashSaleBanner::activeBanners();

        $budgetBanners = collect([
            [
                'id' => 'pelajar',
                'label' => 'Budget Pelajar',
                'sub' => '300rb – 1.999jt',
                'min' => 300000,
                'max' => 1999000,
                'image' => SiteSetting::get('jba_budget_pelajar_banner'),
            ],
            [
                'id' => 'umr',
                'label' => 'Budget UMR',
                'sub' => '2jt – 5.9jt',
                'min' => 2000000,
                'max' => 5900000,
                'image' => SiteSetting::get('jba_budget_umr_banner'),
            ],
            [
                'id' => 'sultan',
                'label' => 'Budget Sultan',
                'sub' => '6jt – 19.9jt',
                'min' => 6000000,
                'max' => 19900000,
                'image' => SiteSetting::get('jba_budget_sultan_banner'),
            ],
            [
                'id' => 'freedom',
                'label' => 'Financial Freedom',
                'sub' => '20jt – 50jt',
                'min' => 20000000,
                'max' => 50000000,
                'image' => SiteSetting::get('jba_budget_freedom_banner'),
            ],
        ])->map(function ($b) {
            $b['image_url'] = $b['image'] ? media_url($b['image']) : null;

            return $b;
        })->all();

        $gameSlugs = array_flip(static::JBA_GAME_SLUGS);

        $remoteTampilan = app(JbaApiClient::class)->tampilan()->keyBy('game');
        $remoteTampilanBySlug = $remoteTampilan
            ->mapWithKeys(function (array $tampilan, string $remoteGame) {
                $slug = static::JBA_REMOTE_GAME_SLUGS[$remoteGame] ?? null;

                return $slug ? [$slug => $tampilan] : [];
            });
        $brandBySlug = $popularGames->mapWithKeys(function (Brand $brand) use ($gameSlugs) {
            $slug = $gameSlugs[$brand->name] ?? null;

            return $slug ? [$slug => $brand] : [];
        });
        $jbaGames = collect(static::JBA_GAME_SLUGS)
            ->map(function (string $name, string $slug) use ($brandBySlug, $remoteTampilanBySlug, $listings) {
                $tampilan = $remoteTampilanBySlug->get($slug, []);
                $brand = $brandBySlug->get($slug);

                return [
                    'slug' => $slug,
                    'name' => $name,
                    'count' => $listings->get($name)?->where('is_sold', false)->count() ?? 0,
                    'sold_count' => $listings->get($name)?->where('is_sold', true)->count() ?? 0,
                    'thumbnail_url' => $tampilan['thumbnail_url'] ?? $brand?->jba_card_image_url,
                    'urutan' => (int) ($tampilan['urutan'] ?? 999),
                ];
            })
            ->sortBy(fn (array $game) => sprintf('%d-%03d-%s', $game['slug'] === 'pubg' ? 0 : 1, $game['urutan'], $game['slug']))
            ->values();

        $stockGameBackgrounds = [
            'mlbb' => 'assets/backround/bg-ml.jpg',
            'pubg' => 'assets/backround/bg-pubg.png',
            'efootball' => 'assets/backround/bg-efootball.png',
            'fcm' => 'assets/backround/bg-fcmobile.png',
            'ff' => 'assets/backround/bg-ff.png',
            'roblox' => 'assets/backround/bg-roblox.png',
            'valorant' => 'assets/backround/bg-valo.png',
        ];

        $gameBanners = [];
        foreach (static::JBA_GAME_SLUGS as $slug => $game) {
            $path = SiteSetting::get('jba_game_banner_'.$slug);
            $gameBanners[$slug] = $remoteTampilanBySlug->get($slug)['banner_url'] ?? ($path ? media_url($path) : null);
        }

        $gameBackgrounds = collect($stockGameBackgrounds)
            ->map(fn (string $path) => asset($path))
            ->all();

        $jbaTestis = static::getTestimonials();
        $jbaRating = collect($jbaTestis)->filter(fn ($t) => ($t['layanan'] ?? '') === 'jual-beli-akun')->avg('rating');
        $jbaRating = $jbaRating ? round((float) $jbaRating, 1) : 4.9;

        return compact('jbaGames', 'listings', 'clientListings', 'testimonials', 'flashSaleBanners', 'budgetBanners', 'gameSlugs', 'gameBanners', 'gameBackgrounds', 'jbaRating', 'serverListings', 'serverTotal', 'serverAvailable', 'serverSold');
    }

    public function index()
    {
        if (Auth::guard('admin')->check() && ! Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $brands = $this->activeGameBrandsQuery()
            // Urutan teratas dipertahankan untuk best seller utama. Setelah itu,
            // game dengan artwork karakter ditampilkan sebelum game lainnya.
            ->orderByRaw("CASE
                WHEN LOWER(name) IN ('pubg', 'pubg mobile') THEN 0
                WHEN LOWER(name) LIKE 'mobile legends%' THEN 1
                WHEN LOWER(name) LIKE 'magic chess%' THEN 2
                WHEN LOWER(name) LIKE 'valorant%' THEN 3
                WHEN LOWER(name) LIKE 'mobile legends adventure%' THEN 4
                WHEN LOWER(name) = 'call of duty mobile' THEN 5
                WHEN COALESCE(topup_character_image, '') <> '' THEN 6
                WHEN is_topup_popular = 1 AND COALESCE(thumbnail, '') <> '' THEN 7
                WHEN is_topup_popular = 1 THEN 8
                WHEN COALESCE(thumbnail, '') <> '' THEN 9
                ELSE 10
            END")
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $popularTopupGames = $this->activeGameBrandsQuery()
            ->where('is_topup_popular', true)
            ->whereNotNull('topup_popular_image')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(6)
            ->get();

        $flashDeals = FlashDeal::with('product')
            ->active()
            ->whereHas('product', fn ($query) => $this->constrainToActiveGameProducts($query))
            ->orderBy('ends_at')
            ->get()
            ->filter(fn (FlashDeal $deal) => $deal->product && $deal->flash_price > 0)
            ->values();

        $stockArtwork = [
            'mlbb' => 'mobile-legends.png',
            'pubg' => 'pubg.png',
            'efootball' => 'efootball.png',
            'fcm' => 'fc-mobile.png',
            'ff' => 'freefire.png',
            'roblox' => 'roblox.png',
            'valorant' => 'valorant.png',
        ];

        $apiAccountListings = app(JbaApiClient::class)->listings();

        $latestAccountStock = collect(static::JBA_GAME_SLUGS)
            ->map(function (string $game, string $slug) use ($apiAccountListings, $stockArtwork) {
                $listings = $apiAccountListings
                    ->where('game', $game)
                    ->where('is_sold', false)
                    ->take(15)
                    ->values();

                return [
                    'game' => $game,
                    'slug' => $slug,
                    'artwork' => 'assets/produk-terbaru/'.$stockArtwork[$slug],
                    'listings' => $listings,
                ];
            })
            ->filter(fn (array $category) => $category['listings']->isNotEmpty())
            ->values();

        $homeTestimonials = collect(static::getTestimonials())
            ->where('layanan', 'topup')
            ->take(6)
            ->values();

        $latestArticles = app(ArticleApiClient::class)->latest(4);

        return view('home', compact('brands', 'flashDeals', 'latestAccountStock', 'popularTopupGames', 'homeTestimonials', 'latestArticles'));
    }

    public function getApiProducts(Request $request)
    {
        $query = $this->activeGameProductsQuery();

        if ($request->filled('brand')) {
            $query->whereRaw('LOWER(brand) = ?', [mb_strtolower((string) $request->brand)]);
        }

        $products = $query->orderBy('selling_price')->get();

        return response()->json($products);
    }

    public function gameDetail(Brand $brand)
    {
        if (Auth::guard('admin')->check() && ! Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        abort_unless($brand->is_active && $brand->catalog_group === 'game', 404);

        $isMobileLegends = str_starts_with(mb_strtolower($brand->name), 'mobile legends');
        $accountCheckEnabled = app(GameAccountService::class)->supportsRealtimeCheck($brand->name);

        $products = $this->activeGameProductsQuery()
            ->whereRaw('LOWER(brand) = ?', [mb_strtolower($brand->name)])
            ->when($isMobileLegends, fn ($query) => $query->where(function ($query) {
                $query->whereRaw('LOWER(buyer_sku_code) LIKE ?', ['%-idn'])
                    ->orWhereRaw('LOWER(TRIM(buyer_sku_code)) LIKE ?', ['spesial%'])
                    ->orWhereRaw('LOWER(TRIM(buyer_sku_code)) LIKE ?', ['special%']);
            }))
            ->orderBy('type')
            ->orderBy('selling_price')
            ->get();

        abort_if($products->isEmpty(), 404);

        $flashDeals = FlashDeal::active()
            ->whereIn('product_id', $products->pluck('id'))
            ->with('product')
            ->get()
            ->keyBy('product_id');

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $paymentMethods = app(PaymentGatewayService::class)->filterAvailableMethods($paymentMethods);
        $gatewayName = app(PaymentGatewayService::class)->providerLabel();

        $pointBalance = Auth::guard('web')->check()
            ? app(\App\Services\BalanceService::class)->balanceFor(Auth::guard('web')->id())
            : 0;

        return view('game-detail', compact('brand', 'products', 'paymentMethods', 'gatewayName', 'flashDeals', 'isMobileLegends', 'accountCheckEnabled', 'pointBalance'));
    }

    public function searchBrands(Request $request)
    {
        $q = $request->input('q', '');

        $brands = $this->activeGameBrandsQuery()
            ->where('name', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['name as brand', 'thumbnail', 'icon']);

        return response()->json($brands);
    }

    public function globalSearch(Request $request)
    {
        $query = trim((string) $request->input('q', ''));

        if ($query === '') {
            return response()->json(['topups' => [], 'accounts' => [], 'fallback' => false]);
        }

        $matchingBrands = Brand::query()
            ->where('is_active', true)
            ->where('catalog_group', 'game')
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->pluck('name');

        $products = $this->activeGameProductsQuery()
            ->where(function ($builder) use ($query, $matchingBrands) {
                $builder->where('product_name', 'like', "%{$query}%")
                    ->orWhere('brand', 'like', "%{$query}%");

                if ($matchingBrands->isNotEmpty()) {
                    $builder->orWhereIn('brand', $matchingBrands);
                }
            })
            ->orderBy('brand')
            ->orderBy('selling_price')
            ->limit(24)
            ->get();

        $matchedBrandNames = $products->pluck('brand')->unique()->values();
        $brands = Brand::query()
            ->whereIn('name', $matchedBrandNames)
            ->get()
            ->keyBy(fn (Brand $brand) => mb_strtolower($brand->name));

        $minimumPrices = $this->activeGameProductsQuery()
            ->whereIn('brand', $matchedBrandNames)
            ->where('selling_price', '>', 0)
            ->selectRaw('LOWER(brand) as brand_key, MIN(selling_price) as minimum_price')
            ->groupBy(DB::raw('LOWER(brand)'))
            ->pluck('minimum_price', 'brand_key');

        $topups = $matchedBrandNames->map(function (string $brandName) use ($brands, $minimumPrices) {
            $brand = $brands->get(mb_strtolower($brandName));
            return [
                'id' => $brand?->id ?: $brandName,
                'type' => 'topup',
                'title' => $brand?->name ?: $brandName,
                'price' => (float) ($minimumPrices->get(mb_strtolower($brandName)) ?? 0),
                'image_url' => $brand?->thumbnail_url,
                'url' => route('games.show', ['brand' => $brand?->name ?: $brandName]),
            ];
        })->values();

        $availableAccounts = app(JbaApiClient::class)
            ->listings()
            ->where('is_sold', false)
            ->values();
        $needle = mb_strtolower($query);
        $accountMatches = $availableAccounts
            ->filter(function (AccountListing $listing) use ($needle) {
                $haystack = mb_strtolower(implode(' ', [
                    $listing->product_name,
                    $listing->specifications,
                    $listing->game,
                ]));

                return str_contains($haystack, $needle);
            })
            ->take(24)
            ->values();

        $fallback = $topups->isEmpty() && $accountMatches->isEmpty();
        $accounts = ($fallback ? $availableAccounts->take(6) : $accountMatches)
            ->map(fn (AccountListing $listing) => [
                'id' => $listing->id,
                'type' => 'account',
                'title' => $listing->product_name,
                'description' => Str::limit(strip_tags((string) $listing->specifications), 140),
                'meta' => $listing->game,
                'price' => (float) $listing->price,
                'image_url' => $listing->photo_url ?: $listing->thumbnail_url,
                'url' => route('jual-beli-akun.detail', $listing->id),
            ])
            ->values();

        return response()->json([
            'topups' => $topups,
            'accounts' => $accounts,
            'fallback' => $fallback,
        ]);
    }

    /** Game yang benar-benar bisa dibeli: aktif, bersumber dari katalog Games Digiflazz, dan punya produk aktif. */
    private function activeGameBrandsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Brand::query()
            ->where('is_active', true)
            ->where('catalog_group', 'game')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('products')
                    ->whereRaw('LOWER(products.brand) = LOWER(brands.name)')
                    ->whereRaw('LOWER(products.category) = ?', ['games'])
                    ->where('products.is_active', true);
            });
    }

    /** Produk top up yang sama dengan katalog admin: kategori Games dari brand game aktif. */
    private function activeGameProductsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(category) = ?', ['games'])
            ->whereIn(DB::raw('LOWER(brand)'), Brand::query()
                ->where('is_active', true)
                ->where('catalog_group', 'game')
                ->selectRaw('LOWER(name)'));
    }

    private function constrainToActiveGameProducts(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('is_active', true)
            ->whereRaw('LOWER(category) = ?', ['games'])
            ->whereIn(DB::raw('LOWER(brand)'), Brand::query()
                ->where('is_active', true)
                ->where('catalog_group', 'game')
                ->selectRaw('LOWER(name)'));
    }

    public function getPaymentMethods()
    {
        $methods = PaymentMethod::where('is_active', true)->get(['name', 'code', 'icon', 'photo', 'photo_light', 'is_active']);
        $methods = app(PaymentGatewayService::class)->filterAvailableMethods($methods)
            ->map(fn ($method) => collect($method)->except('is_active')->all());

        return response()->json($methods);
    }

    /**
     * Deteksi akun game real-time (User ID + Zone ID).
     * Dipakai halaman game detail untuk indikator hijau saat akun ditemukan.
     */
    public function checkAccount(Request $request, GameAccountService $gameAccount)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:100',
            'user_id' => 'required|string|max:32',
            'zone_id' => 'nullable|string|max:20|regex:/^[A-Za-z0-9]+$/',
        ]);

        return response()->json(
            $gameAccount->check($validated['brand'], $validated['user_id'], $validated['zone_id'] ?? null)
        );
    }

    public function checkOrder(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $q = ltrim($q, '#');

        if ($q === '') {
            return response()->json(['message' => 'Masukkan ID transaksi atau email'], 422);
        }

        $orders = Order::where(function ($query) use ($q) {
            $query->where('order_id', $q)
                ->orWhere('customer_number', $q)
                ->orWhere('email', $q)
                ->orWhereHas('user', fn ($uq) => $uq->where('email', $q))
                ->orWhereHas('transaction', fn ($tq) => $tq->where('transaction_id', $q));
        })
            ->orderByDesc('created_at')
            ->get();

        $accountOrders = AccountOrder::query()
            ->with('listing:id,product_name,game')
            ->where(function ($query) use ($q) {
                $query->where('order_ref', $q)
                    ->orWhere('customer_email', $q)
                    ->orWhere('gateway_invoice_id', $q)
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', $q));
            })
            ->orderByDesc('created_at')
            ->get();

        $checkouts = Checkout::query()
            ->where(function ($query) use ($q) {
                $query->where('checkout_ref', $q)
                    ->orWhere('gateway_invoice_id', $q);
            })
            ->orderByDesc('created_at')
            ->get();

        if ($orders->isEmpty() && $accountOrders->isEmpty() && $checkouts->isEmpty()) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        $topupTransactions = $orders->map(fn ($order) => [
            'type' => $order->service_type === 'joki' ? 'joki' : 'topup',
            'order_id' => $order->order_id,
            'product_name' => $order->product_name,
            'customer_number' => $order->customer_number,
            'price' => (float) $order->price,
            'status' => $order->status,
            'processed_at' => $order->updated_at?->format('d M Y H:i'),
            'timestamp' => $order->created_at?->getTimestamp() ?? 0,
        ]);

        $accountTransactions = $accountOrders->map(fn ($order) => [
            'type' => 'account',
            'order_id' => $order->order_ref,
            'product_name' => $order->listing?->product_name ?: 'Stock Akun',
            'customer_number' => null,
            'price' => (float) $order->total_price,
            'status' => $order->status,
            'processed_at' => $order->updated_at?->format('d M Y H:i'),
            'timestamp' => $order->created_at?->getTimestamp() ?? 0,
        ]);

        $checkoutTransactions = $checkouts->map(fn ($checkout) => [
            'type' => 'checkout',
            'order_id' => $checkout->checkout_ref,
            'product_name' => $checkout->item_count.' item dalam keranjang',
            'customer_number' => null,
            'price' => (float) $checkout->total,
            'status' => $checkout->status,
            'processed_at' => $checkout->updated_at?->format('d M Y H:i'),
            'timestamp' => $checkout->created_at?->getTimestamp() ?? 0,
        ]);

        return response()->json([
            'transactions' => $topupTransactions
                ->concat($accountTransactions)
                ->concat($checkoutTransactions)
                ->sortByDesc('timestamp')
                ->map(function (array $transaction) {
                    unset($transaction['timestamp']);

                    return $transaction;
                })
                ->values(),
        ]);
    }

    public function checkTransaction()
    {
        $recentTopups = Order::query()
            ->select(['id', 'order_id', 'product_name', 'brand', 'customer_phone', 'price', 'status', 'created_at'])
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'type' => 'topup',
                'reference' => static::maskPublicTransactionReference($order->order_id, 'JOHEN-TOPUP-'.$order->id),
                'product' => $order->product_name,
                'game' => $order->brand,
                'phone' => $order->customer_phone
                    ? static::maskPublicPhone($order->customer_phone)
                    : '—',
                'total' => (float) $order->price,
                'status' => $order->status,
                'time' => $order->created_at?->translatedFormat('d M Y, H:i').' WIB',
                'table_time' => $order->created_at?->format('d-m-Y H:i:s'),
                'timestamp' => $order->created_at?->getTimestamp() ?? 0,
            ]);

        $recentAccountOrders = AccountOrder::query()
            ->with('listing:id,product_name,game')
            ->select(['id', 'account_listing_id', 'order_ref', 'customer_phone', 'total_price', 'status', 'created_at'])
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AccountOrder $order) => [
                'type' => 'account',
                'reference' => static::maskPublicTransactionReference($order->order_ref, 'JOHEN-AKUN-'.$order->id),
                'product' => $order->listing?->product_name ?: 'Stock Akun',
                'game' => $order->listing?->game ?: 'Stock Akun',
                'phone' => $order->customer_phone
                    ? static::maskPublicPhone($order->customer_phone)
                    : '—',
                'total' => (float) $order->total_price,
                'status' => $order->status,
                'time' => $order->created_at?->translatedFormat('d M Y, H:i').' WIB',
                'table_time' => $order->created_at?->format('d-m-Y H:i:s'),
                'timestamp' => $order->created_at?->getTimestamp() ?? 0,
            ]);

        $recentTransactions = $recentTopups
            ->concat($recentAccountOrders)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();

        return view('check-transaction', compact('recentTopups', 'recentAccountOrders', 'recentTransactions'));
    }

    private static function maskPublicTransactionReference(?string $reference, string $fallback): string
    {
        $value = trim((string) ($reference ?: $fallback));
        $length = mb_strlen($value);

        if ($length <= 6) {
            return mb_substr($value, 0, 5).'••'.mb_substr($value, -2);
        }

        return mb_substr($value, 0, 5).'••••'.mb_substr($value, -3);
    }

    private static function maskPublicPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '—';
        }

        return str_repeat('•', max(4, strlen($digits) - 2)).substr($digits, -2);
    }

    public function leaderboard(Request $request)
    {
        $popularBrands = Brand::where('is_active', true)
            ->where('is_popular', true)
            ->orderBy('sort_order')
            ->get();

        $games = $popularBrands->pluck('name')->toArray();

        $gameFilter = $request->input('game', 'all');
        $minNominal = $request->input('min_nominal');
        $maxNominal = $request->input('max_nominal');
        $period = $request->input('period');
        $sort = $request->input('sort', 'largest');

        $baseQuery = Order::select(
            'orders.user_id',
            'orders.email',
            DB::raw('MAX(COALESCE(users.name, orders.customer_name, orders.email, "Guest")) as name'),
            DB::raw('MAX(users.avatar) as avatar'),
            DB::raw('SUM(orders.price) as total_amount'),
            DB::raw('COUNT(orders.id) as total_count')
        )
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.status', 'success')
            ->where(function ($q2) {
                $q2->whereNotNull('orders.user_id')
                    ->orWhereNotNull('orders.email')
                    ->orWhereNotNull('orders.customer_name');
            });

        if ($gameFilter !== 'all') {
            $baseQuery->where('orders.brand', $gameFilter);
        }

        if (is_numeric($minNominal) && $minNominal > 0) {
            $baseQuery->where('orders.price', '>=', (float) $minNominal);
        }
        if (is_numeric($maxNominal) && $maxNominal > 0) {
            $baseQuery->where('orders.price', '<=', (float) $maxNominal);
        }

        $orderBy = $sort === 'most' ? 'total_count' : 'total_amount';

        $run = function ($query) use ($orderBy) {
            return $query->groupBy('orders.user_id', 'orders.email')
                ->orderByDesc($orderBy)
                ->limit(10)
                ->get()
                ->map(fn ($item, $i) => [
                    'rank' => $i + 1,
                    'name' => $item->name,
                    'avatar' => $item->avatar ? media_url($item->avatar) : null,
                    'amount' => (int) $item->total_amount,
                ])
                ->toArray();
        };

        $periodMap = [
            'daily' => ['key' => 'today', 'query' => (clone $baseQuery)->whereDate('orders.created_at', Carbon::today())],
            'weekly' => ['key' => 'week', 'query' => (clone $baseQuery)->whereBetween('orders.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])],
            'monthly' => ['key' => 'month', 'query' => (clone $baseQuery)->whereYear('orders.created_at', Carbon::now()->year)->whereMonth('orders.created_at', Carbon::now()->month)],
        ];

        if ($request->wantsJson() && array_key_exists($period, $periodMap)) {
            return response()->json([
                'key' => $periodMap[$period]['key'],
                'data' => $run($periodMap[$period]['query']),
            ]);
        }

        $today = $run($periodMap['daily']['query']);
        $week = $run($periodMap['weekly']['query']);
        $month = $run($periodMap['monthly']['query']);

        $leaderboard = compact('today', 'week', 'month');

        if ($request->wantsJson()) {
            return response()->json($leaderboard);
        }

        return view('leaderboard', compact('leaderboard', 'games', 'popularBrands'));
    }

    public function leaderboardDetail($period)
    {
        $popularBrands = Brand::where('is_active', true)
            ->where('is_popular', true)
            ->orderBy('sort_order')
            ->get();

        $periods = ['daily', 'weekly', 'monthly'];
        if (! in_array($period, $periods)) {
            $period = 'daily';
        }

        $title = match ($period) {
            'daily' => 'Leaderboard Hari Ini',
            'weekly' => 'Leaderboard Minggu Ini',
            'monthly' => 'Leaderboard Bulan Ini',
        };

        return view('leaderboard-detail', compact('period', 'title', 'popularBrands'));
    }

    public function leaderboardApi(Request $request)
    {
        $period = $request->input('period', 'daily');
        $gameFilter = $request->input('game', 'all');
        $minNominal = $request->input('min_nominal');
        $maxNominal = $request->input('max_nominal');

        $query = Order::select(
            'orders.user_id',
            'orders.email',
            'users.name as account_name',
            DB::raw('MAX(users.avatar) as avatar'),
            DB::raw('MAX(COALESCE(users.name, orders.customer_name, orders.email, "Guest")) as customer'),
            'orders.brand as game',
            DB::raw('SUM(orders.price) as total_purchase'),
            DB::raw('COUNT(orders.id) as total_transactions'),
            DB::raw('MAX(orders.created_at) as last_transaction')
        )
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.status', 'success')
            ->where(function ($q2) {
                $q2->whereNotNull('orders.user_id')
                    ->orWhereNotNull('orders.email')
                    ->orWhereNotNull('orders.customer_name');
            });

        if ($gameFilter !== 'all') {
            $query->where('orders.brand', $gameFilter);
        }

        if (is_numeric($minNominal) && $minNominal > 0) {
            $query->where('orders.price', '>=', (float) $minNominal);
        }
        if (is_numeric($maxNominal) && $maxNominal > 0) {
            $query->where('orders.price', '<=', (float) $maxNominal);
        }

        if ($period === 'daily') {
            $query->whereDate('orders.created_at', Carbon::today());
        } elseif ($period === 'weekly') {
            $query->whereBetween('orders.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($period === 'monthly') {
            $query->whereYear('orders.created_at', Carbon::now()->year)
                ->whereMonth('orders.created_at', Carbon::now()->month);
        }

        $allData = $query->groupBy('orders.user_id', 'orders.email', 'users.name', 'orders.brand')
            ->orderByDesc('total_purchase')
            ->get()
            ->map(fn ($item, $i) => [
                'rank' => $i + 1,
                'customer' => $item->customer,
                'avatar_url' => $item->avatar ? media_url($item->avatar) : null,
                'game' => $item->game,
                'total_purchase' => (int) $item->total_purchase,
                'total_transactions' => (int) $item->total_transactions,
                'last_transaction' => Carbon::parse($item->last_transaction)->format('d M Y H:i').' WIB',
            ])
            ->toArray();

        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);
        $total = count($allData);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($allData, $offset, $perPage);

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
            'data' => $items,
        ]);
    }

    public function jualBeliAkun()
    {
        $data = static::jbaPageData(null);
        $data['activeGame'] = null;
        $data['activeSlug'] = null;

        return view('pages.jual-beli-akun', $data);
    }

    public function jualBeliAkunGame(string $game)
    {
        $map = static::JBA_GAME_SLUGS;

        if (! isset($map[$game])) {
            abort(404);
        }

        $data = static::jbaPageData($map[$game]);
        $data['activeGame'] = $map[$game];
        $data['activeSlug'] = $game;

        return view('pages.jual-beli-akun', $data);
    }

    /**
     * Pesanan Saya untuk pembelian akun (Stock Akun).
     */
    public function jualBeliAkunOrders()
    {
        $user = Auth::user();

        $orders = AccountOrder::with('listing')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user?->id)
                    ->orWhere('customer_email', $user?->email);
            })
            ->latest()
            ->paginate(10);

        return view('pages.jual-beli-akun-orders', compact('orders'));
    }

    public function jualBeliAkunDetail(int $listing)
    {
        $listing = app(JbaApiClient::class)->find($listing);
        abort_unless($listing && $listing->is_active, 404);

        $related = app(JbaApiClient::class)
            ->listings($listing->game)
            ->where('game', $listing->game)
            ->where('id', '!=', $listing->id)
            ->sortBy(fn (AccountListing $item) => sprintf(
                '%d|%s',
                $item->is_sold ? 1 : 0,
                mb_strtolower($item->product_name)
            ))
            ->values();

        $gameSlug = array_search($listing->game, static::JBA_GAME_SLUGS) ?: null;

        return view('pages.jual-beli-akun-detail', compact('listing', 'related', 'gameSlug'));
    }

    public function jualBeliAkunCheckout(Request $request, int $listing)
    {
        $listing = app(JbaApiClient::class)->find($listing);
        abort_unless($listing, 404);

        if ($listing->is_sold || ! $listing->is_active) {
            return redirect()->route('jual-beli-akun.detail', $listing)
                ->with('error', 'Produk ini sudah tidak tersedia.');
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $paymentMethods = app(PaymentGatewayService::class)->filterAvailableMethods($paymentMethods);
        $gatewayName = app(PaymentGatewayService::class)->providerLabel();

        $pointBalance = auth()->check() ? app(\App\Services\BalanceService::class)->balanceFor(auth()->id()) : 0;
        $emailConfirmation = (array) $request->session()->get('jba_email_confirmation', []);
        $emailCodeRetryAfter = isset($emailConfirmation['sent_at'])
            ? max(0, self::EMAIL_CONFIRMATION_COOLDOWN_SECONDS - (now()->timestamp - (int) $emailConfirmation['sent_at']))
            : 0;
        $emailConfirmationPending = isset($emailConfirmation['email'], $emailConfirmation['code_hash'], $emailConfirmation['expires_at'])
            && (int) $emailConfirmation['expires_at'] >= now()->timestamp;
        $emailConfirmationEmail = $emailConfirmationPending ? (string) $emailConfirmation['email'] : null;
        $oldConfirmationCode = (string) $request->old('email_confirmation_code', '');
        $emailCodeVerified = $emailConfirmationPending
            && isset($emailConfirmation['verified_at'])
            && preg_match('/^\d{6}$/', $oldConfirmationCode) === 1
            && hash_equals((string) $emailConfirmation['code_hash'], hash('sha256', $oldConfirmationCode));

        return view('pages.jual-beli-akun-checkout', compact('listing', 'paymentMethods', 'gatewayName', 'pointBalance', 'emailCodeRetryAfter', 'emailConfirmationPending', 'emailConfirmationEmail', 'emailCodeVerified'));
    }

    public function jualBeliAkunEmailTest(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);
        $email = mb_strtolower(trim($validated['email']));
        $existingConfirmation = (array) $request->session()->get('jba_email_confirmation', []);
        $retryAfter = isset($existingConfirmation['sent_at'])
            ? max(0, self::EMAIL_CONFIRMATION_COOLDOWN_SECONDS - (now()->timestamp - (int) $existingConfirmation['sent_at']))
            : 0;

        if ($retryAfter > 0) {
            return response()->json([
                'message' => "Kode baru dapat dikirim dalam {$retryAfter} detik.",
                'retry_after' => $retryAfter,
            ], 429);
        }

        $confirmationCode = (string) random_int(100000, 999999);

        try {
            Mail::to($email)->send(new AccountEmailTestMail($email, $confirmationCode, business_info()));
        } catch (\Throwable $exception) {
            Log::error('Kode konfirmasi pembelian akun gagal dikirim', [
                'recipient' => $email,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Kode konfirmasi gagal dikirim. Periksa alamat email atau coba lagi.',
            ], 422);
        }

        $request->session()->put('jba_email_confirmation', [
            'email' => $email,
            'code_hash' => hash('sha256', $confirmationCode),
            'expires_at' => now()->addMinutes(15)->timestamp,
            'sent_at' => now()->timestamp,
            'verify_attempts' => 0,
        ]);

        return response()->json([
            'message' => 'Kode konfirmasi sudah dikirim. Periksa inbox atau folder spam.',
            'retry_after' => self::EMAIL_CONFIRMATION_COOLDOWN_SECONDS,
        ]);
    }

    public function jualBeliAkunEmailConfirmationVerify(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);
        $emailConfirmation = (array) $request->session()->get('jba_email_confirmation', []);
        $attempts = (int) ($emailConfirmation['verify_attempts'] ?? 0);

        if ($attempts >= 5) {
            return response()->json([
                'message' => 'Kode sudah terlalu sering salah. Kirim kode konfirmasi baru.',
            ], 422);
        }

        $valid = isset($emailConfirmation['email'], $emailConfirmation['code_hash'], $emailConfirmation['expires_at'])
            && (int) $emailConfirmation['expires_at'] >= now()->timestamp
            && hash_equals((string) $emailConfirmation['email'], mb_strtolower(trim($validated['email'])))
            && hash_equals((string) $emailConfirmation['code_hash'], hash('sha256', $validated['code']));

        if (! $valid) {
            $request->session()->put('jba_email_confirmation.verify_attempts', $attempts + 1);

            return response()->json([
                'message' => 'Kode konfirmasi salah, kedaluwarsa, atau tidak sesuai dengan email.',
            ], 422);
        }

        $request->session()->put('jba_email_confirmation.verified_at', now()->timestamp);

        return response()->json([
            'message' => 'Kode benar. Email berhasil dikonfirmasi.',
        ]);
    }

    public function jualBeliAkunCheckoutStore(Request $request, int $listing)
    {
        $listing = app(JbaApiClient::class)->find($listing);
        abort_unless($listing, 404);

        if ($listing->is_sold || ! $listing->is_active) {
            return redirect()->route('jual-beli-akun.detail', $listing)
                ->with('error', 'Produk ini sudah tidak tersedia.');
        }

        $availableCodes = app(PaymentGatewayService::class)
            ->filterAvailableMethods(PaymentMethod::where('is_active', true)->get())->pluck('code')->all();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'email_confirmation_code' => ['required', 'string', 'regex:/^\d{6}$/'],
            'customer_phone' => 'required|string|max:20',
            'payment_method' => ['required', 'string', \Illuminate\Validation\Rule::in($availableCodes)],
            'notes' => 'nullable|string|max:1000',
            'use_points' => 'nullable|boolean',
            'points_amount' => 'exclude_unless:use_points,1|required|integer|min:1',
        ], [
            'payment_method.required' => 'Pilih metode pembayaran terlebih dahulu.',
            'payment_method.in' => 'Metode pembayaran yang dipilih tidak tersedia.',
        ]);

        $emailConfirmation = (array) $request->session()->get('jba_email_confirmation', []);
        $emailConfirmationValid = isset($emailConfirmation['email'], $emailConfirmation['code_hash'], $emailConfirmation['expires_at'], $emailConfirmation['verified_at'])
            && (int) $emailConfirmation['expires_at'] >= now()->timestamp
            && hash_equals((string) $emailConfirmation['email'], mb_strtolower(trim($validated['customer_email'])))
            && hash_equals((string) $emailConfirmation['code_hash'], hash('sha256', $validated['email_confirmation_code']));

        if (! $emailConfirmationValid) {
            throw ValidationException::withMessages([
                'email_confirmation_code' => 'Kode konfirmasi salah, kedaluwarsa, atau tidak sesuai dengan email.',
            ]);
        }

        $loginCredentials = app(JbaApiClient::class)->loginCredentials((int) $listing->id);

        if (blank($loginCredentials)) {
            return back()
                ->with('error', 'Produk belum siap dikirim otomatis. Silakan hubungi CS Johen.')
                ->withInput();
        }

        $totalPrice = (float) $listing->price;
        $points = $request->boolean('use_points') && auth()->check()
            ? min($totalPrice, app(\App\Services\BalanceService::class)->balanceFor(auth()->id()), (float) ($request->points_amount ?: PHP_INT_MAX))
            : 0;
        $isSimulation = (bool) config('services.payment.simulation');
        $method = strtolower(trim((string) $validated['payment_method']));
        $orderListing = $this->storeJbaApiOrderSnapshot($listing, $loginCredentials);

        $order = AccountOrder::create([
            'account_listing_id' => $orderListing->id,
            'user_id' => auth()->id(),
            'order_ref' => 'JOHEN-'.strtoupper(Str::random(10)),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'payment_method' => $method,
            'status' => 'pending',
            // Nominal dihitung otomatis dari harga listing; user tidak input manual.
            'total_price' => $totalPrice,
            'cash_amount' => max(0, $totalPrice - $points),
            'saldo_amount' => max(0, $totalPrice - $points),
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($points > 0) {
            $points = app(\App\Services\BalanceService::class)->reservePoints($order, $points);
            $order->update([
                'cash_amount' => max(0, $totalPrice - $points),
                'saldo_amount' => max(0, $totalPrice - $points),
            ]);
        }

        // Buat charge pada payment gateway aktif untuk metode yang dipilih.
        // Referensi webhook = order_ref; nominal = total_price.
        $gateway = app(PaymentGatewayService::class);
        if ((float) $order->cash_amount > 0 && ! $isSimulation && $gateway->isConfigured()) {
            $charged = $gateway->chargeAccount($order, $method, [
                'item_name' => $listing->product_name,
            ]);

            if (! $charged) {
                $message = $gateway->lastChargeError()
                    ?: 'Halaman pembayaran Ayolinx gagal dibuat. Silakan coba lagi.';

                app(\App\Services\BalanceService::class)->restorePoints($order, 'Pembuatan pembayaran gagal; Johen Point dikembalikan');
                $order->delete();

                return redirect()->route('jual-beli-akun.checkout', $listing)
                    ->with('error', $message)
                    ->withInput();
            }
        }


        if ((float) $order->cash_amount <= 0) {
            static::settleAccountOrder($order);
        }

        $request->session()->forget('jba_email_confirmation');

        return redirect()->route('jual-beli-akun.payment', $order)
            ->with('success', 'Pesanan berhasil dibuat! Silakan lakukan pembayaran.');
    }

    private function storeJbaApiOrderSnapshot(AccountListing $listing, string $loginCredentials): AccountListing
    {
        return AccountListing::updateOrCreate([
            'source' => 'jba_api',
            'source_id' => (string) $listing->id,
        ], [
            'source_url' => app(JbaApiClient::class)->sourceUrl((int) $listing->id),
            'game' => $listing->game,
            'product_name' => $listing->product_name,
            'specifications' => $listing->specifications,
            'price' => $listing->price,
            'original_price' => $listing->original_price,
            'owner_name' => $listing->owner_name,
            'whatsapp' => $listing->whatsapp,
            'login_credentials' => $loginCredentials,
            'promo_type' => $listing->promo_type,
            'discount_percent' => $listing->discount_percent,
            'collector_tier' => $listing->collector_tier,
            'deal_type' => $listing->deal_type,
            'is_active' => $listing->is_active,
            'is_sold' => $listing->is_sold,
            'photo' => $listing->photo,
            'detail_photo_1' => $listing->detail_photo_1,
            'detail_photo_2' => $listing->detail_photo_2,
            'detail_photo_3' => $listing->detail_photo_3,
            'detail_photo_4' => $listing->detail_photo_4,
            'video_url' => $listing->video_url,
        ]);
    }

    public function jualBeliAkunPayment(AccountOrder $accountOrder)
    {
        $listing = $accountOrder->listing;

        if (! $listing) {
            abort(404);
        }

        $isSimulation = (bool) config('services.payment.simulation');
        $invoiceUrl = $accountOrder->gateway_invoice_url;

        // Label & logo metode dari tabel payment_methods (map code → nama).
        $paymentMethod = PaymentMethod::where('code', strtolower((string) $accountOrder->payment_method))->first();
        $hasReviewed = Review::where('order_id', $accountOrder->order_ref)
            ->where('service_type', 'jual-beli-akun')
            ->exists();
        $reviewUser = auth('web')->user();
        $canReview = $reviewUser && ($accountOrder->user_id
            ? (int) $accountOrder->user_id === (int) $reviewUser->id
            : mb_strtolower(trim($accountOrder->customer_email)) === mb_strtolower(trim($reviewUser->email)));

        return view('pages.jual-beli-akun-payment', compact(
            'accountOrder', 'listing', 'isSimulation', 'invoiceUrl', 'paymentMethod', 'hasReviewed', 'canReview'
        ));
    }

    public function jualBeliAkunSimulatePayment(AccountOrder $accountOrder)
    {
        abort_unless((bool) config('services.payment.simulation'), 404);

        if (! in_array($accountOrder->status, ['pending', 'processing'], true)) {
            return redirect()->route('jual-beli-akun.payment', $accountOrder);
        }

        $gatewayExtra = (array) $accountOrder->gateway_extra;
        $gatewayExtra['payment_status'] = 'SUCCESS';
        $gatewayExtra['simulation'] = true;
        $gatewayExtra['paid_at'] = now()->toIso8601String();

        $accountOrder->update([
            'status' => 'processing',
            'gateway_extra' => $gatewayExtra,
        ]);

        $delivered = static::settleAccountOrder($accountOrder->fresh());
        $accountOrder->refresh();

        if ($delivered) {
            return redirect()->route('jual-beli-akun.payment', $accountOrder)
                ->with('success', 'Pembayaran simulasi berhasil. Data login akun sedang dikirim ke email.');
        }

        return redirect()->route('jual-beli-akun.payment', $accountOrder)
            ->with('error', $accountOrder->credentials_email_error ?: 'Pembayaran diterima dan pesanan sedang diproses.');
    }

    /**
     * Polling status untuk halaman pembayaran JBA.
     * Dicocokkan per tipe gateway (QRIS/VA/retail/e-wallet/invoice).
     * Saat lunas → sukses otomatis + listing ditandai terjual.
     */
    public function jualBeliAkunPaymentStatus(AccountOrder $accountOrder)
    {
        if ($accountOrder->status === 'processing' && ! $accountOrder->credentials_email_sent_at) {
            app(\App\Services\AccountDeliveryService::class)->deliver($accountOrder);
            $accountOrder->refresh();
        }

        if ($accountOrder->status === 'pending') {
            app(\App\Services\AyolinxPaymentService::class)->sync($accountOrder);
            $accountOrder->refresh();
        }

        return response()->json([
            'status' => $accountOrder->status,
            'payment_status' => data_get($accountOrder->gateway_extra, 'payment_status', 'PENDING'),
            'message' => $accountOrder->status === 'sold_out'
                ? 'Maaf, akun ini lebih dahulu dibayar oleh pembeli lain dan sudah terjual.'
                : null,
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * Tandai account order lunas (idempotent): success + listing terjual.
     */
    public static function settleAccountOrder(AccountOrder $accountOrder): bool
    {
        return app(\App\Services\AccountDeliveryService::class)->deliver($accountOrder);
    }

    public static function getTestimonials(): array
    {
        $now = now()->setTimezone('Asia/Jakarta');
        $fmt = fn ($d) => $d->format('d-m-Y H:i:s');

        return [
            ['name' => 'User Free Fire', 'game' => 'Top Up - Free Fire', 'avatar' => '🙂', 'rating' => 5, 'layanan' => 'topup', 'quote' => 'Top up Diamond Free Fire di sini cepat banget. Setelah pembayaran berhasil, diamond langsung masuk ke akun tanpa perlu menunggu lama.', 'date' => $fmt((clone $now)->subMinutes(3))],
            ['name' => 'User Mobile Legends', 'game' => 'Top Up - Mobile Legends', 'avatar' => '😄', 'rating' => 5, 'layanan' => 'topup', 'quote' => 'Top up Diamond MLBB cuma beberapa menit langsung masuk. Harganya juga lebih murah dibanding tempat lain. Sudah langganan dari lama dan selalu aman.', 'date' => $fmt((clone $now)->subMinutes(17))],
            ['name' => 'User PUBG Mobile', 'game' => 'Top Up - PUBG Mobile', 'avatar' => '🎮', 'rating' => 5, 'layanan' => 'topup', 'quote' => 'Top up UC PUBG Mobile menit langsung masuk ke akun. Harganya bersaing, prosesnya cepat, dan sejauh ini tanpa kendala. Sudah beberapa kali top up di sini dan hasilnya selalu memuaskan.', 'date' => $fmt((clone $now)->subHours(2))],
            ['name' => 'User Valorant', 'game' => 'Top Up - Valorant', 'avatar' => '🎯', 'rating' => 5, 'layanan' => 'topup', 'quote' => 'Poin Valorant masuk instan setelah bayar QRIS. Prosesnya jelas dan ada notifikasi tiap tahap. Recommended buat yang males ribet.', 'date' => $fmt((clone $now)->subHours(5))],
            ['name' => 'User Genshin Impact', 'game' => 'Top Up - Genshin Impact', 'avatar' => '💎', 'rating' => 5, 'layanan' => 'topup', 'quote' => 'Genesis Crystal masuk kurang dari 5 menit. CS-nya responsif kalau ada kendala. Harga juga bersahabat buat dompet pelajar seperti saya.', 'date' => $fmt((clone $now)->subHours(8))],
            ['name' => 'User Honor of Kings', 'game' => 'Top Up - Honor of Kings', 'avatar' => '⚔️', 'rating' => 5, 'layanan' => 'topup', 'quote' => 'Top up Token HOK super cepat, kurang dari 2 menit langsung masuk. Harganya juga kompetitif, jadi saya sering top up di sini tiap season baru.', 'date' => $fmt((clone $now)->subDay())],
            ['name' => 'User FIFA Mobile', 'game' => 'Top Up - EA Sports FC', 'avatar' => '⚽', 'rating' => 4, 'layanan' => 'topup', 'quote' => 'FIFA Points masuk secepat kilat. Pertama kali coba agak ragu, tapi setelah bukti sendiri sekarang jadi langganan. Pokoknya recommended!', 'date' => $fmt((clone $now)->subDays(2))],
            ['name' => 'User Steam Wallet', 'game' => 'Top Up - Steam Wallet', 'avatar' => '🕹️', 'rating' => 5, 'layanan' => 'topup', 'quote' => 'Saldo Steam masuk dalam hitungan menit. Harganya bersahabat, prosesnya juga transparan dengan bukti pengisian yang dikirim.', 'date' => $fmt((clone $now)->subDays(4))],
            ['name' => 'User Call of Duty', 'game' => 'Top Up - Call of Duty Mobile', 'avatar' => '🔫', 'rating' => 4, 'layanan' => 'topup', 'quote' => 'CP CODM langsung nambah setelah bayar. Proses cepat tanpa ribet, selalu jadi andalan buat top up mingguan.', 'date' => $fmt((clone $now)->subWeek())],
            ['name' => 'User Mobile Legends', 'game' => 'Joki Rank Mobile Legends', 'avatar' => '🏆', 'rating' => 5, 'layanan' => 'joki', 'quote' => 'Jasa joki rank MLBB profesional banget. Dari Legend ke Mythic dalam 3 hari, aman, fast respon, dan harganya worth it!', 'date' => $fmt((clone $now)->subWeeks(2))],
            ['name' => 'User Free Fire', 'game' => 'Stock Akun - Free Fire', 'avatar' => '🙂', 'rating' => 5, 'layanan' => 'jual-beli-akun', 'quote' => 'Beli akun Free Fire di sini aman dan terpercaya. Akun sesuai deskripsi, harga reasonable, dan proses transaksinya jelas. Recommended buat yang cari akun second.', 'date' => $fmt((clone $now)->subDays(3))],
            ['name' => 'User Mobile Legends', 'game' => 'Stock Akun - Mobile Legends', 'avatar' => '😄', 'rating' => 5, 'layanan' => 'jual-beli-akun', 'quote' => 'Stock akun MLBB saya laku dalam 2 hari. Admin fast respon dan membantu proses negosiasi dengan pembeli. Sangat membantu!', 'date' => $fmt((clone $now)->subDays(6))],
            ['name' => 'User PUBG Mobile', 'game' => 'Stock Akun - PUBG Mobile', 'avatar' => '🎮', 'rating' => 5, 'layanan' => 'jual-beli-akun', 'quote' => 'Pengalaman stock akun PUBG pertama kali dan ternyata gampang. Admin menjelaskan prosedur dengan jelas. Pembayaran cepat cair.', 'date' => $fmt((clone $now)->subDays(10))],
        ];
    }

    public function testimoni()
    {
        $all = static::getTestimonials();

        $reviews = Review::with('user')->where('status', 'approved')
            ->whereNotNull('comment')
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (Review $r) {
                $name = $r->user?->name
                    ?: ($r->email ? str($r->email)->before('@')->toString() : 'User '.($r->game ?: 'Johen'));
                $serviceType = $r->service_type ?: 'topup';
                $gamePrefix = $serviceType === 'jual-beli-akun' ? 'Stock Akun - ' : 'Top Up - ';

                return [
                    'name' => $name,
                    'avatar_url' => $r->user?->avatar ? media_url($r->user->avatar) : null,
                    'game' => $r->game ? $gamePrefix.$r->game : rtrim($gamePrefix, ' -'),
                    'avatar' => '🙂',
                    'rating' => (int) $r->rating,
                    'layanan' => $serviceType,
                    'quote' => $r->comment,
                    'date' => $r->created_at ? $r->created_at->format('d-m-Y H:i:s') : now()->format('d-m-Y H:i:s'),
                ];
            })
            ->toArray();

        $all = array_merge($reviews, $all);

        $layanan = request('layanan');
        $testimonials = $layanan ? array_filter($all, fn ($t) => ($t['layanan'] ?? '') === $layanan) : $all;
        $activeLayanan = $layanan;

        return view('pages.testimoni', compact('testimonials', 'activeLayanan'));
    }

    public function kontak()
    {
        return view('pages.kontak');
    }

    public function faq()
    {
        return view('pages.faq');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function kontakStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'category' => 'required|string|in:topup,jual-beli-akun,pembayaran,keluhan,saran,lainnya',
            'message' => 'required|string|max:5000',
        ]);

        $data = $validated;
        $data['user_id'] = auth()->check() ? auth()->id() : null;

        ContactInquiry::create($data);

        return redirect()->route('kontak')
            ->with('success', 'Pesan berhasil dikirim! Tim CS kami akan menghubungi anda segera.');
    }

    public function myInquiries()
    {
        $inquiries = ContactInquiry::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('pages.my-inquiries', compact('inquiries'));
    }
}
