<?php

namespace Tests\Feature;

use App\Models\AccountListing;
use App\Models\AccountOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckTransactionRecentTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_transaction_page_shows_ten_latest_orders_per_service_with_masked_references(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 11) as $number) {
            Order::create([
                'user_id' => $user->id,
                'order_id' => sprintf('TUP-SECRET-%03d', $number),
                'buyer_sku_code' => 'SKU-'.$number,
                'customer_number' => '08123456789',
                'product_name' => 'Top Up '.$number,
                'brand' => 'Mobile Legends',
                'category' => 'Games',
                'price' => 10000 + $number,
                'status' => $number % 2 === 0 ? 'success' : 'pending',
            ]);
        }

        $listing = AccountListing::create([
            'game' => 'Valorant',
            'product_name' => 'Akun Valorant Premium',
            'specifications' => 'Rank tinggi',
            'price' => 750000,
            'is_active' => true,
        ]);

        AccountOrder::create([
            'account_listing_id' => $listing->id,
            'user_id' => $user->id,
            'order_ref' => 'JBA-SECRET-001',
            'customer_name' => 'Pelanggan Rahasia',
            'customer_email' => 'private@example.com',
            'customer_phone' => '08123456789',
            'payment_method' => 'qris',
            'status' => 'processing',
            'total_price' => 750000,
        ]);

        $this->get(route('check.transaction'))
            ->assertOk()
            ->assertViewHas('recentTopups', fn ($orders) => $orders->count() === 10)
            ->assertViewHas('recentAccountOrders', fn ($orders) => $orders->count() === 1)
            ->assertSee('Top Up')
            ->assertSee('Jual Beli Akun')
            ->assertSee('Top Up 11')
            ->assertDontSee('Top Up 1<', false)
            ->assertSee('Akun Valorant Premium')
            ->assertSee('TUP-••••011')
            ->assertSee('JBA-••••001')
            ->assertDontSee('TUP-SECRET-011')
            ->assertDontSee('JBA-SECRET-001')
            ->assertDontSee('private@example.com')
            ->assertDontSee('08123456789');
    }
}
