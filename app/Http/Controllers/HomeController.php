<?php

namespace App\Http\Controllers;

use App\Models\AccountListing;
use App\Models\AccountOrder;
use App\Models\Brand;
use App\Models\ContactInquiry;
use App\Models\FlashDeal;
use App\Models\FlashSaleBanner;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Services\GameAccountService;
use App\Services\PaymentGatewayService;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    private const JBA_GAME_SLUGS = [
        'mlbb' => 'Mobile Legends',
        'pubg' => 'PUBG Mobile',
        'efootball' => 'E-Football',
        'fcm' => 'FC Mobile',
        'ff' => 'Free Fire',
        'roblox' => 'Roblox',
        'valorant' => 'Valorant',
    ];

    private static function jbaPageData(?string $activeGame = null): array
    {
        $listings = AccountListing::where(function ($q) {
            $q->where('is_active', true)->orWhere('is_sold', true);
        })
            ->orderBy('is_sold', 'asc')
            ->orderBy('game')
            ->orderBy('product_name')
            ->get()
            ->groupBy('game');

        // Daftar awal untuk render server (SEO + fallback tanpa JavaScript).
        // Saat game aktif, tampilkan listing game tersebut; selain itu, ambil
        // sampel per game agar beranda tetap punya konten produk.
        $serverSource = $activeGame
            ? ($listings->get($activeGame) ?? collect())
            : $listings->flatMap(fn ($items) => $items->take(4));
        $serverTotal = $serverSource->count();
        $serverListings = $serverSource->take(24)->values();

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

        $gameBanners = [];
        foreach (static::JBA_GAME_SLUGS as $slug => $game) {
            $path = SiteSetting::get('jba_game_banner_'.$slug);
            $gameBanners[$slug] = $path ? media_url($path) : null;
        }

        $jbaTestis = static::getTestimonials();
        $jbaRating = collect($jbaTestis)->filter(fn ($t) => ($t['layanan'] ?? '') === 'jual-beli-akun')->avg('rating');
        $jbaRating = $jbaRating ? round((float) $jbaRating, 1) : 4.9;

        return compact('popularGames', 'listings', 'testimonials', 'flashSaleBanners', 'budgetBanners', 'gameSlugs', 'gameBanners', 'jbaRating', 'serverListings', 'serverTotal');
    }

    public function index()
    {
        if (Auth::guard('admin')->check() && ! Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $brands = $this->activeGameBrandsQuery()
            // Prioritaskan game yang ditandai populer dan sudah memiliki artwork.
            // Urutan berikutnya tetap memberi keunggulan pada game populer, lalu game
            // dengan thumbnail, sebelum memakai urutan manual dari admin.
            ->orderByRaw("CASE
                WHEN is_topup_popular = 1 AND COALESCE(thumbnail, '') <> '' THEN 0
                WHEN is_topup_popular = 1 THEN 1
                WHEN COALESCE(thumbnail, '') <> '' THEN 2
                ELSE 3
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

        $latestAccountStock = collect(static::JBA_GAME_SLUGS)
            ->map(function (string $game, string $slug) use ($stockArtwork) {
                $listings = AccountListing::query()
                    ->where('game', $game)
                    ->where('is_active', true)
                    ->where('is_sold', false)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(15)
                    ->get();

                return [
                    'game' => $game,
                    'slug' => $slug,
                    'artwork' => 'assets/produk-terbaru/'.$stockArtwork[$slug],
                    'listings' => $listings,
                ];
            })
            ->filter(fn (array $category) => $category['listings']->isNotEmpty())
            ->values();

        return view('home', compact('brands', 'flashDeals', 'latestAccountStock', 'popularTopupGames'));
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

        $products = $this->activeGameProductsQuery()
            ->whereRaw('LOWER(brand) = ?', [mb_strtolower($brand->name)])
            ->when($isMobileLegends, fn ($query) => $query->whereRaw('LOWER(buyer_sku_code) LIKE ?', ['%-idn']))
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

        return view('game-detail', compact('brand', 'products', 'paymentMethods', 'flashDeals', 'isMobileLegends'));
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
        $methods = PaymentMethod::where('is_active', true)->get(['name', 'code', 'icon', 'photo', 'photo_light']);

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

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        return response()->json([
            'transactions' => $orders->map(fn ($o) => [
                'order_id' => $o->order_id,
                'product_name' => $o->product_name,
                'customer_number' => $o->customer_number,
                'price' => (float) $o->price,
                'status' => $o->status,
                'processed_at' => $o->updated_at?->format('d M Y H:i'),
            ])->values(),
        ]);
    }

    public function checkTransaction()
    {
        return view('check-transaction');
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
                ->map(fn ($item, $i) => ['rank' => $i + 1, 'name' => $item->name, 'amount' => (int) $item->total_amount])
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
     * Pesanan Saya untuk pembelian akun (Jual Beli Akun).
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

    public function jualBeliAkunDetail(AccountListing $listing)
    {
        if (! $listing->is_active && ! $listing->is_sold) {
            abort(404);
        }

        $related = AccountListing::where(function ($q) {
            $q->where('is_active', true)->orWhere('is_sold', true);
        })
            ->where('game', $listing->game)
            ->where('id', '!=', $listing->id)
            ->orderBy('is_sold', 'asc')
            ->orderBy('product_name')
            ->get();

        $gameSlug = array_search($listing->game, static::JBA_GAME_SLUGS) ?: null;

        return view('pages.jual-beli-akun-detail', compact('listing', 'related', 'gameSlug'));
    }

    public function jualBeliAkunCheckout(AccountListing $listing)
    {
        if ($listing->is_sold || ! $listing->is_active) {
            return redirect()->route('jual-beli-akun.detail', $listing)
                ->with('error', 'Produk ini sudah tidak tersedia.');
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $paymentMethods = app(PaymentGatewayService::class)->filterAvailableMethods($paymentMethods);

        return view('pages.jual-beli-akun-checkout', compact('listing', 'paymentMethods'));
    }

    public function jualBeliAkunCheckoutStore(Request $request, AccountListing $listing)
    {
        if ($listing->is_sold || ! $listing->is_active) {
            return redirect()->route('jual-beli-akun.detail', $listing)
                ->with('error', 'Produk ini sudah tidak tersedia.');
        }

        $availableCodes = PaymentMethod::where('is_active', true)->pluck('code')->all();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|string|in:'.implode(',', array_unique(array_merge($availableCodes, ['qris']))),
            'notes' => 'nullable|string|max:1000',
        ]);

        $totalPrice = (float) $listing->price;
        $isSimulation = (bool) config('services.payment.simulation');
        $method = strtolower(trim((string) $validated['payment_method']));

        $order = AccountOrder::create([
            'account_listing_id' => $listing->id,
            'user_id' => auth()->id(),
            'order_ref' => 'JBA-'.strtoupper(Str::random(10)),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'payment_method' => $method,
            'status' => 'pending',
            // Nominal dihitung otomatis dari harga listing; user tidak input manual.
            'total_price' => $totalPrice,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Buat charge gateway (Xendit) untuk metode yang dipilih.
        // Referensi webhook = order_ref; nominal = total_price.
        if (! $isSimulation && app(XenditService::class)->isConfigured()) {
            $gateway = app(PaymentGatewayService::class);
            $charged = $gateway->chargeAccount($order, $method, [
                'item_name' => $listing->product_name,
            ]);

            if (! $charged) {
                // QRIS: fallback ke QR statis (gambar QR milik toko).
                if ($method === 'qris') {
                    Log::warning('QRIS dynamic account order gagal, fallback statis', [
                        'order_ref' => $order->order_ref,
                    ]);
                } else {
                    $label = PaymentMethod::where('code', $method)->value('name') ?: $method;
                    $minAmount = in_array($gateway->resolve($method)['gateway_type'], ['va', 'retail'], true)
                        ? 'Rp 10.000'
                        : 'Rp 1.000';
                    $message = "Metode $label gagal dibuat untuk nominal ini (minimal $minAmount)."
                        .' Silakan pilih QRIS atau metode lain.';

                    $order->delete();

                    return redirect()->route('jual-beli-akun.checkout', $listing)
                        ->with('error', $message)
                        ->withInput();
                }
            }
        }

        return redirect()->route('jual-beli-akun.payment', $order)
            ->with('success', 'Pesanan berhasil dibuat! Silakan lakukan pembayaran.');
    }

    public function jualBeliAkunPayment(AccountOrder $accountOrder)
    {
        $listing = $accountOrder->listing;

        if (! $listing) {
            abort(404);
        }

        $isSimulation = (bool) config('services.payment.simulation');
        $gatewayType = $accountOrder->gateway_type ?: 'qris';
        // QRIS dinamis (Xendit) dianggap aktif bila qr_string terisi
        // → nominal otomatis saat scan. Selain itu pakai QRIS statis (gambar QR milik toko).
        $isDynamic = ! empty($accountOrder->qr_string);
        $qrString = $accountOrder->qr_string;
        // QRIS statis (gambar QR milik toko) sebagai fallback.
        $qrisImage = (string) SiteSetting::get('qris_image', '');

        $vaNumber = $accountOrder->va_number;
        $paymentCode = $accountOrder->payment_code;
        $checkoutUrl = $accountOrder->checkout_url;
        $invoiceUrl = $accountOrder->gateway_invoice_url;
        $gatewayExtra = $accountOrder->gateway_extra ?: [];

        // Label & logo metode dari tabel payment_methods (map code → nama).
        $paymentMethod = PaymentMethod::where('code', strtolower((string) $accountOrder->payment_method))->first();

        return view('pages.jual-beli-akun-payment', compact(
            'accountOrder', 'listing', 'isSimulation', 'isDynamic', 'qrString', 'qrisImage',
            'gatewayType', 'vaNumber', 'paymentCode', 'checkoutUrl', 'invoiceUrl', 'gatewayExtra', 'paymentMethod'
        ));
    }

    /**
     * Polling status untuk halaman pembayaran JBA.
     * Dicocokkan per tipe gateway (QRIS/VA/retail/e-wallet/invoice).
     * Saat lunas → sukses otomatis + listing ditandai terjual.
     */
    public function jualBeliAkunPaymentStatus(AccountOrder $accountOrder)
    {
        if ($accountOrder->status === 'pending'
            && ! empty($accountOrder->gateway_invoice_id)
            && app(XenditService::class)->isConfigured()) {
            $xendit = app(XenditService::class);
            $type = $accountOrder->gateway_type ?: 'qris';
            $paid = false;
            $failed = null;

            if ($type === 'qris' || ! empty($accountOrder->qr_string)) {
                $qr = $xendit->getQr($accountOrder->gateway_invoice_id);

                if ($qr && ! empty($qr['status'])) {
                    $status = strtoupper($qr['status']);
                    $paid = in_array($status, ['INACTIVE', 'COMPLETED'], true);
                    $failed = in_array($status, ['FAILED', 'EXPIRED'], true) ? 'failed' : null;
                }
            } elseif ($type === 'va') {
                $va = $xendit->getVirtualAccount($accountOrder->gateway_invoice_id);

                if ($va && ! empty($va['status'])) {
                    $status = strtoupper($va['status']);
                    $paid = $status === 'INACTIVE';
                    $failed = in_array($status, ['FAILED', 'EXPIRED'], true) ? 'failed' : null;
                }
            } elseif ($type === 'retail') {
                $retail = $xendit->getRetailOutlet($accountOrder->gateway_invoice_id);

                if ($retail && ! empty($retail['status'])) {
                    $status = strtoupper($retail['status']);
                    $paid = $status === 'INACTIVE';
                    $failed = in_array($status, ['FAILED', 'EXPIRED'], true) ? 'failed' : null;
                }
            } elseif ($type === 'ewallet') {
                $charge = $xendit->getEwalletCharge($accountOrder->gateway_invoice_id);

                if ($charge && ! empty($charge['status'])) {
                    $status = strtoupper($charge['status']);
                    $paid = in_array($status, ['SUCCEEDED', 'COMPLETED', 'CAPTURED'], true);
                    $failed = in_array($status, ['FAILED', 'EXPIRED', 'CANCELLED'], true) ? 'failed' : null;
                }
            } else {
                $invoice = $xendit->getInvoice($accountOrder->gateway_invoice_id);

                if ($invoice && ! empty($invoice['status'])) {
                    $status = strtoupper($invoice['status']);
                    $paid = in_array($status, ['PAID', 'SETTLED'], true);
                    $failed = $status === 'EXPIRED' ? 'failed' : null;
                }
            }

            if ($paid) {
                static::settleAccountOrder($accountOrder);
            } elseif ($failed) {
                $accountOrder->update(['status' => $failed]);
            }
        }

        return response()->json(['status' => $accountOrder->status]);
    }

    /**
     * Tandai account order lunas (idempotent): success + listing terjual.
     */
    public static function settleAccountOrder(AccountOrder $accountOrder): void
    {
        if ($accountOrder->status !== 'pending') {
            return;
        }

        $accountOrder->update(['status' => 'success']);
        $accountOrder->listing?->update(['is_sold' => true]);
        Log::info('Account order lunas', ['order_ref' => $accountOrder->order_ref]);
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
            ['name' => 'User Free Fire', 'game' => 'Jual Akun - Free Fire', 'avatar' => '🙂', 'rating' => 5, 'layanan' => 'jual-beli-akun', 'quote' => 'Beli akun Free Fire di sini aman dan terpercaya. Akun sesuai deskripsi, harga reasonable, dan proses transaksinya jelas. Recommended buat yang cari akun second.', 'date' => $fmt((clone $now)->subDays(3))],
            ['name' => 'User Mobile Legends', 'game' => 'Jual Akun - Mobile Legends', 'avatar' => '😄', 'rating' => 5, 'layanan' => 'jual-beli-akun', 'quote' => 'Jual akun MLBB saya laku dalam 2 hari. Admin fast respon dan membantu proses negosiasi dengan pembeli. Sangat membantu!', 'date' => $fmt((clone $now)->subDays(6))],
            ['name' => 'User PUBG Mobile', 'game' => 'Jual Akun - PUBG Mobile', 'avatar' => '🎮', 'rating' => 5, 'layanan' => 'jual-beli-akun', 'quote' => 'Pengalaman jual akun PUBG pertama kali dan ternyata gampang. Admin menjelaskan prosedur dengan jelas. Pembayaran cepat cair.', 'date' => $fmt((clone $now)->subDays(10))],
        ];
    }

    public function testimoni()
    {
        $all = static::getTestimonials();

        $reviews = Review::where('status', 'approved')
            ->whereNotNull('comment')
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (Review $r) {
                $name = $r->user?->name
                    ?: ($r->email ? str($r->email)->before('@')->toString() : 'User '.($r->game ?: 'Johen'));

                return [
                    'name' => $name,
                    'game' => ($r->game ? 'Top Up - '.$r->game : 'Top Up'),
                    'avatar' => '🙂',
                    'rating' => (int) $r->rating,
                    'layanan' => 'topup',
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
