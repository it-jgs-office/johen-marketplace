<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use App\Services\DigiflazzService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DigiflazzGameSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('digiflazz.username', 'test-game-sync');
        config()->set('digiflazz.key', 'test-key');
        config()->set('digiflazz.price_list_refresh_cooldown_seconds', 0);
    }

    private function item(string $sku, string $brand, string $category = 'Games'): array
    {
        return [
            'buyer_sku_code' => $sku,
            'brand' => $brand,
            'category' => $category,
            'product_name' => "{$brand} 10",
            'price' => 10000,
            'type' => 'Umum',
            'buyer_product_status' => true,
            'seller_product_status' => true,
            'unlimited_stock' => true,
            'stock' => 0,
        ];
    }

    public function test_sync_requests_only_games_and_creates_missing_game_brands(): void
    {
        $existing = Brand::create([
            'name' => 'Mobile Legends',
            'category' => 'moba',
            'catalog_group' => 'game',
            'description' => 'Pengaturan admin',
        ]);
        Brand::updateOrCreate(['name' => 'TELKOMSEL'], ['catalog_group' => 'pulsa']);

        Http::fake(['*/price-list' => Http::response(['data' => [
            array_merge($this->item('ML10', 'MOBILE LEGENDS'), ['type' => 'Indonesia', 'buyer_sku_code' => 'ml10-idn']),
            $this->item('usrnameml-johen', 'MOBILE LEGENDS'),
            $this->item('SF10', 'Starfield'),
            $this->item('P10', 'TELKOMSEL', 'Pulsa'),
            $this->item('BAD10', 'TELKOMSEL'),
        ]])]);

        $result = app(DigiflazzService::class)->syncProducts(true);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['count']);
        $this->assertSame(1, $result['brands_created']);
        $ml = Product::where('buyer_sku_code', 'ml10-idn')->firstOrFail();
        $this->assertSame('Mobile Legends', $ml->brand);
        $this->assertSame('instant', $ml->type);
        $this->assertSame('ID', $ml->region);
        $this->assertSame(9999, $ml->stock);
        $this->assertSame('Starfield', Product::where('buyer_sku_code', 'SF10')->value('brand'));
        $this->assertFalse(Product::whereIn('buyer_sku_code', ['P10', 'BAD10', 'usrnameml-johen'])->exists());
        $this->assertSame('Pengaturan admin', $existing->fresh()->description);
        $this->assertSame('game', Brand::where('name', 'Starfield')->value('catalog_group'));

        Http::assertSent(fn ($request) => $request->url() === config('digiflazz.base_url').'/price-list'
            && $request['cmd'] === 'prepaid'
            && $request['category'] === 'Games');
    }

    public function test_admin_manual_sync_bypasses_cached_price_list(): void
    {
        $cacheKey = 'digiflazz_pricelist_games_'.md5('test-game-sync');
        Cache::put($cacheKey, [$this->item('OLD-CACHED', 'Old Cached Game')], now()->addHour());

        Http::fake(['*/price-list' => Http::response([
            'data' => [$this->item('NEW-FRESH', 'New Fresh Game')],
        ])]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.sync'))
            ->assertRedirect(route('admin.products'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['buyer_sku_code' => 'NEW-FRESH']);
        $this->assertDatabaseMissing('products', ['buyer_sku_code' => 'OLD-CACHED']);
        Http::assertSentCount(1);
    }

    public function test_failed_force_refresh_keeps_valid_cache_and_reports_api_message(): void
    {
        $cacheKey = 'digiflazz_pricelist_games_'.md5('test-game-sync');
        $cached = [$this->item('SAFE-CACHED', 'Safe Cached Game')];
        Cache::put($cacheKey, $cached, now()->addHour());

        Http::fake(['*/price-list' => Http::response([
            'data' => [
                'rc' => '83',
                'message' => 'Anda telah mencapai limitasi pengecekan pricelist.',
            ],
        ])]);

        $result = app(DigiflazzService::class)->syncProducts(true);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('limitasi pengecekan pricelist', $result['message']);
        $this->assertSame($cached, Cache::get($cacheKey));
        $this->assertDatabaseMissing('products', ['buyer_sku_code' => 'SAFE-CACHED']);
    }

    public function test_force_refresh_respects_local_cooldown_before_calling_api(): void
    {
        config()->set('digiflazz.price_list_refresh_cooldown_seconds', 120);
        Cache::put(
            'digiflazz_pricelist_force_until_'.md5('test-game-sync'),
            now()->addSeconds(60)->getTimestamp(),
            now()->addSeconds(60)
        );

        Http::fake();

        $result = app(DigiflazzService::class)->syncProducts(true);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Tunggu', $result['message']);
        Http::assertNothingSent();
    }

    public function test_sync_deactivates_only_missing_game_products(): void
    {
        Brand::create(['name' => 'Old Game', 'catalog_group' => 'game']);
        Brand::updateOrCreate(['name' => 'TELKOMSEL'], ['catalog_group' => 'pulsa']);
        $game = Product::create([
            'buyer_sku_code' => 'OLD-GAME', 'brand' => 'Old Game', 'category' => 'Games',
            'product_name' => 'Old Game 10', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'Umum', 'stock' => 10, 'is_active' => true,
        ]);
        $pulsa = Product::create([
            'buyer_sku_code' => 'OLD-PULSA', 'brand' => 'TELKOMSEL', 'category' => 'Pulsa',
            'product_name' => 'Pulsa 10', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'Umum', 'stock' => 10, 'is_active' => true,
        ]);

        Http::fake(['*/price-list' => Http::response(['data' => [$this->item('NEW-GAME', 'New Game')]])]);

        $result = app(DigiflazzService::class)->syncProducts(true);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['deactivated']);
        $this->assertFalse($game->fresh()->is_active);
        $this->assertTrue($pulsa->fresh()->is_active);
    }

    public function test_sync_archives_legacy_game_product_even_with_old_category(): void
    {
        Brand::create(['name' => 'Mobile Legends', 'catalog_group' => 'game']);
        $legacy = Product::create([
            'buyer_sku_code' => 'JG-ML232', 'brand' => 'Mobile Legends', 'category' => 'moba',
            'product_name' => 'Legacy', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'instant', 'stock' => 10, 'is_active' => true,
        ]);
        Http::fake(['*/price-list' => Http::response(['data' => [$this->item('ml10-idn', 'MOBILE LEGENDS')]])]);

        app(DigiflazzService::class)->syncProducts(true);

        $this->assertFalse($legacy->fresh()->is_active);
    }

    public function test_admin_topup_lists_only_games_then_opens_its_product_catalog(): void
    {
        $mobileLegends = Brand::create(['name' => 'Mobile Legends', 'catalog_group' => 'game']);
        Brand::updateOrCreate(['name' => 'TELKOMSEL'], ['catalog_group' => 'pulsa']);
        $game = Product::create([
            'buyer_sku_code' => 'ml10-idn', 'brand' => 'Mobile Legends', 'category' => 'Games',
            'product_name' => 'ML 10', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'instant', 'stock' => 9999, 'is_active' => true,
        ]);
        Product::create([
            'buyer_sku_code' => 'old-game', 'brand' => 'Mobile Legends', 'category' => 'Games',
            'product_name' => 'Old ML', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'instant', 'stock' => 0, 'is_active' => false,
        ]);
        Product::create([
            'buyer_sku_code' => 'tel10', 'brand' => 'TELKOMSEL', 'category' => 'Games',
            'product_name' => 'Telkomsel 10', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'instant', 'stock' => 10, 'is_active' => true,
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin, 'admin')->get(route('admin.products'))
            ->assertOk()
            ->assertSee('Top Up')
            ->assertSee('Mobile Legends')
            ->assertSee(route('admin.products.game', $mobileLegends), false)
            ->assertSee('Edit')
            ->assertSee('<th class="text-left">Produk Aktif</th>', false)
            ->assertSee('<th class="text-left">Aksi</th>', false)
            ->assertSee('justify-start gap-2', false)
            ->assertDontSee('TELKOMSEL')
            ->assertSee('1 produk aktif');

        $this->actingAs($admin, 'admin')->get(route('admin.products.game', $mobileLegends))
            ->assertOk()
            ->assertSee('ml10-idn')
            ->assertSee('Atur Markup')
            ->assertSee('Komisi')
            ->assertSee('+Rp 1.000', false)
            ->assertSee('fa-pencil', false)
            ->assertSee('Rp 11.000')
            ->assertDontSee('old-game');

        $this->actingAs($admin, 'admin')->get(route('admin.products.game', ['brand' => $mobileLegends, 'status' => 'all']))
            ->assertOk()
            ->assertSee('old-game');

        $this->actingAs($admin, 'admin')->get(route('admin.brands.edit', $mobileLegends))
            ->assertOk()
            ->assertSee('Edit Game')
            ->assertSee('Gambar Game');
        $this->assertSame(9999, $game->stock);
    }

    public function test_frontend_uses_the_same_active_game_catalog_as_admin(): void
    {
        $game = Brand::create([
            'name' => 'Game Frontend Aktif',
            'catalog_group' => 'game',
            'is_active' => true,
        ]);
        $emptyGame = Brand::create([
            'name' => 'Game Tanpa Produk',
            'catalog_group' => 'game',
            'is_active' => true,
        ]);
        $pulsa = Brand::create([
            'name' => 'Pulsa Tidak Tampil',
            'catalog_group' => 'pulsa',
            'is_active' => true,
        ]);
        $inactive = Brand::create([
            'name' => 'Game Nonaktif',
            'catalog_group' => 'game',
            'is_active' => false,
        ]);
        $product = Product::create([
            'buyer_sku_code' => 'game-front-10', 'brand' => $game->name, 'category' => 'Games',
            'product_name' => 'Game Frontend 10', 'price' => 10000, 'selling_price' => 11500,
            'type' => 'instant', 'stock' => 9999, 'is_active' => true,
        ]);
        Product::create([
            'buyer_sku_code' => 'pulsa-front-10', 'brand' => $pulsa->name, 'category' => 'Pulsa',
            'product_name' => 'Pulsa 10', 'price' => 10000, 'selling_price' => 11500,
            'type' => 'instant', 'stock' => 9999, 'is_active' => true,
        ]);
        Product::create([
            'buyer_sku_code' => 'game-off-10', 'brand' => $inactive->name, 'category' => 'Games',
            'product_name' => 'Game Nonaktif 10', 'price' => 10000, 'selling_price' => 11500,
            'type' => 'instant', 'stock' => 9999, 'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($game->name)
            ->assertSee('class="game-card-search-data"', false)
            ->assertDontSee('class="game-card-info"', false)
            ->assertDontSee($emptyGame->name)
            ->assertDontSee($pulsa->name)
            ->assertDontSee($inactive->name);

        $this->getJson(route('api.products', ['brand' => $game->name]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $product->id);
        $this->getJson(route('api.products', ['brand' => $pulsa->name]))
            ->assertOk()
            ->assertJsonCount(0);
        $this->getJson(route('api.brands.search', ['q' => 'Frontend']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.brand', $game->name);

        $this->get(route('games.show', $game))->assertOk()->assertSee($product->product_name);
        $this->get(route('games.show', $pulsa))->assertNotFound();
    }

    public function test_admin_can_edit_only_selling_price_and_sync_preserves_override(): void
    {
        Brand::create(['name' => 'Mobile Legends', 'catalog_group' => 'game']);
        $product = Product::create([
            'buyer_sku_code' => 'ml10-idn', 'brand' => 'Mobile Legends', 'category' => 'Games',
            'product_name' => 'ML 10', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'instant', 'stock' => 9999, 'is_active' => true,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.products.selling-price', $product), ['selling_price' => 15500])
            ->assertOk()
            ->assertJsonPath('message', 'Harga jual berhasil diperbarui');

        $this->assertDatabaseHas('products', [
            'id' => $product->id, 'selling_price' => 15500, 'selling_price_override' => 15500,
        ]);

        Http::fake(['*/price-list' => Http::response(['data' => [$this->item('ml10-idn', 'MOBILE LEGENDS')]])]);
        app(DigiflazzService::class)->syncProducts(true);

        $this->assertEquals(15500, $product->fresh()->selling_price);
        $this->assertFalse(Route::has('admin.products.edit'));
        $this->assertFalse(Route::has('admin.products.update'));
        $this->assertFalse(Route::has('admin.products.destroy'));
        $this->assertFalse(Route::has('admin.products.stock'));
        $this->assertTrue(Route::has('admin.products.selling-price'));
    }

    public function test_admin_can_apply_rupiah_or_percentage_markup_to_all_game_products(): void
    {
        $brand = Brand::create(['name' => 'Mobile Legends', 'catalog_group' => 'game']);
        $first = Product::create([
            'buyer_sku_code' => 'ml10-idn', 'brand' => 'Mobile Legends', 'category' => 'Games',
            'product_name' => 'ML 10', 'price' => 10000, 'selling_price' => 11000,
            'type' => 'instant', 'stock' => 9999, 'is_active' => true,
        ]);
        $second = Product::create([
            'buyer_sku_code' => 'ml20-idn', 'brand' => 'Mobile Legends', 'category' => 'Games',
            'product_name' => 'ML 20', 'price' => 20000, 'selling_price' => 22000,
            'type' => 'instant', 'stock' => 9999, 'is_active' => true,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.products.markup', $brand), [
                'mode' => 'rupiah',
                'value' => 1500,
            ])
            ->assertOk()
            ->assertJsonPath('updated', 2)
            ->assertJsonPath('prices.'.$first->id, 11500);

        $this->assertDatabaseHas('products', [
            'id' => $first->id,
            'selling_price' => 11500,
            'selling_markup_type' => 'rupiah',
            'selling_markup_value' => 1500,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $second->id,
            'selling_price' => 21500,
            'selling_markup_type' => 'rupiah',
            'selling_markup_value' => 1500,
        ]);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.products.markup', $brand), [
                'mode' => 'persentase',
                'value' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('updated', 2);

        $this->assertDatabaseHas('products', [
            'id' => $first->id,
            'selling_price' => 11000,
            'selling_markup_type' => 'persentase',
            'selling_markup_value' => 10,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $second->id,
            'selling_price' => 22000,
            'selling_markup_type' => 'persentase',
            'selling_markup_value' => 10,
        ]);

        $firstItem = array_merge($this->item('ml10-idn', 'MOBILE LEGENDS'), ['price' => 12000]);
        $secondItem = array_merge($this->item('ml20-idn', 'MOBILE LEGENDS'), ['price' => 30000]);
        Http::fake(['*/price-list' => Http::response(['data' => [$firstItem, $secondItem]])]);
        app(DigiflazzService::class)->syncProducts(true);

        $this->assertEquals(13200, $first->fresh()->selling_price);
        $this->assertEquals(33000, $second->fresh()->selling_price);
        $this->assertTrue(Route::has('admin.products.markup'));
    }

    public function test_non_game_response_does_not_change_catalog(): void
    {
        $brandCount = Brand::count();
        Http::fake(['*/price-list' => Http::response(['data' => [$this->item('P10', 'TELKOMSEL', 'Pulsa')]])]);

        $result = app(DigiflazzService::class)->syncProducts(true);

        $this->assertFalse($result['success']);
        $this->assertSame(0, Product::count());
        $this->assertSame($brandCount, Brand::count());
    }

    public function test_manual_game_and_product_creation_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('admin.brands.store'));
        $this->assertFalse(Route::has('admin.products.store'));

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.brands'))
            ->assertOk()
            ->assertDontSee('Tambah Game');
        $this->actingAs($admin, 'admin')
            ->get(route('admin.products'))
            ->assertOk()
            ->assertDontSee('Tambah Produk');
    }
}
