<?php

namespace Tests\Feature;

use App\Models\GachaPrize;
use App\Models\GachaSpin;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GachaVoucherTest extends TestCase
{
    use RefreshDatabase;

    private function prize(array $overrides = []): GachaPrize
    {
        return GachaPrize::create(array_merge([
            'label' => 'Diskon 10%',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_spend' => 10000,
            'weight' => 10,
            'quota' => 5,
            'validity_days' => 30,
            'color' => '#7c3aed',
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'buyer_sku_code' => 'TEST-SKU-'.strtoupper(Str::random(8)),
            'product_name' => 'Test Top Up',
            'brand' => 'Mobile Legends',
            'category' => 'instant',
            'type' => 'topup',
            'price' => 0,
            'selling_price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ], $overrides));
    }

    public function test_gacha_config_exposes_active_prizes(): void
    {
        $this->prize(['label' => 'Diskon 5%', 'weight' => 30, 'sort_order' => 0]);
        $this->prize(['label' => 'Diskon 20%', 'weight' => 10, 'sort_order' => 1]);
        $this->prize(['label' => 'Habis', 'quota' => 0, 'weight' => 50, 'sort_order' => 2]);
        $this->prize(['label' => 'Mati', 'is_active' => false, 'weight' => 99, 'sort_order' => 3]);

        $response = $this->getJson('/api/gacha');

        $response->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('daily_limit', 1)
            ->assertJsonPath('remaining', 1)
            ->assertJsonCount(2, 'prizes');

        $this->assertSame(['Diskon 5%', 'Diskon 20%'], array_column($response->json('prizes'), 'label'));
    }

    public function test_gacha_config_disabled_when_no_prizes(): void
    {
        $this->getJson('/api/gacha')->assertOk()->assertJsonPath('enabled', false);
    }

    public function test_spin_issues_voucher_with_unique_code(): void
    {
        $prize = $this->prize();

        $response = $this->postJson('/api/gacha/spin');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('prize_id', $prize->id)
            ->assertJsonPath('remaining', 0);

        $code = $response->json('code');
        $this->assertMatchesRegularExpression('/^JHN-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);

        $voucher = Voucher::where('code', $code)->firstOrFail();
        $this->assertNull($voucher->user_id);
        $this->assertSame($prize->id, $voucher->gacha_prize_id);
        $this->assertSame(1, $voucher->quota);
        $this->assertSame(0, $voucher->used_count);
        $this->assertTrue($voucher->expires_at->isFuture());

        $this->assertDatabaseHas('gacha_spins', [
            'gacha_prize_id' => $prize->id,
            'voucher_id' => $voucher->id,
        ]);

        $this->assertSame(4, $prize->fresh()->quota);
        $this->assertSame(1, $prize->fresh()->won_count);
    }

    public function test_spin_is_limited_to_once_per_day_for_guests(): void
    {
        $this->prize();

        $this->postJson('/api/gacha/spin')->assertOk();

        $this->postJson('/api/gacha/spin')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(1, GachaSpin::count());
    }

    public function test_guest_with_own_identity_is_not_blocked_by_same_ip(): void
    {
        $this->prize();

        $this->withSession(['gacha_guest_id' => 'guest-one-identity'])->postJson('/api/gacha/spin')->assertOk();

        // Pengunjung lain memakai IP publik yang sama tapi punya guest_id sendiri
        // tidak boleh ikut terblokir.
        $this->withSession(['gacha_guest_id' => 'guest-two-identity'])->postJson('/api/gacha/spin')->assertOk();

        $this->assertSame(2, GachaSpin::count());
        $this->assertDatabaseHas('gacha_spins', ['guest_id' => 'guest-one-identity']);
        $this->assertDatabaseHas('gacha_spins', ['guest_id' => 'guest-two-identity']);
    }

    public function test_spin_is_limited_to_once_per_day_for_users(): void
    {
        $this->prize();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/gacha/spin')->assertOk();
        $this->actingAs($user)->postJson('/api/gacha/spin')->assertStatus(422);

        $this->assertSame(1, GachaSpin::count());
        $this->assertSame($user->id, GachaSpin::first()->user_id);
    }

    public function test_spin_can_be_blocked_by_daily_limit_setting(): void
    {
        $this->prize();
        SiteSetting::set('gacha_daily_limit', '0');

        $this->postJson('/api/gacha/spin')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, GachaSpin::count());
    }

    public function test_spin_skips_exhausted_prize(): void
    {
        $this->prize(['label' => 'Habis', 'quota' => 0, 'weight' => 1]);
        $available = $this->prize(['label' => 'Aman', 'weight' => 1, 'sort_order' => 1]);

        $this->postJson('/api/gacha/spin')
            ->assertOk()
            ->assertJsonPath('prize_id', $available->id);
    }

    public function test_guest_voucher_can_be_claimed_by_a_user(): void
    {
        $this->prize();
        $code = $this->postJson('/api/gacha/spin')->json('code');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/vouchers')
            ->post('/vouchers/claim', ['code' => $code])
            ->assertRedirect('/vouchers')
            ->assertSessionHas('success');

        $this->assertSame($user->id, Voucher::where('code', $code)->first()->user_id);
    }

    public function test_claimed_voucher_cannot_be_claimed_again(): void
    {
        $this->prize();
        $code = $this->postJson('/api/gacha/spin')->json('code');
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->post('/vouchers/claim', ['code' => $code]);
        $this->actingAs($second)
            ->from('/vouchers')
            ->post('/vouchers/claim', ['code' => $code])
            ->assertSessionHas('error');

        $this->assertSame($first->id, Voucher::where('code', $code)->first()->user_id);
    }

    public function test_voucher_index_lists_only_own_vouchers(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = Voucher::create(['code' => 'MINE-0001', 'user_id' => $user->id, 'discount_type' => 'percent', 'discount_value' => 10, 'quota' => 1]);
        Voucher::create(['code' => 'THEIRS-001', 'user_id' => $other->id, 'discount_type' => 'percent', 'discount_value' => 10, 'quota' => 1]);

        $this->actingAs($user)
            ->get('/vouchers')
            ->assertOk()
            ->assertSee('MINE-0001')
            ->assertDontSee('THEIRS-001');

        $this->assertCount(1, [$mine]);
    }

    public function test_validate_endpoint_reports_discount(): void
    {
        $user = User::factory()->create();
        Voucher::create([
            'code' => 'JHN-TEST-0001',
            'user_id' => $user->id,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_spend' => 10000,
            'quota' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        $this->actingAs($user)
            ->postJson('/api/vouchers/validate', ['code' => 'jhn-test-0001', 'subtotal' => 100000])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('discount', 10000)
            ->assertJsonPath('total', 90000);
    }

    public function test_validate_endpoint_rejects_unknown_and_expired_codes(): void
    {
        $user = User::factory()->create();
        Voucher::create([
            'code' => 'JHN-EXPIRED1',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'quota' => 1,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/vouchers/validate', ['code' => 'JHN-EXPIRED1', 'subtotal' => 100000])
            ->assertStatus(422)
            ->assertJsonPath('valid', false);

        $this->actingAs($user)
            ->postJson('/api/vouchers/validate', ['code' => 'NGADA-0000', 'subtotal' => 100000])
            ->assertStatus(422)
            ->assertJsonPath('valid', false);
    }

    public function test_validate_endpoint_enforces_minimum_spend(): void
    {
        $user = User::factory()->create();
        Voucher::create([
            'code' => 'JHN-MINSEND1',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_spend' => 500000,
            'quota' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        $this->actingAs($user)
            ->postJson('/api/vouchers/validate', ['code' => 'JHN-MINSEND1', 'subtotal' => 10000])
            ->assertStatus(422)
            ->assertJsonPath('valid', false);
    }

    public function test_order_uses_voucher_and_marks_it_used(): void
    {
        $this->prize();
        $user = User::factory()->create();
        $voucher = Voucher::create([
            'code' => 'JHN-ORDER001',
            'user_id' => $user->id,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'quota' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        $product = $this->product();

        $response = $this->actingAs($user)->postJson('/orders', [
            'product_id' => $product->id,
            'customer_number' => '12345',
            'promo_code' => 'JHN-ORDER001',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $order = \App\Models\Order::latest('id')->first();
        $this->assertSame(90000.0, (float) $order->price);
        $this->assertSame($voucher->id, $order->voucher_id);
        $this->assertSame(1, $voucher->fresh()->used_count);
        $this->assertSame($order->id, $voucher->fresh()->order_id);
    }

    public function test_order_rejects_exhausted_voucher(): void
    {
        $user = User::factory()->create();
        $voucher = Voucher::create([
            'code' => 'JHN-ONCEUSE1',
            'user_id' => $user->id,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'quota' => 1,
            'expires_at' => now()->addDays(5),
        ]);
        $voucher->forceFill(['used_count' => 1])->save();

        $product = $this->product();

        $this->actingAs($user)->postJson('/orders', [
            'product_id' => $product->id,
            'customer_number' => '12345',
            'promo_code' => 'JHN-ONCEUSE1',
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(0, \App\Models\Order::count());
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_order_rejects_voucher_below_minimum_spend(): void
    {
        $user = User::factory()->create();
        $voucher = Voucher::create([
            'code' => 'JHN-MINBUY01',
            'user_id' => $user->id,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_spend' => 500000,
            'quota' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        $product = $this->product(['selling_price' => 20000]);

        $this->actingAs($user)->postJson('/orders', [
            'product_id' => $product->id,
            'customer_number' => '12345',
            'promo_code' => 'JHN-MINBUY01',
        ])->assertStatus(422);

        $this->assertSame(0, \App\Models\Order::count(), 'Order gagal tidak boleh tetap dibuat.');
        $this->assertSame(0, $voucher->fresh()->used_count, 'Voucher tidak boleh terpakai.');
    }

    public function test_order_rejects_unknown_code(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->postJson('/orders', [
            'product_id' => $product->id,
            'customer_number' => '12345',
            'promo_code' => 'JHN-NOT-EXIST',
        ])->assertStatus(422);

        $this->assertSame(0, \App\Models\Order::count());
    }

    public function test_release_discounts_returns_voucher_usage(): void
    {
        $voucher = Voucher::create([
            'code' => 'JHN-RELEASE1',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'quota' => 1,
            'used_count' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        $product = $this->product(['selling_price' => 50000]);

        $order = \App\Models\Order::create([
            'order_id' => 'TUP-TESTREL01',
            'buyer_sku_code' => $product->buyer_sku_code,
            'customer_number' => '1',
            'product_name' => $product->product_name,
            'brand' => $product->brand,
            'category' => $product->category,
            'price' => 45000,
            'quantity' => 1,
            'status' => 'pending',
            'voucher_id' => $voucher->id,
        ]);

        $order->releaseDiscounts();

        $this->assertNull($order->fresh()->voucher_id);
        $this->assertSame(0, $voucher->fresh()->used_count);
        $this->assertNull($voucher->fresh()->order_id);
        $this->assertNull($voucher->fresh()->used_at);

        $order->releaseDiscounts();
        $this->assertSame(0, $voucher->fresh()->used_count, 'Pemanggilan berulang tidak boleh menggandakan jatah.');
    }

    public function test_admin_can_manage_prizes_and_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/gacha-prizes')
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->postJson('/admin/gacha-prizes', [
                'label' => 'Diskon 15%',
                'discount_type' => 'percent',
                'discount_value' => 15,
                'min_spend' => 0,
                'weight' => 8,
                'validity_days' => 30,
                'color' => '#ff0000',
                'is_active' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $prize = GachaPrize::where('label', 'Diskon 15%')->firstOrFail();
        $this->assertSame('15%', $prize->value_label);

        $this->actingAs($admin, 'admin')
            ->postJson('/admin/gacha-prizes/settings', ['gacha_enabled' => '0', 'gacha_daily_limit' => 3])
            ->assertOk()
            ->assertJsonPath('enabled', false)
            ->assertJsonPath('daily_limit', 3);

        $this->assertSame('0', SiteSetting::get('gacha_enabled'));
        $this->assertSame('3', SiteSetting::get('gacha_daily_limit'));

        $this->actingAs($admin, 'admin')
            ->get('/admin/vouchers')
            ->assertOk();
    }

    public function test_admin_rejects_percent_over_hundred(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/admin/gacha-prizes', [
                'label' => 'Kelewat',
                'discount_type' => 'percent',
                'discount_value' => 250,
                'weight' => 5,
                'validity_days' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('discount_value');

        $this->assertSame(0, GachaPrize::count());
    }

    public function test_admin_can_issue_manual_voucher(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->postJson('/admin/vouchers', [
                'discount_type' => 'fixed',
                'discount_value' => 15000,
                'quota' => 3,
                'validity_days' => 60,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $voucher = Voucher::where('source', Voucher::SOURCE_MANUAL)->firstOrFail();
        $this->assertSame('Rp15.000', $voucher->value_label);
        $this->assertNull($voucher->user_id);
        $this->assertTrue($voucher->expires_at->isFuture());
    }

    public function test_admin_cannot_issue_duplicate_code(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Voucher::create(['code' => 'JHN-DUP0001', 'discount_type' => 'percent', 'discount_value' => 5, 'quota' => 1]);

        $this->actingAs($admin, 'admin')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/admin/vouchers', [
                'code' => 'jhn-dup0001',
                'discount_type' => 'percent',
                'discount_value' => 5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_admin_can_edit_voucher(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $voucher = Voucher::create([
            'code' => 'JHN-EDIT0001',
            'discount_type' => 'percent',
            'discount_value' => 5,
            'quota' => 1,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin, 'admin')
            ->putJson('/admin/vouchers/'.$voucher->id, [
                'code' => 'jhn-edit0002',
                'label' => 'Koreksi admin',
                'discount_type' => 'fixed',
                'discount_value' => 25000,
                'min_spend' => 100000,
                'quota' => 4,
                'validity_days' => 60,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'JHN-EDIT0002');

        $voucher->refresh();
        $this->assertSame('JHN-EDIT0002', $voucher->code);
        $this->assertSame('fixed', $voucher->discount_type);
        $this->assertSame(25000.0, (float) $voucher->discount_value);
        $this->assertSame(4, $voucher->quota);
        $this->assertSame('Koreksi admin', $voucher->label);
        $this->assertTrue($voucher->expires_at->isAfter(now()));
    }

    public function test_admin_edit_keeps_code_when_left_unchanged(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $voucher = Voucher::create([
            'code' => 'JHN-KEEP0001',
            'discount_type' => 'percent',
            'discount_value' => 5,
            'quota' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->putJson('/admin/vouchers/'.$voucher->id, [
                'code' => 'JHN-KEEP0001',
                'discount_type' => 'percent',
                'discount_value' => 8,
            ])
            ->assertOk();

        $this->assertSame('JHN-KEEP0001', $voucher->fresh()->code);
        $this->assertSame(8.0, (float) $voucher->fresh()->discount_value);
    }

    public function test_widget_is_hidden_when_gacha_disabled(): void
    {
        $this->prize();
        SiteSetting::set('gacha_enabled', '0');

        $this->get('/')->assertOk()->assertDontSee('id="gc-fab"', false);
    }

    public function test_widget_is_rendered_with_fab_on_the_left(): void
    {
        $this->prize();

        $this->get('/')
            ->assertOk()
            ->assertSee('id="gc-fab"', false)
            ->assertSee('#gc-overlay:not(.active)', false)
            ->assertSee('#lc-overlay:not(.active)', false)
            ->assertSee('id="gc-modal" class="gc-modal" role="dialog" aria-modal="true" aria-labelledby="gcTitle" aria-hidden="true"', false)
            ->assertSee('id="lc-popup" class="lc-popup" role="dialog" aria-modal="true" aria-label="Live Chat" aria-hidden="true"', false)
            ->assertSee('css/gacha.css', false)
            ->assertSee('js/gacha.js', false);
    }
}
