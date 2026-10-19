<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTransaction extends Model
{
    /** Saldo naik karena pembayaran masuk. */
    public const TYPE_DEPOSIT = 'deposit';

    /** Saldo dikunci untuk order yang sedang diproses Digiflazz (tidak mengubah saldo). */
    public const TYPE_HOLD = 'hold';

    /** Saldo benar-benar terpakai karena topup berhasil. */
    public const TYPE_CONSUME = 'consume';

    /** Kunci dilepas tanpa memakai saldo (topup gagal/timeout) -> dana kembali ke user. */
    public const TYPE_RELEASE = 'release';

    /** Tarik ulang saldo karena topup ternyata sukses setelah dana dikembalikan. */
    public const TYPE_CORRECTION_DEBIT = 'correction_debit';

    /** Penyesuaian manual oleh admin. */
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_POINT_USE = 'point_use';
    public const TYPE_POINT_RESTORE = 'point_restore';

    protected $fillable = [
        'user_id',
        'order_id',
        'account_order_id',
        'checkout_id',
        'admin_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'meta',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mutasi yang benar-benar mengubah saldo (untuk ditampilkan di mutasi).
     * `hold` dan `release` hanya penanda siklus hidup escrow.
     */
    public function affectsBalance(): bool
    {
        return ! in_array($this->type, [self::TYPE_HOLD, self::TYPE_RELEASE], true);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'Pembayaran masuk',
            self::TYPE_HOLD => 'Johen Point dikunci untuk topup',
            self::TYPE_CONSUME => 'Dipakai untuk topup',
            self::TYPE_RELEASE => 'Refund ke Johen Point',
            self::TYPE_CORRECTION_DEBIT => 'Koreksi:topup terlambat sukses',
            self::TYPE_ADJUSTMENT => 'Penyesuaian admin',
            self::TYPE_POINT_USE => 'Dipakai saat checkout',
            self::TYPE_POINT_RESTORE => 'Point dikembalikan',
            default => $this->type,
        };
    }
}
