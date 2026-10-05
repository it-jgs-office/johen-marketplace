<?php

namespace Tests\Feature;

use App\Jobs\SettleCheckoutJob;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CheckoutSettlementService;
use App\Services\TopupSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Keranjang + checkout gabungan: satu charge Xendit menutup banyak order,
 * lalu setiap order anak disettle sendiri lewat Digiflazz.
 *
 * Semua test berjalan di mode simulasi supaya tidak pernah menyentuh API
 * sungguhan (test memakai SQLite in-memory).
 */
class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jangan sampai config ini jatuh ke nilai .env asli.
        config([
            'services.payment.simulation' => true,
            'services.payment.channel' => 'qris',
        ]);
    }

    private function user(): User
    {
        return User::factory()->create(['username' => 'u'.Str::lower(Str::random(8))]);
    }

    private function brand(string $name, bool $requiresZone = false): Brand
    {
        return Brand::create([
            'name' => $name,
            'requires_zone_id' => $requiresZone,
        ]);
    }

    private function product(string $brand = 'Mobile Legends', int $price = 50000, int $stock = 10): Product
    {
        return Product::create([
            'buyer_sku_code' => 'sku-'.Str::lower(Str::random(8)),
            'product_name' => Str::random(6).' Diamond',
            'brand' => $brand,
            'category' => 'instant',
            'type' => 'topup',
            'price' => 0,
            'selling_price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function addToCart(User $user, Product $product, int $qty = 1): CartItem
    {
        return CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $qty,
        ]);
    }

    /* ==================== KERANJANG ==================== */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/keranjang')->assertRedirect(route('login'));
        $this->postJson('/keranjang', ['product_id' => 1])->assertStatus(401);
    }

    public function test_user_can_add_product_to_cart(): void
    {
        $user = $this->user();
        $product = $this->product();

        $this->actingAs($user)
            ->postJson('/keranjang', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 2]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_merges_quantity_and_caps_at_99(): void
    {
        $user = $this->user();
        $product = $this->product(stock: 500);

        $this->actingAs($user)->postJson('/keranjang', ['product_id' => $product->id, 'quantity' => 5])->assertOk();
        $this->actingAs($user)->postJson('/keranjang', ['product_id' => $product->id, 'quantity' => 3])
            ->assertOk()
            ->assertJson(['count' => 8]);

        // Satu baris per user+produk, bukan baris baru tiap klik.
        $this->assertSame(1, CartItem::where('user_id', $user->id)->count());
        $this->assertSame(8, CartItem::where('user_id', $user->id)->first()->quantity);
    }

    public function test_adding_inactive_or_out_of_stock_product_fails(): void
    {
        $user = $this->user();

        $inactive = $this->product();
        $inactive->update(['is_active' => false]);

        $this->actingAs($user)
            ->postJson('/keranjang', ['product_id' => $inactive->id])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $empty = $this->product(stock: 0);

        $this->actingAs($user)
            ->postJson('/keranjang', ['product_id' => $empty->id])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame(0, CartItem::count());
    }

    public function test_user_can_update_and_remove_item(): void
    {
        $user = $this->user();
        $product = $this->product();
        $item = $this->addToCart($user, $product, 1);

        $this->actingAs($user)
            ->patchJson('/keranjang/item/'.$item->id, ['quantity' => 4])
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 4]);

        $this->assertSame(4, $item->fresh()->quantity);

        // Jumlah tidak boleh melebihi stok.
        $product->update(['stock' => 2]);
        $this->actingAs($user)
            ->patchJson('/keranjang/item/'.$item->id, ['quantity' => 10])
            ->assertOk();
        $this->assertSame(2, $item->fresh()->quantity);

        $this->actingAs($user)
            ->deleteJson('/keranjang/item/'.$item->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, CartItem::count());
    }

    public function test_user_cannot_touch_other_users_cart_item(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $item = $this->addToCart($owner, $this->product(), 2);

        $this->actingAs($other)
            ->patchJson('/keranjang/item/'.$item->id, ['quantity' => 1])
            ->assertJson(['success' => false]);

        $this->assertSame(2, $item->fresh()->quantity);
    }

    public function test_cart_page_renders_items_and_count_endpoint(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product(), 2);
        $this->addToCart($user, $this->product('Free Fire'), 1);

        $this->actingAs($user)
            ->get('/keranjang')
            ->assertOk()
            ->assertSee('Keranjang Saya');

        $this->actingAs($user)->getJson('/keranjang/jumlah')->assertOk()->assertJson(['count' => 3]);
    }

    /* ==================== CART STATE (REALTIME) ==================== */

    public function test_cart_state_endpoint_returns_count_quantity_and_subtotal(): void
    {
        // Endpoint hanya untuk pemilik keranjangnya, jadi dicek sebelum login.
        $this->getJson('/keranjang/state')->assertStatus(401);

        $user = $this->user();
        $first = $this->product('Mobile Legends', price: 50000);
        $second = $this->product('Free Fire', price: 20000);

        $this->addToCart($user, $first, 2);
        $this->addToCart($user, $second, 3);

        $response = $this->actingAs($user)->getJson('/keranjang/state')->assertOk();

        $response->assertJson([
            'state' => [
                'count' => 5,
                'total_qty' => 5,
                'subtotal' => 100000 + 60000,
                'unavailable' => 0,
            ],
        ]);

        $items = $response->json('state.items');
        $this->assertCount(2, $items);

        $this->assertSame([$first->id, $second->id], array_column($items, 'product_id'));
        $this->assertSame([2, 3], array_column($items, 'quantity'));
        $this->assertSame([100000, 60000], array_column($items, 'line_total'));
        $this->assertSame([10, 10], array_column($items, 'max'));
        $this->assertSame([false, false], array_column($items, 'unavailable'));

        // Keranjang orang lain tidak ikut terbaca.
        $this->actingAs($this->user())->getJson('/keranjang/state')->assertOk()->assertJson(['state' => ['count' => 0]]);
    }

    public function test_cart_state_keeps_out_of_stock_item_in_count_but_excludes_from_totals(): void
    {
        $user = $this->user();
        $available = $this->product('Mobile Legends', price: 50000);
        $soldOut = $this->product('Free Fire', price: 20000);

        $this->addToCart($user, $available, 1);
        $this->addToCart($user, $soldOut, 4);

        // Stok habis setelah item masuk keranjang: item tetap ada, tidak dihapus.
        $soldOut->update(['stock' => 0]);

        $response = $this->actingAs($user)->getJson('/keranjang/state')->assertOk();

        $response->assertJson([
            'state' => [
                'count' => 5,
                'total_qty' => 1,
                'subtotal' => 50000,
                'unavailable' => 1,
            ],
        ]);

        $items = collect($response->json('state.items'))->keyBy('product_id');
        $this->assertTrue($items[$soldOut->id]['unavailable']);
        $this->assertSame(0, $items[$soldOut->id]['max']);
        $this->assertFalse($items[$available->id]['unavailable']);

        // Halaman keranjang menandai item itu dan menonaktifkan tombol checkout.
        $this->actingAs($user)->get('/keranjang')
            ->assertOk()
            ->assertSee('data-role="cart-filled"', false)
            ->assertSee('data-role="row-out-note"', false)
            ->assertSee('cart-item-out', false)
            ->assertSee('data-role="cart-unavailable"', false)
            ->assertSee('btn-disabled', false);
    }

    public function test_every_cart_mutation_returns_fresh_state(): void
    {
        $user = $this->user();
        $product = $this->product('Mobile Legends', price: 50000);

        $this->actingAs($user)
            ->postJson('/keranjang', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['state' => ['count' => 2, 'total_qty' => 2, 'subtotal' => 100000]]);

        $item = CartItem::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->patchJson('/keranjang/item/'.$item->id, ['quantity' => 3])
            ->assertOk()
            ->assertJson([
                'count' => 3,
                'state' => [
                    'count' => 3,
                    'total_qty' => 3,
                    'subtotal' => 150000,
                    'items' => [['id' => $item->id, 'quantity' => 3, 'line_total' => 150000]],
                ],
            ]);

        $this->actingAs($user)
            ->deleteJson('/keranjang/item/'.$item->id)
            ->assertOk()
            ->assertJson([
                'count' => 0,
                'state' => ['count' => 0, 'total_qty' => 0, 'subtotal' => 0, 'unavailable' => 0, 'items' => []],
            ]);
    }

    public function test_clearing_cart_returns_empty_state(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product('Mobile Legends'), 2);
        $this->addToCart($user, $this->product('Free Fire'), 1);

        $this->actingAs($user)
            ->postJson('/keranjang/kosongkan')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'state' => ['count' => 0, 'total_qty' => 0, 'subtotal' => 0, 'items' => []],
            ]);

        $this->assertSame(0, CartItem::count());
    }

    public function test_cart_page_marks_elements_for_realtime_updates(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product('Mobile Legends', price: 50000), 2);

        $this->actingAs($user)->get('/keranjang')
            ->assertOk()
            // Badge di header desktop & hamburger.
            ->assertSee('data-role="cart-badge"', false)
            ->assertSee('window.CART_STATE_URL', false)
            // Angka & nominal di halaman keranjang.
            ->assertSee('data-role="cart-summary-qty"', false)
            ->assertSee('data-role="cart-payable-qty"', false)
            ->assertSee('data-role="cart-subtotal"', false)
            ->assertSee('data-role="cart-total"', false)
            ->assertSee('data-role="cart-checkout-btn"', false)
            ->assertSee('data-role="cart-empty"', false)
            // Satu baris per item, lengkap dengan hook qty & nominal.
            ->assertSee('data-role="qty"', false)
            ->assertSee('data-role="line-total"', false)
            ->assertSee('js-remove-form', false)
            ->assertSee('js-clear-form', false);
    }

    public function test_game_detail_and_checkout_expose_realtime_cart_count(): void
    {
        $brand = $this->brand('Mobile Legends');
        $this->product('Mobile Legends', price: 50000);
        $user = $this->user();
        $this->addToCart($user, Product::first(), 3);

        $this->actingAs($user)->get(route('games.show', $brand->name))
            ->assertOk()
            ->assertSee('data-role="cart-note"', false)
            ->assertSee('data-role="cart-summary-qty"', false)
            // Angka awal ikut state's badge, bukan placeholder.
            ->assertSee('Di keranjang:', false);

        $this->actingAs($user)->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('data-role="cart-summary-items"', false)
            ->assertSee('data-role="cart-payable-qty"', false)
            ->assertSee('data-role="cart-subtotal"', false)
            ->assertSee('refreshCartState', false);
    }

    /* ==================== CHECKOUT ==================== */

    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/checkout')->assertRedirect(route('cart.index'));
        $this->assertSame(0, Checkout::count());
    }

    public function test_checkout_creates_one_checkout_and_one_order_per_cart_item(): void
    {
        $user = $this->user();
        $mlbb = $this->product('Mobile Legends', 50000);
        $ff = $this->product('Free Fire', 30000);
        $this->addToCart($user, $mlbb, 1);
        $this->addToCart($user, $ff, 2);

        $response = $this->actingAs($user)->post('/checkout', [
            'accounts' => [
                'mobile legends' => ['customer_number' => '123456789'],
                'free fire' => ['customer_number' => '99887766'],
            ],
            'payment_method' => 'qris',
        ]);

        $checkout = Checkout::firstOrFail();
        $response->assertRedirect(route('checkout.payment', $checkout));

        // Satu charge, dua order anak.
        $this->assertSame(1, Checkout::count());
        $this->assertSame(2, Order::count());
        $this->assertSame(3, $checkout->item_count);
        $this->assertSame(50000 + 30000 * 2, $checkout->subtotal);
        $this->assertSame($checkout->subtotal, $checkout->total);

        // Keranjang dikosongkan setelah checkout berhasil.
        $this->assertSame(0, CartItem::count());

        $orders = Order::orderBy('id')->get();
        $this->assertSame(['123456789', '99887766'], $orders->pluck('customer_number')->all());
        $this->assertSame([50000, 60000], $orders->pluck('price')->map(fn ($p) => (int) $p)->all());
        $this->assertSame([1, 2], $orders->pluck('quantity')->map(fn ($q) => (int) $q)->all());
        $this->assertCount(2, $orders->pluck('checkout_id')->filter()->all());
    }

    public function test_two_items_of_same_brand_share_one_account_field(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product('Mobile Legends', 50000), 1);
        $this->addToCart($user, $this->product('Mobile Legends', 70000), 1);

        $this->actingAs($user)->post('/checkout', [
            'accounts' => ['mobile legends' => ['customer_number' => '555000111']],
        ])->assertRedirect();

        $this->assertSame(2, Order::count());
        $this->assertSame(1, Checkout::count());
        // Satu Order per item, bukan satu Order untuk dua nominal.
        $this->assertSame([50000, 70000], Order::orderBy('id')->get()->pluck('price')->map(fn ($p) => (int) $p)->all());
        $this->assertSame(['555000111', '555000111'], Order::orderBy('id')->get()->pluck('customer_number')->all());
    }

    public function test_checkout_requires_user_id_for_each_brand(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product('Mobile Legends'), 1);

        // Tidak ada input sama sekali untuk brand yang ada di keranjang.
        $this->actingAs($user)
            ->from(route('checkout.create'))
            ->post('/checkout', ['accounts' => ['free fire' => ['customer_number' => '111']]])
            ->assertRedirect(route('checkout.create'))
            ->assertSessionHasErrors('accounts.mobile legends.customer_number');

        $this->assertSame(0, Order::count());
    }

    public function test_zone_id_is_required_when_brand_demands_it(): void
    {
        $user = $this->user();
        $this->brand('Mobile Legends', requiresZone: true);
        $this->addToCart($user, $this->product('Mobile Legends'), 1);

        $this->actingAs($user)
            ->from(route('checkout.create'))
            ->post('/checkout', ['accounts' => ['mobile legends' => ['customer_number' => '12345']]])
            ->assertSessionHasErrors('accounts.mobile legends.zone_id');

        $this->actingAs($user)->post('/checkout', [
            'accounts' => ['mobile legends' => ['customer_number' => '12345', 'zone_id' => '2231']],
        ])->assertRedirect();

        $this->assertSame('2231', Order::first()->zone_id);
    }

    public function test_promo_code_reduces_total_and_is_split_across_orders(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product('Mobile Legends', 100000), 1);
        $this->addToCart($user, $this->product('Free Fire', 50000), 1);

        $this->actingAs($user)->post('/checkout', [
            'accounts' => [
                'mobile legends' => ['customer_number' => '111'],
                'free fire' => ['customer_number' => '222'],
            ],
            'promo_code' => 'JOHENI10',
        ])->assertRedirect();

        $checkout = Checkout::firstOrFail();
        $this->assertSame(150000, $checkout->subtotal);
        $this->assertSame(15000, $checkout->discount);
        $this->assertSame(135000, $checkout->total);

        // Harga order anak dijumlahkan kembali ke total checkout.
        $this->assertSame(135000, (int) Order::sum('price'));
    }

    public function test_unknown_promo_code_returns_back_with_error(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product(), 1);

        $this->actingAs($user)
            ->from(route('checkout.create'))
            ->post('/checkout', [
                'accounts' => ['mobile legends' => ['customer_number' => '111']],
                'promo_code' => 'NGGAKADA',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        // Keranjang tidak boleh hilang kalau checkout gagal.
        $this->assertSame(1, CartItem::count());
    }

    public function test_virtual_account_rejects_total_below_minimum(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product(price: 5000), 1);

        $this->actingAs($user)
            ->from(route('checkout.create'))
            ->post('/checkout', [
                'accounts' => ['mobile legends' => ['customer_number' => '111']],
                'payment_method' => 'bca_va',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());

        // Nominal yang sama diterima kalau metodenya QRIS.
        $this->actingAs($user)->post('/checkout', [
            'accounts' => ['mobile legends' => ['customer_number' => '111']],
            'payment_method' => 'qris',
        ])->assertRedirect();
        $this->assertSame(1, Order::count());
    }

    public function test_out_of_stock_item_blocks_checkout(): void
    {
        $user = $this->user();
        $product = $this->product(stock: 1);
        $this->addToCart($user, $product, 1);
        $product->update(['stock' => 0]);

        $this->actingAs($user)
            ->from(route('checkout.create'))
            ->post('/checkout', ['accounts' => ['mobile legends' => ['customer_number' => '111']]])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, CartItem::count());
    }

    public function test_checkout_pages_are_private_to_the_owner(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $this->addToCart($owner, $this->product(), 1);

        $this->actingAs($owner)->post('/checkout', [
            'accounts' => ['mobile legends' => ['customer_number' => '111']],
        ]);

        $checkout = Checkout::firstOrFail();

        $this->actingAs($other)->get(route('checkout.payment', $checkout))->assertForbidden();
        $this->actingAs($other)->getJson(route('checkout.status', $checkout))->assertForbidden();
    }

    /* ==================== SETTLEMENT ==================== */

    private function fakeDigiflazz(array $status = ['data' => ['status' => 'Pending']]): void
    {
        Http::fake(function (Request $request) use ($status) {
            return Http::response($status, 200);
        });
    }

    private function paidCheckout(User $user, int $items = 2): Checkout
    {
        for ($i = 0; $i < $items; $i++) {
            $this->addToCart($user, $this->product('Brand '.$i, 50000), 1);
        }

        $accounts = [];
        for ($i = 0; $i < $items; $i++) {
            $accounts['brand '.$i] = ['customer_number' => '10000'.$i];
        }

        $this->actingAs($user)->post('/checkout', ['accounts' => $accounts])->assertRedirect();

        return Checkout::firstOrFail();
    }

    public function test_paid_checkout_queues_one_job_and_settles_every_child(): void
    {
        Queue::fake();
        $this->fakeDigiflazz();

        $user = $this->user();
        $checkout = $this->paidCheckout($user, 2);

        app(CheckoutSettlementService::class)->markPaid($checkout);

        $checkout->refresh();
        $this->assertSame('processing', $checkout->status);

        // Satu job untuk seluruh item, bukan satu job per item.
        Queue::assertPushed(SettleCheckoutJob::class, 1);

        // afterCommit tidak memicu apa pun selama transaksi test berjalan,
        // jadi job dijalankan manual seperti yang dilakukan worker.
        $job = new SettleCheckoutJob($checkout->id, $checkout->orders()->pluck('id')->all());
        $job->handle(app(TopupSettlementService::class));

        $this->assertSame(2, Order::where('status', 'processing')->count());

        // Semua order sudah keluar dari status pending.
        $this->assertSame(0, Order::where('status', 'pending')->count());
    }

    public function test_mark_failed_cancels_pending_children(): void
    {
        $user = $this->user();
        $checkout = $this->paidCheckout($user, 2);

        app(CheckoutSettlementService::class)->markFailed($checkout, 'EXPIRED');

        $checkout->refresh();
        $this->assertSame('failed', $checkout->status);
        $this->assertSame(2, Order::where('status', 'failed')->count());
        $this->assertSame(0, Checkout::where('status', 'pending')->count());
    }

    public function test_simulation_endpoint_is_closed_when_simulation_disabled(): void
    {
        config(['services.payment.simulation' => false]);

        $user = $this->user();
        $checkout = $this->paidCheckout($user, 1);

        $this->actingAs($user)->post(route('checkout.simulate', $checkout))->assertNotFound();
        $this->assertSame('pending', $checkout->fresh()->status);
    }

    /* ==================== WEBHOOK XENDIT ==================== */

    private function sendWebhook(array $payload)
    {
        return $this->postJson('/payment/notification', $payload, [
            'x-callback-token' => 'token-test',
        ]);
    }

    /**
     * Xendit mengirim tipe webhook berbeda; reference checkout bisa muncul di
     * external_id, data.external_id, atau data.id (id invoice).
     */
    public function test_checkout_webhook_marks_paid_for_every_payload_shape(): void
    {
        config(['xendit.callback_token' => 'token-test']);

        $shapes = [
            // Invoice V2.
            fn (string $ref) => ['event' => 'invoice.paid', 'external_id' => $ref, 'status' => 'PAID'],
            // QRIS: reference di data.external_id.
            fn (string $ref) => ['event' => 'qr.payment', 'data' => ['external_id' => $ref, 'status' => 'COMPLETED']],
            // Virtual account / retail: reference + payment_status di level atas.
            fn (string $ref) => ['event' => 'fva.paid', 'external_id' => $ref, 'payment_status' => 'PAID'],
            // Hanya id invoice yang dikirim, tanpa reference sama sekali.
            fn (string $ref, string $invoiceId) => ['event' => 'invoice.paid', 'data' => ['id' => $invoiceId, 'status' => 'SETTLED']],
        ];

        foreach ($shapes as $index => $shape) {
            $this->fakeDigiflazz();

            $invoiceId = 'inv-'.$index;

            $user = $this->user();
            $checkout = $this->paidCheckout($user, 2);
            $checkout->update(['gateway_invoice_id' => $invoiceId]);

            // Bentuk payload yang hanya membawa id invoice memakai id unik di
            // iterasi ini, supaya tidak ikut mencocokkan checkout sebelumnya.
            $payload = $shape($checkout->checkout_ref, $invoiceId);

            $this->sendWebhook($payload)
                ->assertOk()
                ->assertJson(['status' => 'ok']);

            $this->assertSame('processing', $checkout->fresh()->status, 'payload #'.$index);

            // Di environment test queue-nya sync, jadi job settlement langsung
            // jalan dan semua order anak keluar dari status pending.
            $this->assertSame(0, $checkout->orders()->where('status', 'pending')->count(), 'payload #'.$index);
            $this->assertSame(2, $checkout->orders()->where('status', 'processing')->count(), 'payload #'.$index);
        }
    }

    public function test_checkout_webhook_expired_marks_failed_and_skips_other_orders(): void
    {
        Queue::fake();
        config(['xendit.callback_token' => 'token-test']);

        $user = $this->user();
        $checkout = $this->paidCheckout($user, 2);
        $stranger = Order::create([
            'order_id' => 'TUP-STRANGER',
            'buyer_sku_code' => 'sku-stranger',
            'product_name' => 'Diamond',
            'brand' => 'Mobile Legends',
            'category' => 'instant',
            'customer_number' => '999',
            'price' => 50000,
            'status' => 'pending',
        ]);

        $this->sendWebhook([
            'event' => 'invoice.expired',
            'data' => ['external_id' => $checkout->checkout_ref, 'status' => 'EXPIRED'],
        ])->assertOk();

        $this->assertSame('failed', $checkout->fresh()->status);
        $this->assertSame(2, Order::where('status', 'failed')->count());

        // Order di luar checkout tidak boleh ikut tersentuh.
        $this->assertSame('pending', $stranger->fresh()->status);
        Queue::assertNotPushed(SettleCheckoutJob::class);
    }

    public function test_webhook_with_wrong_callback_token_is_rejected(): void
    {
        config(['xendit.callback_token' => 'token-test']);

        $user = $this->user();
        $checkout = $this->paidCheckout($user, 1);

        $this->postJson('/payment/notification', [
            'event' => 'invoice.paid',
            'external_id' => $checkout->checkout_ref,
            'status' => 'PAID',
        ], ['x-callback-token' => 'token-salah'])->assertStatus(401);

        $this->assertSame('pending', $checkout->fresh()->status);
    }

    public function test_status_endpoint_reports_per_item_progress(): void
    {
        $user = $this->user();
        $checkout = $this->paidCheckout($user, 2);

        // Satu item sukses, satu masih pending.
        $first = Order::orderBy('id')->first();
        $first->update(['status' => 'success']);
        Transaction::where('order_id', $first->id)->update(['status' => 'success']);

        $this->actingAs($user)->getJson(route('checkout.status', $checkout))
            ->assertOk()
            ->assertJson(['count' => 2, 'success' => 1, 'failed' => 0])
            ->assertJsonCount(2, 'items');

        $this->assertSame('processing', $checkout->fresh()->status);

        // Setelah semua selesai, parent jadi sukses.
        Order::where('status', 'pending')->update(['status' => 'success']);
        $this->actingAs($user)->getJson(route('checkout.status', $checkout))
            ->assertJson(['status' => 'success', 'success' => 2]);
    }

    public function test_guest_cannot_reach_cart_and_checkout(): void
    {
        $this->get('/keranjang')->assertRedirect(route('login'));
        $this->get('/checkout')->assertRedirect(route('login'));
        $this->post('/checkout', [])->assertRedirect(route('login'));
    }

    /* ==================== TAMPILAN HALAMAN ==================== */

    public function test_navbar_shows_cart_link_and_badge(): void
    {
        $user = $this->user();
        $this->addToCart($user, $this->product(), 2);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee(route('cart.index'), false)
            ->assertSee('nav-cart-badge', false)
            // Tombol header disembunyikan di mobile, jadi keranjang tetap
            // bisa dibuka lewat hamburger menu.
            ->assertSee('mobile-menu__cart', false);

        // Badge ikut terisi sesuai jumlah item.
        $this->actingAs($user)->get(route('home'))->assertSee('>2</span>', false);
    }

    public function test_checkout_page_renders_account_form_per_brand(): void
    {
        $user = $this->user();
        $this->brand('Mobile Legends', requiresZone: true);
        $mlbb = $this->product('Mobile Legends');
        $this->addToCart($user, $mlbb, 1);
        $this->addToCart($user, $this->product('Free Fire'), 1);

        $this->actingAs($user)->get(route('checkout.create'))
            ->assertOk()
            // Group input mengikuti brand, bukan per item.
            ->assertSee('name="accounts[mobile legends][customer_number]"', false)
            ->assertSee('name="accounts[mobile legends][zone_id]"', false)
            ->assertSee('name="accounts[free fire][customer_number]"', false)
            // Brand tanpa zone tidak boleh memunculkan input zone.
            ->assertDontSee('name="accounts[free fire][zone_id]"', false)
            // Satu form untuk seluruh item, bukan satu form per item.
            ->assertSee('id="checkoutForm"', false)
            ->assertSee('Bayar Sekarang', false);
    }

    public function test_payment_page_lists_every_item_with_status(): void
    {
        $user = $this->user();
        $checkout = $this->paidCheckout($user, 2);

        $this->actingAs($user)->get(route('checkout.payment', $checkout))
            ->assertOk()
            ->assertSee($checkout->checkout_ref)
            ->assertSee('Status Per Item (2)', false)
            ->assertSee('data-order="', false);
    }

    public function test_game_detail_shows_add_to_cart_button(): void
    {
        $brand = $this->brand('Mobile Legends');
        $product = $this->product('Mobile Legends');

        $user = $this->user();

        $this->actingAs($user)->get(route('games.show', $brand->name))
            ->assertOk()
            ->assertSee('id="addToCartBtn"', false)
            ->assertSee('id="addToCartBtnMobile"', false)
            // Tombol beli langsung tetap ada.
            ->assertSee('Pesan Sekarang', false);
    }
}
