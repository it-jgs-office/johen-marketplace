<?php

namespace App\Http\Controllers;

use App\Models\AccountListing;
use App\Models\Brand;
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
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('admin.account-listings.index', compact('listings', 'brands', 'games', 'filters'));
    }

    public function create()
    {
        return redirect()->route('admin.account-listings');
    }

    public function store(Request $request, JbaApiClient $api)
    {
        $this->validateRequest($request, true);
        $result = $api->adminCreate($this->payload($request), $this->files($request));

        if ($result['successful'] && ! empty($result['json']['id'])) {
            $this->saveLocalCredentials($request, (int) $result['json']['id'], $api);
        }

        return $this->writeResponse(
            $request,
            $result,
            'Listing akun berhasil ditambahkan.'
        );
    }

    public function edit(int $listing, JbaApiClient $api)
    {
        $accountListing = $api->adminFind($listing);
        abort_unless($accountListing, 404);
        $localListing = AccountListing::query()
            ->where('source', 'jba_api')
            ->where('source_id', (string) $listing)
            ->first();
        $accountListing->login_credentials = $localListing?->login_credentials;
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('admin.account-listings.edit', compact('accountListing', 'brands'));
    }

    public function update(Request $request, int $listing, JbaApiClient $api)
    {
        $accountListing = $api->adminFind($listing);
        abort_unless($accountListing, 404);
        $this->validateRequest($request);
        $result = $api->adminUpdate($listing, $this->payload($request, $accountListing), $this->files($request));

        if ($result['successful']) {
            $this->saveLocalCredentials($request, $listing, $api);
        }

        return $this->writeResponse(
            $request,
            $result,
            'Listing akun berhasil diperbarui.'
        );
    }

    public function toggle(int $listing, JbaApiClient $api)
    {
        $accountListing = $api->adminFind($listing);
        abort_unless($accountListing, 404);
        $raw = (array) $accountListing->getAttribute('jba_raw');

        return $this->writeResponse(
            request(),
            $api->adminUpdate($listing, $this->payloadFromRaw($raw, [
                'status' => $accountListing->is_sold ? 'tersedia' : 'habis',
            ])),
            'Status akun berhasil diperbarui.'
        );
    }

    public function destroy(int $listing, JbaApiClient $api)
    {
        return $this->writeResponse(
            request(),
            $api->adminDelete($listing),
            'Listing akun berhasil dihapus.'
        );
    }

    private function validateRequest(Request $request, bool $isCreate = false): void
    {
        $request->validate([
            'game' => 'required|string|max:100',
            'id_game' => 'required|string|max:255',
            'product_name' => 'required|string|max:160',
            'specifications' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'penjual' => 'required|string|max:80',
            'urutan' => 'required|integer|min:0',
            'collector_tier' => 'nullable|in:ternama,terhormat,juragan,sultan',
            'is_sold' => 'nullable|boolean',
            'photo' => ($isCreate ? 'required' : 'nullable').'|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'detail_photo_4' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'video_url' => 'nullable|string|max:255',
            'login_credentials' => 'required|string|max:4000',
        ]);
    }

    private function saveLocalCredentials(Request $request, int $listingId, JbaApiClient $api): void
    {
        AccountListing::updateOrCreate([
            'source' => 'jba_api',
            'source_id' => (string) $listingId,
        ], [
            'source_url' => $api->sourceUrl($listingId),
            'game' => (string) $request->input('game'),
            'product_name' => (string) $request->input('product_name'),
            'specifications' => (string) $request->input('specifications'),
            'owner_name' => (string) $request->input('penjual'),
            'price' => (float) $request->input('price'),
            'original_price' => $request->filled('original_price') ? (float) $request->input('original_price') : null,
            'is_active' => true,
            'is_sold' => $request->boolean('is_sold'),
            'login_credentials' => trim((string) $request->input('login_credentials')),
        ]);
    }

    private function payload(Request $request, ?AccountListing $current = null): array
    {
        $raw = (array) $current?->getAttribute('jba_raw');
        $hargaJual = (int) $request->input('price');
        $hargaAsli = (int) ($request->filled('original_price') ? $request->input('original_price') : $hargaJual);
        $diskon = $hargaAsli > $hargaJual
            ? min(99, max(0, (int) round((1 - ($hargaJual / $hargaAsli)) * 100)))
            : 0;

        return $this->payloadFromRaw($raw, [
            'game' => $this->remoteGame((string) $request->input('game')),
            'id_game' => $request->input('id_game'),
            'record' => $request->input('product_name'),
            'penjual' => $request->input('penjual'),
            'harga' => $hargaAsli,
            'diskon' => $diskon,
            'kolektor' => $this->collectorLabel($request->input('collector_tier')),
            'deskripsi' => $request->input('specifications'),
            'video' => $request->input('video_url'),
            'status' => $request->boolean('is_sold') ? 'habis' : 'tersedia',
            'urutan' => (int) $request->input('urutan'),
        ]);
    }

    private function payloadFromRaw(array $raw, array $changes = []): array
    {
        $payload = [
            'game' => $raw['game'] ?? 'ml',
            'id_game' => $raw['id_game'] ?? '',
            'record' => $raw['record'] ?? '',
            'penjual' => $raw['penjual'] ?? 'Johen Gaming',
            'harga' => (int) ($raw['harga'] ?? 0),
            'diskon' => (int) ($raw['diskon'] ?? 0),
            'diskon_mulai' => $raw['diskon_mulai'] ?? null,
            'diskon_selesai' => $raw['diskon_selesai'] ?? null,
            'diskon_nonaktif' => (bool) ($raw['diskon_nonaktif'] ?? false),
            'label' => $raw['label'] ?? null,
            'kolektor' => $raw['kolektor'] ?? null,
            'deskripsi' => $raw['deskripsi'] ?? '',
            'video' => $raw['video'] ?? null,
            'status' => $raw['status'] ?? 'tersedia',
            'deal_type' => $raw['deal_type'] ?? 'normal',
            'urutan' => (int) ($raw['urutan'] ?? 0),
        ];

        if (($payload['deal_type'] ?? 'normal') === 'bandel') {
            $items = array_values(array_filter((array) ($raw['bandel_items'] ?? []), 'is_array'));
            $payload['bandel_items'] = [
                'kode_akun' => array_column($items, 'kode_akun'),
                'jumlah_hero' => array_column($items, 'jumlah_hero'),
                'jumlah_skin' => array_column($items, 'jumlah_skin'),
                'deskripsi' => array_column($items, 'deskripsi'),
                'foto_existing' => array_column($items, 'foto'),
            ];
        }

        return array_merge($payload, $changes);
    }

    private function files(Request $request): array
    {
        return [
            'foto' => $request->file('photo'),
            'screenshots[]' => array_filter([
                $request->file('detail_photo_1'),
                $request->file('detail_photo_2'),
                $request->file('detail_photo_3'),
                $request->file('detail_photo_4'),
            ]),
        ];
    }

    private function remoteGame(string $game): string
    {
        return match (mb_strtolower(trim($game))) {
            'mobile legends', 'mobile legends: bang bang', 'ml', 'mlbb' => 'ml',
            'pubg', 'pubg mobile' => 'pubg',
            'fc mobile', 'fcm', 'fc_mobile' => 'fc_mobile',
            'free fire', 'ff' => 'ff',
            'valorant' => 'valorant',
            'roblox' => 'roblox',
            'e-football', 'efootball' => 'efootball',
            default => $game,
        };
    }

    private function collectorLabel(?string $tier): ?string
    {
        return match ($tier) {
            'ternama' => 'Kolektor Ternama',
            'terhormat' => 'Kolektor Terhormat',
            'juragan' => 'Kolektor Juragan',
            'sultan' => 'Kolektor Sultan',
            default => null,
        };
    }

    private function writeResponse(Request $request, array $result, string $successMessage)
    {
        if ($result['successful']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($result['json'] + ['message' => $successMessage], $result['status'] ?: 200);
            }

            return redirect()->route('admin.account-listings')->with('success', $successMessage);
        }

        $errors = $result['json']['errors'] ?? ['api' => [$result['json']['message'] ?? 'API Jual Beli Akun tidak dapat dihubungi.']];
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['errors' => $errors, 'message' => $result['json']['message'] ?? 'Data gagal disimpan.'], $result['status'] ?: 502);
        }

        return back()->withErrors($errors)->withInput();
    }
}
