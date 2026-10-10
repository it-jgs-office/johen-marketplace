<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\ImageOptimizer;
use App\Services\MediaStore;
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

        return view('admin.jba-game-cards.index', compact('brands'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        abort_unless($brand->is_popular, 404);

        $request->validate([
            'jba_card_image' => 'required|image|mimes:jpeg,png,webp|max:5120|dimensions:max_width=8000,max_height=8000',
        ]);

        $previous = $brand->jba_card_image;
        $path = ImageOptimizer::storeOptimized($request->file('jba_card_image'), 'brands/jba-cards', 960, 960);
        $brand->update(['jba_card_image' => $path]);

        if ($previous && $previous !== $brand->thumbnail && $previous !== $path) {
            MediaStore::delete($previous);
        }

        return back()->with('success', 'Gambar kartu '.$brand->name.' berhasil disimpan.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        abort_unless($brand->is_popular, 404);

        $previous = $brand->jba_card_image;
        $brand->update(['jba_card_image' => null]);

        if ($previous && $previous !== $brand->thumbnail) {
            MediaStore::delete($previous);
        }

        return back()->with('success', 'Gambar kartu '.$brand->name.' berhasil dihapus.');
    }
}
