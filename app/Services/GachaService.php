<?php

namespace App\Services;

use App\Models\GachaPrize;
use App\Models\GachaSpin;
use App\Models\SiteSetting;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GachaService
{
    /**
     * Berapa kali satu pengunjung boleh memutar roda per hari.
     * Bisa diatur dari panel admin lewat SiteSetting "gacha_daily_limit".
     */
    public function dailyLimit(): int
    {
        $limit = (int) SiteSetting::get('gacha_daily_limit', 1);

        return max(0, $limit);
    }

    public function isEnabled(): bool
    {
        return (bool) SiteSetting::get('gacha_enabled', true);
    }

    /**
     * Hadiah yang sedang tayang, diurutkan sesuai urutan tampil pada roda.
     */
    public function activePrizes(): Collection
    {
        return GachaPrize::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (GachaPrize $prize) => $prize->isEligible())
            ->values();
    }

    /**
     * Sisa jatah putar untuk identitas pengunjung tertentu hari ini.
     */
    public function remainingSpins(?int $userId, ?string $guestId = null, ?string $ip = null): int
    {
        $limit = $this->dailyLimit();

        if ($limit < 1) {
            return 0;
        }

        return max(0, $limit - $this->spinsToday($userId, $guestId, $ip));
    }

    public function spinsToday(?int $userId, ?string $guestId = null, ?string $ip = null): int
    {
        return $this->spinQuery($userId, $guestId, $ip)->count();
    }

    /**
     * Putar roda. Hadiah dipilih di server dengan bobot (weight) tiap sektor
     * dan kuota sektor dikunci secara atomik supaya tidak bisa melebihi kuota
     * ketika ada beberapa visitor memutar bersamaan.
     *
     * @return array{prize: GachaPrize, voucher: Voucher, spin: GachaSpin}
     */
    public function spin(?int $userId, ?string $guestId = null, ?string $ip = null): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Gacha sedang tidak tersedia.');
        }

        $limit = $this->dailyLimit();

        if ($limit < 1) {
            throw new RuntimeException('Gacha sedang tidak tersedia.');
        }

        if ($this->spinsToday($userId, $guestId, $ip) >= $limit) {
            throw new RuntimeException('Kamu sudah memutar roda hari ini. Kembali besok ya!');
        }

        return DB::transaction(function () use ($userId, $guestId, $ip, $limit) {
            // pickPrize() mengunci baris hadiah, sehingga request bersamaan
            // dari identitas yang sama saling menunggu di sini. Karena itu
            // hitungan putaran di bawah selalu melihat putaran yang baru commit.
            $prize = $this->pickPrize();

            if ($this->spinsToday($userId, $guestId, $ip) >= $limit) {
                throw new RuntimeException('Kamu sudah memutar roda hari ini. Kembali besok ya!');
            }

            $voucher = Voucher::create([
                'code' => Voucher::generateCode(),
                'user_id' => $userId,
                'gacha_prize_id' => $prize->id,
                'source' => Voucher::SOURCE_GACHA,
                'label' => $prize->label,
                'discount_type' => $prize->discount_type,
                'discount_value' => $prize->discount_value,
                'min_spend' => $prize->min_spend,
                'quota' => 1,
                'expires_at' => now()->addDays(max(1, (int) $prize->validity_days)),
            ]);

            $prize->consumeQuota();

            $spin = GachaSpin::create([
                'user_id' => $userId,
                'guest_id' => $userId ? null : $guestId,
                'ip_address' => $ip,
                'gacha_prize_id' => $prize->id,
                'voucher_id' => $voucher->id,
                'result_label' => $prize->label,
                'discount_type' => $prize->discount_type,
                'discount_value' => $prize->discount_value,
            ]);

            return ['prize' => $prize, 'voucher' => $voucher, 'spin' => $spin];
        });
    }

    /**
     * Pilih hadiah dengan baris hadiah dikunci agar urutan peserta global dan
     * kuota tetap konsisten saat beberapa pengunjung memutar bersamaan.
     */
    private function pickPrize(): GachaPrize
    {
        $allPrizes = GachaPrize::query()
            ->lockForUpdate()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $prizes = $allPrizes
            ->filter(fn (GachaPrize $prize) => $prize->isEligible())
            ->values();

        if ($prizes->isEmpty()) {
            throw new RuntimeException('Semua hadiah sudah habis. Coba lagi nanti ya!');
        }

        if ((bool) SiteSetting::get('gacha_special_prize_enabled', true)) {
            $specialPrizeId = (int) SiteSetting::get('gacha_special_prize_id', 0);
            $specialPrize = $specialPrizeId > 0
                ? $allPrizes->firstWhere('id', $specialPrizeId)
                : ($prizes->first(fn (GachaPrize $prize) => $this->isTwentyPercentPrize($prize))
                    ?? $allPrizes->first(fn (GachaPrize $prize) => $this->isTwentyPercentPrize($prize)));

            if ($specialPrize && $this->isTwentyPercentPrize($specialPrize)) {
                $interval = max(2, (int) SiteSetting::get('gacha_special_prize_interval', 20));
                $counterBase = max(0, (int) SiteSetting::get('gacha_special_prize_counter_base', 0));
                $participantNumber = max(0, GachaSpin::count() - $counterBase) + 1;

                if ($participantNumber % $interval === 0) {
                    if (! $specialPrize->isEligible()) {
                        throw new RuntimeException('Hadiah Diskon 20% sedang habis. Hubungi admin.');
                    }

                    return $specialPrize;
                }

                // Peserta selain urutan pemenang tidak boleh mendapat hadiah khusus.
                $prizes = $prizes
                    ->reject(fn (GachaPrize $prize) => $this->isTwentyPercentPrize($prize))
                    ->values();

                if ($prizes->isEmpty()) {
                    throw new RuntimeException('Belum ada hadiah reguler yang bisa dimasuki undian.');
                }
            }
        }

        return $this->pickByWeight($prizes);
    }

    private function isTwentyPercentPrize(GachaPrize $prize): bool
    {
        return $prize->discount_type === 'percent'
            && (float) $prize->discount_value === 20.0;
    }

    private function pickByWeight(Collection $prizes): GachaPrize
    {

        $total = (int) $prizes->sum('weight');

        if ($total < 1) {
            throw new RuntimeException('Belum ada hadiah yang bisa dimasuki undian.');
        }

        $roll = random_int(1, $total);
        $cursor = 0;

        foreach ($prizes as $prize) {
            $cursor += (int) $prize->weight;

            if ($roll <= $cursor) {
                return $prize;
            }
        }

        return $prize;
    }

    /**
     * Penghitung putaran harian: user login dihitung per akun, tamu dihitung
     * per guest_id (session). IP hanya dipakai sebagai identitas ketika guest_id
     * tidak tersedia, supaya pengunjung yang satu IP publik (kantor, wifi,
     * NAT provider) tidak ikut terblokir gara-gara orang lain memutar.
     */
    private function spinQuery(?int $userId, ?string $guestId, ?string $ip)
    {
        $since = now()->startOfDay();

        return GachaSpin::where('created_at', '>=', $since)
            ->where(function ($query) use ($userId, $guestId, $ip) {
                if ($userId) {
                    $query->where('user_id', $userId);

                    return;
                }

                if ($guestId) {
                    $query->where('guest_id', $guestId);

                    return;
                }

                if ($ip) {
                    $query->where('ip_address', $ip);

                    return;
                }

                $query->whereRaw('1 = 0');
            });
    }
}
