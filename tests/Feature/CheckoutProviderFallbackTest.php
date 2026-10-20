<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CheckProvider;
use App\Models\Product;
use App\Services\GameAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutProviderFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.payment.simulation', false);
        config()->set('digiflazz.username', 'test-fallback');
        config()->set('digiflazz.key', 'test-key');

        Brand::updateOrCreate(['name' => 'Free Fire'], [
            'catalog_group' => 'game',
            'requires_zone_id' => false,
            'is_active' => true,
        ]);

        CheckProvider::flushCache();
    }

    private function provider(?string $sku): CheckProvider
    {
        return CheckProvider::create([
            'brand_pattern' => 'free fire*',
            'label' => 'Free Fire',
            'free_type' => 'none',
            'free_config' => null,
            'fallback_free_type' => null,
            'fallback_free_config' => null,
            'digiflazz_fallback_enabled' => true,
            'digiflazz_sku' => $sku,
            'enabled' => true,
            'priority' => 1,
        ]);
    }

    private function product(string $sku = 'ff10-idn'): Product
    {
        return Product::create([
            'buyer_sku_code' => $sku,
            'brand' => 'Free Fire',
            'category' => 'Games',
            'product_name' => 'Free Fire 10 Diamonds',
            'price' => 10000,
            'selling_price' => 11000,
            'type' => 'instant',
            'stock' => 100,
            'is_active' => true,
        ]);
    }

    public function test_digiflazz_sku_is_not_called_realtime_even_when_configured(): void
    {
        $this->provider('cekff-johen');
        Http::fake(['*/transaction' => Http::response(['data' => ['status' => 'Sukses', 'rc' => '00', 'username' => 'FF Player']])]);

        $result = app(GameAccountService::class)->check('Free Fire', '12345678');

        $this->assertFalse($result['valid']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'transaction'));
    }

    public function test_checkout_uses_digiflazz_when_provider_has_sku(): void
    {
        $this->provider('cekff-johen');
        $product = $this->product();

        Http::fake(['*/transaction' => Http::response(['data' => [
            'status' => 'Sukses', 'rc' => '00', 'username' => 'FF Player', 'region' => 'IDN',
        ]])]);

        $message = app(GameAccountService::class)->availabilityMessage($product, '12345678', null);

        $this->assertNull($message);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'transaction')
            && $request['buyer_sku_code'] === 'cekff-johen');
    }

    public function test_checkout_stays_neutral_when_sku_empty(): void
    {
        $this->provider(null);
        $product = $this->product();

        Http::fake(['*/transaction' => Http::response(['data' => ['status' => 'Sukses', 'rc' => '00']])]);

        $message = app(GameAccountService::class)->availabilityMessage($product, '12345678', null);

        $this->assertNull($message);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'transaction'));
    }
}
