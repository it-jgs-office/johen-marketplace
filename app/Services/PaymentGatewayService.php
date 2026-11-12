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

    public function __construct(protected AyolinxService $ayolinx) {}

    public function provider(): string
    {
        return 'ayolinx';
    }

    public function isConfigured(): bool
    {
        return $this->ayolinx->isConfigured();
    }

    public function providerLabel(): string
    {
        return 'Ayolinx Payment Link'.($this->ayolinx->isProduction() ? '' : ' Sandbox');
    }

    public function lastChargeError(): ?string
    {
        return $this->lastChargeError;
    }

    public function resolve(string $method): array
    {
        $code = $this->normalizeMethodCode($method);

        return match ($code) {
            'qris' => ['gateway_type' => 'invoice', 'category' => 'qris', 'ayolinx_channel' => (string) config('ayolinx.qris_channel', 'BNC_QRIS')],
            'dana' => ['gateway_type' => 'invoice', 'category' => 'ewallet', 'ayolinx_channel' => 'EMONEY_DANA_SNAP'],
            'bca_va' => ['gateway_type' => 'invoice', 'category' => 'va', 'ayolinx_channel' => 'VIRTUAL_ACCOUNT_BCA'],
            'bni_va' => ['gateway_type' => 'invoice', 'category' => 'va', 'ayolinx_channel' => 'VIRTUAL_ACCOUNT_BNI'],
            'bri_va' => ['gateway_type' => 'invoice', 'category' => 'va', 'ayolinx_channel' => 'VIRTUAL_ACCOUNT_BRI'],
            'cimb_va' => ['gateway_type' => 'invoice', 'category' => 'va', 'ayolinx_channel' => 'VIRTUAL_ACCOUNT_CIMB'],
            'mandiri_va' => ['gateway_type' => 'invoice', 'category' => 'va', 'ayolinx_channel' => 'VIRTUAL_ACCOUNT_MANDIRI'],
            'bss_va' => ['gateway_type' => 'invoice', 'category' => 'va', 'ayolinx_channel' => 'VIRTUAL_ACCOUNT_BSS'],
            'alfamart' => ['gateway_type' => 'invoice', 'category' => 'convenience_store', 'ayolinx_channel' => 'RETAIL_ALFAMART'],
            default => ['gateway_type' => 'invoice', 'category' => null, 'ayolinx_channel' => null],
        };
    }

    public function normalizeMethodCode(string $method): string
    {
        $method = strtolower(trim($method));

        return match ($method) {
            'bca', 'bni', 'bri', 'bss', 'cimb', 'mandiri' => $method.'_va',
            default => $method,
        };
    }

    public function assertAvailableMethod(string $method): void
    {
        $code = $this->normalizeMethodCode($method);

        if ($this->resolve($code)['ayolinx_channel'] === null
            || ! PaymentMethod::where('code', $code)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran belum tersedia di Ayolinx. Pilih metode lain.',
            ]);
        }
    }

    public function filterAvailableMethods($methods)
    {
        return collect($methods)->filter(function ($method) {
            $code = $this->normalizeMethodCode((string) (is_object($method) ? $method->code : $method['code']));

            return (bool) (is_object($method) ? $method->is_active : $method['is_active'])
                && $this->resolve($code)['ayolinx_channel'] !== null;
        })->map(function ($method) {
            $resolved = $this->resolve((string) $method->code);
            $method->category = $resolved['category'];
            $method->setAttribute('gateway_provider', 'ayolinx');
            $method->setAttribute('gateway_code', $resolved['ayolinx_channel']);

            return $method;
        })->values();
    }

    public function charge(Order $order, string $method, array $gatewayMeta): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);

        return $this->chargePaymentLink($order, $method, [
            'amount' => (int) round((float) $order->cash_amount),
            'reference_id' => (string) $order->order_id,
            'item_name' => ($gatewayMeta['item_name'] ?? $order->product_name).(((int) ($order->quantity ?? 1) > 1) ? ' x'.(int) $order->quantity : ''),
            'customer_name' => Auth::check() ? (string) Auth::user()->name : (string) ($order->customer_name ?: $order->customer_number),
            'customer_phone' => (string) ($order->customer_phone ?: ''),
            'email' => Auth::check() ? (string) Auth::user()->email : (string) ($order->email ?: 'guest@johengaming.id'),
        ]);
    }

    public function chargeAccount(AccountOrder $order, string $method, array $gatewayMeta = []): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);
        $listing = $order->listing;

        return $this->chargePaymentLink($order, $method, [
            'amount' => (int) round((float) $order->cash_amount),
            'reference_id' => (string) $order->order_ref,
            'item_name' => $listing?->product_name ?: ($gatewayMeta['item_name'] ?? 'Akun Game'),
            'customer_name' => (string) ($order->customer_name ?: 'Pelanggan Johen'),
            'customer_phone' => (string) ($order->customer_phone ?: ''),
            'email' => (string) ($order->customer_email ?: 'guest@johengaming.id'),
        ]);
    }

    public function chargeCheckout(Checkout $checkout, string $method, array $gatewayMeta = []): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);

        return $this->chargePaymentLink($checkout, $method, [
            'amount' => (int) $checkout->cash_amount,
            'reference_id' => (string) $checkout->checkout_ref,
            'item_name' => $gatewayMeta['item_name'] ?? ($checkout->item_count.' item Johen Gaming'),
            'customer_name' => Auth::check() ? (string) Auth::user()->name : (string) ($gatewayMeta['customer_name'] ?? 'Pelanggan Johen'),
            'customer_phone' => (string) ($checkout->phone ?? ''),
            'email' => (string) ($checkout->email ?: 'guest@johengaming.id'),
        ]);
    }

    protected function chargePaymentLink(object $payment, string $method, array $context): bool
    {
        $normalizedMethod = $this->normalizeMethodCode($method);
        $channel = (string) $this->resolve($normalizedMethod)['ayolinx_channel'];
        [$phone, $countryCode] = $this->phoneParts((string) $context['customer_phone']);
        $expiresAt = now('Asia/Jakarta')->addMinutes(max(1, (int) config('ayolinx.expiry_minutes', 60)));
        $localReference = (string) $context['reference_id'];
        $partnerReferenceNo = $this->partnerReferenceNo($payment, $localReference);

        $payload = [
            'partnerReferenceNo' => $partnerReferenceNo,
            'customerName' => mb_substr(trim((string) $context['customer_name']) ?: 'Pelanggan Johen', 0, 100),
            'customerEmail' => filter_var($context['email'], FILTER_VALIDATE_EMAIL) ? $context['email'] : 'guest@johengaming.id',
            'customerPhoneNumber' => $phone,
            'customerPhoneNumberCountryCode' => $countryCode,
            'listItem' => [[
                'name' => mb_substr(trim((string) $context['item_name']) ?: 'Pesanan Johen Gaming', 0, 100),
                'price' => (string) (int) $context['amount'],
                'qty' => '1',
            ]],
            'listPaymentChannel' => [$channel],
            'description' => 'Pembayaran '.$localReference,
            'expiryTime' => $expiresAt->format('Y-m-d\TH:i:sP'),
            'urlParams' => [[
                'type' => 'NOTIFICATION',
                'url' => route('payment.notification'),
            ]],
        ];

        $result = $this->ayolinx->createPaymentLink($payload);

        if (! ($result['success'] ?? false)) {
            $this->lastChargeError = $result['message'] ?? 'Halaman pembayaran Ayolinx gagal dibuat.';
            Log::warning('Ayolinx charge failed', [
                'reference' => $context['reference_id'],
                'message' => $this->lastChargeError,
            ]);

            return false;
        }

        $response = $result['data'];
        $paymentLink = (string) $response['paymentLink'];

        $payment->update([
            'payment_method' => $normalizedMethod,
            'gateway_type' => 'invoice',
            'gateway_invoice_id' => (string) ($response['partnerReferenceNo'] ?? $partnerReferenceNo),
            'gateway_invoice_url' => $paymentLink,
            'checkout_url' => $paymentLink,
            'qr_string' => null,
            'va_number' => null,
            'payment_code' => null,
            'gateway_extra' => [
                'provider' => 'ayolinx',
                'integration' => 'payment_link',
                'environment' => $this->ayolinx->isProduction() ? 'production' : 'sandbox',
                'reference_id' => $localReference,
                'partner_reference_no' => $partnerReferenceNo,
                'payment_link_id' => $response['partnerReferenceNo'] ?? $partnerReferenceNo,
                'payment_method' => $normalizedMethod,
                'channel' => $channel,
                'payment_status' => 'PENDING',
                'expected_amount' => (int) $context['amount'],
                'expired_date' => (string) ($response['expiryDate'] ?? $expiresAt->toIso8601String()),
            ],
        ]);

        return true;
    }

    private function partnerReferenceNo(object $payment, string $localReference): string
    {
        $seed = $payment::class.'|'.$localReference;
        $hashValue = (int) hexdec(substr(hash('sha256', $seed), 0, 12));

        return (string) (100000000000000 + ($hashValue % 900000000000000));
    }

    private function phoneParts(string $phone): array
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?: '';

        if (str_starts_with($digits, '62')) {
            return [substr($digits, 2), '+62'];
        }

        if (str_starts_with($digits, '0')) {
            return [substr($digits, 1), '+62'];
        }

        return [$digits ?: '80000000000', '+62'];
    }
}
