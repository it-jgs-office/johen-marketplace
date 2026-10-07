<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowStockMail;

class Product extends Model
{
    public const LOW_STOCK_THRESHOLD = 10;

    protected $fillable = [
        'buyer_sku_code',
        'brand',
        'category',
        'product_name',
        'photo',
        'price',
        'selling_price',
        'selling_price_override',
        'selling_markup_type',
        'selling_markup_value',
        'type',
        'is_active',
        'stock',
        'stock_alert_sent',
        'region',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'stock' => 'integer',
        'stock_alert_sent' => 'boolean',
        'selling_price_override' => 'decimal:2',
        'selling_markup_value' => 'decimal:2',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return media_url($this->photo);
        }
        return null;
    }

    public function consumeStock(int $qty = 1): void
    {
        $this->decrement('stock', max(1, $qty));
        $this->refresh();

        if ($this->stock < static::LOW_STOCK_THRESHOLD) {
            $this->notifyLowStockOnce();
        }
    }

    public function resetStockAlert(): void
    {
        if ($this->stock_alert_sent) {
            $this->update(['stock_alert_sent' => false]);
        }
    }

    protected function notifyLowStockOnce(): void
    {
        if ($this->stock_alert_sent) {
            return;
        }

        $adminEmail = SiteSetting::get('admin_email') ?: SiteSetting::get('contact_email');

        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new LowStockMail($this));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal kirim email stok menipis', [
                    'product_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->update(['stock_alert_sent' => true]);
    }
}
