<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Keranjang belanja.
 *
 * Harga di keranjang hanya indikatif: flash deal dan stok bisa berubah kapan
 * saja. Harga yang dipakai saat bayar selalu dihitung ulang di CheckoutService
 * di dalam transaksi database.
 */
class CartService
{
    public const MAX_QTY = 99;

    /**
     * Tambah produk ke keranjang. Produk yang sudah ada digabung qty-nya.
     *
     * @return array{0:bool,1:string,2:int} [sukses, pesan, total item di keranjang]
     */
    public function add(int $userId, int $productId, int $quantity = 1): array
    {
        $quantity = max(1, min(self::MAX_QTY, $quantity));

        $product = Product::where('id', $productId)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            return [false, 'Produk tidak tersedia.', 0];
        }

        if ($product->stock < 1) {
            return [false, 'Stok produk ini sedang kosong.', 0];
        }

        $item = CartItem::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        $target = min(self::MAX_QTY, ($item?->quantity ?? 0) + $quantity);

        if ($item) {
            $item->update(['quantity' => $target]);
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'quantity' => $target,
            ]);
        }

        return [true, $product->product_name.' masuk keranjang.', $this->countItems($userId)];
    }

    public function updateQuantity(int $userId, int $itemId, int $quantity): array
    {
        $item = $this->findItem($userId, $itemId);

        if (! $item) {
            return [false, 'Item keranjang tidak ditemukan.', 0];
        }

        $quantity = max(1, min(self::MAX_QTY, $quantity));

        // Batasi total per produk dengan stok yang tersedia supaya user tidak
        // menambah item yang memang tidak bisa dipenuhi.
        if ($item->product && $item->product->stock > 0) {
            $quantity = min($quantity, $item->product->stock);
        }

        $item->update(['quantity' => $quantity]);

        return [true, 'Jumlah diperbarui.', $this->countItems($userId)];
    }

    public function remove(int $userId, int $itemId): array
    {
        $item = $this->findItem($userId, $itemId);

        if ($item) {
            $item->delete();
        }

        return [true, 'Item dihapus dari keranjang.', $this->countItems($userId)];
    }

    public function clear(int $userId): void
    {
        CartItem::where('user_id', $userId)->delete();
    }

    public function countItems(int $userId): int
    {
        return (int) CartItem::where('user_id', $userId)->sum('quantity');
    }

    public function itemsFor(int $userId): Collection
    {
        return CartItem::with('product')
            ->where('user_id', $userId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Baris keranjang lengkap dengan harga indikatif, sudah termasuk harga
     * flash deal kalau sedang aktif.
     *
     * @return array{items:Collection,subtotal:int,total_qty:int,unavailable:int}
     */
    public function summary(int $userId): array
    {
        $items = $this->itemsFor($userId);

        if ($items->isEmpty()) {
            return [
                'items' => $items,
                'subtotal' => 0,
                'total_qty' => 0,
                'unavailable' => 0,
            ];
        }

        $products = $items->pluck('product_id')->filter()->all();

        $flashDeals = FlashDeal::active()
            ->whereIn('product_id', $products)
            ->get()
            ->keyBy('product_id');

        $subtotal = 0;
        $qty = 0;
        $unavailable = 0;

        foreach ($items as $item) {
            $product = $item->product;

            if (! $product || ! $product->is_active || $product->stock < 1) {
                $item->unavailable = true;
                $item->unit_price = 0;
                $item->line_total = 0;
                $unavailable++;
                continue;
            }

            $deal = $flashDeals->get($product->id);
            $unit = $deal ? (int) $deal->flash_price : (int) $product->selling_price;

            $item->unavailable = false;
            $item->flash_deal = $deal;
            $item->unit_price = $unit;
            $item->original_price = (int) $product->selling_price;
            $item->line_total = $unit * (int) $item->quantity;

            $subtotal += $item->line_total;
            $qty += (int) $item->quantity;
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'total_qty' => $qty,
            'unavailable' => $unavailable,
        ];
    }

    /**
     * Satu grup input per brand. Karena kebutuhan Zone ID mengikuti brand,
     * item dari game berbeda tidak boleh memakai User ID yang sama.
     *
     * @return Collection<int,object>
     */
    public function brandGroups(int $userId): \Illuminate\Support\Collection
    {
        $items = $this->itemsFor($userId)
            ->filter(fn ($i) => $i->product && $i->product->is_active && $i->product->stock >= 1);

        $brands = Brand::whereIn('name', $items->pluck('product.brand')->filter()->unique()->all())
            ->get()
            ->keyBy(fn ($b) => strtolower((string) $b->name));

        return $items
            ->groupBy(fn ($i) => strtolower((string) $i->product->brand))
            ->map(function ($groupItems, $brandKey) use ($brands) {
                $brand = $brands->get($brandKey);

                return (object) [
                    'key' => $brandKey,
                    'name' => $brand?->name ?? $groupItems->first()->product->brand,
                    'requires_zone_id' => (bool) ($brand?->requires_zone_id ?? false)
                        || (str_starts_with($brandKey, 'mobile legends') && $groupItems->contains(fn ($item) => str_ends_with(mb_strtolower($item->product->buyer_sku_code), '-idn'))),
                    'icon' => $brand?->icon,
                    'thumbnail' => $brand?->thumbnail,
                    'thumbnail_url' => $brand?->thumbnail_url,
                    'items' => $groupItems,
                ];
            })
            ->values();
    }

    /**
     * Ringkasan keranjang dalam bentuk yang bisa langsung dikirim ke browser.
     *
     * Dipakai supaya semua angka (badge navbar, jumlah item di halaman keranjang
     * dan checkout, subtotal, status stok) berasal dari satu sumber data. Jadi
     * begitu produk ditambah atau dihapus, frontend cukup mem-patch angka yang
     * sudah tampil tanpa perlu reload halaman.
     *
     * `count` sengaja menghitung seluruh baris keranjang, termasuk yang stoknya
     * habis, supaya badge dan keterangan "N item di keranjang" selalu sama.
     * Item yang tidak bisa dibayar tidak masuk `subtotal`.
     *
     * @return array{count:int,total_qty:int,subtotal:int,unavailable:int,items:array<int,array<string,mixed>>}
     */
    public function state(int $userId): array
    {
        $summary = $this->summary($userId);

        $items = $summary['items']
            ->map(function (CartItem $item) {
                $product = $item->product;
                $quantity = (int) $item->quantity;
                $unitPrice = (int) ($item->unit_price ?? 0);
                $lineTotal = (int) ($item->line_total ?? 0);

                return [
                    'id' => (int) $item->id,
                    'product_id' => (int) $item->product_id,
                    'name' => $product?->product_name,
                    'brand' => $product?->brand,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'original_price' => (int) ($item->original_price ?? 0),
                    'flash_deal' => (bool) ($item->flash_deal ?? false),
                    'unavailable' => (bool) ($item->unavailable ?? false),
                    // Batas tombol "+" supaya tidak melebihi stok yang tersedia.
                    'max' => (int) ($product?->stock ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'count' => $this->countItems($userId),
            'total_qty' => (int) $summary['total_qty'],
            'subtotal' => (int) $summary['subtotal'],
            'unavailable' => (int) $summary['unavailable'],
            'items' => $items,
        ];
    }

    protected function findItem(int $userId, int $itemId): ?CartItem
    {
        return CartItem::where('user_id', $userId)->whereKey($itemId)->first();
    }
}
