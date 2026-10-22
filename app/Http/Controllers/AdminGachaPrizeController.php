<?php

namespace App\Http\Controllers;

use App\Models\GachaPrize;
use App\Models\GachaSpin;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class AdminGachaPrizeController extends Controller
{
    public function index(Request $request)
    {
        $prizes = GachaPrize::withCount('vouchers')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        $today = GachaSpin::whereDate('created_at', now()->toDateString())->count();
        $totalSpins = GachaSpin::count();
        $specialInterval = max(2, (int) SiteSetting::get('gacha_special_prize_interval', 20));
        $counterBase = max(0, (int) SiteSetting::get('gacha_special_prize_counter_base', 0));

        $stats = [
            'enabled' => (bool) SiteSetting::get('gacha_enabled', true),
            'daily_limit' => (int) SiteSetting::get('gacha_daily_limit', 1),
            'spins_today' => $today,
            'vouchers_issued' => $totalSpins,
            'special_prize_enabled' => (bool) SiteSetting::get('gacha_special_prize_enabled', true),
            'special_prize_id' => (int) SiteSetting::get('gacha_special_prize_id', 0),
            'special_prize_interval' => $specialInterval,
            'special_sector_size' => max(40, min(100, (int) SiteSetting::get('gacha_special_sector_size', 72))),
            'special_sequence_position' => (max(0, $totalSpins - $counterBase) % $specialInterval) + 1,
        ];

        $specialPrizeOptions = GachaPrize::where('discount_type', 'percent')
            ->where('discount_value', 20)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'label', 'is_active']);

        return view('admin.gacha.prizes', compact('prizes', 'stats', 'specialPrizeOptions') + [
            'totalWeight' => max(1, (int) GachaPrize::sum('weight')),
        ]);
    }

    public function store(Request $request)
    {
        GachaPrize::create($this->validateData($request));

        return $this->ok($request, 'Hadiah gacha berhasil ditambahkan');
    }

    public function update(Request $request, GachaPrize $gachaPrize)
    {
        $gachaPrize->update($this->validateData($request, $gachaPrize));

        return $this->ok($request, 'Hadiah gacha berhasil diperbarui');
    }

    public function toggle(GachaPrize $gachaPrize)
    {
        $gachaPrize->update(['is_active' => ! $gachaPrize->is_active]);

        return back()->with('success', 'Status hadiah berhasil diubah');
    }

    public function destroy(GachaPrize $gachaPrize)
    {
        $gachaPrize->delete();

        return redirect()->route('admin.gacha-prizes')->with('success', 'Hadiah gacha berhasil dihapus');
    }

    /**
     * Simpan urutan tampil sektor roda sekaligus dari panel admin.
     */
    public function reorder(Request $request)
    {
        $order = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:gacha_prizes,id',
        ])['order'];

        foreach (array_values($order) as $index => $id) {
            GachaPrize::whereKey($id)->update(['sort_order' => $index]);
        }

        return $this->ok($request, 'Urutan sektor roda berhasil disimpan');
    }

    /**
     * Pengaturan umum dan hadiah khusus berdasarkan urutan peserta global.
     */
    public function settings(Request $request)
    {
        $data = $request->validate([
            'gacha_enabled' => 'required|boolean',
            'gacha_daily_limit' => 'required|integer|min:0|max:50',
            'gacha_special_prize_enabled' => 'required|boolean',
            'gacha_special_prize_id' => 'nullable|integer|exists:gacha_prizes,id',
            'gacha_special_prize_interval' => 'required|integer|min:2|max:100000',
            'gacha_special_sector_size' => 'required|integer|min:40|max:100',
            'reset_special_sequence' => 'nullable|boolean',
        ]);

        if (! empty($data['gacha_special_prize_id'])) {
            $specialPrize = GachaPrize::find($data['gacha_special_prize_id']);

            if ($specialPrize->discount_type !== 'percent' || (float) $specialPrize->discount_value !== 20.0) {
                return response()->json([
                    'message' => 'Hadiah khusus harus berupa Diskon 20%.',
                    'errors' => ['gacha_special_prize_id' => ['Hadiah khusus harus berupa Diskon 20%.']],
                ], 422);
            }
        }

        SiteSetting::set('gacha_enabled', $data['gacha_enabled'] ? '1' : '0');
        SiteSetting::set('gacha_daily_limit', (string) $data['gacha_daily_limit']);
        SiteSetting::set('gacha_special_prize_enabled', $data['gacha_special_prize_enabled'] ? '1' : '0');
        SiteSetting::set('gacha_special_prize_id', (string) ($data['gacha_special_prize_id'] ?? 0));
        SiteSetting::set('gacha_special_prize_interval', (string) $data['gacha_special_prize_interval']);
        SiteSetting::set('gacha_special_sector_size', (string) $data['gacha_special_sector_size']);

        if (! empty($data['reset_special_sequence'])) {
            SiteSetting::set('gacha_special_prize_counter_base', (string) GachaSpin::count());
        }

        $payload = [
            'message' => 'Pengaturan gacha berhasil disimpan',
            'enabled' => (bool) SiteSetting::get('gacha_enabled', true),
            'daily_limit' => (int) SiteSetting::get('gacha_daily_limit', 1),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('admin.gacha-prizes')->with('success', $payload['message']);
    }

    private function validateData(Request $request, ?GachaPrize $gachaPrize = null): array
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('discount_type') === 'percent' && (float) $value > 100) {
                        $fail('Diskon persen maksimal 100%.');
                    }
                },
            ],
            'min_spend' => 'nullable|integer|min:0',
            'weight' => 'required|integer|min:1|max:10000',
            'quota' => 'nullable|integer|min:0',
            'validity_days' => 'required|integer|min:1|max:3650',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'discount_type' => 'tipe diskon',
            'discount_value' => 'nilai diskon',
            'min_spend' => 'minimal belanja',
            'validity_days' => 'masa berlaku',
        ]);

        $quota = $data['quota'] ?? null;

        return [
            'label' => $data['label'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_spend' => (int) ($data['min_spend'] ?? 0),
            'weight' => (int) $data['weight'],
            'quota' => ($quota === null || (int) $quota < 1) ? null : (int) $quota,
            'validity_days' => (int) $data['validity_days'],
            'color' => $data['color'] ?: '#7c3aed',
            'sort_order' => (int) ($data['sort_order'] ?? ($gachaPrize?->sort_order ?? 0)),
            'is_active' => $request->boolean('is_active', $gachaPrize?->is_active ?? true),
        ];
    }

    private function ok(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.gacha-prizes')->with('success', $message);
    }
}
