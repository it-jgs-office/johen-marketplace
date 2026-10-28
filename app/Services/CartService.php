<?php

namespace App\Services;

use App\Models\CartItem;
use Illuminate\Support\Collection;

class CartService
{
    public const MAX_QTY = 99;

    /** @return array{0:bool,1:string,2:int} */
    public function add(int $userId, int $listingId): array
    {
        $listing = app(JbaApiClient::class)->find($listingId);

        if (! $listing || ! $listing->is_active || $listing->is_sold) {
            return [false, 'Akun ini sudah tidak tersedia.', $this->countItems($userId)];
        }

        CartItem::firstOrCreate([
            'user_id' => $userId,
            'account_listing_id' => $listing->id,
        ], [
            'quantity' => 1,
        ]);

        return [true, $listing->product_name.' masuk keranjang.', $this->countItems($userId)];
    }

    public function remove(int $userId, int $itemId): array
    {
        CartItem::query()
            ->where('user_id', $userId)
            ->whereNotNull('account_listing_id')
            ->whereKey($itemId)
            ->delete();

        return [true, 'Akun dihapus dari keranjang.', $this->countItems($userId)];
    }

    public function clear(int $userId): void
    {
        CartItem::where('user_id', $userId)->whereNotNull('account_listing_id')->delete();
    }

    public function countItems(int $userId): int
    {
        return CartItem::where('user_id', $userId)->whereNotNull('account_listing_id')->count();
    }

    /** @return Collection<int, CartItem> */
    public function itemsFor(int $userId): Collection
    {
        $items = CartItem::query()
            ->where('user_id', $userId)
            ->whereNotNull('account_listing_id')
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            return $items;
        }

        $listings = app(JbaApiClient::class)->listings()->keyBy('id');

        return $items->map(function (CartItem $item) use ($listings) {
            $listing = $listings->get($item->account_listing_id);
            $item->setRelation('listing', $listing);
            $item->unavailable = ! $listing || ! $listing->is_active || $listing->is_sold;
            $item->line_total = $item->unavailable ? 0 : (int) $listing->price;

            return $item;
        });
    }

    /** @return array{items:Collection,subtotal:int,total_qty:int,unavailable:int} */
    public function summary(int $userId): array
    {
        $items = $this->itemsFor($userId);

        return [
            'items' => $items,
            'subtotal' => (int) $items->sum('line_total'),
            'total_qty' => (int) $items->where('unavailable', false)->count(),
            'unavailable' => (int) $items->where('unavailable', true)->count(),
        ];
    }

    /** @return array{count:int,total_qty:int,subtotal:int,unavailable:int,items:array<int,array<string,mixed>>} */
    public function state(int $userId): array
    {
        $summary = $this->summary($userId);

        return [
            'count' => $this->countItems($userId),
            'total_qty' => $summary['total_qty'],
            'subtotal' => $summary['subtotal'],
            'unavailable' => $summary['unavailable'],
            'items' => $summary['items']->map(fn (CartItem $item) => [
                'id' => (int) $item->id,
                'account_listing_id' => (int) $item->account_listing_id,
                'unavailable' => (bool) $item->unavailable,
                'line_total' => (int) $item->line_total,
            ])->values()->all(),
        ];
    }
}
