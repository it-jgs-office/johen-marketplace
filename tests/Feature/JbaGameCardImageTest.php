<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JbaGameCardImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_replace_and_delete_jba_card_without_changing_topup_thumbnail(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/topup.jpg', 'old image');

        $brand = Brand::create([
            'name' => 'Mobile Legends',
            'category' => 'Games',
            'catalog_group' => 'game',
            'is_active' => true,
            'is_popular' => true,
            'thumbnail' => 'brands/topup.jpg',
            'jba_card_image' => 'brands/topup.jpg',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.jba-game-cards'))
            ->assertOk()
            ->assertSee('Mobile Legends');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.jba-game-cards.update', $brand), [
                'jba_card_image' => UploadedFile::fake()->image('jba-card.jpg', 600, 800),
            ])
            ->assertRedirect();

        $brand->refresh();
        $this->assertSame('brands/topup.jpg', $brand->thumbnail);
        $this->assertNotSame($brand->thumbnail, $brand->jba_card_image);
        Storage::disk('public')->assertExists([$brand->thumbnail, $brand->jba_card_image]);

        $this->get(route('jual-beli-akun'))
            ->assertOk()
            ->assertSee('src="'.$brand->jba_card_image_url.'"', false)
            ->assertDontSee('src="'.$brand->thumbnail_url.'"', false);

        $jbaPath = $brand->jba_card_image;
        $this->actingAs($admin, 'admin')
            ->delete(route('admin.jba-game-cards.destroy', $brand))
            ->assertRedirect();

        $this->assertNull($brand->refresh()->jba_card_image);
        Storage::disk('public')->assertExists('brands/topup.jpg');
        Storage::disk('public')->assertMissing($jbaPath);
    }

    public function test_jba_card_upload_rejects_non_images(): void
    {
        Storage::fake('public');
        $brand = Brand::create(['name' => 'Free Fire', 'is_popular' => true]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.jba-game-cards.update', $brand), [
                'jba_card_image' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            ])
            ->assertSessionHasErrors('jba_card_image');

        $this->assertNull($brand->refresh()->jba_card_image);
    }

    public function test_replacing_topup_thumbnail_preserves_the_initial_jba_card_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/shared.jpg', 'old image');
        $brand = Brand::create([
            'name' => 'PUBG Mobile',
            'category' => 'Games',
            'service_type' => 'topup',
            'catalog_group' => 'game',
            'thumbnail' => 'brands/shared.jpg',
            'jba_card_image' => 'brands/shared.jpg',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.brands.update', $brand), [
                'name' => $brand->name,
                'category' => 'Games',
                'service_type' => 'topup',
                'catalog_group' => 'game',
                'thumbnail' => UploadedFile::fake()->image('topup-new.jpg', 600, 600),
            ])
            ->assertRedirect();

        $brand->refresh();
        $this->assertSame('brands/shared.jpg', $brand->jba_card_image);
        $this->assertNotSame($brand->thumbnail, $brand->jba_card_image);
        Storage::disk('public')->assertExists([$brand->thumbnail, $brand->jba_card_image]);
    }
}
