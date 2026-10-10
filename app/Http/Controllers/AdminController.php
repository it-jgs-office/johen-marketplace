<?php

namespace App\Http\Controllers;

use App\Mail\ContactReplyMail;
use App\Models\AccountOrder;
use App\Models\Brand;
use App\Models\ContactInquiry;
use App\Models\LiveChatChannel;
use App\Models\LiveChatOperator;
use App\Models\LiveChatOperatorSchedule;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\DigiflazzService;
use App\Services\ImageOptimizer;
use App\Services\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    protected DigiflazzService $digiflazz;

    public function __construct(DigiflazzService $digiflazz)
    {
        $this->digiflazz = $digiflazz;
    }

    public function dashboard()
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'success_orders' => Order::where('status', 'success')->count(),
            'total_users' => User::count(),
            'total_revenue' => Order::where('status', 'success')->sum('price'),
            'digiflazz_configured' => $this->digiflazz->isConfigured(),
            'digiflazz_last_sync' => \App\Models\SiteSetting::get('digiflazz_last_sync'),
            'digiflazz_product_count' => \App\Models\SiteSetting::get('digiflazz_product_count', '0'),
        ];

        $recentOrders = Order::with('user')->latest()->take(10)->get();

        $stats['account_total'] = AccountOrder::count();
        $stats['account_pending'] = AccountOrder::where('status', 'pending')->count();
        $stats['account_success'] = AccountOrder::where('status', 'success')->count();
        $stats['account_revenue'] = AccountOrder::where('status', 'success')->sum('total_price');
        $stats['account_processing'] = AccountOrder::where('status', 'processing')->count();

        $recentAccountOrders = AccountOrder::with('listing')
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentAccountOrders'));
    }

    public function gatewayStatus()
    {
        $appUrl = config('app.url');
        $isLocal = in_array(parse_url($appUrl, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1']);

        $xenditConfigured = app(\App\Services\XenditService::class)->isConfigured();
        $xenditSvc = app(\App\Services\XenditService::class);

        $checks = [
            'domain_public' => [
                'label' => 'Domain/URL publik (bukan localhost)',
                'ok' => !$isLocal,
                'detail' => $isLocal
                    ? 'APP_URL masih "' . $appUrl . '". Webhook Xendit & Digiflazz tidak dapat menjangkau localhost. Deploy + set APP_URL ke https://domain.'
                    : 'APP_URL = ' . $appUrl,
            ],
            'xendit_configured' => [
                'label' => 'Xendit API key terkonfigurasi',
                'ok' => $xenditSvc->isConfigured(),
                'detail' => $xenditSvc->isConfigured() ? 'Secret key terisi.' : 'XENDIT_SECRET_KEY masih kosong.',
            ],
            'xendit_live' => [
                'label' => 'Xendit dalam mode live (bukan test)',
                'ok' => (bool) config('xendit.is_production'),
                'detail' => config('xendit.is_production')
                    ? 'Mode live aktif.'
                    : 'Masih mode test (' . (str_starts_with((string) config('xendit.secret_key'), 'xnd_development_') ? 'key development' : 'check key') . '). Verifikasi & ganti key live setelah approve.',
            ],
            'xendit_callback_token' => [
                'label' => 'Xendit callback token terisi',
                'ok' => (string) config('xendit.callback_token') !== '',
                'detail' => (string) config('xendit.callback_token') !== '' ? 'Terkonfigurasi.' : 'XENDIT_CALLBACK_TOKEN kosong Ã¢â‚¬â€ webhook akan ditolak.',
            ],
            'webhook_route' => [
                'label' => 'CSRF dikecualikan di webhook',
                'ok' => true,
                'detail' => 'payment/notification & digiflazz/callback sudah di-exclude CSRF.',
            ],
            'digiflazz_configured' => [
                'label' => 'Digiflazz API terkonfigurasi',
                'ok' => $this->digiflazz->isConfigured(),
                'detail' => $this->digiflazz->isConfigured() ? 'Username & key terisi.' : 'DIGIFLAZZ_USERNAME/KEY kosong.',
            ],
            'digiflazz_mismatch' => [
                'label' => 'Digiflazz key & mode konsisten',
                'ok' => count($this->digiflazz->configProblems()) === 0,
                'detail' => implode(' ', $this->digiflazz->configProblems()) ?: 'Key & mode konsisten (username: ' . $this->digiflazz->getUsername() . ', production=' . ($this->digiflazz->isProduction() ? 'ya' : 'tidak') . ').',
            ],
            'simulation' => [
                'label' => 'Mode simulasi pembayaran',
                'ok' => !(bool) config('services.payment.simulation'),
                'detail' => config('services.payment.simulation')
                    ? 'PAYMENT_SIMULATION=true Ã¢â‚¬â€ order tidak melibatkan pembayaran Digiflazz nyata.'
                    : 'PAYMENT_SIMULATION=false Ã¢â‚¬â€ pembayaran nyata.',
            ],
            'push_notification' => [
                'label' => 'Web Push (notifikasi live chat)',
                'ok' => !empty(config('services.vapid.public_key')) && !empty(config('services.vapid.private_key')),
                'detail' => !empty(config('services.vapid.public_key')) && !empty(config('services.vapid.private_key'))
                    ? 'VAPID keys aktif. Pastikan admin & user mengaktifkan notifikasi di browser (tombol "Aktifkan Notifikasi").'
                    : 'VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY belum diisi di .env.',
            ],
        ];

        $pushStats = [
            'configured' => !empty(config('services.vapid.public_key')) && !empty(config('services.vapid.private_key')),
            'web' => \App\Models\PushSubscription::where('guard', 'web')->count(),
            'admin' => \App\Models\PushSubscription::where('guard', 'admin')->count(),
            'lcadmin' => \App\Models\PushSubscription::where('guard', 'lcadmin')->count(),
        ];

        $digiflazzConfigured = $this->digiflazz->isConfigured();
        $balanceResult = $digiflazzConfigured ? $this->digiflazz->checkBalance() : [];
        $digiflazzBalance = $digiflazzConfigured ? $this->digiflazz->formatBalance($balanceResult) : null;
        $digiflazzBalanceNumber = (float) ($balanceResult['data']['balance'] ?? $balanceResult['balance'] ?? 0);
        $digiflazzProductCount = (int) SiteSetting::get('digiflazz_product_count', '0');
        $digiflazzMarginPercent = $this->digiflazz->getMarginPercent();
        $digiflazzLastSync = SiteSetting::get('digiflazz_last_sync');

        return view('admin.gateway-status', compact(
            'checks',
            'isLocal',
            'appUrl',
            'pushStats',
            'digiflazzConfigured',
            'digiflazzBalance',
            'digiflazzBalanceNumber',
            'digiflazzProductCount',
            'digiflazzMarginPercent',
            'digiflazzLastSync',
        ));
    }

    // ---- PRODUCTS ----
    public function products(Request $request)
    {
        // Tetap dukung tautan lama yang mengirim ?brand= agar email/notifikasi
        // langsung menuju daftar nominal game tersebut.
        if ($request->filled('brand')) {
            $brand = Brand::query()
                ->where('catalog_group', 'game')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $request->brand)])
                ->first();

            if ($brand) {
                return redirect()->route('admin.products.game', $brand);
            }
        }

        $games = Brand::query()
            ->where('catalog_group', 'game')
            ->where('is_active', true)
            ->orderByDesc('is_topup_popular')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Brand $brand) {
                $brand->product_count = Product::query()
                    ->whereRaw('LOWER(brand) = ?', [mb_strtolower($brand->name)])
                    ->whereRaw('LOWER(category) = ?', ['games'])
                    ->where('is_active', true)
                    ->count();

                return $brand;
            })
            ->filter(fn (Brand $brand) => $brand->product_count > 0)
            ->values();

        return view('admin.products.topup', compact('games'));
    }

    /** Daftar nominal/diamond yang tersedia untuk satu game top up. */
    public function productsByGame(Request $request, Brand $brand)
    {
        abort_unless($brand->catalog_group === 'game', 404);

        $query = $this->gameProductsQuery()
            ->whereRaw('LOWER(brand) = ?', [mb_strtolower($brand->name)])
            ->orderBy('product_name');

        if ($request->query('status', 'active') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->query('status', 'active') !== 'all') {
            $query->where('is_active', true);
        }

        $products = $query->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products', 'brand'));
    }

    /** Katalog admin hanya berisi produk kategori Games dari brand game. */
    private function gameProductsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->whereRaw('LOWER(category) = ?', ['games'])
            ->whereIn(DB::raw('LOWER(brand)'), Brand::query()
                ->where('catalog_group', 'game')
                ->selectRaw('LOWER(name)'));
    }

    /** Harga jual adalah satu-satunya nilai produk Digiflazz yang bisa diubah admin. */
    public function productsUpdateSellingPrice(Request $request, Product $product)
    {
        $validated = $request->validate([
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $price = round((float) $validated['selling_price'], 2);
        $product->update([
            'selling_price' => $price,
            // Harga override tidak ditimpa saat sinkronisasi dari Digiflazz.
            'selling_price_override' => $price,
            'selling_markup_type' => null,
            'selling_markup_value' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Harga jual berhasil diperbarui',
                'selling_price' => $product->selling_price,
            ]);
        }

        return back()->with('success', 'Harga jual berhasil diperbarui');
    }

    /** Terapkan markup Rupiah atau persentase pada semua produk dalam satu game. */
    public function productsApplyMarkup(Request $request, Brand $brand)
    {
        abort_unless($brand->catalog_group === 'game', 404);

        $validated = $request->validate([
            'mode' => ['required', 'in:rupiah,persentase'],
            'value' => ['required', 'numeric', 'min:0', 'max:100000000'],
        ]);

        $products = $this->gameProductsQuery()
            ->whereRaw('LOWER(brand) = ?', [mb_strtolower($brand->name)])
            ->get();

        $value = round((float) $validated['value'], 2);
        $prices = [];
        DB::transaction(function () use ($products, $validated, $value, &$prices) {
            foreach ($products as $product) {
                $sellingPrice = $this->markupSellingPrice((float) $product->price, $validated['mode'], $value);
                $product->update([
                    'selling_price' => $sellingPrice,
                    // Markup tersimpan sebagai aturan agar sinkronisasi berikutnya
                    // menghitung ulang dengan harga modal terbaru dari Digiflazz.
                    'selling_price_override' => null,
                    'selling_markup_type' => $validated['mode'],
                    'selling_markup_value' => $value,
                ]);
                $prices[$product->id] = $sellingPrice;
            }
        });

        return response()->json([
            'message' => count($prices).' harga jual berhasil diperbarui.',
            'updated' => count($prices),
            'prices' => $prices,
        ]);
    }

    private function markupSellingPrice(float $cost, string $mode, float $value): float
    {
        $price = $mode === 'persentase'
            ? $cost * (1 + ($value / 100))
            : $cost + $value;

        return round(max(0, $price), 2);
    }

    public function productsToggle(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);
        return back()->with('success', 'Status produk berhasil diubah');
    }

    public function productsSync(Request $request)
    {
        // Tombol sinkronisasi admin harus mengambil kondisi katalog saat ini,
        // bukan memakai cache price list yang dapat berumur hingga satu jam.
        $force = $request->boolean('force', true);
        $result = $this->digiflazz->syncProducts($force);

        if ($result['success']) {
            return redirect()->route('admin.products')->with('success', $result['message']);
        }

        return redirect()->route('admin.products')->with('error', $result['message']);
    }

    // ---- BRANDS ----
    public function brands()
    {
        $brands = Brand::orderBy('sort_order')->orderBy('name')->paginate(20);
        return view('admin.brands.index', compact('brands'));
    }

    public function brandsEdit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function brandsUpdate(Request $request, Brand $brand)
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
            'category' => 'required|string|max:50',
            'service_type' => 'required|string|in:topup,joki,both',
            'catalog_group' => 'nullable|string|in:game,pulsa',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:5120|dimensions:max_width=8000,max_height=8000',
            'topup_character_image' => 'nullable|image|mimes:png,webp|max:4096|dimensions:max_width=8000,max_height=8000',
            'topup_popular_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096|dimensions:max_width=8000,max_height=8000',
            'topup_popular_logo' => 'nullable|image|mimes:png,webp|max:2048|dimensions:max_width=8000,max_height=8000',
            'featured_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=8000,max_height=8000',
            'featured_img_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=8000,max_height=8000',
            'featured_img_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=8000,max_height=8000',
            'featured_img_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:max_width=8000,max_height=8000',
            'carousel_bg' => 'nullable|image|mimes:jpeg,png,jpg|max:10240|dimensions:max_width=8000,max_height=8000',
            'detail_bg' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240|dimensions:max_width=8000,max_height=8000',
            'detail_bg_position' => 'nullable|string|max:50',
            'remove_detail_bg' => 'nullable|boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'is_topup_popular' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($request->input('return_to') === 'topup'
            && $request->boolean('is_topup_popular')
            && ! $brand->topup_popular_image
            && ! $request->hasFile('topup_popular_image')) {
            $validator->errors()->add('topup_popular_image', 'Gambar card wajib diunggah saat game populer diaktifkan.');
        }

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'name' => $request->name,
            'category' => $request->category,
            'service_type' => $request->input('service_type', 'topup'),
            'catalog_group' => $request->input('catalog_group', 'game'),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'is_popular' => $request->boolean('is_popular', false),
            'is_topup_popular' => $request->boolean('is_topup_popular', (bool) $brand->is_topup_popular),
            'sort_order' => $request->integer('sort_order', (int) $brand->sort_order),
        ];

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            if ($brand->thumbnail && $brand->thumbnail !== $brand->jba_card_image) {
                MediaStore::delete($brand->thumbnail);
            }
            $data['thumbnail'] = ImageOptimizer::storeOptimized($request->file('thumbnail'), 'brands', 640, 640);
        }

        if ($request->hasFile('topup_character_image') && $request->file('topup_character_image')->isValid()) {
            if ($brand->topup_character_image) {
                MediaStore::delete($brand->topup_character_image);
            }
            $data['topup_character_image'] = ImageOptimizer::storeOptimized($request->file('topup_character_image'), 'brands/characters', 1200, 1600, 1024 * 1024);
        }

        if ($request->hasFile('topup_popular_image') && $request->file('topup_popular_image')->isValid()) {
            if ($brand->topup_popular_image) {
                MediaStore::delete($brand->topup_popular_image);
            }
            $data['topup_popular_image'] = ImageOptimizer::storeOptimized($request->file('topup_popular_image'), 'brands', 1280, 720);
        }

        if ($request->hasFile('topup_popular_logo') && $request->file('topup_popular_logo')->isValid()) {
            if ($brand->topup_popular_logo) {
                MediaStore::delete($brand->topup_popular_logo);
            }
            $data['topup_popular_logo'] = ImageOptimizer::storeOptimized($request->file('topup_popular_logo'), 'brands', 640, 640);
        }

        if ($request->hasFile('featured_thumbnail') && $request->file('featured_thumbnail')->isValid()) {
            if ($brand->featured_thumbnail) {
                MediaStore::delete($brand->featured_thumbnail);
            }
            $data['featured_thumbnail'] = ImageOptimizer::storeOptimized($request->file('featured_thumbnail'), 'brands', 1280, 1280);
        }

        $data = array_merge($data, $this->handleFeaturedImages($request, $brand));

        if ($request->hasFile('carousel_bg') && $request->file('carousel_bg')->isValid()) {
            if ($brand->carousel_bg) {
                MediaStore::delete($brand->carousel_bg);
            }
            $data['carousel_bg'] = ImageOptimizer::optimizeAndCrop($request->file('carousel_bg'), '2:1');
        }

        if ($request->hasFile('detail_bg') && $request->file('detail_bg')->isValid()) {
            if ($brand->detail_bg) {
                MediaStore::delete($brand->detail_bg);
            }
            $data['detail_bg'] = ImageOptimizer::optimizeAndCrop($request->file('detail_bg'), '21:9');
            $data['detail_bg_position'] = $request->input('detail_bg_position', 'center');
        } elseif ($request->boolean('remove_detail_bg')) {
            if ($brand->detail_bg) {
                MediaStore::delete($brand->detail_bg);
            }
            $data['detail_bg'] = null;
            $data['detail_bg_position'] = 'center';
        } else {
            $data['detail_bg_position'] = $request->input('detail_bg_position', 'center');
        }

        $brand->update($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Game berhasil diperbarui']);
        }

        return $request->input('return_to') === 'topup'
            ? redirect()->route('admin.products')->with('success', 'Game berhasil diperbarui')
            : redirect()->route('admin.brands')->with('success', 'Game berhasil diperbarui');
    }

    public function brandsToggle(Brand $brand)
    {
        $brand->update(['is_active' => !$brand->is_active]);
        return back()->with('success', 'Status game berhasil diubah');
    }

    public function brandsDestroy(Brand $brand)
    {
        if ($brand->thumbnail) {
            MediaStore::delete($brand->thumbnail);
        }
        if ($brand->jba_card_image && $brand->jba_card_image !== $brand->thumbnail) {
            MediaStore::delete($brand->jba_card_image);
        }
        if ($brand->topup_character_image) {
            MediaStore::delete($brand->topup_character_image);
        }
        if ($brand->topup_popular_image) {
            MediaStore::delete($brand->topup_popular_image);
        }
        if ($brand->topup_popular_logo) {
            MediaStore::delete($brand->topup_popular_logo);
        }
        if ($brand->featured_thumbnail) {
            MediaStore::delete($brand->featured_thumbnail);
        }
        foreach (['featured_img_1', 'featured_img_2', 'featured_img_3'] as $f) {
            if ($brand->$f) {
                MediaStore::delete($brand->$f);
            }
        }
        if ($brand->detail_bg) {
            MediaStore::delete($brand->detail_bg);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('success', 'Game berhasil dihapus');
    }

    // ---- ORDERS ----
    public function orders()
    {
        $orders = Order::with('user', 'transaction')->latest()->paginate(20);
        return view('admin.orders.index', compact('orders'));
    }

    public function ordersShow(Order $order)
    {
        $order->load('user', 'transaction');
        return view('admin.orders.show', compact('order'));
    }

    public function ordersUpdateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,success,failed,cancelled',
        ]);

        $previous = $order->status;
        $order->update(['status' => $request->status]);

        if (in_array($request->status, ['failed', 'cancelled']) && !in_array($previous, ['failed', 'cancelled'])) {
            $order->releaseDiscounts();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui menjadi ' . $request->status,
                'status' => $request->status,
            ]);
        }

        return back()->with('success', 'Status pesanan berhasil diperbarui menjadi ' . $request->status);
    }

    // ---- ACCOUNT ORDERS (Jual Beli Akun) ----
    public function accountOrders(Request $request)
    {
        $query = AccountOrder::with('user', 'listing');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_ref', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('listing', function ($l) use ($search) {
                        $l->where('product_name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('admin.account-orders.index', compact('orders'));
    }

    public function accountOrdersShow(AccountOrder $accountOrder)
    {
        $accountOrder->load('user', 'listing');
        return view('admin.account-orders.show', compact('accountOrder'));
    }

    public function accountOrdersUpdateStatus(Request $request, AccountOrder $accountOrder)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,success,failed,cancelled',
        ]);

        $newStatus = $request->status;

        $accountOrder->update(['status' => $newStatus]);

        // Sinkronkan status penjualan listing.
        if ($newStatus === 'success') {
            $accountOrder->listing?->update(['is_sold' => true]);
        } elseif (in_array($newStatus, ['failed', 'cancelled'])) {
            $accountOrder->listing?->update(['is_sold' => false]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui menjadi ' . $newStatus,
                'status' => $newStatus,
            ]);
        }

        return back()->with('success', 'Status pesanan akun berhasil diperbarui menjadi ' . $newStatus);
    }

    // ---- USERS ----
    public function users()
    {
        $users = User::with('assignedChannels')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function usersEdit(User $user)
    {
        $user->load('assignedChannels');
        $channels = LiveChatChannel::active()->ordered()->get();
        return view('admin.users.edit', compact('user', 'channels'));
    }

    public function usersUpdate(Request $request, User $user)
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'is_admin' => 'boolean',
            'is_live_chat_admin' => 'boolean',
            'is_live_chat_cs' => 'boolean',
            'password' => 'nullable|min:6',
            'channel_id' => 'nullable|integer|exists:live_chat_channels,id',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()->messages()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => $request->boolean('is_admin', false),
            'is_live_chat_admin' => $request->boolean('is_live_chat_admin', false),
            'is_live_chat_cs' => $request->boolean('is_live_chat_cs', false),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        DB::beginTransaction();

        try {
            $user->update($data);

            LiveChatOperator::where('user_id', $user->id)->delete();

            if ($user->is_live_chat_admin && $request->filled('channel_id')) {
                $operator = LiveChatOperator::create([
                    'user_id' => $user->id,
                    'channel_id' => $request->channel_id,
                    'is_active' => true,
                ]);

                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                foreach ($days as $day) {
                    LiveChatOperatorSchedule::create([
                        'operator_id' => $operator->id,
                        'day_of_week' => $day,
                        'start_time' => '07:00',
                        'end_time' => '23:00',
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Pengguna berhasil diperbarui']);
            }

            return redirect()->route('admin.users')->with('success', 'Pengguna berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => ['error' => [$e->getMessage()]]], 422);
            }
            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function usersStore(Request $request)
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|min:6',
            'is_admin' => 'boolean',
            'is_live_chat_admin' => 'boolean',
            'is_live_chat_cs' => 'boolean',
            'channel_id' => 'nullable|integer|exists:live_chat_channels,id',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()->messages()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => $request->password,
                'is_admin' => $request->boolean('is_admin', false),
                'is_live_chat_admin' => $request->boolean('is_live_chat_admin', false),
                'is_live_chat_cs' => $request->boolean('is_live_chat_cs', false),
            ]);

            if ($user->is_live_chat_admin && $request->filled('channel_id')) {
                $operator = LiveChatOperator::create([
                    'user_id' => $user->id,
                    'channel_id' => $request->channel_id,
                    'is_active' => true,
                ]);

                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                foreach ($days as $day) {
                    LiveChatOperatorSchedule::create([
                        'operator_id' => $operator->id,
                        'day_of_week' => $day,
                        'start_time' => '07:00',
                        'end_time' => '23:00',
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Pengguna berhasil dibuat']);
            }

            return redirect()->route('admin.users')->with('success', 'Pengguna berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => ['error' => [$e->getMessage()]]], 422);
            }
            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    // ---- CONTACT INQUIRIES ----
    public function contactInquiries()
    {
        $inquiries = ContactInquiry::latest()->paginate(20);
        return view('admin.contact-inquiries.index', compact('inquiries'));
    }

    public function contactInquiriesShow(ContactInquiry $contactInquiry)
    {
        return view('admin.contact-inquiries.show', ['inquiry' => $contactInquiry]);
    }

    public function contactInquiriesMarkRead(ContactInquiry $contactInquiry)
    {
        $contactInquiry->update(['is_read' => true]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pesan ditandai sudah dibaca.']);
        }

        return back()->with('success', 'Pesan ditandai sudah dibaca.');
    }

    public function contactInquiriesDestroy(ContactInquiry $contactInquiry)
    {
        $contactInquiry->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pesan berhasil dihapus.']);
        }

        return redirect()->route('admin.contact-inquiries')->with('success', 'Pesan berhasil dihapus.');
    }

    public function contactInquiriesReply(Request $request, ContactInquiry $contactInquiry)
    {
        $validator = validator($request->all(), [
            'reply' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()->messages()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Mail::to($contactInquiry->email)->send(new ContactReplyMail($contactInquiry, $validator->validated()['reply']));

        $contactInquiry->update([
            'is_read' => true,
            'admin_reply' => $validator->validated()['reply'],
            'responded_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Balasan berhasil dikirim ke ' . $contactInquiry->email]);
        }

        return back()->with('success', 'Balasan berhasil dikirim ke ' . $contactInquiry->email);
    }

    public function settings()
    {
        $settings = SiteSetting::allKeyValue();

        return view('admin.settings', compact('settings'));
    }

    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'site_description' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_whatsapp' => 'nullable|string|max:50',
            'contact_instagram' => 'nullable|string|max:255',
            'contact_phone_display' => 'nullable|string|max:50',
            'contact_cs_email' => 'nullable|email|max:255',
            'contact_cs_hours' => 'nullable|string|max:120',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_npwp' => 'nullable|string|max:64',
            'footer_text' => 'nullable|string|max:500',
            'digiflazz_margin_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $textKeys = ['site_name', 'site_tagline', 'site_description', 'contact_email', 'contact_whatsapp', 'contact_instagram', 'contact_phone_display', 'contact_cs_email', 'contact_cs_hours', 'company_name', 'company_address', 'company_npwp', 'footer_text', 'min_balance_alert'];

        foreach ($textKeys as $key) {
            if ($request->has($key)) {
                \App\Models\SiteSetting::set($key, $request->input($key, ''));
            }
        }

        // Kredensial Digiflazz (username/key/production) sengaja TIDAK bisa
        // disimpan dari sini: sumbernya .env. Row SiteSetting untuk key itu
        // akan menimpa config() dan memblokir .env, jadi jangan pernah
        // ditulis ulang dari panel admin.
        if ($request->has('digiflazz_margin_percent')) {
            SiteSetting::set('digiflazz_margin_percent', (string) $request->input('digiflazz_margin_percent'));
        }

        if ($request->hasFile('site_logo') && $request->file('site_logo')->isValid()) {
            $oldLogo = \App\Models\SiteSetting::get('site_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                MediaStore::delete($oldLogo);
            }
            $path = ImageOptimizer::storeOptimized($request->file('site_logo'), 'settings', 512, 512);
            \App\Models\SiteSetting::set('site_logo', $path, 'image');
        }

        $bannerKeys = ['site_hero_banner', 'site_hero_banner_2', 'site_hero_banner_3'];
        foreach ($bannerKeys as $key) {
            if ($request->hasFile($key) && $request->file($key)->isValid()) {
                $oldBanner = \App\Models\SiteSetting::get($key);
                if ($oldBanner && Storage::disk('public')->exists($oldBanner)) {
                    MediaStore::delete($oldBanner);
                }
                $path = ImageOptimizer::storeOptimized($request->file($key), 'settings', 1920, 750);
                \App\Models\SiteSetting::set($key, $path, 'image');
            }
        }

$jbaBannerKeys = ['jba_hero_banner', 'jba_hero_banner_2', 'jba_hero_banner_3'];
    foreach ($jbaBannerKeys as $key) {
        if ($request->hasFile($key) && $request->file($key)->isValid()) {
            $oldBanner = \App\Models\SiteSetting::get($key);
            if ($oldBanner && Storage::disk('public')->exists($oldBanner)) {
                MediaStore::delete($oldBanner);
            }
            $path = ImageOptimizer::storeOptimized($request->file($key), 'settings', 1920, 750);
            \App\Models\SiteSetting::set($key, $path, 'image');
        }
    }

$jbaBudgetKeys = [
        'jba_budget_pelajar_banner',
        'jba_budget_umr_banner',
        'jba_budget_sultan_banner',
        'jba_budget_freedom_banner',
    ];
    foreach ($jbaBudgetKeys as $key) {
        if ($request->hasFile($key) && $request->file($key)->isValid()) {
            $oldBanner = \App\Models\SiteSetting::get($key);
            if ($oldBanner && Storage::disk('public')->exists($oldBanner)) {
                MediaStore::delete($oldBanner);
            }
            $path = ImageOptimizer::storeOptimized($request->file($key), 'settings', 960, 480);
            \App\Models\SiteSetting::set($key, $path, 'image');
        }
    }

$jbaGameBannerKeys = [
        'jba_game_banner_mlbb',
        'jba_game_banner_pubg',
        'jba_game_banner_efootball',
        'jba_game_banner_fcm',
        'jba_game_banner_ff',
        'jba_game_banner_roblox',
        'jba_game_banner_valorant',
    ];
    foreach ($jbaGameBannerKeys as $key) {
        if ($request->hasFile($key) && $request->file($key)->isValid()) {
            $oldBanner = \App\Models\SiteSetting::get($key);
            if ($oldBanner && Storage::disk('public')->exists($oldBanner)) {
                MediaStore::delete($oldBanner);
            }
            $path = ImageOptimizer::storeOptimized($request->file($key), 'settings', 1920, 1080);
            \App\Models\SiteSetting::set($key, $path, 'image');
        }
    }

    // QRIS statis untuk pembayaran Jual Beli Akun.
    if ($request->hasFile('qris_image') && $request->file('qris_image')->isValid()) {
        $oldQris = \App\Models\SiteSetting::get('qris_image');
        if ($oldQris && Storage::disk('public')->exists($oldQris)) {
            MediaStore::delete($oldQris);
        }
        $path = ImageOptimizer::storeOptimized($request->file('qris_image'), 'settings', 800, 800);
        \App\Models\SiteSetting::set('qris_image', $path, 'image');
    }

        return redirect()->route('admin.settings')->with('success', 'Pengaturan berhasil disimpan');
    }

    public function digiflazzTest()
    {
        $result = $this->digiflazz->testConnection();
        return response()->json($result);
    }

    private function handleFeaturedImages(Request $request, ?Brand $brand = null): array
    {
        $data = [];
        foreach (['featured_img_1', 'featured_img_2', 'featured_img_3'] as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                if ($brand && $brand->$field) {
                    MediaStore::delete($brand->$field);
                }
                $data[$field] = ImageOptimizer::storeOptimized($request->file($field), 'brands', 1280, 1280);
            }
        }
        return $data;
    }
}
