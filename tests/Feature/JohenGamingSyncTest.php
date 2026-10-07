<?php

namespace Tests\Feature;

use App\Models\AccountListing;
use App\Models\Media;
use App\Models\User;
use App\Jobs\RunJohenGamingSync;
use App\Services\JohenGamingSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JohenGamingSyncTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__.'/../Fixtures/'.$name);
    }

    private function fakeJohenSite(array $overrides = []): void
    {
        $category = $overrides['category'] ?? $this->fixture('johen-category-ml.html');
        $index = $overrides['index'] ?? $this->fixture('johen-index.html');

        Http::fake([
            'https://johengaming.id/produk/jual-beli-akun/ml/170' => Http::response($this->fixture('johen-detail-170.html')),
            'https://johengaming.id/produk/jual-beli-akun/ml/171' => Http::response($this->fixture('johen-detail-171.html')),
            'https://johengaming.id/produk/jual-beli-akun/ml' => Http::response($category),
            'https://johengaming.id/produk/jual-beli-akun' => Http::response($index),
            'https://johengaming.id/storage/jba/*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']),
        ]);
    }

    public function test_sync_imports_category_and_detail_listings(): void
    {
        Storage::fake('public');
        $this->fakeJohenSite();

        $manual = AccountListing::create([
            'game' => 'Mobile Legends',
            'product_name' => 'AKUN MANUAL',
            'specifications' => 'Manual listing',
            'price' => 100000,
            'is_active' => true,
        ]);

        $stats = app(JohenGamingSyncService::class)->sync('ml');

        $this->assertSame(2, $stats['created']);
        $this->assertSame(0, $stats['sold']);
        $this->assertSame(0, $stats['errors']);

        $listing = AccountListing::where('source_id', 'ml:170')->first();
        $this->assertNotNull($listing);
        $this->assertSame('johengaming', $listing->source);
        $this->assertSame('Mobile Legends', $listing->game);
        $this->assertStringContainsString('MIYA SABER', $listing->product_name);
        $this->assertSame(45000000.0, (float) $listing->price);
        $this->assertSame(50000000.0, (float) $listing->original_price);
        $this->assertSame(10, $listing->discount_percent);
        $this->assertSame('hot', $listing->promo_type);
        $this->assertSame('Johen', $listing->owner_name);
        $this->assertSame('sultan', $listing->collector_tier);
        $this->assertSame('normal', $listing->deal_type);
        $this->assertTrue($listing->is_active);
        $this->assertFalse($listing->is_sold);
        $this->assertStringContainsString('COLLECTOR', $listing->specifications);
        $this->assertStringContainsString('ID Akun: 129139383', $listing->specifications);
        $this->assertSame('https://johengaming.id/produk/jual-beli-akun/ml/170', $listing->source_url);
        $this->assertStringEndsWith('account-listings/johengaming-ml-170-0.jpg', $listing->photo);
        $this->assertNotNull($listing->detail_photo_1);

        $this->assertTrue(Storage::disk('public')->exists($listing->photo));
        $this->assertNotNull(Media::where('path', $listing->photo)->first());

        // Listing tanpa diskon tetap satu harga.
        $plain = AccountListing::where('source_id', 'ml:171')->first();
        $this->assertNotNull($plain);
        $this->assertSame(9900000.0, (float) $plain->price);
        $this->assertNull($plain->original_price);
        $this->assertNull($plain->discount_percent);

        // Listing lama (non-sumber) tidak tersentuh.
        $manual->refresh();
        $this->assertSame('AKUN MANUAL', $manual->product_name);
        $this->assertSame(100000.0, (float) $manual->price);
    }

    public function test_sync_is_idempotent(): void
    {
        Storage::fake('public');
        $this->fakeJohenSite();

        $service = app(JohenGamingSyncService::class);

        $first = $service->sync('ml');
        $this->assertSame(2, $first['created']);

        $second = $service->sync('ml');
        $this->assertSame(0, $second['created']);
        $this->assertSame(2, $second['unchanged']);

        $this->assertSame(2, AccountListing::where('source', 'johengaming')->count());
    }

    public function test_sold_is_never_reverted_on_available_source(): void
    {
        Storage::fake('public');
        $this->fakeJohenSite();

        AccountListing::create([
            'source' => 'johengaming',
            'source_id' => 'ml:171',
            'source_url' => 'https://johengaming.id/produk/jual-beli-akun/ml/171',
            'game' => 'Mobile Legends',
            'product_name' => 'LEGEND : SABER ALUCARD FRANCO FREYA LESLEY GRANGER GUINEVERE',
            'specifications' => 'sudah terjual di marketplace',
            'price' => 9900000,
            'original_price' => 12000000,
            'is_sold' => true,
            'is_active' => true,
        ]);

        app(JohenGamingSyncService::class)->sync('ml');

        $listing = AccountListing::where('source_id', 'ml:171')->first();
        $this->assertTrue($listing->is_sold);
    }

    public function test_manual_listing_is_probably_marked_sold_by_name(): void
    {
        Storage::fake('public');
        $this->fakeJohenSite();

        AccountListing::create([
            'source' => 'johengaming',
            'source_id' => 'ml:172',
            'source_url' => 'https://johengaming.id/produk/jual-beli-akun/ml/172',
            'game' => 'Mobile Legends',
            'product_name' => 'LEGEND LUNOX',
            'specifications' => 'akun legend lunox',
            'price' => 1200000,
            'is_sold' => false,
            'is_active' => true,
        ]);

        app(JohenGamingSyncService::class)->sync('ml');

        $listing = AccountListing::where('source_id', 'ml:172')->first();
        $this->assertTrue($listing->is_sold);
    }

    public function test_dry_run_does_not_write(): void
    {
        Storage::fake('public');
        $this->fakeJohenSite();

        $this->artisan('jba:sync-johengaming', ['--game' => 'ml', '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(0, AccountListing::count());
    }

    public function test_admin_can_trigger_sync(): void
    {
        Storage::fake('public');
        $this->fakeJohenSite();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.account-listings'))
            ->post(route('admin.account-listings.sync', ['game' => 'ml']))
            ->assertRedirect(route('admin.account-listings'))
            ->assertSessionHas('success');

        $this->assertSame(2, AccountListing::where('source', 'johengaming')->count());
        $this->assertSame('completed', Cache::get(RunJohenGamingSync::STATUS_KEY)['state']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.account-listings.sync-status'))
            ->assertOk()
            ->assertJsonPath('result.created', 2);
    }

    public function test_admin_cannot_start_an_overlapping_sync(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Cache::put(RunJohenGamingSync::RUNNING_KEY, true, now()->addMinute());

        $this->actingAs($admin, 'admin')
            ->post(route('admin.account-listings.sync'))
            ->assertSessionHas('error');

        $this->assertSame(0, AccountListing::where('source', 'johengaming')->count());
    }

    public function test_sync_reports_source_failure_instead_of_empty_success(): void
    {
        Http::fake([
            'https://johengaming.id/produk/jual-beli-akun/ml' => Http::response('Unavailable', 503),
            'https://johengaming.id/produk/jual-beli-akun' => Http::response($this->fixture('johen-index.html')),
        ]);

        $result = app(JohenGamingSyncService::class)->sync('ml');

        $this->assertSame(1, $result['errors']);
        $this->assertStringContainsString('HTTP 503', $result['failed'][0]);
    }
}
