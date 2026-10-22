<?php

namespace App\Http\Controllers;

use App\Models\GachaSpin;
use App\Models\SiteSetting;
use App\Services\GachaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class GachaController extends Controller
{
    public function __construct(protected GachaService $gacha) {}

    /**
     * Data konfigurasi roda untuk dirender di sisi klien.
     */
    public function index(Request $request)
    {
        $userId = $request->user()?->id;
        $guestId = $this->guestId($request);
        $prizes = $this->gacha->activePrizes();
        $specialSectorScale = max(40, min(100, (int) SiteSetting::get('gacha_special_sector_size', 72))) / 100;

        return response()->json([
            'enabled' => $this->gacha->isEnabled() && $prizes->isNotEmpty(),
            'daily_limit' => $this->gacha->dailyLimit(),
            'remaining' => $this->gacha->remainingSpins($userId, $guestId, $request->ip()),
            'my_vouchers' => $this->recentVouchers($userId, $guestId),
            'prizes' => $prizes->map(fn ($prize) => [
                'id' => $prize->id,
                'label' => $prize->label,
                'color' => $prize->color,
                'icon' => $prize->icon,
                'weight' => (int) $prize->weight,
                'remaining' => $prize->remaining_quota,
                'is_twenty_percent' => $prize->discount_type === 'percent'
                    && (float) $prize->discount_value === 20.0,
                'sector_scale' => $prize->discount_type === 'percent'
                    && (float) $prize->discount_value === 20.0
                        ? $specialSectorScale
                        : 1,
            ])->values(),
        ]);
    }

    public function spin(Request $request)
    {
        $userId = $request->user()?->id;
        $guestId = $this->guestId($request);

        try {
            $result = $this->gacha->spin($userId, $guestId, $request->ip());
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $voucher = $result['voucher'];
        $prize = $result['prize'];

        return response()->json([
            'success' => true,
            'prize_id' => $prize->id,
            'label' => $prize->label,
            'code' => $voucher->code,
            'value_label' => $voucher->value_label,
            'min_spend' => (int) $voucher->min_spend,
            'expires_at' => $voucher->expires_at?->toIso8601String(),
            'remaining' => $this->gacha->remainingSpins($userId, $guestId, $request->ip()),
        ]);
    }

    /**
     * Identitas tamu disimpan di session (bukan dari input klien) supaya kode
     * voucher tamu tidak bisa dibaca orang lain dengan menebak id-nya.
     */
    private function guestId(Request $request): ?string
    {
        if ($request->user()) {
            return null;
        }

        $guestId = $request->session()->get('gacha_guest_id');

        if (! $guestId) {
            $guestId = Str::random(40);
            $request->session()->put('gacha_guest_id', $guestId);
        }

        return $guestId;
    }

    /**
     * Voucher hasil undian terakhir agar tetap bisa dilihat setelah reload.
     */
    private function recentVouchers(?int $userId, ?string $guestId): array
    {
        $spins = GachaSpin::with('voucher')
            ->where(function ($query) use ($userId, $guestId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('guest_id', $guestId);
                }
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return $spins->map(fn (GachaSpin $spin) => [
            'label' => $spin->result_label,
            'code' => $spin->voucher?->code,
            'value_label' => $spin->voucher?->value_label,
            'redeemable' => (bool) $spin->voucher?->isRedeemable(),
            'created_at' => $spin->created_at?->toIso8601String(),
        ])->values()->all();
    }
}
