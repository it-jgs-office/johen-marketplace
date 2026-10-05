<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    /**
     * Nama kolom gateway sengaja sama dengan `orders` supaya
     * PaymentGatewayService::runCharge() bisa menulis langsung ke model ini
     * tanpa perlu cabang khusus.
     */
    protected $fillable = [
        'checkout_ref',
        'user_id',
        'email',
        'phone',
        'subtotal',
        'discount',
        'total',
        'voucher_id',
        'payment_method',
        'gateway_invoice_id',
        'gateway_invoice_url',
        'gateway_type',
        'qr_string',
        'va_number',
        'payment_code',
        'checkout_url',
        'gateway_extra',
        'status',
        'item_count',
    ];

    protected $casts = [
        'gateway_extra' => 'array',
        'subtotal' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
        'item_count' => 'integer',
    ];

    /** Status anak yang sudah selesai dan tidak akan berubah lagi. */
    public const TERMINAL = ['success', 'failed'];

    /**
     * URL memakai kode yang terbaca manusia (`CART-XXXX`), bukan id angka,
     * jadi route model binding harus memakai `checkout_ref`.
     */
    public function getRouteKeyName(): string
    {
        return 'checkout_ref';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Dipakai PaymentGatewayService sebagai sumber nominal charge.
     */
    public function getOrderIdAttribute(): string
    {
        return (string) $this->checkout_ref;
    }

    public function getPriceAttribute(): float
    {
        return (float) $this->total;
    }

    public function isPaid(): bool
    {
        return $this->status === 'processing' || $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'expired'], true);
    }

    /**
     * Hitung ulang status induk dari status seluruh order anak.
     *
     * Dipanggil setelah fan-out settlement supaya halaman status cart tidak
     * menampilkan "processing" selamanya. Order anak yang belum terminal
     * membuat induk tetap 'processing'.
     */
    public function syncStatus(): string
    {
        $counts = $this->orders()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $success = (int) ($counts['success'] ?? 0);
        $failed = (int) ($counts['failed'] ?? 0);

        $status = match (true) {
            $total === 0 => $this->status,
            ($success + $failed) < $total => 'processing',
            $success > 0 => 'success',
            default => 'failed',
        };

        // Jangan turunkan status yang sudah final oleh jalur pembayaran.
        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }

        return $status;
    }
}