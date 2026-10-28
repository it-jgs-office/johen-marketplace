<?php

namespace App\Services;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentGatewayService
{
    protected ?string $lastChargeError = null;

    public function __construct(protected MidtransService $midtrans) {}

    public function provider(): string
    {
        return 'midtrans';
    }

    public function isDoku(): bool
    {
        return false;
    }

    public function isConfigured(): bool
    {
        return $this->midtrans->isConfigured();
    }

    public function providerLabel(): string
    {
        return 'Midtrans Core API Sandbox';
    }

    public function lastChargeError(): ?string
    {
        return $this->lastChargeError;
    }

    public function resolve(string $method): array
    {
        $code = $this->normalizeMethodCode($method);

        return match ($code) {
            'qris' => ['gateway_type' => 'qris', 'midtrans_code' => 'qris', 'payment_type' => 'qris'],
            'gopay' => ['gateway_type' => 'qris', 'midtrans_code' => 'gopay', 'payment_type' => 'gopay'],
            'bca_va', 'bni_va', 'bri_va', 'cimb_va', 'permata_va' => [
                'gateway_type' => 'va',
                'midtrans_code' => $code,
                'payment_type' => 'bank_transfer',
                'bank' => str_replace('_va', '', $code),
            ],
            'mandiri_va' => ['gateway_type' => 'va', 'midtrans_code' => 'echannel', 'payment_type' => 'echannel'],
            'alfamart', 'indomaret' => ['gateway_type' => 'retail', 'midtrans_code' => $code, 'payment_type' => 'cstore'],
            default => ['gateway_type' => 'core', 'midtrans_code' => null],
        };
    }

    public function normalizeMethodCode(string $method): string
    {
        $method = strtolower(trim($method));

        return match ($method) {
            'bca', 'bni', 'bri', 'bsi', 'cimb', 'danamon', 'mandiri', 'permata' => $method.'_va',
            default => $method,
        };
    }

