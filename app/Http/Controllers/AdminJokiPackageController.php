<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Services\ImageOptimizer;
use App\Services\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminJokiPackageController extends Controller
{
    public function index()
    {
        $packages = Product::where('type', 'joki')
            ->orderBy('joki_sort_order')
            ->orderBy('selling_price')
            ->get();

        return view('admin.joki-packages.index', compact('packages'));
    }

    public function store(Request $request)
    {
        Product::create($this->validated($request));

        return back()->with('success', 'Paket joki berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->type === 'joki', 404);
        $product->update($this->validated($request, $product));

        return back()->with('success', 'Paket joki berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        abort_unless($product->type === 'joki', 404);
        if ($product->photo && ! str_starts_with($product->photo, 'assets/')) {
            MediaStore::delete($product->photo);
        }
        $product->delete();

        return back()->with('success', 'Paket joki berhasil dihapus.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'product_name' => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'estimated_duration' => 'nullable|string|max:80',
            'joki_sort_order' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'photo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        $photo = $product?->photo;
        if ($request->hasFile('photo')) {
            if ($photo && ! str_starts_with($photo, 'assets/')) MediaStore::delete($photo);
            $photo = ImageOptimizer::storeOptimized($request->file('photo'), 'joki/packages', 512, 512);
        }

        $price = (float) $data['selling_price'];

        return [
            'buyer_sku_code' => $product?->buyer_sku_code ?: 'joki-ml-'.Str::lower(Str::random(10)),
            'brand' => $product?->brand ?: (Brand::whereRaw('LOWER(name) LIKE ?', ['mobile legends%'])->value('name') ?: 'Mobile Legends'),
            'category' => 'Games',
            'product_name' => $data['product_name'],
            'photo' => $photo,
            'price' => $product?->price ?? $price,
            'selling_price' => $price,
            'selling_price_override' => $price,
            'type' => 'joki',
            'estimated_duration' => $data['estimated_duration'] ?? null,
            'joki_sort_order' => (int) ($data['joki_sort_order'] ?? 0),
            'stock' => (int) ($data['stock'] ?? 9999),
            'region' => 'ID',
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
