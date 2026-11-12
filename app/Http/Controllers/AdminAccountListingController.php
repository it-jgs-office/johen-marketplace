<?php

namespace App\Http\Controllers;

use App\Models\AccountListing;
use App\Services\JbaApiClient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminAccountListingController extends Controller
{
    public function index(Request $request, JbaApiClient $api)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'game' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:tersedia,habis'],
            'sort' => ['nullable', 'in:urutan_asc,terbaru,harga_asc,harga_desc'],
        ]);

        $allListings = $api->adminListings();
        $games = $allListings->pluck('game')->filter()->unique()->sort()->values();
        $query = mb_strtolower(trim((string) ($filters['q'] ?? '')));

        $listings = $allListings
            ->when($query !== '', function ($items) use ($query) {
                return $items->filter(function (AccountListing $listing) use ($query) {
                    $raw = $listing->getAttribute('jba_raw') ?? [];
                    $haystack = implode(' ', [
                        $listing->game,
                        $listing->product_name,
                        $listing->owner_name,
                        $listing->specifications,
                        $raw['id_game'] ?? '',
                    ]);

                    return str_contains(mb_strtolower($haystack), $query);
                });
            })
            ->when($filters['game'] ?? null, fn ($items, $game) => $items->where('game', $game))
            ->when($filters['status'] ?? null, function ($items, $status) {
                return $items->filter(fn (AccountListing $listing) => $status === 'tersedia' ? ! $listing->is_sold : $listing->is_sold);
            });

        $sort = $filters['sort'] ?? 'urutan_asc';
        $listings = (match ($sort) {
            'terbaru' => $listings->sortByDesc(fn (AccountListing $listing) => data_get($listing->getAttribute('jba_raw'), 'created_at', '')),
            'harga_asc' => $listings->sortBy(fn (AccountListing $listing) => $listing->price),
            'harga_desc' => $listings->sortByDesc(fn (AccountListing $listing) => $listing->price),
            default => $listings->sortBy(fn (AccountListing $listing) => [data_get($listing->getAttribute('jba_raw'), 'urutan', PHP_INT_MAX), mb_strtolower($listing->product_name)]),
        })->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $listings = new LengthAwarePaginator(
            $listings->forPage($page, $perPage)->values(),
            $listings->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.account-listings.index', compact('listings', 'games', 'filters'));
    }

    public function show(int $listing, JbaApiClient $api)
    {
        $accountListing = $api->adminFind($listing);
        abort_unless($accountListing, 404);

        return view('admin.account-listings.show', compact('accountListing'));
    }
}
