<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Buku besar saldo user.
 *
 * Alur dana untuk satu order topup:
 *
 *   1. Pembayaran Xendit sukses      -> `deposit`  (+nominal)  saldo_status: none -> held
 *   2. Topup Digiflazz berhasil       -> `consume`  (-nominal)  saldo_status: held -> consumed
 *   3. Topup gagal / lewat timeout    -> `release`  (0)         saldo_status: held -> refunded
 *   4. Topup sukses TELAT (kasus 3)-> `correction_debit` (-nominal) refunded -> reversed
 *
 * Kunci keamanannya ada di dua lapis:
 *   - `orders.saldo_status` hanya boleh berpindah dari status yang diharapkan,
 *     dicek di dalam transaksi yang sama dengan perubahan saldo.
 *   - `balance_transactions.idempotency_key` unik, jadi kalau webhook Xendit
 *     atau Digiflazz terkirim berulang, mutasi kedua ditolak diam-diam.
 */
class BalanceService
{
    /**
     * Saldo milik user. Order tanpa user (checkout tamu) tidak punya saldo.
     */
    public function balanceFor(?int $userId): float
    {
        if (! $userId) {
            return 0.0;
        }

        $this->ensureAccount($userId);

        return (float) UserBalance::query()->where('user_id', $userId)->value('balance');
    }

