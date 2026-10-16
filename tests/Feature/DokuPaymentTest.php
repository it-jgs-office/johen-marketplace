<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Transaction;
use App\Services\DokuService;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DokuPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.payment.gateway' => 'doku',
            'services.payment.simulation' => false,
            'doku.client_id' => 'BRN-TEST',
            'doku.secret_key' => 'sandbox-secret',
            'doku.environment' => 'sandbox',
            'doku.sandbox_url' => 'https://api-sandbox.doku.com',
        ]);
    }

    public function test_checkout_request_uses_signed_doku_sandbox_api(): void
    {
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => Http::response([
                'response' => [
                    'order' => ['invoice_number' => 'INV-TEST-1'],
                    'payment' => [
                        'token_id' => 'doku-token-1',
                        'url' => 'https://sandbox.doku.com/checkout-link-v2/doku-token-1',
                        'expired_date' => '20261012120000',
                    ],
                ],
            ]),
        ]);

        $payload = [
            'order' => ['amount' => 50000, 'invoice_number' => 'INV-TEST-1'],
            'payment' => ['payment_due_date' => 60],
        ];

        $result = app(DokuService::class)->createCheckout($payload);

        $this->assertTrue($result['success']);
        $this->assertSame('doku-token-1', $result['token_id']);

        Http::assertSent(function (ClientRequest $request) {
            $requestId = $request->header('Request-Id')[0] ?? '';
            $timestamp = $request->header('Request-Timestamp')[0] ?? '';
            $body = $request->body();
            $components = implode("\n", [
                'Client-Id:BRN-TEST',
                'Request-Id:'.$requestId,
                'Request-Timestamp:'.$timestamp,
                'Request-Target:/checkout/v1/payment',
                'Digest:'.base64_encode(hash('sha256', $body, true)),
            ]);
            $expected = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $components, 'sandbox-secret', true));

            return $request->url() === 'https://api-sandbox.doku.com/checkout/v1/payment'
                && ($request->header('Client-Id')[0] ?? null) === 'BRN-TEST'
                && hash_equals($expected, (string) ($request->header('Signature')[0] ?? ''));
        });
    }

    public function test_gateway_saves_doku_checkout_url_on_order(): void
    {
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => Http::response([
                'response' => [
                    'payment' => [
                        'token_id' => 'token-order',
                        'url' => 'https://sandbox.doku.com/checkout-link-v2/token-order',
                        'expired_date' => '20261012120000',
                    ],
                ],
            ]),
        ]);

        $order = $this->order();

        $charged = app(PaymentGatewayService::class)->charge($order, 'qris', [
            'item_name' => 'Diamond Game',
            'unit_price' => 50000,
        ]);

        $order->refresh();
        $this->assertTrue($charged);
        $this->assertSame('doku_checkout', $order->gateway_type);
        $this->assertSame('token-order', $order->gateway_invoice_id);
        $this->assertSame('https://sandbox.doku.com/checkout-link-v2/token-order', $order->gateway_invoice_url);
        $this->assertSame('QRIS', $order->gateway_extra['payment_method_type']);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api-sandbox.doku.com/checkout/v1/payment'
            && ! isset($request->data()['payment']['payment_method_types'])
        );
    }

    public function test_doku_exposes_one_universal_checkout_option(): void
    {
        $methods = app(PaymentGatewayService::class)->filterAvailableMethods(collect());

        $this->assertCount(1, $methods);
        $this->assertSame('doku_checkout', $methods->first()->code);
        $this->assertSame('DOKU Checkout', $methods->first()->name);
    }

    public function test_valid_success_notification_settles_topup_order(): void
    {
        Http::fake([
            '*' => Http::response(['data' => ['status' => 'Pending']], 200),
        ]);

        $order = $this->order();
        Transaction::create([
            'order_id' => $order->id,
            'gross_amount' => 50000,
            'status' => 'pending',
        ]);

        $payload = [
            'transaction' => ['status' => 'SUCCESS', 'original_request_id' => 'doku-request-1'],
            'order' => ['invoice_number' => $order->order_id, 'amount' => 50000],
            'channel' => ['id' => 'QRIS', 'name' => 'QRIS'],
        ];

        $response = $this->sendNotification($payload);

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('processing', $order->transaction->fresh()->status);
    }

    public function test_checkout_failed_notification_is_not_treated_as_final(): void
    {
        $order = $this->order();
        $payload = [
            'transaction' => ['status' => 'FAILED', 'original_request_id' => 'doku-request-2'],
            'order' => ['invoice_number' => $order->order_id, 'amount' => 50000],
            'channel' => ['id' => 'QRIS'],
        ];

        $this->sendNotification($payload)->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_notification_rejects_invalid_signature(): void
    {
        $payload = json_encode([
            'transaction' => ['status' => 'SUCCESS'],
            'order' => ['invoice_number' => 'UNKNOWN', 'amount' => 50000],
        ], JSON_UNESCAPED_SLASHES);

        $this->call('POST', '/payment/doku/notification', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CLIENT_ID' => 'BRN-TEST',
            'HTTP_REQUEST_ID' => (string) Str::uuid(),
            'HTTP_REQUEST_TIMESTAMP' => '2026-10-12T05:00:00Z',
            'HTTP_SIGNATURE' => 'HMACSHA256=invalid',
        ], $payload)->assertUnauthorized();
    }

    private function sendNotification(array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $requestId = (string) Str::uuid();
        $timestamp = '2026-10-12T05:00:00Z';
        $signature = app(DokuService::class)->signature(
            'POST',
            '/payment/doku/notification',
            $requestId,
            $timestamp,
            $body,
        );

        return $this->call('POST', '/payment/doku/notification', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CLIENT_ID' => 'BRN-TEST',
            'HTTP_REQUEST_ID' => $requestId,
            'HTTP_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_SIGNATURE' => $signature,
        ], $body);
    }

    private function order(): Order
    {
        return Order::create([
            'order_id' => 'TUP-'.strtoupper(Str::random(10)),
            'buyer_sku_code' => 'mlbb-test',
            'customer_number' => '123456789',
            'customer_name' => 'DOKU Sandbox',
            'customer_phone' => '08123456789',
            'email' => 'sandbox@example.com',
            'product_name' => 'Diamond Game',
            'brand' => 'Mobile Legends',
            'category' => 'Game',
            'price' => 50000,
            'quantity' => 1,
            'status' => 'pending',
        ]);
    }
}
