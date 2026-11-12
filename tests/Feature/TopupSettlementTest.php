<?php

namespace Tests\Feature;

use App\Models\BalanceTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\TopupSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Alur settlement topup: Ayolinx sukses -> saldo naik -> Digiflazz diproses ->
 * sukses / gagal / timeout -> saldo akhirnya dikembalikan atau terpakai.
 *
 * Respons Digiflazz dikendalikan lewat closure yang membaca variabel lokal,
 * karena `Http::fake()` hanya memakai stub PERTAMA untuk satu URL.
 */
class TopupSettlementTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, array> cmd => body respons */
    private array $digiflazz = [];

    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'digiflazz.reconcile.grace_minutes' => 30,
            'digiflazz.reconcile.max_polls' => 12,
        ]);

        $this->digiflazz = [
            'topup' => ['data' => ['status' => 'Pending']],
            'status' => ['data' => ['status' => 'Pending']],
        ];
        $this->requests = [];

        Http::fake(function (Request $request) {
            $cmd = $request['cmd'] ?? '';
            $this->requests[] = $cmd;

            return Http::response($this->digiflazz[$cmd] ?? ['data' => ['status' => 'Pending']], 200);
        });
    }

    private function reply(string $cmd, array $body): void
    {
        $this->digiflazz[$cmd] = $body;
    }

    private function user(): User
    {
        return User::factory()->create(['username' => 'u'.Str::lower(Str::random(8))]);
    }

    private function order(?User $user, array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $user?->id,
            'order_id' => 'TUP-'.strtoupper(Str::random(10)),
            'buyer_sku_code' => 'mlbb-5-diamond',
            'customer_number' => '08123456789',
            'product_name' => '5 Diamond',
            'brand' => 'MOBILE LEGENDS',
            'category' => 'Game',
            'price' => 50000,
            'quantity' => 1,
            'status' => 'pending',
        ], $overrides));

        Transaction::create([
            'order_id' => $order->id,
            'gross_amount' => $overrides['price'] ?? 50000,
            'status' => 'pending',
        ]);

        return $order;
    }

    private function settlement(): TopupSettlementService
    {
        return app(TopupSettlementService::class);
    }

    private function balance(): BalanceService
    {
        return app(BalanceService::class);
    }

    public function test_paid_order_credits_balance_and_sends_topup(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->settlement()->markPaid($order);

        $order->refresh();

        $this->assertSame('processing', $order->status);
        $this->assertSame('held', $order->saldo_status);
        $this->assertNotNull($order->digiflazz_started_at);
        $this->assertSame(50000.0, $this->balance()->balanceFor($user->id));
        $this->assertSame('processing', $order->transaction->refresh()->status);
        $this->assertContains('topup', $this->requests);
    }

    public function test_topup_success_consumes_balance_and_stores_sn(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->settlement()->markPaid($order);

        $this->reply('status', ['data' => ['status' => 'Sukses', 'sn' => 'SN-ABC-123']]);
        $this->assertSame('sukses', $this->settlement()->poll($order->refresh()));

        $order->refresh();

        $this->assertSame('success', $order->status);
        $this->assertSame('consumed', $order->saldo_status);
        $this->assertSame('SN-ABC-123', $order->note);
        $this->assertSame(0.0, $this->balance()->balanceFor($user->id));
    }

    public function test_immediate_success_in_topup_response_is_settled_at_once(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->reply('topup', ['data' => ['status' => 'Sukses', 'sn' => 'SN-INSTANT']]);
        $this->settlement()->markPaid($order);

        $order->refresh();

        $this->assertSame('success', $order->status);
        $this->assertSame('consumed', $order->saldo_status);
        $this->assertSame(0.0, $this->balance()->balanceFor($user->id));
    }

    public function test_topup_failure_returns_balance_to_user(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->settlement()->markPaid($order);

        $this->reply('status', ['data' => ['status' => 'Gagal', 'message' => 'Stok habis']]);
        $this->assertSame('gagal', $this->settlement()->poll($order->refresh()));

        $order->refresh();

        $this->assertSame('failed', $order->status);
        $this->assertSame('refunded', $order->saldo_status);
        $this->assertSame('Stok habis', $order->note);
        $this->assertNotNull($order->saldo_released_at);
        $this->assertSame(50000.0, $this->balance()->balanceFor($user->id));
    }

    public function test_failed_topup_response_is_settled_immediately(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->reply('topup', ['data' => ['status' => 'Gagal', 'message' => 'Produk tidak aktif']]);
        $this->settlement()->markPaid($order);

        $order->refresh();

        $this->assertSame('failed', $order->status);
        $this->assertSame('refunded', $order->saldo_status);
        $this->assertSame(50000.0, $this->balance()->balanceFor($user->id));
    }

    public function test_duplicate_payment_webhook_does_not_credit_twice(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->settlement()->markPaid($order);
        $this->settlement()->markPaid($order->refresh());
        $this->settlement()->markPaid($order->refresh());

        $this->assertSame(50000.0, $this->balance()->balanceFor($user->id));
        $this->assertSame(1, BalanceTransaction::where('order_id', $order->id)->count());
        $this->assertSame(1, count(array_keys($this->requests, 'topup', true)));
    }

    public function test_duplicate_success_webhook_does_not_consume_twice(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->reply('topup', ['data' => ['status' => 'Sukses', 'sn' => 'SN-1']]);
        $this->settlement()->markPaid($order);

        // Webhook Digiflazz datang lagi untuk order yang sudah final.
        $this->settlement()->applyResult($order->refresh(), ['data' => ['status' => 'Sukses', 'sn' => 'SN-1']]);

        $this->assertSame(0.0, $this->balance()->balanceFor($user->id));
        $this->assertSame(1, BalanceTransaction::where('order_id', $order->id)->where('type', BalanceTransaction::TYPE_CONSUME)->count());
    }

    public function test_late_success_after_refund_debits_balance_again(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 30000]);

        $this->settlement()->markPaid($order);
        $this->settlement()->settleFailure($order->refresh(), 'Stok habis');

        $this->assertSame(30000.0, $this->balance()->balanceFor($user->id));
        $this->assertSame('refunded', $order->refresh()->saldo_status);

        // Secara lokal topup sebenarnya sudah masuk, tapi webhook-nya telat.
        $this->settlement()->applyResult($order->refresh(), ['data' => ['status' => 'Sukses', 'sn' => 'SN-LATE']]);

        $order->refresh();

        $this->assertSame('success', $order->status);
        $this->assertSame('reversed', $order->saldo_status);
        $this->assertSame('settled', $order->reconcile_status);
        $this->assertSame(0.0, $this->balance()->balanceFor($user->id));
        $this->assertDatabaseHas('balance_transactions', [
            'order_id' => $order->id,
            'type' => BalanceTransaction::TYPE_CORRECTION_DEBIT,
            'amount' => -30000,
        ]);
    }

    public function test_pending_result_keeps_order_processing(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->settlement()->markPaid($order);
        $result = $this->settlement()->poll($order->refresh());

        $order->refresh();

        $this->assertNull($result);
        $this->assertSame('processing', $order->status);
        $this->assertSame('held', $order->saldo_status);
        $this->assertSame(1, $order->digiflazz_poll_count);
        $this->assertNotNull($order->last_status_check_at);
        $this->assertSame(50000.0, $this->balance()->balanceFor($user->id));
    }

    public function test_grace_period_is_not_exceeded_before_the_deadline(): void
    {
        $order = $this->order($this->user(), [
            'digiflazz_started_at' => now()->subMinutes(29),
            'digiflazz_poll_count' => 3,
        ]);

        $this->assertFalse($this->settlement()->hasExceededGracePeriod($order));
        $this->assertFalse($this->settlement()->hasExceededPollLimit($order));
    }

    public function test_grace_period_and_poll_limit_are_enforced(): void
    {
        $overTime = $this->order($this->user(), [
            'digiflazz_started_at' => now()->subMinutes(31),
            'digiflazz_poll_count' => 3,
        ]);
        $this->assertTrue($this->settlement()->hasExceededGracePeriod($overTime));

        $overPolls = $this->order($this->user(), [
            'digiflazz_started_at' => now(),
            'digiflazz_poll_count' => 12,
        ]);
        $this->assertTrue($this->settlement()->hasExceededPollLimit($overPolls));
    }

    public function test_order_without_digiflazz_timestamp_is_not_auto_refunded(): void
    {
        $order = $this->order($this->user(), ['digiflazz_started_at' => null]);

        $this->assertFalse($this->settlement()->hasExceededGracePeriod($order));
    }

    public function test_success_consumes_product_stock(): void
    {
        $user = $this->user();
        $product = Product::create([
            'buyer_sku_code' => 'mlbb-5-diamond',
            'product_name' => '5 Diamond',
            'brand' => 'MOBILE LEGENDS',
            'category' => 'instant',
            'type' => 'topup',
            'price' => 45000,
            'selling_price' => 50000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $order = $this->order($user, ['price' => 50000, 'quantity' => 2]);

        $this->reply('topup', ['data' => ['status' => 'Sukses', 'sn' => 'SN-STOCK']]);
        $this->settlement()->markPaid($order);

        $this->assertSame(8, (int) $product->refresh()->stock);
    }

    public function test_failure_does_not_consume_product_stock(): void
    {
        $product = Product::create([
            'buyer_sku_code' => 'mlbb-5-diamond',
            'product_name' => '5 Diamond',
            'brand' => 'MOBILE LEGENDS',
            'category' => 'instant',
            'type' => 'topup',
            'price' => 45000,
            'selling_price' => 50000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $order = $this->order($this->user(), ['price' => 50000, 'quantity' => 2]);

        $this->reply('topup', ['data' => ['status' => 'Gagal', 'message' => 'Stok habis']]);
        $this->settlement()->markPaid($order);

        $this->assertSame(10, (int) $product->refresh()->stock);
    }

    public function test_guest_order_is_processed_without_any_balance(): void
    {
        $order = $this->order(null, ['price' => 50000]);

        $this->settlement()->markPaid($order);

        $order->refresh();

        $this->assertSame('processing', $order->status);
        $this->assertSame('none', $order->saldo_status);
        $this->assertContains('topup', $this->requests);
        $this->assertSame(0, BalanceTransaction::count());
    }

    public function test_guest_order_can_still_succeed(): void
    {
        $order = $this->order(null, ['price' => 50000]);

        $this->reply('topup', ['data' => ['status' => 'Sukses', 'sn' => 'SN-TAMU']]);
        $this->settlement()->markPaid($order);

        $order->refresh();

        $this->assertSame('success', $order->status);
        $this->assertSame('none', $order->saldo_status);
        $this->assertSame('SN-TAMU', $order->note);
        $this->assertSame(0, BalanceTransaction::count());
    }

    public function test_non_pending_order_is_ignored_by_mark_paid(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000, 'status' => 'success']);

        $this->settlement()->markPaid($order);

        $this->assertSame(0.0, $this->balance()->balanceFor($user->id));
        $this->assertSame([], $this->requests);
    }

    public function test_transport_error_marks_order_failed_and_keeps_money(): void
    {
        $user = $this->user();
        $order = $this->order($user, ['price' => 50000]);

        $this->reply('topup', ['data' => ['status' => 'Gagal', 'message' => 'Gagal terhubung']]);
        $this->settlement()->markPaid($order);

        $order->refresh();

        $this->assertSame('failed', $order->status);
        $this->assertSame('refunded', $order->saldo_status);
        $this->assertSame(50000.0, $this->balance()->balanceFor($user->id));
    }

    public function test_whole_flow_keeps_ledger_and_balance_in_sync(): void
    {
        $user = $this->user();

        // 1. Sukses: 50.000 masuk lalu keluar.
        $a = $this->order($user, ['price' => 50000]);
        $this->settlement()->markPaid($a);
        $this->settlement()->applyResult($a->refresh(), ['data' => ['status' => 'Sukses', 'sn' => 'SN-A']]);

        // 2. Gagal: 75.000 tetap di user.
        $b = $this->order($user, ['price' => 75000]);
        $this->settlement()->markPaid($b);
        $this->settlement()->settleFailure($b->refresh(), 'Stok habis');

        $this->assertSame(75000.0, $this->balance()->balanceFor($user->id));

        // 3. Sukses terlambat: kredit 30.000 ditarik kembali.
        $c = $this->order($user, ['price' => 30000]);
        $this->settlement()->markPaid($c);
        $this->settlement()->settleFailure($c->refresh(), 'Timeout');
        $this->settlement()->applyResult($c->refresh(), ['data' => ['status' => 'Sukses', 'sn' => 'SN-C']]);

        $this->assertSame(75000.0, $this->balance()->balanceFor($user->id));

        $running = 0.0;
        foreach (BalanceTransaction::where('user_id', $user->id)->orderBy('id')->get() as $tx) {
            $running = round($running + (float) $tx->amount, 2);
            $this->assertSame($running, (float) $tx->balance_after, "Snapshot {$tx->type} order {$tx->order_id} tidak cocok.");
        }

        $this->assertSame($running, $this->balance()->balanceFor($user->id));

        // 3 order: deposit+consume, deposit+release, deposit+release+correction.
        $this->assertCount(7, BalanceTransaction::where('user_id', $user->id)->get());
        $this->assertSame(0, BalanceTransaction::where('user_id', $user->id)->whereNull('idempotency_key')->count());
    }
}
