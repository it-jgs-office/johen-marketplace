<?php

namespace App\Services;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * DOKU memakai hosted Checkout, sedangkan integrasi Xendit tetap tersedia
 * sebagai provider alternatif.
 */
class PaymentGatewayService
{
    protected XenditService $xendit;

    protected DokuService $doku;

    protected ?string $lastChargeError = null;

    public function __construct(XenditService $xendit, DokuService $doku)
    {
        $this->xendit = $xendit;
        $this->doku = $doku;
    }

    public function provider(): string
    {
        return strtolower((string) config('services.payment.gateway', 'xendit'));
    }

    public function isDoku(): bool
    {
        return $this->provider() === 'doku';
    }

    public function isConfigured(): bool
    {
        return $this->isDoku() ? $this->doku->isConfigured() : $this->xendit->isConfigured();
    }

    public function providerLabel(): string
    {
        return $this->isDoku() ? 'DOKU' : 'Xendit';
    }

    public function lastChargeError(): ?string
    {
        return $this->lastChargeError;
    }

    /**
     * Mapping code metode pembayaran -> info gateway.
     */
    public function resolve(string $method): array
    {
        return match ($method) {
            'doku_wallet' => ['gateway_type' => 'ewallet', 'channel_code' => 'DOKU'],
            'akulaku' => ['gateway_type' => 'paylater'],
            'bjb_va', 'bjb' => ['gateway_type' => 'va', 'bank_code' => 'BJB', 'label' => 'BJB Virtual Account'],
            'bnc_va', 'bnc' => ['gateway_type' => 'va', 'bank_code' => 'BNC', 'label' => 'BNC Virtual Account'],
            'bri_va', 'bri' => ['gateway_type' => 'va', 'bank_code' => 'BRI', 'label' => 'BRI Virtual Account'],
            'bni_va', 'bni' => ['gateway_type' => 'va', 'bank_code' => 'BNI', 'label' => 'BNI Virtual Account'],
            'bsi_va', 'bsi' => ['gateway_type' => 'va', 'bank_code' => 'BSI', 'label' => 'BSI Virtual Account'],
            'bss_va', 'bss' => ['gateway_type' => 'va', 'bank_code' => 'BSS', 'label' => 'BSS Virtual Account'],
            'btn_va', 'btn' => ['gateway_type' => 'va', 'bank_code' => 'BTN', 'label' => 'BTN Virtual Account'],
            'cimb_va', 'cimb' => ['gateway_type' => 'va', 'bank_code' => 'CIMB', 'label' => 'CIMB Virtual Account'],
            'doku_va', 'doku' => ['gateway_type' => 'va', 'bank_code' => 'DOKU', 'label' => 'DOKU Virtual Account'],
            'danamon_va', 'danamon' => ['gateway_type' => 'va', 'bank_code' => 'DANAMON', 'label' => 'Danamon Virtual Account'],
            'maybank_va', 'maybank' => ['gateway_type' => 'va', 'bank_code' => 'MAYBANK', 'label' => 'Maybank Virtual Account'],
            'permata_va', 'permata' => ['gateway_type' => 'va', 'bank_code' => 'PERMATA', 'label' => 'Permata Virtual Account'],
            'sinarmas_va', 'sinarmas' => ['gateway_type' => 'va', 'bank_code' => 'SINARMAS', 'label' => 'Sinarmas Virtual Account'],
            'alfamart' => ['gateway_type' => 'retail', 'retail_outlet_name' => 'ALFAMART', 'label' => 'Alfamart'],
            'indomaret' => ['gateway_type' => 'retail', 'retail_outlet_name' => 'INDOMARET', 'label' => 'Indomaret'],
            default => ['gateway_type' => 'invoice'],
        };
    }

    public function ewalletChannelCode(string $method): string
    {
        return match ($method) {
            'gopay' => 'GOPAY',
            'ovo' => 'ID_OVO',
            'dana' => 'ID_DANA',
            'shopeepay' => 'ID_SHOPEEPAY',
            'linkaja' => 'ID_LINKAJA',
            default => 'ID_DANA',
        };
    }