    public function assertAvailableMethod(string $method): void
    {
        $code = $this->normalizeMethodCode($method);

        if ($this->resolve($code)['midtrans_code'] === null
            || ! PaymentMethod::where('code', $code)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran belum tersedia di Midtrans. Pilih metode lain.',
            ]);
        }
    }

    public function filterAvailableMethods($methods)
    {
        return collect($methods)->filter(function ($method) {
            $code = $this->normalizeMethodCode((string) (is_object($method) ? $method->code : $method['code']));

            return (bool) (is_object($method) ? $method->is_active : $method['is_active'])
                && $this->resolve($code)['midtrans_code'] !== null;
        })->map(function ($method) {
            $resolved = $this->resolve((string) $method->code);
            $method->category = match ($resolved['gateway_type']) {
                'retail' => 'convenience_store',
                default => $resolved['gateway_type'],
            };
            $method->setAttribute('gateway_provider', 'midtrans');
            $method->setAttribute('gateway_code', $resolved['midtrans_code']);

            return $method;
        })->values();
    }

    public function charge(Order $order, string $method, array $gatewayMeta): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);

        return $this->chargeCore($order, $method, [
            'amount' => (int) round((float) $order->cash_amount),
            'reference_id' => (string) $order->order_id,
            'item_name' => ($gatewayMeta['item_name'] ?? $order->product_name).(((int) ($order->quantity ?? 1) > 1) ? ' x'.(int) $order->quantity : ''),
            'customer_name' => Auth::check() ? (string) Auth::user()->name : (string) ($order->customer_name ?: $order->customer_number),
            'customer_phone' => (string) ($order->customer_phone ?: ''),
            'email' => Auth::check() ? (string) Auth::user()->email : (string) ($order->email ?: 'guest@johengaming.id'),
            'redirect_url' => route('payment.detail', $order),
        ]);
    }

    public function chargeAccount(AccountOrder $order, string $method, array $gatewayMeta = []): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);
        $listing = $order->listing;

        return $this->chargeCore($order, $method, [
            'amount' => (int) round((float) $order->cash_amount),
            'reference_id' => (string) $order->order_ref,
            'item_name' => $listing?->product_name ?: ($gatewayMeta['item_name'] ?? 'Akun Game'),
            'customer_name' => (string) ($order->customer_name ?: 'Pelanggan Johen'),
            'customer_phone' => (string) ($order->customer_phone ?: ''),
            'email' => (string) ($order->customer_email ?: 'guest@johengaming.id'),
            'redirect_url' => route('jual-beli-akun.payment', $order),
        ]);
    }

    public function chargeCheckout(Checkout $checkout, string $method, array $gatewayMeta = []): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);

        return $this->chargeCore($checkout, $method, [
            'amount' => (int) $checkout->cash_amount,
            'reference_id' => (string) $checkout->checkout_ref,
            'item_name' => $gatewayMeta['item_name'] ?? ($checkout->item_count.' item Johen Gaming'),
            'customer_name' => Auth::check() ? (string) Auth::user()->name : (string) ($gatewayMeta['customer_name'] ?? 'Pelanggan Johen'),
            'customer_phone' => (string) ($checkout->phone ?? ''),
            'email' => (string) ($checkout->email ?: 'guest@johengaming.id'),
            'redirect_url' => route('checkout.payment', $checkout),
        ]);
    }

    protected function chargeCore(object $payment, string $method, array $ctx): bool
    {
        $normalizedMethod = $this->normalizeMethodCode($method);
        $resolved = $this->resolve($normalizedMethod);
        $midtransCode = $resolved['midtrans_code'];
        $itemName = mb_substr(trim((string) $ctx['item_name']) ?: 'Pesanan Johen Gaming', 0, 50);
        $customerName = trim((string) $ctx['customer_name']) ?: 'Pelanggan Johen';
        $nameParts = preg_split('/\s+/', $customerName, 2) ?: [$customerName];

        $payload = [
            'payment_type' => $resolved['payment_type'],
            'transaction_details' => [
                'order_id' => (string) $ctx['reference_id'],
                'gross_amount' => (int) $ctx['amount'],
            ],
            'item_details' => [[
                'id' => mb_substr((string) $ctx['reference_id'], 0, 50),
                'price' => (int) $ctx['amount'],
                'quantity' => 1,
                'name' => $itemName,
            ]],
            'customer_details' => array_filter([
                'first_name' => mb_substr($nameParts[0] ?? 'Pelanggan', 0, 50),
                'last_name' => mb_substr($nameParts[1] ?? '', 0, 50),
                'email' => filter_var($ctx['email'], FILTER_VALIDATE_EMAIL) ? $ctx['email'] : null,
                'phone' => $this->normalizePhone((string) $ctx['customer_phone']),
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        if ($resolved['payment_type'] === 'qris') {
            $payload['qris'] = ['acquirer' => 'gopay'];
        } elseif ($resolved['payment_type'] === 'gopay') {
            $payload['gopay'] = [
                'enable_callback' => true,
                'callback_url' => (string) $ctx['redirect_url'],
            ];
        } elseif ($resolved['payment_type'] === 'bank_transfer') {
            $payload['bank_transfer'] = ['bank' => $resolved['bank']];
        } elseif ($resolved['payment_type'] === 'echannel') {
            $payload['echannel'] = [
                'bill_info1' => 'Pembayaran Johen',
                'bill_info2' => $itemName,
            ];
        } elseif ($resolved['payment_type'] === 'cstore') {
            $payload['cstore'] = [
                'store' => $midtransCode,
                'message' => 'JOHEN GAMING',
            ];
        }

        $result = $this->midtrans->chargeCore($payload);

        if (! ($result['success'] ?? false)) {
            $this->lastChargeError = $result['message'] ?? 'Pembayaran Midtrans gagal dibuat.';
            Log::warning('Midtrans charge failed', ['order_id' => $ctx['reference_id'], 'message' => $this->lastChargeError]);

            return false;
        }

        $response = $result['data'];
        $actions = collect($response['actions'] ?? []);
        $qrImageUrl = data_get($actions->firstWhere('name', 'generate-qr-code-v2'), 'url')
            ?: data_get($actions->firstWhere('name', 'generate-qr-code'), 'url');
        $deepLinkUrl = data_get($actions->firstWhere('name', 'deeplink-redirect'), 'url');
        $vaNumber = data_get($response, 'va_numbers.0.va_number')
            ?: ($response['permata_va_number'] ?? null)
            ?: ($response['bill_key'] ?? null);
        $paymentCode = $response['payment_code'] ?? null;

        $payment->update([
            'payment_method' => $normalizedMethod,
            'gateway_type' => $resolved['gateway_type'],
            'gateway_invoice_id' => $response['transaction_id'],
            'gateway_invoice_url' => null,
            'checkout_url' => $deepLinkUrl,
            'qr_string' => $response['qr_string'] ?? null,
            'va_number' => $vaNumber,
            'payment_code' => $paymentCode,
            'gateway_extra' => [
                'provider' => 'midtrans',
                'integration' => 'core_api',
                'environment' => $this->midtrans->isProduction() ? 'production' : 'sandbox',
                'order_id' => (string) $ctx['reference_id'],
                'transaction_id' => $response['transaction_id'],
                'payment_type' => $response['payment_type'] ?? $resolved['payment_type'],
                'payment_method' => $midtransCode,
                'payment_status' => $response['transaction_status'] ?? 'pending',
                'bank_code' => data_get($response, 'va_numbers.0.bank') ?? $resolved['bank'] ?? null,
                'biller_code' => $response['biller_code'] ?? null,
                'retail_outlet_name' => $response['store'] ?? null,
                'label' => $response['store'] ?? null,
                'expected_amount' => (int) $ctx['amount'],
                'qr_image_url' => $qrImageUrl,
                'deeplink_url' => $deepLinkUrl,
                'expired_date' => $response['expiry_time'] ?? now()->addDay()->toIso8601String(),
            ],
        ]);

        return true;
    }

    protected function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '+62'.substr($digits, 1);
        }

        return '+'.$digits;
    }
}