    /**
     * Pastikan baris saldo ada. Dibuat di luar transaksi utama supaya tidak
     * ikut terkunci bersama yang lain; bentrok unik ditelan diam-diam karena
     * artinya row-nya sudah dibuat proses lain.
     */
    protected function ensureAccount(int $userId): void
    {
        try {
            UserBalance::query()->firstOrCreate(['user_id' => $userId], ['balance' => 0]);
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }
        }
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true);
    }

    /**
     * Nominal escrow untuk order: pakai `saldo_amount`, dan untuk order lama
     * yang belum punya nilai itu, jatuh ke `price`.
     */
    public function amountFor(Order $order): float
    {
        $amount = (float) ($order->cash_amount ?? 0);
        if ($amount <= 0 && (float) ($order->points_used ?? 0) <= 0) {
            $amount = (float) ($order->saldo_amount ?: $order->price);
        }
        return round($amount, 2);
    }

    /**
     * Langkah 1: pembayaran masuk, saldo bertambah dan dikunci untuk order ini.
     */
    public function markPaid(Order $order): ?BalanceTransaction
    {
        return $this->transition(
            order: $order,
            type: BalanceTransaction::TYPE_DEPOSIT,
            from: ['none', 'held'],
            to: 'held',
            multiplier: 1,
            description: 'Pembayaran topup '.$order->order_id,
        );
    }

    /**
     * Langkah 2: topup sukses, saldo terpakai.
     */
    public function markDelivered(Order $order): ?BalanceTransaction
    {
        return $this->transition(
            order: $order,
            type: BalanceTransaction::TYPE_CONSUME,
            from: ['held'],
            to: 'consumed',
            multiplier: -1,
            description: 'Topup '.$order->order_id.' berhasil masuk ke akun',
        );
    }

    /**
     * Langkah 3: topup gagal atau lewat batas waktu. Saldo tidak berubah --
     * uangnya memang belum pernah keluar dari saldo user -- tapi order
     * ditandai sudah dikembalikan agar tidak diproses ulang.
     */
    public function markRefunded(Order $order, string $reason = 'Topup gagal diproses'): ?BalanceTransaction
    {
        return $this->transition(
            order: $order,
            type: BalanceTransaction::TYPE_RELEASE,
            from: ['held'],
            to: 'refunded',
            multiplier: 0,
            description: $reason,
        );
    }

    /**
     * Langkah 4: topup ternyata sukses SETELAH saldo sempat dikembalikan.
     * Saldo ditarik ulang supaya user tidak menerima diamond dan uangnya
     * sekaligus. Saldo boleh menjadi negatif; itu disengaja agar utang
     * tercatat dan bisa ditagih saat user.topup berikutnya.
     */
    public function markLateSuccess(Order $order): ?BalanceTransaction
    {
        $tx = $this->transition(
            order: $order,
            type: BalanceTransaction::TYPE_CORRECTION_DEBIT,
            from: ['refunded'],
            to: 'reversed',
            multiplier: -1,
            description: 'Topup '.$order->order_id.' sukses terlambat, saldo ditarik ulang',
        );

        if ($tx) {
            $order->forceFill(['reconcile_status' => 'settled'])->save();
        }

        return $tx;
    }

    public function markAccountPaid(AccountOrder $order): ?BalanceTransaction
    {
        return $this->accountTransition($order, BalanceTransaction::TYPE_DEPOSIT, ['none'], 'held', 1,
            'Pembayaran akun '.$order->order_ref);
    }

    public function markAccountDelivered(AccountOrder $order): ?BalanceTransaction
    {
        $this->consumePoints($order);
        return $this->accountTransition($order, BalanceTransaction::TYPE_CONSUME, ['held'], 'consumed', -1,
            'Pesanan akun '.$order->order_ref.' berhasil');
    }

    public function markAccountRefunded(AccountOrder $order, string $reason): ?BalanceTransaction
    {
        $this->restorePoints($order, 'Refund Johen Point: '.$reason);
        return $this->accountTransition($order, BalanceTransaction::TYPE_RELEASE, ['held'], 'refunded', 0, $reason);
    }

    private function accountTransition(AccountOrder $order, string $type, array $from, string $to, int $multiplier, string $description): ?BalanceTransaction
    {
        $userId = (int) $order->user_id;
        if ($userId <= 0) return null;
        $this->ensureAccount($userId);

        return DB::transaction(function () use ($order, $type, $from, $to, $multiplier, $description, $userId) {
            $locked = AccountOrder::whereKey($order->id)->lockForUpdate()->first();
            if (! $locked || ! in_array($locked->saldo_status, $from, true)) return null;
            $account = UserBalance::where('user_id', $userId)->lockForUpdate()->firstOrFail();
            $base = round((float) $locked->cash_amount, 2);
            $amount = $base * $multiplier;
            $newBalance = round((float) $account->balance + $amount, 2);
            $account->update(['balance' => $newBalance]);
            $locked->forceFill(['saldo_amount' => $base, 'saldo_status' => $to]);
            if ($to === 'refunded') $locked->saldo_released_at = now();
            $locked->save();

            return BalanceTransaction::create([
                'user_id' => $userId, 'account_order_id' => $locked->id, 'type' => $type,
                'amount' => $amount, 'balance_after' => $newBalance, 'description' => $description,
                'idempotency_key' => 'account_order:'.$locked->id.':'.$type,
            ]);
        });
    }

    /**
     * Eksekusi satu perpindahan status escrow + mutasi saldo secara atomik.
     *
     * Mengembalikan null bila tidak ada yang perlu dilakukan: order tamu,
     * atau transisi status yang tidak lagi relevan (mis. webhook dobel).
     */
    protected function transition(
        Order $order,
        string $type,
        array $from,
        string $to,
        int $multiplier,
        string $description,
        array $meta = [],
    ): ?BalanceTransaction {
        $userId = (int) ($order->user_id ?? 0);

        if ($userId <= 0) {
            // Checkout tamu tidak punya saldo; alur topup tetap jalan seperti biasa.
            return null;
        }

        $idempotencyKey = "order:{$order->id}:{$type}";

        $this->ensureAccount($userId);

        return DB::transaction(function () use ($order, $type, $from, $to, $multiplier, $description, $meta, $idempotencyKey, $userId) {
            // Kunci baris order supaya dua proses paralel (webhook + scheduler)
            // tidak sama-sama membaca status lama lalu sama-sama menerapkan.
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if (! $locked) {
                return null;
            }

            if ($locked->saldo_status === $to) {
                return null; // Sudah pernah diterapkan.
            }

            if (! in_array((string) $locked->saldo_status, $from, true)) {
                return null; // Status tidak sesuai ekspektasi.
            }

            $account = UserBalance::query()->where('user_id', $userId)->lockForUpdate()->first();

            if (! $account) {
                return null;
            }

            $amount = round($this->amountFor($locked) * $multiplier, 2);
            $newBalance = round((float) $account->balance + $amount, 2);

            $account->update(['balance' => $newBalance]);

            $locked->forceFill([
                'saldo_status' => $to,
                'saldo_amount' => $this->amountFor($locked),
            ]);

            if ($to === 'refunded') {
                $locked->forceFill(['saldo_released_at' => now()]);
            }

            $locked->save();

            return BalanceTransaction::create([
                'user_id' => $userId,
                'order_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'meta' => $meta ?: null,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    /**
     * Saldo cukup untuk membayar order? Tidak dipakai di alur pembayaran Xendit
     * (user selalu bayar lewat gateway), tapi berguna untuk pemeriksaan admin
     * dan rencana memakai saldo sebagai metode pembayaran.
     */
    public function canCover(?int $userId, float $amount): bool
    {
        return $this->balanceFor($userId) + 0.001 >= round($amount, 2);
    }

    /** Kurangi Johen Point saat order dibuat. Aman dipanggil ulang. */
    public function reservePoints(object $payable, float $requested): float
    {
        $userId = (int) ($payable->user_id ?? 0);
        $total = round((float) ($payable->total ?? $payable->total_price ?? $payable->price ?? 0), 2);
        $requested = max(0, min(round($requested, 2), $total));

        if ($userId <= 0 || $requested <= 0) {
            return 0.0;
        }

        $this->ensureAccount($userId);

        return DB::transaction(function () use ($payable, $userId, $requested) {
            $locked = $payable::query()->whereKey($payable->getKey())->lockForUpdate()->firstOrFail();
            if ((string) ($locked->points_status ?? 'none') !== 'none') {
                return (float) $locked->points_used;
            }

            $account = UserBalance::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
            $used = min($requested, (float) $account->balance);
            if ($used <= 0) {
                return 0.0;
            }

            $newBalance = round((float) $account->balance - $used, 2);
            $account->update(['balance' => $newBalance]);
            $locked->forceFill(['points_used' => $used, 'points_status' => 'reserved'])->save();

            BalanceTransaction::create(array_merge($this->referenceColumns($locked), [
                'user_id' => $userId,
                'type' => BalanceTransaction::TYPE_POINT_USE,
                'amount' => -$used,
                'balance_after' => $newBalance,
                'description' => 'Johen Point dipakai untuk '.$this->referenceLabel($locked),
                'idempotency_key' => $this->referenceKey($locked).':point_use',
            ]));

            return $used;
        });
    }

    /** Kembalikan point yang sempat dipakai, misalnya pembayaran gateway kedaluwarsa. */
    public function restorePoints(object $payable, string $reason): ?BalanceTransaction
    {
        $userId = (int) ($payable->user_id ?? 0);
        if ($userId <= 0) return null;
        $this->ensureAccount($userId);

        return DB::transaction(function () use ($payable, $userId, $reason) {
            $locked = $payable::query()->whereKey($payable->getKey())->lockForUpdate()->first();
            if (! $locked || $locked->points_status !== 'reserved' || (float) $locked->points_used <= 0) return null;

            $account = UserBalance::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
            $amount = round((float) $locked->points_used, 2);
            $newBalance = round((float) $account->balance + $amount, 2);
            $account->update(['balance' => $newBalance]);
            $locked->forceFill(['points_status' => 'restored'])->save();

            return BalanceTransaction::create(array_merge($this->referenceColumns($locked), [
                'user_id' => $userId,
                'type' => BalanceTransaction::TYPE_POINT_RESTORE,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $reason,
                'idempotency_key' => $this->referenceKey($locked).':point_restore',
            ]));
        });
    }

    public function consumePoints(object $payable): void
    {
        $payable::query()->whereKey($payable->getKey())->where('points_status', 'reserved')->update(['points_status' => 'consumed']);
    }

    /** Tarik kembali point yang sudah direfund bila provider kemudian menyatakan sukses. */
    public function debitRestoredPoints(Order $order): ?BalanceTransaction
    {
        $userId = (int) $order->user_id;
        if ($userId <= 0) return null;
        $this->ensureAccount($userId);

        return DB::transaction(function () use ($order, $userId) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $locked || $locked->points_status !== 'restored' || (float) $locked->points_used <= 0) return null;
            $account = UserBalance::where('user_id', $userId)->lockForUpdate()->firstOrFail();
            $amount = round((float) $locked->points_used, 2);
            $newBalance = round((float) $account->balance - $amount, 2);
            $account->update(['balance' => $newBalance]);
            $locked->update(['points_status' => 'consumed']);

            return BalanceTransaction::create([
                'user_id' => $userId, 'order_id' => $locked->id,
                'type' => BalanceTransaction::TYPE_CORRECTION_DEBIT, 'amount' => -$amount,
                'balance_after' => $newBalance,
                'description' => 'Koreksi Johen Point karena topup '.$locked->order_id.' sukses terlambat',
                'idempotency_key' => 'order:'.$locked->id.':point_reversal',
            ]);
        });
    }

    /** Penyesuaian point oleh admin, satu idempotency key hanya bisa sekali. */
    public function adjust(User $user, float $amount, string $reason, ?int $adminId, string $key): BalanceTransaction
    {
        $this->ensureAccount($user->id);

        return DB::transaction(function () use ($user, $amount, $reason, $adminId, $key) {
            if ($existing = BalanceTransaction::where('idempotency_key', $key)->first()) return $existing;
            $account = UserBalance::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $newBalance = round((float) $account->balance + $amount, 2);
            if ($newBalance < 0) throw new \RuntimeException('Johen Point user tidak mencukupi.');
            $account->update(['balance' => $newBalance]);

            return BalanceTransaction::create([
                'user_id' => $user->id, 'admin_id' => $adminId,
                'type' => BalanceTransaction::TYPE_ADJUSTMENT, 'amount' => $amount,
                'balance_after' => $newBalance, 'description' => $reason,
                'idempotency_key' => $key,
            ]);
        });
    }

    private function referenceColumns(object $model): array
    {
        return match (true) {
            $model instanceof Order => ['order_id' => $model->id],
            $model instanceof AccountOrder => ['account_order_id' => $model->id],
            $model instanceof Checkout => ['checkout_id' => $model->id],
            default => [],
        };
    }

    private function referenceKey(object $model): string
    {
        return match (true) {
            $model instanceof Order => 'order:'.$model->id,
            $model instanceof AccountOrder => 'account_order:'.$model->id,
            $model instanceof Checkout => 'checkout:'.$model->id,
            default => class_basename($model).':'.$model->getKey(),
        };
    }

    private function referenceLabel(object $model): string
    {
        return (string) ($model->order_id ?? $model->order_ref ?? $model->checkout_ref ?? $model->getKey());
    }

    public function recentFor(int $userId, int $limit = 20)
    {
        return BalanceTransaction::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Total yang masih terpakai untuk topup yang belum selesai. Dipakai admin
     * untuk melihat berapa dana yang sedang "di fly".
     */
    public function heldTotal(): float
    {
        return (float) Order::query()->where('saldo_status', 'held')->sum('saldo_amount');
    }

    public function logReconcile(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }
}
