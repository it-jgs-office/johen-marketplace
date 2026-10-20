<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckProviderController extends Controller
{
    public function index(): View
    {
        $providers = CheckProvider::orderBy('priority')->orderBy('label')->get();

        return view('admin.check-providers', compact('providers'));
    }

    public function update(Request $request, CheckProvider $checkProvider): RedirectResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'free_type' => 'nullable|string|max:40',
            'free_config_json' => 'nullable|string',
            'fallback_free_type' => 'nullable|string|max:40',
            'fallback_free_config_json' => 'nullable|string',
            'digiflazz_fallback_enabled' => 'required|boolean',
            'digiflazz_sku' => 'nullable|string|max:120',
            'priority' => 'nullable|integer|min:0|max:999',
        ]);

        $checkProvider->update([
            'label' => $data['label'],
            'free_type' => $data['free_type'] ?: null,
            'free_config' => $this->decodeJson($data['free_config_json'] ?? null),
            'fallback_free_type' => $data['fallback_free_type'] ?: null,
            'fallback_free_config' => $this->decodeJson($data['fallback_free_config_json'] ?? null),
            'digiflazz_fallback_enabled' => $request->boolean('digiflazz_fallback_enabled'),
            'digiflazz_sku' => $data['digiflazz_sku'] ?: null,
            'priority' => $data['priority'] ?? 0,
        ]);

        CheckProvider::flushCache();

        return back()->with('success', 'Provider '.$checkProvider->label.' berhasil disimpan.');
    }

    public function toggle(CheckProvider $checkProvider): RedirectResponse
    {
        $checkProvider->update(['enabled' => ! $checkProvider->enabled]);
        CheckProvider::flushCache();

        return back()->with('success', 'Provider '.$checkProvider->label.' '.($checkProvider->enabled ? 'diaktifkan' : 'dinonaktifkan').'.');
    }

    private function decodeJson(?string $json): ?array
    {
        $json = trim((string) $json);

        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
