<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Services\BalanceService;
use App\Services\DigiflazzService;
use App\Services\GameAccountService;
use App\Services\PaymentGatewayService;
use App\Services\TopupOrderBuilder;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected DigiflazzService $digiflazz;
    protected XenditService $xendit;
    protected PaymentGatewayService $gateway;
    protected BalanceService $balance;
    protected TopupOrderBuilder $builder;

    public function __construct(
        DigiflazzService $digiflazz,
        XenditService $xendit,
        PaymentGatewayService $gateway,
        BalanceService $balance,
        TopupOrderBuilder $builder
    ) {
        $this->digiflazz = $digiflazz;
        $this->xendit = $xendit;
        $this->gateway = $gateway;
        $this->balance = $balance;
        $this->builder = $builder;
    }

    public function create(Product $product)
    {
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->get();

        $paymentMethods = $this->gateway->filterAvailableMethods($paymentMethods);
        $requiresZoneId = str_starts_with(mb_strtolower($product->brand), 'mobile legends')
            || (bool) Brand::where('name', $product->brand)->value('requires_zone_id');

        return view('orders.create', compact('product', 'paymentMethods', 'requiresZoneId'));
    }

    public function store(Request $request, GameAccountService $gameAccount)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_number' => 'required|string|max:100',
            'zone_id' => 'nullable|string|max:20|regex:/^[A-Za-z0-9]+$/',
            'customer_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'quantity' => 'nullable|integer|min:1|max:99',
            'promo_code' => 'nullable|string|max:32',
            'payment_method' => 'nullable|string|max:50',
        ], [
            'zone_id.regex' => 'Zone ID hanya boleh berisi huruf dan angka.',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < 1) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Maaf, stok produk ini sedang kosong'], 422);
            }
            return back()->with('error', 'Maaf, stok produk ini sedang kosong');
        }

        // Game yang membutuhkan Zone ID wajib mengisinya (divalidasi di server).
        $brandRequiresZone = (str_starts_with(mb_strtolower($product->brand), 'mobile legends')
                && str_ends_with(mb_strtolower($product->buyer_sku_code), '-idn'))
            || (bool) Brand::where('name', $product->brand)->value('requires_zone_id');
        $zoneId = $request->filled('zone_id') ? trim($request->zone_id) : null;

        if ($brandRequiresZone && empty($zoneId)) {
            $message = 'Zone ID wajib diisi untuk game ini.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->withErrors(['zone_id' => $message])->withInput();
        }

        $regionError = $gameAccount->idnAvailabilityMessage($product, trim((string) $request->customer_number), $zoneId);
        if ($regionError !== null) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $regionError], 422);
            }

            return back()->withErrors(['zone_id' => $regionError])->withInput();
        }

        $quantity = (int) ($request->quantity ?? 1);

        try {
            $order = $this->createTopupOrder($product, [
                'customer_number' => trim($request->customer_number),
                'zone_id' => $zoneId,
                'customer_name' => $request->customer_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'quantity' => $quantity,
                'promo_code' => $request->promo_code,
                'payment_method' => $request->payment_method,
            ]);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }

        $demo = $order->gateway_invoice_id ? false : true;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('payment.detail', $order),
                'demo' => $demo,
            ]);
        }

        return redirect()->route('orders.show', $order);
    }

    /**
     * "Beli Lagi": buat ulang order dari order sebelumnya langsung ke halaman pembayaran.
     */
    public function reorder(Request $request, Order $source)
    {
        $product = Product::where('buyer_sku_code', $source->buyer_sku_code)
            ->where('is_active', true)
            ->first();

        if (!$product || $product->stock < 1) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Stok produk sedang kosong.'], 422);
            }
            return back()->with('error', 'Maaf, stok produk ini sedang kosong');
        }

        try {
            $order = $this->createTopupOrder($product, [
                'customer_number' => $source->customer_number,
                'zone_id' => $source->effective_zone_id,
                'customer_name' => $source->customer_name,
                'email' => $source->email,
                'quantity' => (int) ($source->quantity ?: 1),
            ]);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'redirect' => route('payment.detail', $order), 'demo' => true]);
        }

        return redirect()->route('payment.detail', $order);
    }

    /**
     * Membuat order top up baru lengkap dengan charge gateway + transaksi.
     */
    private function createTopupOrder(Product $product, array $input): Order
    {
        $quantity = (int) ($input['quantity'] ?? 1);
        $customerNumber = $input['customer_number'];
        $zoneId = $input['zone_id'] ?? null;
        $customerName = $input['customer_name'] ?? null;
        $customerPhone = $input['phone'] ?? ($input['customer_phone'] ?? null);
        $email = $input['email'] ?? null;

        return DB::transaction(function () use ($product, $input, $quantity, $customerNumber, $zoneId, $customerName, $customerPhone, $email) {
            // Resolve flash deal aktif secara atomik. Jika kuota tersisa,
            // order memakai harga flash dan kuota terkunci untuk pesanan ini.
            $locked = $this->builder->lockItem($product, $quantity);

            $subtotal = $locked['line_total'];

            $voucherId = null;
            $voucher = null;

            if (! empty($input['promo_code'])) {
                ['discount' => $discount, 'voucher' => $voucher] = $this->builder->voucherDiscount(
                    (string) $input['promo_code'],
                    $subtotal
                );

                $subtotal -= $discount;
                $voucherId = $voucher?->id;
            }

            $order = $this->builder->createOrder([
                'product' => $product,
                'user' => Auth::user(),
                'quantity' => $quantity,
                'customer_number' => $customerNumber,
                'zone_id' => $zoneId,
                'customer_name' => $customerName,
                'phone' => $customerPhone,
                'email' => $email,
                'price' => $subtotal,
                'voucher_id' => $voucherId,
            ], $locked);

            if ($voucher) {
                $voucher->markUsed($order);
            }

            if (!config('services.payment.simulation') && $this->xendit->isConfigured()) {
                $method = !empty($input['payment_method']) ? $input['payment_method'] : config('services.payment.channel', 'qris');

                $charged = $this->gateway->charge($order, $method, [
                    'item_name' => $product->product_name,
                    'unit_price' => $locked['unit_price'],
                ]);

                if (!$charged) {
                    $label = \App\Models\PaymentMethod::where('code', $method)->value('name') ?: $method;
                    $minAmount = in_array($order->gateway_type, ['va', 'retail'], true) ? 'Rp 10.000' : 'Rp 1.000';
                    $message = "Metode $label gagal dibuat untuk nominal ini (minimal $minAmount)."
                        ." Silakan pilih QRIS atau metode lain. (order: {$order->order_id})";
                    throw new \RuntimeException($message);
                }
            }

            return $order;
        });
    }

    public function show(Order $order)
    {
        $brand = Brand::where('name', $order->brand)->first();
        $product = Product::where('buyer_sku_code', $order->buyer_sku_code)->first();

        $recommendedProducts = Product::where('brand', $order->brand)
            ->where('is_active', true)
            ->where('type', 'instant')
            ->orderBy('selling_price')
            ->limit(3)
            ->get();

        return view('orders.show', compact('order', 'brand', 'product', 'recommendedProducts'));
    }

    public function myOrders()
    {
        $user = Auth::user();

        $orders = Order::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest()
            ->paginate(10);

        $orders->load('transaction');

        $brandNames = $orders->pluck('brand')->unique()->filter();
        $brands = Brand::whereIn('name', $brandNames)
            ->orWhereIn(DB::raw('UPPER(name)'), $brandNames->map(fn($n) => strtoupper((string) $n)))
            ->get()
            ->keyBy(fn($b) => strtolower($b->name));

        $skuCodes = $orders->pluck('buyer_sku_code')->unique()->filter();
        $products = Product::whereIn('buyer_sku_code', $skuCodes)->get()->keyBy('buyer_sku_code');

        // Saldo + mutasi untuk panel "Saldo Saya" di menu customer.
        $balance = $this->balance->balanceFor($user->id);
        $balanceTransactions = $this->balance->recentFor($user->id, 10);

        return view('orders.index', compact('orders', 'brands', 'products', 'balance', 'balanceTransactions'));
    }
}
