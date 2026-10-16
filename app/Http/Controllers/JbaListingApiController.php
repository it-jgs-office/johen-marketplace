<?php

namespace App\Http\Controllers;

use App\Models\AccountListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JbaListingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $input = $request->validate([
            'game' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $listings = AccountListing::query()
            ->where('is_active', true)
            ->where('is_sold', false)
            ->when($input['game'] ?? null, fn ($query, string $game) => $query->where('game', $game))
            ->latest('updated_at')
            ->paginate($input['per_page'] ?? 24)
            ->through(fn (AccountListing $listing) => $this->listingData($listing));

        return response()->json([
            'client' => $request->attributes->get('jba_api_client'),
            'data' => $listings->items(),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
            ],
        ]);
    }

    public function show(Request $request, int $listing): JsonResponse
    {
        $accountListing = AccountListing::query()
            ->where('is_active', true)
            ->where('is_sold', false)
            ->findOrFail($listing);

        return response()->json([
            'client' => $request->attributes->get('jba_api_client'),
            'data' => $this->listingData($accountListing),
        ]);
    }

    private function listingData(AccountListing $listing): array
    {
        return [
            'id' => $listing->id,
            'game' => $listing->game,
            'product_name' => $listing->product_name,
            'specifications' => $listing->specifications,
            'price' => (float) $listing->price,
            'original_price' => $listing->original_price === null ? null : (float) $listing->original_price,
            'promo_type' => $listing->promo_type,
            'discount_percent' => $listing->discount_percent,
            'collector_tier' => $listing->collector_tier,
            'deal_type' => $listing->deal_type,
            'thumbnail_url' => $listing->thumbnail_url,
            'photo_url' => $listing->photo_url,
            'detail_photo_urls' => $listing->detail_photo_urls,
            'video_url' => $listing->video_url,
            'updated_at' => $listing->updated_at?->toIso8601String(),
        ];
    }
}
