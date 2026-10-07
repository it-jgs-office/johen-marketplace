<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\GameAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        Brand::updateOrCreate(['name' => 'Mobile Legends'], [
            'catalog_group' => 'game',
            'requires_zone_id' => true,
            'is_active' => true,
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

    public function test_ml_lookup_uses_digiflazz_sku_and_caches_indonesia_result(): void
    {
        Http::fake(['*/transaction' => Http::response(['data' => [
            'status' => 'Sukses', 'rc' => '00', 'username' => 'Player One', 'region' => 'IDN',
        ]])]);

        $first = app(GameAccountService::class)->check('Mobile Legends', '12345678', '2001');
        $second = app(GameAccountService::class)->check('Mobile Legends', '12345678', '2001');

        $this->assertSame($first, $second);
        $this->assertTrue($first['valid']);
        $this->assertSame('Player One', $first['nickname']);
        $this->assertSame('Indonesia', $first['region']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['buyer_sku_code'] === 'usrnameml-johen'
            && $request['customer_no'] === '12345678.2001'
            && $request['sign'] === md5('test-ml-region'.'test-key'.$request['ref_id']));
    }

    public function test_ml_lookup_reads_country_and_nickname_from_sn(): void
    {
        Http::fake(['*/transaction' => Http::response(['data' => [
            'status' => 'Sukses', 'rc' => '00', 'sn' => 'Username: Player Two | Region: Malaysia',
        ]])]);

        $result = app(GameAccountService::class)->check('Mobile Legends', '87654321', '2002');

        $this->assertTrue($result['valid']);
        $this->assertSame('Player Two', $result['nickname']);
        $this->assertSame('Malaysia', $result['region']);
    }

    public function test_account_check_endpoint_returns_detected_region(): void
    {
        Http::fake(['*/transaction' => Http::response(['data' => [
            'status' => 'Sukses', 'rc' => '00', 'sn' => 'Username: Player Five | Country: IDN',
        ]])]);

        $this->postJson(route('api.account.check'), [
            'brand' => 'Mobile Legends', 'user_id' => '12345679', 'zone_id' => '2006',
        ])->assertOk()
            ->assertJsonPath('nickname', 'Player Five')
            ->assertJsonPath('region', 'Indonesia');
    }

    public function test_pending_lookup_does_not_claim_indonesia(): void
    {
        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request->data();

            return Http::response(['data' => [
                'status' => count($requests) === 1 ? 'Pending' : 'Sukses',
                'rc' => count($requests) === 1 ? '03' : '00',
                'region' => 'IDN',
            ]]);
        });

        $service = app(GameAccountService::class);
        $result = $service->check('Mobile Legends', '99999999', '2003');

        $this->assertFalse($result['checked']);
        $this->assertNull($result['region']);

        $resolved = $service->check('Mobile Legends', '99999999', '2003');
        $this->assertSame('Indonesia', $resolved['region']);
        $this->assertSame('topup', $requests[0]['cmd']);
        $this->assertSame('status', $requests[1]['cmd']);
        $this->assertSame($requests[0]['ref_id'], $requests[1]['ref_id']);
    }

    public function test_ml_page_shows_only_idn_skus_by_default(): void
    {
        $idn = $this->product('ml10-idn');
        $other = $this->product('ml10-mys');
        $checker = $this->product('usrnameml-johen');

        $this->get(route('games.show', 'Mobile Legends'))
            ->assertOk()
            ->assertSee('Cek Username &amp; Region', false)
            ->assertSee('id="accountLockNotice"', false)
            ->assertSee('Harap isi ID game terlebih dahulu.', false)
            ->assertSee('data-id="'.$idn->id.'"', false)
            ->assertDontSee('data-id="'.$other->id.'"', false)
            ->assertDontSee('data-id="'.$checker->id.'"', false);
    }

    public function test_direct_checkout_rejects_non_indonesia_ml_account(): void
    {
        $product = $this->product('ml10-idn');
        Http::fake(['*/transaction' => Http::response(['data' => [
            'status' => 'Sukses', 'rc' => '00', 'username' => 'Player Three', 'region' => 'Philippines',
        ]])]);

        $this->postJson(route('orders.store'), [
            'product_id' => $product->id,
            'customer_number' => '88888888',
            'zone_id' => '2004',
        ])->assertStatus(422)->assertJsonPath('message', 'Region Filipina belum tersedia saat ini.');

        $this->assertSame(0, Order::count());
    }

    public function test_idn_checkout_requires_zone_even_if_brand_flag_is_off(): void
    {
        Brand::where('name', 'Mobile Legends')->update(['requires_zone_id' => false]);
        $product = $this->product('ml11-idn');

        $this->postJson(route('orders.store'), [
            'product_id' => $product->id, 'customer_number' => '88888889',
        ])->assertStatus(422)->assertJsonPath('message', 'Zone ID wajib diisi untuk game ini.');

        $this->assertSame(0, Order::count());
    }

    public function test_cart_checkout_rejects_non_indonesia_ml_account(): void
    {
        $product = $this->product('ml20-idn');
        $user = User::factory()->create();
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        Http::fake(['*/transaction' => Http::response(['data' => [
            'status' => 'Sukses', 'rc' => '00', 'username' => 'Player Four', 'region' => 'MY',
        ]])]);

        $this->actingAs($user)->post(route('checkout.store'), [
            'accounts' => ['mobile legends' => ['customer_number' => '77777777', 'zone_id' => '2005']],
        ])->assertSessionHasErrors('accounts.mobile legends.zone_id');

        $this->assertSame(0, Order::count());
    }
}
