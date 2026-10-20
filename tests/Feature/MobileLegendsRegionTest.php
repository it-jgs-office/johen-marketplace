<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\CheckProvider;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\GameAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileLegendsRegionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.payment.simulation', false);
        config()->set('digiflazz.username', 'test-ml-region');
        config()->set('digiflazz.key', 'test-key');
        config()->set('services.alu.api_key', '');

        Brand::updateOrCreate(['name' => 'Mobile Legends'], [
            'catalog_group' => 'game',
            'requires_zone_id' => true,
            'is_active' => true,
        ]);

        CheckProvider::flushCache();
    }

    /** Baris provider MLBB untuk jalur gratis isan + fallback Digiflazz. */
    private function mlProvider(): CheckProvider
    {
        return CheckProvider::create([
            'brand_pattern' => 'mobile legends*',
            'label' => 'Mobile Legends',
            'free_type' => 'isan',
            'free_config' => ['game' => 'ml', 'params' => ['id' => '{user_id}', 'server' => '{zone_id}'], 'region' => 'Indonesia'],
            'fallback_free_type' => null,
            'fallback_free_config' => null,
            'digiflazz_fallback_enabled' => true,
            'digiflazz_sku' => 'usrnameml-johen',
            'enabled' => true,
            'priority' => 1,
        ]);
    }

    private function product(string $sku): Product
    {
        return Product::create([
            'buyer_sku_code' => $sku,
            'brand' => 'Mobile Legends',
            'category' => 'Games',
            'product_name' => 'Mobile Legends 10 Diamonds',
            'price' => 10000,
            'selling_price' => 11000,
            'type' => 'instant',
            'stock' => 100,
            'is_active' => true,
        ]);
    }

    public function test_free_provider_returns_and_caches_indonesia_result(): void
    {
        $this->mlProvider();
        Http::fake(['*isan*' => Http::response(['success' => true, 'name' => 'Player One'])]);

        $first = app(GameAccountService::class)->check('Mobile Legends', '12345678', '2001');
        $second = app(GameAccountService::class)->check('Mobile Legends', '12345678', '2001');

        $this->assertSame($first, $second);
        $this->assertTrue($first['valid']);
        $this->assertSame('Player One', $first['nickname']);
        $this->assertSame('Indonesia', $first['region']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'isan'));
    }

    public function test_gopay_provider_decodes_nickname_and_detects_region(): void
    {
        CheckProvider::create([
            'brand_pattern' => 'mobile legends*',
            'label' => 'Mobile Legends',
            'free_type' => 'gopay',
            'free_config' => ['code' => 'MOBILE_LEGENDS'],
            'fallback_free_type' => null,
            'fallback_free_config' => null,
            'digiflazz_fallback_enabled' => false,
            'digiflazz_sku' => null,
            'enabled' => true,
            'priority' => 1,
        ]);

        Http::fake(['*user-account*' => Http::response([
            'message' => 'Success',
            'data' => ['countryOrigin' => 'id', 'username' => 'Outrageous+Dominance'],
        ], 201)]);

        $result = app(GameAccountService::class)->check('Mobile Legends', '1114917746', '13486');

        $this->assertTrue($result['valid']);
        $this->assertSame('Outrageous Dominance', $result['nickname']);
        $this->assertSame('Indonesia', $result['region']);
        Http::assertSent(fn ($request) => $request['code'] === 'MOBILE_LEGENDS');
    }

    public function test_gopay_provider_rejects_echoed_id_as_nickname(): void
    {
        CheckProvider::create([
            'brand_pattern' => 'mobile legends*',
            'label' => 'Mobile Legends',
            'free_type' => 'gopay',
            'free_config' => ['code' => 'MOBILE_LEGENDS'],
            'fallback_free_type' => null,
            'fallback_free_config' => null,
            'digiflazz_fallback_enabled' => false,
            'digiflazz_sku' => null,
            'enabled' => true,
            'priority' => 1,
        ]);

        Http::fake(['*user-account*' => Http::response([
            'message' => 'Success',
            'data' => ['countryOrigin' => '', 'username' => '1114917746'],
        ], 201)]);

        $result = app(GameAccountService::class)->check('Mobile Legends', '1114917746', '13486');

        $this->assertFalse($result['valid']);
        $this->assertNull($result['nickname']);
    }

    public function test_network_failure_is_neutral_and_not_cached(): void
    {
        $this->mlProvider();
        Http::fake(['*isan*' => Http::response('', 500)]);

        $result = app(GameAccountService::class)->check('Mobile Legends', '99999999', '2003');

        $this->assertFalse($result['checked']);
        $this->assertNull($result['region']);
    }

    public function test_digiflazz_fallback_only_used_when_paid_allowed_at_checkout(): void
    {
        $this->mlProvider();
        Http::fake([
            '*isan*' => Http::response(['success' => false], 200),
            '*/transaction' => Http::response(['data' => [
                'status' => 'Sukses', 'rc' => '00', 'sn' => 'Username: Player Two | Region: Malaysia',
            ]]),
        ]);

        // Indikator real-time (tanpa fallback berbayar) tidak memanggil Digiflazz.
        $neutral = app(GameAccountService::class)->check('Mobile Legends', '87654321', '2002');
        $this->assertFalse($neutral['valid']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'transaction'));

        // Jalur checkout (fallback berbayar diizinkan) memakai Digiflazz.
        Cache::flush();
        $paid = app(GameAccountService::class)->check('Mobile Legends', '87654321', '2002', true);
        $this->assertTrue($paid['valid']);
        $this->assertSame('Player Two', $paid['nickname']);
        $this->assertSame('Malaysia', $paid['region']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'transaction')
            && $request['buyer_sku_code'] === 'usrnameml-johen');
    }

    public function test_account_check_endpoint_returns_detected_region(): void
    {
        $this->mlProvider();
        Http::fake(['*isan*' => Http::response(['success' => true, 'name' => 'Player Five'])]);

        $this->postJson(route('api.account.check'), [
            'brand' => 'Mobile Legends', 'user_id' => '12345679', 'zone_id' => '2006',
        ])->assertOk()
            ->assertJsonPath('nickname', 'Player Five')
            ->assertJsonPath('region', 'Indonesia');
    }

    public function test_ml_page_shows_only_idn_skus_by_default(): void
    {
        $idn = $this->product('ml10-idn');
        $other = $this->product('ml10-mys');
        $checker = $this->product('usrnameml-johen');

        $this->get(route('games.show', 'Mobile Legends'))
            ->assertOk()
            ->assertSee('id="accountLockNotice"', false)
            ->assertSee('data-id="'.$idn->id.'"', false)
            ->assertDontSee('data-id="'.$other->id.'"', false)
            ->assertDontSee('data-id="'.$checker->id.'"', false);
    }

    public function test_direct_checkout_rejects_non_indonesia_ml_account(): void
    {
        $this->mlProvider();
        $product = $this->product('ml10-idn');
        Http::fake([
            '*isan*' => Http::response(['success' => false], 200),
            '*/transaction' => Http::response(['data' => [
                'status' => 'Sukses', 'rc' => '00', 'username' => 'Player Three', 'region' => 'Philippines',
            ]]),
        ]);

        $this->postJson(route('orders.store'), [
            'product_id' => $product->id,
            'customer_number' => '88888888',
            'zone_id' => '2004',
            'email' => 'buyer@example.com',
        ])->assertStatus(422)->assertJsonPath('message', 'Region Filipina belum tersedia saat ini.');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_allows_when_region_unverifiable(): void
    {
        $this->mlProvider();
        $product = $this->product('ml10-idn');
        // Semua provider gagal: checkout tetap diizinkan (gating dilonggarkan).
        Http::fake([
            '*isan*' => Http::response('', 500),
            '*/transaction' => Http::response(['data' => ['status' => 'Pending', 'rc' => '03']]),
        ]);

        $this->postJson(route('orders.store'), [
            'product_id' => $product->id,
            'customer_number' => '88888880',
            'zone_id' => '2007',
            'email' => 'buyer@example.com',
        ])->assertStatus(200);
    }

    public function test_idn_checkout_requires_zone_even_if_brand_flag_is_off(): void
    {
        Brand::where('name', 'Mobile Legends')->update(['requires_zone_id' => false]);
        $product = $this->product('ml11-idn');

        $this->postJson(route('orders.store'), [
            'product_id' => $product->id, 'customer_number' => '88888889',
            'email' => 'buyer@example.com',
        ])->assertStatus(422)->assertJsonPath('message', 'Zone ID wajib diisi untuk game ini.');

        $this->assertSame(0, Order::count());
    }

    public function test_cart_checkout_rejects_non_indonesia_ml_account(): void
    {
        $this->mlProvider();
        $product = $this->product('ml20-idn');
        $user = User::factory()->create();
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        Http::fake([
            '*isan*' => Http::response(['success' => false], 200),
            '*/transaction' => Http::response(['data' => [
                'status' => 'Sukses', 'rc' => '00', 'username' => 'Player Four', 'region' => 'MY',
            ]]),
        ]);

        $this->actingAs($user)->post(route('checkout.store'), [
            'accounts' => ['mobile legends' => ['customer_number' => '77777777', 'zone_id' => '2005']],
            'email' => 'buyer@example.com',
        ])->assertSessionHasErrors('accounts.mobile legends.zone_id');

        $this->assertSame(0, Order::count());
    }
}
