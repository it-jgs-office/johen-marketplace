<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\JbaApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JbaGameCardController extends Controller
{
    public function index(): View
    {
        $brands = Brand::where('is_popular', true)
            ->orderBy('name')
            ->get();
        $tampilan = app(JbaApiClient::class)->tampilan()->keyBy('game');
        $cards = $brands->map(function (Brand $brand) use ($tampilan) {
            $game = $this->remoteGame($brand);

            return [
                'brand' => $brand,
                'game' => $game,
                'thumbnail_url' => $game ? ($tampilan->get($game)['thumbnail_url'] ?? null) : null,
                'banner_url' => $game ? ($tampilan->get($game)['banner_url'] ?? null) : null,
                'urutan' => $game ? ($tampilan->get($game)['urutan'] ?? null) : null,
            ];
        })->filter(fn (array $card) => $card['game'])->values();

        return view('admin.jba-game-cards.index', compact('cards'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $game = $this->remoteGame($brand);
        abort_unless($brand->is_popular && $game, 404);

        $request->validate([
            'jba_card_image' => 'nullable|image|mimes:jpeg,png,webp|max:2048|dimensions:max_width=8000,max_height=8000',
            'jba_banner_image' => 'nullable|image|mimes:jpeg,png,webp|max:4096|dimensions:max_width=8000,max_height=8000',
            'jba_urutan' => 'nullable|integer|min:1|max:99',
            'hapus_jba_card_image' => 'nullable|boolean',
            'hapus_jba_banner_image' => 'nullable|boolean',
        ]);

        $saved = app(JbaApiClient::class)->updateTampilan([
            'jba_'.$game.'_urutan' => $request->input('jba_urutan'),
            'jba_'.$game.'_hapus_thumbnail' => $request->boolean('hapus_jba_card_image'),
            'jba_'.$game.'_hapus_banner' => $request->boolean('hapus_jba_banner_image'),
        ], [
            'jba_'.$game.'_thumbnail' => $request->file('jba_card_image'),
            'jba_'.$game.'_banner' => $request->file('jba_banner_image'),
        ]);

        if (! $saved) {
            return back()->with('error', 'Gambar tidak dapat disimpan ke API Stock Akun.');
        }

        return back()->with('success', 'Tampilan '.$brand->name.' berhasil disimpan ke company profile.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $game = $this->remoteGame($brand);
        abort_unless($brand->is_popular && $game, 404);

        $deleted = app(JbaApiClient::class)->updateTampilan([
            'jba_'.$game.'_hapus_thumbnail' => true,
        ]);

        if (! $deleted) {
            return back()->with('error', 'Gambar tidak dapat dihapus dari API Stock Akun.');
        }

        return back()->with('success', 'Gambar kartu '.$brand->name.' berhasil dihapus dari company profile.');
    }

    private function remoteGame(Brand $brand): ?string
    {
        return match (mb_strtolower(trim($brand->name))) {
            'mobile legends', 'mobile legends: bang bang' => 'ml',
            'pubg', 'pubg mobile' => 'pubg',
            'fc mobile' => 'fc_mobile',
            'free fire' => 'ff',
            'valorant' => 'valorant',
            'roblox' => 'roblox',
            'e-football', 'efootball' => 'efootball',
            default => null,
        };
    }
}
