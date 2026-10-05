<?php

namespace App\Services;

use App\Models\FlashDeal;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Membangun satu baris order top-up.
 *
 * Dipakai oleh dua pemanggil supaya hasilnya identik:
 *   - OrderController (Beli Sekarang / pesan inline), satu order per checkout
 *   - CheckoutController (keranjang), satu order anak per item
 *
 * Pemecahan jadi dua langkah disengaja. Harga harus dihitung untuk semua item
 * dulu supaya voucher bisa diterapkan ke total gabungan, baru baris order-nya
 * dibuat. `lockItem()` membekukan harga flash deal dan incarceration kuotanya,
 * jadi WAJIB dipanggil di dalam transaksi database.
 */
class TopupOrderBuilder
{
    /**
     * Kunci harga satu item dan pesan kuota flash deal-nya.
     *
     * @return array{unit_price:int,original_price:?int,flash_deal_id:?int,line_total:int}
     *
     * @throws RuntimeException bila stok produk habis
     */
    public function lockItem(Product $product, int $quantity): array
    {
        $quantity = max(1, min(CartService::MAX_QTY, $quantity));

        if ($product->stock < 1) {
            throw new RuntimeException('Stok '.$product->product_name.' sedang kosong.');
        }

        $flash = FlashDeal::active()
            ->lockForUpdate()
            ->where('product_id', $product->id)
            ->first();

        $unitPrice = (int) $product->selling_price;
        $originalPrice = null;
        $flashDealId = null;

        if ($flash) {
            $unitPrice = (int) $flash->flash_price;
            $originalPrice = (int) $product->selling_price;
            $flashDealId = $flash->id;
            $flash->consumeQty($quantity);
        }

        return [
            'unit_price' => $unitPrice,
            'original_price' => $originalPrice,
            'flash_deal_id' => $flashDealId,
            'line_total' => $unitPrice * $quantity,
        ];
    }

    /**
     * Hitung potongan voucher terhadap subtotal.
     *
     * @return array{discount:int,voucher:?Voucher}
     *
     * @throws RuntimeException bila kode tidak valid
     */
    public function voucherDiscount(?string $code, int $subtotal): array
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return ['discount' => 0, 'voucher' => null];
        }

        // Kode lama yang masih dipakai, tidak disimpan di tabel vouchers.
        if ($code === 'JOHENI10' || $code === 'JOHENGAMING10') {
            return ['discount' => (int) round($subtotal * 0.1), 'voucher' => null];
        }

        $voucher = Voucher::whereRaw('UPPER(code) = ?', [$code])->lockForUpdate()->first();

        if (! $voucher) {
            throw new RuntimeException('Kode voucher tidak ditemukan. Periksa kembali kodenya.');
        }

        if ($voucher->is_expired) {
            throw new RuntimeException('Masa berlaku voucher '.$code.' sudah habis.');
        }

        if ($voucher->is_exhausted) {
            throw new RuntimeException('Voucher '.$code.' sudah habis dipakai.');
        }

        $discount = $voucher->discountFor($subtotal);

        if ($discount < 1) {
            throw new RuntimeException($voucher->min_spend > 0
                ? 'Voucher ini berlaku untuk belanja minimal Rp'.number_format($voucher->min_spend, 0, ',', '.').'.'
                : 'Voucher '.$code.' tidak bisa dipakai untuk pesanan ini.');
        }

        return ['discount' => $discount, 'voucher' => $voucher];
    }

    /**
     * Buat baris order + Transaction. Tidak menyentuh gateway.
     */
    public function createOrder(array $input, array $locked): Order
    {
        $product = $input['product'];
        $quantity = (int) $input['quantity'];
        $price = (int) $input['price'];

        $order = Order::create([
            'user_id' => ($input['user'] ?? null)?->id,
            'checkout_id' => ($input['checkout'] ?? null)?->id,
            'order_id' => 'TUP-'.strtoupper(Str::random(10)),
            'buyer_sku_code' => $product->buyer_sku_code,
            'customer_number' => trim((string) $input['customer_number']),
            'zone_id' => $input['zone_id'] ?? null,
            'customer_name' => $input['customer_name'] ?? null,
            'customer_phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? ($input['user'] ?? null)?->email,
            'product_name' => $product->product_name,
            'brand' => $product->brand,
            'category' => $product->category,
            'price' => $price,
            'original_price' => $locked['original_price'] ?? null,
            'flash_deal_id' => $locked['flash_deal_id'] ?? null,
            'voucher_id' => $input['voucher_id'] ?? null,
            'quantity' => $quantity,
            'status' => 'pending',
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'gross_amount' => $price,
            'status' => 'pending',
        ]);

        return $order;
    }

    /**
     * Bagikan diskon ke beberapa order anak proporsional terhadap harga baris.
     *
     * Sisa pembagian akibat pembulatan ditambahkan ke baris terakhir supaya
     * jumlah price anak selalu sama dengan total yang dibayar user.
     *
     * @param  array<int,int>  $lineTotals  harga mentah tiap baris, urutan sama dengan $orders
     * @param  array<int,Order>  $orders
     * @return array<int,int> harga final tiap order, urutan sama dengan $orders
     */
    public function allocateDiscount(array $orders, array $lineTotals, int $discount): array
    {
        $subtotal = array_sum($lineTotals);

        if ($discount < 1 || $subtotal < 1) {
            return [];
        }

        // Jangan sampai diskon melebihi nilai keranjang.
        $discount = min($discount, $subtotal);

        $allocated = [];
        $running = 0;

        foreach ($lineTotals as $i => $lineTotal) {
            $share = (int) round($discount * ($lineTotal / $subtotal));
            $allocated[$i] = $share;
            $running += $share;
        }

        // Sisa pembulakan menempel ke baris terbesar supaya tidak negatif.
        $remainder = $discount - $running;

        if ($remainder !== 0 && $lineTotals !== []) {
            $biggest = array_keys($lineTotals, max($lineTotals))[0];
            $allocated[$biggest] += $remainder;
        }

        $prices = [];

        foreach ($orders as $i => $order) {
            $price = max(0, (int) $lineTotals[$i] - $allocated[$i]);
            $prices[$i] = $price;

            $order->update(['price' => $price]);

            $order->transaction?->update(['gross_amount' => $price]);
        }

        return $prices;
    }

    /**
     * Paket jatah pakai voucher ke semua order anak sekaligus.
     */
    public function consumeVoucher(?Voucher $voucher, array $orders): void
    {
        if (! $voucher) {
            return;
        }

        foreach ($orders as $order) {
            $voucher->markUsed($order);
        }
    }
}