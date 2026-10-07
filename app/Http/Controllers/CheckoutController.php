<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\CartService;
use App\Services\CheckoutSettlementService;
use App\Services\PaymentGatewayService;
use App\Services\TopupOrderBuilder;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Minimal nominal per kategori metode, mengikuti batas minimum channel Xendit.
     */
    protected const MIN_AMOUNT = [
        'qris' => 1000,
        'ewallet' => 1000,
        'va' => 10000,
        'convenience_store' => 10000,
    ];

    protected CartService $cart;
    protected TopupOrderBuilder $builder;
    protected PaymentGatewayService $gateway;
    protected CheckoutSettlementService $checkoutSettlement;
    protected XenditService $xendit;

    public function __construct(
        CartService $cart,
        TopupOrderBuilder $builder,
        PaymentGatewayService $gateway,
        CheckoutSettlementService $checkoutSettlement,
        XenditService $xendit
    ) {
        $this->cart = $cart;
        $this->builder = $builder;
        $this->gateway = $gateway;
        $this->checkoutSettlement = $checkoutSettlement;
        $this->xendit = $xendit;
    }

    /**
     * Halaman form checkout. Satu grup input per brand karena kebutuhan Zone ID
     * juga mengikuti brand.
     */
    public function create(Request $request)
    {
        $summary = $this->cart->summary(Auth::id());

        if ($summary['items']->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kamu masih kosong.');
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $paymentMethods = $this->gateway->filterAvailableMethods($paymentMethods);

        return view('checkout.index', [
            'items' => $summary['items'],
            'subtotal' => $summary['subtotal'],
            'totalQty' => $summary['total_qty'],
            'brandGroups' => $this->cart->brandGroups(Auth::id()),
            'paymentMethods' => $paymentMethods,
            'minAmounts' => self::MIN_AMOUNT,
            'accounts' => (array) old('accounts', []),
        ]);
    }

    /**
     * Buat satu charge untuk seluruh keranjang, lalu satu order anak per item.
     *
     * Semua baris order + charge dibuat dalam satu transaksi: kalau charge ke
     * Xendit gagal, kuota flash deal yang sempat terpakai ikut kembali.
     */
    public function store(Request $request, \App\Services\GameAccountService $gameAccount)
    {
        $user = Auth::user();

        $request->validate([
            'accounts' => ['required', 'array'],
            'accounts.*.customer_number' => ['required', 'string', 'max:100'],
            'accounts.*.zone_id' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'promo_code' => ['nullable', 'string', 'max:32'],
        ], [
            'accounts.*.customer_number.required' => 'User ID wajib diisi untuk semua game di keranjang.',
            'accounts.*.zone_id.regex' => 'Zone ID hanya boleh berisi huruf dan angka.',
        ]);

        $summary = $this->cart->summary($user->id);

        if ($summary['items']->isEmpty()) {
            return back()->with('error', 'Keranjang kamu masih kosong.');
        }

        $brandGroups = $this->cart->brandGroups($user->id);
        $accounts = (array) $request->input('accounts', []);

        // Game yang butuh Zone ID harus dikunci di server, tidak cukup di UI.
        // User ID juga dicek per brand: validasi wildcard hanya memeriksa input
        // yang benar-benar terkirim, jadi brand tanpa field bisa lolos ke store.
        foreach ($brandGroups as $group) {
            $input = $accounts[$group->key] ?? [];

            if (trim((string) ($input['customer_number'] ?? '')) === '') {
                return back()
                    ->withErrors(['accounts.'.$group->key.'.customer_number' => 'User ID wajib diisi untuk '.$group->name.'.'])
                    ->withInput();
            }

            if (! $group->requires_zone_id) {
                continue;
            }

            if (trim((string) ($input['zone_id'] ?? '')) === '') {
                return back()
                    ->withErrors(['accounts.'.$group->key.'.zone_id' => 'Zone ID wajib diisi untuk '.$group->name.'.'])
                    ->withInput();
            }
        }

        foreach ($summary['items'] as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $key = strtolower((string) $product->brand);
            $input = $accounts[$key] ?? [];
            $regionError = $gameAccount->idnAvailabilityMessage(
                $product,
                trim((string) ($input['customer_number'] ?? '')),
                $this->cleanZone($input['zone_id'] ?? null)
            );
            if ($regionError !== null) {
                return back()->withErrors(['accounts.'.$key.'.zone_id' => $regionError])->withInput();
            }
        }

        $email = trim((string) ($request->email ?: $user->email));
        $phone = trim((string) $request->phone);
        $method = (string) ($request->payment_method ?: config('services.payment.channel', 'qris'));

        try {
            $checkout = DB::transaction(function () use ($user, $summary, $brandGroups, $accounts, $email, $phone, $method, $request) {
                $lockedRows = [];
                $lineTotals = [];

                // Tahap 1: kunci harga & kuota flash deal untuk semua item dulu,
                // supaya voucher bisa diterapkan ke total gabungan.
                foreach ($summary['items'] as $item) {
                    if (! $item->product || ! $item->product->is_active || $item->product->stock < 1) {
                        throw new \RuntimeException($item->product->product_name.' stoknya habis. Hapus dulu dari keranjang.');
                    }

                    $locked = $this->builder->lockItem($item->product, (int) $item->quantity);
                    $lockedRows[] = $locked;
                    $lineTotals[] = $locked['line_total'];
                }

                $subtotal = (int) array_sum($lineTotals);
                $discount = 0;
                $voucher = null;

                if (! empty($request->promo_code)) {
                    ['discount' => $discount, 'voucher' => $voucher] = $this->builder->voucherDiscount(
                        (string) $request->promo_code,
                        $subtotal
                    );
                }

                $total = max(0, $subtotal - $discount);

                // Peringatan dini untuk nominal yang di bawah minimum channel.
                $min = $this->minAmountFor($method);

                if ($total < $min) {
                    throw new \RuntimeException(
                        'Total Rp'.number_format($total, 0, ',', '.').' di bawah minimal '
                        .'Rp'.number_format($min, 0, ',', '.').' untuk metode ini. Pilih QRIS atau e-wallet.'
                    );
                }

                $checkout = Checkout::create([
                    'checkout_ref' => 'CART-'.strtoupper(Str::random(10)),
                    'user_id' => $user->id,
                    'email' => $email,
                    'phone' => $phone,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'voucher_id' => $voucher?->id,
                    'item_count' => (int) $summary['total_qty'],
                    'status' => 'pending',
                ]);

                // Tahap 2: satu order anak per item.
                $orders = [];
                $index = 0;

                foreach ($summary['items'] as $item) {
                    $brandKey = strtolower((string) $item->product->brand);

                    $orders[$index] = $this->builder->createOrder([
                        'product' => $item->product,
                        'user' => $user,
                        'quantity' => (int) $item->quantity,
                        'customer_number' => (string) ($accounts[$brandKey]['customer_number'] ?? ''),
                        'zone_id' => $this->cleanZone($accounts[$brandKey]['zone_id'] ?? null),
                        'customer_name' => trim((string) ($accounts[$brandKey]['customer_name'] ?? '')) ?: null,
                        'phone' => $phone ?: null,
                        'email' => $email,
                        'price' => $lineTotals[$index],
                        'voucher_id' => $voucher?->id,
                        'checkout' => $checkout,
                    ], $lockedRows[$index]);

                    $index++;
                }

                if ($discount > 0) {
                    $this->builder->allocateDiscount($orders, $lineTotals, $discount);
                }

                $this->builder->consumeVoucher($voucher, $orders);

                // Satu charge untuk total gabungan, bukan satu per item.
                if (! config('services.payment.simulation') && $this->xendit->isConfigured()) {
                    $charged = $this->gateway->chargeCheckout($checkout, $method, [
                        'customer_name' => $user->name,
                    ]);

                    if (! $charged) {
                        throw new \RuntimeException(
                            'Metode pembayaran gagal dibuat untuk nominal ini. Silakan pilih metode lain.'
                            .' (checkout: '.$checkout->checkout_ref.')'
                        );
                    }
                }

                return $checkout;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $this->cart->clear($user->id);

        return redirect()->route('checkout.payment', $checkout);
    }

    public function payment(Request $request, Checkout $checkout)
    {
        $this->ensureOwned($checkout);

        $isSimulation = (bool) config('services.payment.simulation');
        $isDemo = $isSimulation || ! $this->xendit->isConfigured() || empty($checkout->gateway_invoice_id);

        return view('checkout.payment', [
            'checkout' => $checkout,
            'orders' => $checkout->orders()->orderBy('id')->get(),
            'isDemo' => $isDemo,
            'isSimulation' => $isSimulation,
        ]);
    }

    /**
     * Endpoint poling untuk halaman pembayaran. Mengembalikan status per item
     * supaya user tahu item mana yang sudah selesai.
     */
    public function status(Request $request, Checkout $checkout)
    {
        $this->ensureOwned($checkout);

        // Sama seperti order biasa: kalau webhook belum sampai, tanya gateway
        // secara langsung supaya halaman tidak bergantung pada webhook saja.
        if ($checkout->status === 'pending') {
            $this->syncFromGateway($checkout);
            $checkout->refresh();
        }

        $checkout->syncStatus();
        $checkout->refresh();

        $orders = $checkout->orders()->orderBy('id')->get();

        return response()->json([
            'status' => $checkout->status,
            'total' => (int) $checkout->total,
            'success' => $orders->where('status', 'success')->count(),
            'failed' => $orders->where('status', 'failed')->count(),
            'count' => $orders->count(),
            'items' => $orders->map(fn (Order $o) => [
                'order_id' => $o->order_id,
                'product_name' => $o->product_name,
                'quantity' => (int) $o->quantity,
                'price' => (int) $o->price,
                'status' => $o->status,
                'note' => $o->note,
            ])->values(),
        ]);
    }

    /**
     * Simulasi pembayaran, hanya aktif bila services.payment.simulation true.
     * Dipakai untuk menguji alur fan-out tanpa memanggil API sungguhan.
     */
    public function simulate(Request $request, Checkout $checkout)
    {
        abort_unless((bool) config('services.payment.simulation'), 404);

        $this->ensureOwned($checkout);

        $this->checkoutSettlement->markPaid($checkout);

        return back()->with('success', 'Pembayaran simulasi diterima.');
    }

    /**
     * Baca status charge langsung dari Xendit (cadangan kalau webhook telat).
     */
    protected function syncFromGateway(Checkout $checkout): void
    {
        $id = (string) $checkout->gateway_invoice_id;

        if ($id === '') {
            return;
        }

        $data = match ($checkout->gateway_type) {
            'qris' => $this->xendit->getQr($id),
            'va' => $this->xendit->getVirtualAccount($id),
            'retail' => $this->xendit->getRetailOutlet($id),
            'ewallet' => $this->xendit->getEwalletCharge($id),
            default => $this->xendit->getInvoice($id),
        };

        if (! $data) {
            return;
        }

        $status = strtoupper((string) ($data['status'] ?? ''));

        // QR dinamis & VA/retail jadi INACTIVE setelah dibayar.
        $paid = in_array($status, ['PAID', 'SETTLED', 'SUCCEEDED', 'COMPLETED', 'CAPTURED', 'INACTIVE'], true)
            && ! in_array($status, ['EXPIRED', 'FAILED', 'CANCELLED'], true);

        if ($paid) {
            $this->checkoutSettlement->markPaid($checkout);
        } elseif (in_array($status, ['EXPIRED', 'FAILED', 'CANCELLED'], true)) {
            $this->checkoutSettlement->markFailed($checkout, 'Pembayaran '.$status.' di gateway');
        }
    }

    /**
     * Minimal nominal untuk sebuah metode, mengikuti batas channel Xendit.
     */
    public function minAmountFor(string $method): int
    {
        return match ($this->gateway->resolve($method)['gateway_type']) {
            'va' => self::MIN_AMOUNT['va'],
            'retail' => self::MIN_AMOUNT['convenience_store'],
            'ewallet' => self::MIN_AMOUNT['ewallet'],
            default => self::MIN_AMOUNT['qris'],
        };
    }

    protected function cleanZone($zoneId): ?string
    {
        $zoneId = trim((string) $zoneId);

        return $zoneId === '' ? null : $zoneId;
    }

    /**
     * Checkout hanya boleh dilihat pemiliknya.
     */
    protected function ensureOwned(Checkout $checkout): void
    {
        $userId = Auth::id();

        abort_if(
            $userId === null || (int) $checkout->user_id !== (int) $userId,
            403
        );
    }
}
