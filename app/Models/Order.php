<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'checkout_id',
        'order_id',
        'gateway_invoice_id',
        'gateway_invoice_url',
        'gateway_type',
        'qr_string',
        'va_number',
        'payment_code',
        'checkout_url',
        'gateway_extra',
        'payment_method',
        'buyer_sku_code',
        'customer_number',
        'zone_id',
        'customer_name',
        'customer_phone',
        'email',
        'product_name',
        'brand',
        'category',
        'price',
        'original_price',
        'flash_deal_id',
        'voucher_id',
        'quantity',
        'status',
        'note',
        'saldo_amount',
        'saldo_status',
        'digiflazz_started_at',
        'digiflazz_poll_count',
        'last_status_check_at',
        'saldo_released_at',
        'reconcile_status',
    ];

    protected $appends = ['effective_zone_id'];

    protected $casts = [
        'gateway_extra' => 'array',
        'saldo_amount' => 'decimal:2',
        'digiflazz_started_at' => 'datetime',
        'last_status_check_at' => 'datetime',
        'saldo_released_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }

    /**
     * Zone ID efektif untuk ditampilkan.
     * Fallback order lama: zone tersimpan di customer_name berupa angka murni.
     */
    public function getEffectiveZoneIdAttribute(): ?string
    {
        if (! empty($this->zone_id)) {
            return $this->zone_id;
        }

        $name = trim((string) $this->customer_name);

        if ($name !== '' && ctype_digit($name) && ! str_contains($this->customer_number, '.')) {
            return $name;
        }

        return null;
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function balanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    /** Order sudah lunas tapi topup-nya masih berjalan. */
    public function isAwaitingTopup(): bool
    {
        return $this->status === 'processing' && $this->saldo_status === 'held';
    }

    /** Saldo order ini sudah dikembalikan ke user. */
    public function isRefunded(): bool
    {
        return in_array($this->saldo_status, ['refunded', 'reversed'], true);
    }

    public function flashDeal()
    {
        return $this->belongsTo(FlashDeal::class);
    }

    /**
     * Kembalikan kuota flash deal saat pesanan dibatalkan/gagal.
     * Idempotent: flash_deal_id di-null-kan setelah kuota dikembalikan
     * sehingga webhook/permintaan berulang tidak menggandakan kuota.
     */
    public function releaseFlashQuota(): void
    {
        $dealId = $this->flash_deal_id;
        $qty = (int) ($this->quantity ?? 1);

        if (! $dealId || $qty < 1) {
            return;
        }

        $this->update(['flash_deal_id' => null]);

        if ($deal = FlashDeal::find($dealId)) {
            $deal->restockQty($qty);
        }
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Kembalikan kuota flash deal dan jatah pakai voucher saat pesanan
     * dibatalkan/gagal.
     */
    public function releaseDiscounts(): void
    {
        $this->releaseFlashQuota();
        $this->releaseVoucherUsage();
    }

    /**
     * Kembalikan jatah pakai voucher saat pesanan dibatalkan/gagal.
     * Idempotent: voucher_id di-null-kan setelah kuota dikembalikan sehingga
     * webhook/permintaan berulang tidak menggandakan jatah pakai.
     */
    public function releaseVoucherUsage(): void
    {
        $voucherId = $this->voucher_id;

        if (! $voucherId) {
            return;
        }

        $this->update(['voucher_id' => null]);

        if ($voucher = Voucher::find($voucherId)) {
            $voucher->releaseUsage();
        }
    }
}
