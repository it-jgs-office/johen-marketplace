<?php

namespace Tests\Feature;

use App\Models\CheckProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CheckProviderAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function provider(): CheckProvider
    {
        CheckProvider::flushCache();

        return CheckProvider::create([
            'brand_pattern' => 'mobile legends*',
            'label' => 'Mobile Legends',
            'free_type' => 'alu',
            'free_config' => [],
            'fallback_free_type' => 'isan',
            'fallback_free_config' => ['game' => 'ml'],
            'digiflazz_fallback_enabled' => true,
            'digiflazz_sku' => 'usrnameml-johen',
            'enabled' => true,
            'priority' => 1,
        ]);
    }

    public function test_admin_can_view_check_providers_page(): void
    {
        $this->provider();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.check-providers'))
            ->assertOk()
            ->assertSee('Mobile Legends')
            ->assertSee('usrnameml-johen');
    }

    public function test_admin_can_update_provider_and_cache_is_flushed(): void
    {
        $provider = $this->provider();

        Cache::put('check_providers_all', ['stale'], 3600);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.check-providers.update', $provider), [
                'label' => 'Mobile Legends ID',
                'free_type' => 'isan',
                'free_config_json' => '{"game":"ml"}',
                'fallback_free_type' => 'alu',
                'fallback_free_config_json' => '',
                'digiflazz_fallback_enabled' => '1',
                'digiflazz_sku' => 'usrnameml-johen',
                'priority' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $provider->refresh();
        $this->assertSame('Mobile Legends ID', $provider->label);
        $this->assertSame('isan', $provider->free_type);
        $this->assertSame(['game' => 'ml'], $provider->free_config);
        $this->assertNull($provider->fallback_free_config);
        $this->assertTrue($provider->digiflazz_fallback_enabled);
        $this->assertSame(5, $provider->priority);
        $this->assertNull(Cache::get('check_providers_all'));
    }

    public function test_admin_can_toggle_provider(): void
    {
        $provider = $this->provider();

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.check-providers.toggle', $provider))
            ->assertRedirect();

        $this->assertFalse($provider->refresh()->enabled);
    }
}