    /**
     * Normalisasi nomor HP ke format internasional 62... (untuk OVO).
     */
    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Siapkan payment method yang benar berdasarkan code yang dipilih untuk
     * diteruskan sebagai reference pembayaran (disimpan di order.payment_method).
     */
    public function normalizeMethodCode(string $method): string
    {
        return match ($method) {
            'bjb', 'bnc', 'bni', 'bri', 'bsi', 'bss', 'btn', 'cimb', 'doku', 'danamon', 'maybank', 'permata', 'sinarmas' => $method.'_va',
            default => $method,
        };
    }

    public function assertAvailableMethod(string $method): void
    {
        if (! $this->isDoku()) {
            return;
        }

        $code = $this->normalizeMethodCode($method);
        if (! $this->doku->supportsMethod($code)
            || ! PaymentMethod::where('code', $code)->where('is_active', true)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran belum tersedia. Pilih metode aktif atau hubungi CS.',
            ]);
        }
    }

    /** DOKU menampilkan seluruh kanal aktif melalui halaman Checkout ter-host. */
    public function filterAvailableMethods($methods)
    {
        if ($this->isDoku()) {
            return collect($methods)->filter(function ($method) {
                $code = $this->normalizeMethodCode((string) $method->code);

                return (bool) $method->is_active && $this->doku->supportsMethod($code);
            })->map(function ($method) {
                $type = $this->resolve((string) $method->code)['gateway_type'];
                $method->category = $type === 'retail' ? 'convenience_store' : $type;

                return $method;
            })->values();
        }

        $available = $this->xendit->availableChannels();

        if ($available === null || empty($available)) {
            return $methods;
        }

        $avail = array_flip($available);

        return collect($methods)->filter(function ($m) use ($avail) {
            $code = is_object($m) ? $m->code : $m['code'] ?? null;

            return $code !== null && isset($avail[$this->xendit->channelCodeFor($code)]);
        })->values();
    }

    /**
     * Buat charge gateway untuk order top-up & isi kolom gateway di order.
     * Return true bila berhasil, false bila gagal.
     */
    public function charge(Order $order, string $method, array $gatewayMeta): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);
        $resolved = $this->resolve($method);
        $type = $this->isDoku() ? 'doku_checkout' : $resolved['gateway_type'];

        $ctx = [
            'amount' => (int) $order->cash_amount,
            'reference_id' => $order->order_id,
            'item_name' => ($gatewayMeta['item_name'] ?? $order->product_name).(((int) ($order->quantity ?? 1) > 1) ? ' x'.(int) $order->quantity : ''),
            'unit_price' => (int) $order->cash_amount,
            'customer_number' => (string) $order->customer_number,
            'customer_name' => Auth::check() ? (string) Auth::user()->name : (string) ($order->customer_name ?: $order->customer_number),
            'customer_phone' => (string) ($order->customer_phone ?: $order->customer_number),
            'email' => Auth::check() ? (string) Auth::user()->email : (string) ($order->email ?: 'guest@johengaming.id'),
            'quantity' => 1,
            'category' => $order->category,
            'redirect_url' => route('payment.detail', $order),
        ];

        $order->update([
            'payment_method' => $this->normalizeMethodCode($method),
            'gateway_type' => $type,
        ]);

        return $this->runCharge($order, $type, $resolved, $ctx);
    }

    /**
     * Buat charge gateway untuk order jual-beli-akun & isi kolom gateway di order.
     * Referensi webhook memakai order_ref (JBA-...), nominal dari total_price.
     * Return true bila berhasil, false bila gagal.
     */
    public function chargeAccount(AccountOrder $order, string $method, array $gatewayMeta = []): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);
        $resolved = $this->resolve($method);
        $type = $this->isDoku() ? 'doku_checkout' : $resolved['gateway_type'];
        $listing = $order->listing;

        $itemName = $listing?->product_name ?: ($gatewayMeta['item_name'] ?? 'Akun Game');

        $ctx = [
            'amount' => (int) round((float) $order->cash_amount),
            'reference_id' => (string) $order->order_ref,
            'item_name' => $itemName,
            'unit_price' => (int) round((float) $order->cash_amount),
            'customer_number' => (string) ($order->customer_phone ?: $order->customer_email),
            'customer_name' => (string) ($order->customer_name ?: 'JOHEM'),
            'customer_phone' => (string) ($order->customer_phone ?: ''),
            'email' => (string) ($order->customer_email ?: 'guest@johengaming.id'),
            'quantity' => 1,
            'category' => $listing?->game,
            'redirect_url' => route('jual-beli-akun.payment', $order),
        ];

        $order->update([
            'payment_method' => $this->normalizeMethodCode($method),
            'gateway_type' => $type,
        ]);

        $charged = $this->runCharge($order, $type, $resolved, $ctx);

        if (! $charged) {
            $order->update(['gateway_type' => null]);
        }

        return $charged;
    }

    /**
     * Buat charge gateway untuk satu charge gabungan dari keranjang.
     *
     * Checkout memegang seluruh nominal, jadi amount-nya `total` dan
     * reference-nya `checkout_ref`. Run charge menulis kolom gateway langsung ke
     * model Checkout karena nama kolomnya sengaja dibuat sama dengan orders.
     */
    public function chargeCheckout(Checkout $checkout, string $method, array $gatewayMeta = []): bool
    {
        $this->lastChargeError = null;
        $this->assertAvailableMethod($method);
        $resolved = $this->resolve($method);
        $type = $this->isDoku() ? 'doku_checkout' : $resolved['gateway_type'];
        $total = (int) $checkout->cash_amount;

        $ctx = [
            'amount' => $total,
            'reference_id' => $checkout->checkout_ref,
            'item_name' => $gatewayMeta['item_name'] ?? ($checkout->item_count.' item Johen Gaming'),
            'unit_price' => $total,
            'customer_number' => (string) ($checkout->phone ?: $checkout->email),
            'customer_name' => Auth::check()
                ? (string) Auth::user()->name
                : (string) ($gatewayMeta['customer_name'] ?? 'JOHEM'),
            'customer_phone' => (string) ($checkout->phone ?? ''),
            'email' => (string) ($checkout->email ?: 'guest@johengaming.id'),
            'quantity' => 1,
            'category' => 'Top Up Game',
            'redirect_url' => route('checkout.payment', $checkout),
        ];

        $checkout->update([
            'payment_method' => $this->normalizeMethodCode($method),
            'gateway_type' => $type,
        ]);

        $charged = $this->runCharge($checkout, $type, $resolved, $ctx);

        if (! $charged) {
            $checkout->update(['gateway_type' => null]);
        }

        return $charged;
    }

    /**
     * Jalankan charge sesuai tipe gateway dengan konteks payload yang seragam.
     * Konteks memisahkan data order (Order vs AccountOrder vs Checkout) dari
     * logika charge.
     */
    protected function runCharge(object $order, string $type, array $resolved, array $ctx): bool
    {
        if ($this->isDoku()) {
            return $this->chargeDokuCheckout($order, $ctx);
        }

        switch ($type) {
            case 'qris':
                return $this->chargeQris($order, $ctx);

            case 'va':
                return $this->chargeVa($order, $ctx, $resolved);

            case 'ewallet':
                return $this->chargeEwallet($order, $ctx, $resolved);

            case 'retail':
                return $this->chargeRetail($order, $ctx, $resolved);

            default:
                return $this->chargeInvoice($order, $ctx);
        }
    }

    protected function chargeDokuCheckout(object $order, array $ctx): bool
    {
        $method = (string) ($order->payment_method ?? '');
        $dokuMethod = $this->doku->paymentMethodType($method);
        $itemName = trim((string) $ctx['item_name']) ?: 'Pesanan Johen Gaming';
        $customerName = trim((string) $ctx['customer_name']) ?: 'Pelanggan Johen';
        $phone = $this->normalizePhone((string) ($ctx['customer_phone'] ?: $ctx['customer_number']));

        $payload = [
            'order' => [
                'amount' => (int) $ctx['amount'],
                'invoice_number' => (string) $ctx['reference_id'],
                'currency' => 'IDR',
                'callback_url' => $ctx['redirect_url'],
                'callback_url_result' => $ctx['redirect_url'],
                'language' => 'ID',
                'auto_redirect' => false,
                'line_items' => [[
                    'name' => mb_substr($itemName, 0, 255),
                    'quantity' => 1,
                    'price' => (int) $ctx['amount'],
                ]],
            ],
            'payment' => [
                'payment_due_date' => max(1, (int) config('doku.payment_due_minutes', 60)),
            ],
            'customer' => array_filter([
                'name' => mb_substr($customerName, 0, 100),
                'email' => filter_var($ctx['email'], FILTER_VALIDATE_EMAIL) ? $ctx['email'] : null,
                'phone' => $phone !== '' ? $phone : null,
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        // Pada DOKU Checkout ter-host, biarkan DOKU menampilkan seluruh kanal
        // yang memang aktif pada akun merchant. Pembatasan hanya dikirim bila
        // sengaja diaktifkan melalui environment.
        if (config('doku.restrict_payment_method') && $dokuMethod !== null) {
            $payload['payment']['payment_method_types'] = [$dokuMethod];
        }

        $result = $this->doku->createCheckout($payload);

        if (! ($result['success'] ?? false)) {
            $this->lastChargeError = $result['message'] ?? 'DOKU Checkout gagal dibuat.';
            Log::warning('DOKU Checkout gagal dibuat', [
                'order_id' => $ctx['reference_id'],
                'message' => $this->lastChargeError,
                'request_id' => $result['request_id'] ?? null,
            ]);

            return false;
        }

        $order->update([
            'gateway_invoice_id' => $result['token_id'],
            'gateway_invoice_url' => $result['payment_url'],
            'checkout_url' => $result['payment_url'],
            'gateway_extra' => [
                'provider' => 'doku',
                'integration' => 'checkout',
                'invoice_number' => (string) $ctx['reference_id'],
                'request_id' => $result['request_id'],
                'expired_date' => $result['expired_date'] ?? null,
                'payment_method_type' => $dokuMethod,
                'environment' => $this->doku->isSandbox() ? 'sandbox' : 'production',
            ],
        ]);

        return true;
    }

    protected function chargeQris(object $order, array $ctx): bool
    {
        $itemName = $ctx['item_name'];
        if (($ctx['quantity'] ?? 1) > 1) {
            $itemName .= ' x'.$ctx['quantity'];
        }

        $result = $this->xendit->createQr([
            'reference_id' => $ctx['reference_id'],
            'type' => 'DYNAMIC',
            'currency' => 'IDR',
            'amount' => $ctx['amount'],
            'expires_at' => now()->addHour()->toIso8601String(),
            'description' => $itemName.' - '.$ctx['customer_number'],
            'metadata' => [
                'order_id' => $ctx['reference_id'],
                'product' => $itemName,
                'customer_number' => $ctx['customer_number'],
            ],
        ]);

        if (! $result['success']) {
            Log::warning('QRIS charge gagal', ['order_id' => $ctx['reference_id'], 'error' => $result]);

            return false;
        }

        $order->update([
            'gateway_invoice_id' => $result['qr_id'],
            'qr_string' => $result['qr_string'],
            'gateway_extra' => ['expired_date' => now()->addHour()->toIso8601String()],
        ]);

        return true;
    }

    protected function chargeVa(object $order, array $ctx, array $resolved): bool
    {
        $result = $this->xendit->createVirtualAccount([
            'external_id' => $ctx['reference_id'],
            'bank_code' => $resolved['bank_code'],
            'name' => strtoupper(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $ctx['customer_name']), 0, 45)) ?: 'JOHEM',
            'is_single_use' => true,
            'is_closed' => true,
            'expected_amount' => $ctx['amount'],
            'expiration_date' => now()->addHour()->toIso8601String(),
            'customer' => [
                'given_names' => $ctx['customer_name'],
                'email' => $ctx['email'],
            ],
            'currency' => 'IDR',
            'country' => 'ID',
        ]);

        if (! $result['success']) {
            Log::warning('VA charge gagal', ['order_id' => $ctx['reference_id'], 'error' => $result]);

            return false;
        }

        $order->update([
            'gateway_invoice_id' => $result['id'],
            'va_number' => $result['account_number'],
            'gateway_extra' => [
                'bank_code' => $resolved['bank_code'],
                'expected_amount' => $result['expected_amount'],
                'label' => $resolved['label'] ?? null,
                'expired_date' => now()->addHour()->toIso8601String(),
            ],
        ]);

        return true;
    }

    protected function chargeEwallet(object $order, array $ctx, array $resolved): bool
    {
        $channelCode = $resolved['channel_code'];
        $callbackUrl = $ctx['redirect_url'];

        $channelProperties = [
            'success_redirect_url' => $callbackUrl,
            'failure_redirect_url' => $callbackUrl,
            'cancel_redirect_url' => $callbackUrl,
        ];

        // OVO memerlukan app_id (Client ID Xendit) + mobile_number di channel_properties.
        if ($channelCode === 'ID_OVO') {
            $channelProperties['mobile_number'] = $this->normalizePhone($ctx['customer_phone'] ?: $ctx['customer_number']);
            $appId = (string) config('xendit.ovo_app_id', '');
            if ($appId !== '') {
                $channelProperties['app_id'] = $appId;
            }
        }

        $result = $this->xendit->createEwalletCharge([
            'reference_id' => $ctx['reference_id'],
            'currency' => 'IDR',
            'amount' => $ctx['amount'],
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'channel_code' => $channelCode,
            'channel_properties' => $channelProperties,
            'callback_url' => route('payment.notification'),
            'metadata' => [
                'order_id' => $ctx['reference_id'],
            ],
        ]);

        if (! $result['success']) {
            Log::warning('Ewallet charge gagal', ['order_id' => $ctx['reference_id'], 'error' => $result]);

            return false;
        }

        $order->update([
            'gateway_invoice_id' => $result['id'],
            'checkout_url' => $result['checkout_url'],
            'gateway_extra' => [
                'channel_code' => $result['channel_code'],
                'is_redirect_required' => $result['is_redirect_required'],
                'actions' => $result['actions'] ?? null,
                'expired_date' => now()->addHour()->toIso8601String(),
            ],
        ]);

        return true;
    }

    protected function chargeRetail(object $order, array $ctx, array $resolved): bool
    {
        $result = $this->xendit->createRetailOutlet([
            'external_id' => $ctx['reference_id'],
            'retail_outlet_name' => $resolved['retail_outlet_name'],
            'name' => substr(preg_replace('/[^A-Za-z0-9 ]/', '', (string) ($ctx['customer_name'] ?: $ctx['customer_number'])), 0, 40) ?: 'JOHEM',
            'expected_amount' => $ctx['amount'],
            'expiration_date' => now()->addHour()->toIso8601String(),
            'is_single_use' => true,
        ]);

        if (! $result['success']) {
            Log::warning('Retail charge gagal', ['order_id' => $ctx['reference_id'], 'error' => $result]);

            return false;
        }

        $order->update([
            'gateway_invoice_id' => $result['id'],
            'payment_code' => $result['payment_code'],
            'gateway_extra' => [
                'retail_outlet_name' => $resolved['retail_outlet_name'],
                'expected_amount' => $result['expected_amount'],
                'label' => $resolved['label'] ?? null,
                'expired_date' => now()->addHour()->toIso8601String(),
            ],
        ]);

        return true;
    }

    protected function chargeInvoice(object $order, array $ctx): bool
    {
        $itemName = $ctx['item_name'];
        if (($ctx['quantity'] ?? 1) > 1) {
            $itemName .= ' x'.$ctx['quantity'];
        }

        $result = $this->xendit->createInvoice([
            'external_id' => $ctx['reference_id'],
            'amount' => $ctx['amount'],
            'description' => $itemName.' - '.$ctx['customer_number'],
            'payer_email' => $ctx['email'],
            'customer' => [
                'given_names' => $ctx['customer_name'],
                'email' => $ctx['email'],
            ],
            'invoice_duration' => 86400,
            'currency' => 'IDR',
            'items' => [
                [
                    'name' => $itemName,
                    'quantity' => (int) ($ctx['quantity'] ?: 1),
                    'price' => (int) ($ctx['unit_price'] ?? $ctx['amount']),
                    'category' => $ctx['category'],
                ],
            ],
            'success_redirect_url' => $ctx['redirect_url'],
            'failure_redirect_url' => $ctx['redirect_url'],
        ]);

        if (! $result['success']) {
            Log::warning('Invoice charge gagal', ['order_id' => $ctx['reference_id'], 'error' => $result]);

            return false;
        }

        $order->update([
            'gateway_invoice_id' => $result['id'],
            'gateway_invoice_url' => $result['invoice_url'],
            'gateway_extra' => ['expired_date' => now()->addDay()->toIso8601String()],
        ]);

        return true;
    }
}
