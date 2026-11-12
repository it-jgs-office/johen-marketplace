<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\BalanceService;
use App\Services\DigiflazzService;
use App\Services\PaymentGatewayService;
use App\Services\TopupSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected DigiflazzService $digiflazz;

    protected PaymentGatewayService $gateway;

    protected TopupSettlementService $settlement;

    protected BalanceService $balance;

    public function __construct(
        DigiflazzService $digiflazz,
        PaymentGatewayService $gateway,
        TopupSettlementService $settlement,
        BalanceService $balance,
    ) {
        $this->digiflazz = $digiflazz;
        $this->gateway = $gateway;
        $this->settlement = $settlement;
        $this->balance = $balance;
    }

    public function detail(Order $order)
    {
        if ($order->status === 'pending') {
            $this->syncFromGateway($order);
            $order->refresh();
            app(\App\Services\PaymentExpirationService::class)->expireIfDue($order);
            $order->refresh();
        }

        $invoiceUrl = $order->gateway_invoice_url;
        $isSimulation = (bool) config('services.payment.simulation');
        $isDemo = $isSimulation || ! $this->gateway->isConfigured() || empty($order->gateway_invoice_id);
        $checkoutUrl = $order->checkout_url;
        $gatewayName = $this->gateway->providerLabel();
        $brand = Brand::where('name', $order->brand)->first();
        $hasReviewed = Review::where('order_id', $order->order_id)
            ->where('service_type', 'topup')
            ->exists();
        $reviewUser = auth('web')->user();
        $canReview = $reviewUser && ($order->user_id
            ? (int) $order->user_id === (int) $reviewUser->id
            : ($order->email && mb_strtolower(trim($order->email)) === mb_strtolower(trim($reviewUser->email))));
        $reorderProduct = Product::where('buyer_sku_code', $order->buyer_sku_code)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->first();
        $canCancel = $order->status === 'pending' && $this->canCancelOrder(request(), $order);

        return view('payment.detail', compact(
            'order', 'invoiceUrl', 'isDemo', 'isSimulation', 'brand', 'hasReviewed', 'canReview', 'reorderProduct',
            'checkoutUrl', 'gatewayName', 'canCancel'
        ));
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($this->canCancelOrder($request, $order), 403);

        $this->syncFromGateway($order);
        $order->refresh();

        if ($order->status !== 'pending') {
            return back()->with('error', 'Pesanan tidak dapat dibatalkan karena status pembayaran sudah berubah.');
        }

        $cancelled = Order::query()
            ->whereKey($order->getKey())
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'note' => 'Pesanan dibatalkan oleh pelanggan.',
                'updated_at' => now(),
            ]);

        if (! $cancelled) {
            return back()->with('error', 'Pesanan tidak dapat dibatalkan karena statusnya sudah berubah.');
        }

        $order->refresh();
        $order->transaction?->update(['status' => 'cancelled']);
        $order->releaseDiscounts();
        $this->balance->restorePoints($order, 'Pesanan dibatalkan; Johen Point dikembalikan');

        $request->session()->put('cancelable_order_ids', array_values(array_filter(
            (array) $request->session()->get('cancelable_order_ids', []),
            fn ($id) => (int) $id !== (int) $order->getKey()
        )));

        return redirect()->route('payment.detail', $order)
            ->with('success', 'Pesanan berhasil dibatalkan.');
    }

    private function canCancelOrder(Request $request, Order $order): bool
    {
        $userId = auth('web')->id();

        if ($userId !== null) {
            return (int) $order->user_id === (int) $userId;
        }

        return in_array(
            (int) $order->getKey(),
            array_map('intval', (array) $request->session()->get('cancelable_order_ids', [])),
            true
        );
    }

    /**
     * Simulasi pembayaran (PAYMENT_SIMULATION=true):
     * menandai order lunas lalu memicu topUp Digiflazz seperti callback pembayaran.
     */
    public function simulatePay(Request $request, Order $order)
    {
        abort_unless(config('services.payment.simulation'), 404);

        if ($order->status !== 'pending') {
            return response()->json(['status' => $order->status, 'note' => $order->note]);
        }

        $order->transaction?->update([
            'payment_type' => 'Simulasi (QRIS)',
            'status' => 'settled',
            'raw_response' => [
                'simulation' => true,
                'paid_at' => now()->toDateTimeString(),
            ],
        ]);

        Log::info('Pembayaran simulasi diproses', ['order_id' => $order->order_id]);

        $this->handlePaid($order);
        $order->refresh();

        return response()->json([
            'status' => $order->status,
            'note' => $order->note,
        ]);
    }

    public function success(Order $order)
    {
        return view('payment.success', compact('order'));
    }

    public function status(Order $order)
    {
        $this->syncFromGateway($order);
        $order->refresh();
        app(\App\Services\PaymentExpirationService::class)->expireIfDue($order);
        $order->refresh();

        if ($order->status === 'processing') {
            $this->syncFromDigiflazz($order);
            $order->refresh();
        }

        return response()->json([
            'status' => $order->status,
            'note' => $order->note,
            'saldo_status' => $order->saldo_status,
            'saldo_amount' => (float) $order->saldo_amount,
            'saldo_refunded' => $order->isRefunded(),
            'payment_status' => data_get($order->gateway_extra, 'payment_status', 'PENDING'),
        ])->header('Cache-Control', 'no-store, private');
    }

    private function syncFromGateway(Order $order): void
    {
        app(\App\Services\AyolinxPaymentService::class)->sync($order);
    }

    private function handlePaid(Order $order): void
    {
        $this->settlement->markPaid($order);
    }

    /**
     * Terapkan hasil respons Digiflazz ke order.
     *
     * Logikanya hidup di TopupSettlementService supaya callback Ayolinx, webhook
     * Digiflazz, polling browser, dan command rekonsiliasi memakai satu aturan
     * yang sama. Method ini tetap ada karena dipanggil dari callback Digiflazz.
     */
    public function applyDigiflazzResult(Order $order, array $result): void
    {
        $this->settlement->applyResult($order, $result);
    }

    /**
     * Webhook dari Digiflazz: push status final transaksi (Sukses/Gagal/Pending).
     * Diverifikasi via header X-Hub-Signature (HMAC-SHA256 body dengan API key).
     */
    public function digiflazzCallback(Request $request)
    {
        $signature = (string) $request->header('X-Hub-Signature');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $this->digiflazz->getKey());

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            Log::warning('Digiflazz callback: signature tidak valid');

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        try {
            $data = $request->json('data');

            if (empty($data) || empty($data['ref_id'])) {
                return response()->json(['status' => 'ok']);
            }

            $order = Order::where('order_id', $data['ref_id'])->first();

            if (! $order) {
                Log::warning('Digiflazz callback: order tidak ditemukan', ['ref_id' => $data['ref_id']]);

                return response()->json(['status' => 'ok']);
            }

            Log::info('Digiflazz callback received', ['order_id' => $order->order_id, 'data' => $data]);

            $this->applyDigiflazzResult($order, ['data' => $data]);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Digiflazz callback error: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function syncFromDigiflazz(Order $order): void
    {
        $this->settlement->poll($order);
    }
}
