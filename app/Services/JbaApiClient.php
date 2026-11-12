<?php

namespace App\Services;

use App\Models\AccountListing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JbaApiClient
{
    private const GAME_LABELS = [
        'ml' => 'Mobile Legends',
        'pubg' => 'PUBG Mobile',
        'fc_mobile' => 'FC Mobile',
        'ff' => 'Free Fire',
        'roblox' => 'Roblox',
        'valorant' => 'Valorant',
        'efootball' => 'E-Football',
    ];

    public function listings(?string $game = null): Collection
    {
        try {
            $remoteGame = $game ? $this->remoteGameSlug($game) : null;
            $listings = Cache::remember(
                'jba_api.listings.'.($remoteGame ?: 'all'),
                now()->addMinute(),
                function () use ($remoteGame) {
                    $response = Http::acceptJson()
                        ->timeout(15)
                        ->get($this->endpoint('/jual-beli-akun'), array_filter(['game' => $remoteGame]));

                    if (! $response->successful()) {
                        Log::warning('JBA API listing request failed.', ['status' => $response->status()]);
                        throw new \RuntimeException('JBA API listing request failed.');
                    }

                    return $response->json('data', []);
                }
            );

            return collect($listings)
                ->filter(fn ($listing) => is_array($listing))
                ->map(fn (array $listing) => $this->mapListing($listing))
                ->values();
        } catch (\Throwable $e) {
            Log::warning('JBA API listing request failed.', ['exception' => $e->getMessage()]);

            return collect();
        }
    }

    /** Konfigurasi kartu dan banner yang dikelola dari company profile. */
    public function tampilan(): Collection
    {
        try {
            $tampilan = Cache::remember('jba_api.tampilan', now()->addMinute(), function () {
                $response = Http::acceptJson()
                    ->timeout(15)
                    ->get($this->endpoint('/jual-beli-akun/tampilan'));

                if (! $response->successful()) {
                    Log::warning('JBA API tampilan request failed.', ['status' => $response->status()]);
                    throw new \RuntimeException('JBA API tampilan request failed.');
                }

                return $response->json('data', []);
            });

            return collect($tampilan)
                ->filter(fn ($item) => is_array($item) && ! empty($item['game']))
                ->values();
        } catch (\Throwable $e) {
            Log::warning('JBA API tampilan request failed.', ['exception' => $e->getMessage()]);

            return collect();
        }
    }

    public function find(int $id): ?AccountListing
    {
        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->get($this->endpoint('/jual-beli-akun/'.$id));

            if (! $response->successful() || ! is_array($response->json('data'))) {
                return null;
            }

            return $this->mapListing($response->json('data'));
        } catch (\Throwable $e) {
            Log::warning('JBA API detail request failed.', ['listing_id' => $id, 'exception' => $e->getMessage()]);

            return null;
        }
    }

    /** Data admin selalu diambil dari company profile, termasuk stok habis. */
    public function adminListings(): Collection
    {
        try {
            $response = $this->adminRequest()->get($this->endpoint('/admin/jual-beli-akun'));

            if (! $response->successful()) {
                Log::warning('JBA API admin listing request failed.', ['status' => $response->status()]);

                return collect();
            }

            return collect($response->json('data', []))
                ->filter(fn ($listing) => is_array($listing))
                ->map(fn (array $listing) => $this->mapListing($listing))
                ->values();
        } catch (\Throwable $e) {
            Log::warning('JBA API admin listing request failed.', ['exception' => $e->getMessage()]);

            return collect();
        }
    }

    public function adminFind(int $id): ?AccountListing
    {
        try {
            $response = $this->adminRequest()->get($this->endpoint('/admin/jual-beli-akun/'.$id));

            return $response->successful() && is_array($response->json('data'))
                ? $this->mapListing($response->json('data'))
                : null;
        } catch (\Throwable $e) {
            Log::warning('JBA API admin detail request failed.', ['listing_id' => $id, 'exception' => $e->getMessage()]);

            return null;
        }
    }

    public function loginCredentials(int $id): ?string
    {
        try {
            $response = $this->adminRequest()->get($this->endpoint('/admin/jual-beli-akun/'.$id.'/credentials'));

            if (! $response->successful()) {
                Log::warning('JBA API credentials request failed.', [
                    'listing_id' => $id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $login = trim((string) $response->json('data.account_login'));
            $password = (string) $response->json('data.account_password');
            $zimbraLogin = trim((string) $response->json('data.zimbra_login'));
            $zimbraPassword = (string) $response->json('data.zimbra_password');

            if ($login === '' || blank($password) || $zimbraLogin === '' || blank($zimbraPassword)) {
                return null;
            }

            return "AKUN GAME\nEmail/Username: {$login}\nPassword: {$password}"
                ."\n\nAKUN ZIMBRA\nEmail: {$zimbraLogin}\nPassword: {$zimbraPassword}";
        } catch (\Throwable $e) {
            Log::warning('JBA API credentials request failed.', [
                'listing_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Klaim stok di company profile secara atomik.
     * true = berhasil, false = sudah diklaim pesanan lain, null = koneksi gagal.
     */
    public function claimSold(int $id, string $orderRef): ?bool
    {
        try {
            $response = $this->adminRequest()->post(
                $this->endpoint('/admin/jual-beli-akun/'.$id.'/claim'),
                ['order_ref' => $orderRef],
            );

            if ($response->status() === 409) {
                return false;
            }

            if (! $response->successful()) {
                Log::warning('JBA API stock claim failed.', [
                    'listing_id' => $id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            if (! (bool) $response->json('claimed')) {
                return false;
            }

            foreach (array_merge(['all'], array_keys(static::GAME_LABELS)) as $game) {
                Cache::forget('jba_api.listings.'.$game);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('JBA API stock claim failed.', [
                'listing_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function sourceUrl(int $id): string
    {
        return $this->endpoint('/jual-beli-akun/'.$id);
    }

    private function mapListing(array $data): AccountListing
    {
        $screenshots = array_values(array_filter((array) ($data['screenshots_urls'] ?? []), 'is_string'));
        $originalPrice = (float) ($data['harga'] ?? 0);
        $finalPrice = (float) ($data['harga_final'] ?? $originalPrice);
        $status = strtolower((string) ($data['status'] ?? 'tersedia'));

        $listing = new AccountListing([
            'game' => (string) (static::GAME_LABELS[$data['game'] ?? ''] ?? $data['game_label'] ?? $data['game'] ?? 'Game'),
            'product_name' => (string) (($data['record'] ?? null) ?: ($data['id_game'] ?? 'Akun Game')),
            'specifications' => (string) ($data['deskripsi'] ?? ''),
            'price' => $finalPrice,
            'original_price' => $originalPrice > $finalPrice ? $originalPrice : null,
            'owner_name' => $data['penjual'] ?? null,
            'whatsapp' => $data['wa_beli'] ?? null,
            'promo_type' => ! empty($data['diskon_aktif']) ? 'diskon' : 'none',
            'discount_percent' => ! empty($data['diskon_aktif']) ? (int) ($data['diskon'] ?? 0) : null,
            'collector_tier' => $this->collectorTier((string) ($data['kolektor'] ?? '')),
            'deal_type' => ($data['deal_type'] ?? 'normal') === 'bandel' ? 'bundle' : 'normal',
            'is_active' => true,
            'is_sold' => $status !== 'tersedia',
            'photo' => $data['foto_url'] ?? null,
            'detail_photo_1' => $screenshots[0] ?? null,
            'detail_photo_2' => $screenshots[1] ?? null,
            'detail_photo_3' => $screenshots[2] ?? null,
            'detail_photo_4' => $screenshots[3] ?? null,
            'video_url' => ($data['video'] ?? null) ?: ($data['video_upload_url'] ?? null),
        ]);

        $listing->setAttribute('id', (int) ($data['id'] ?? 0));
        $listing->setAttribute('id_game', (string) ($data['id_game'] ?? ''));
        $listing->setAttribute('jba_raw', $data);

        return $listing;
    }

    private function remoteGameSlug(string $game): string
    {
        $game = mb_strtolower(trim($game));

        foreach (static::GAME_LABELS as $slug => $label) {
            if ($game === mb_strtolower($label)) {
                return $slug;
            }
        }

        return $game;
    }

    private function collectorTier(string $collector): ?string
    {
        $collector = mb_strtolower($collector);

        foreach (['ternama', 'terhormat', 'juragan', 'sultan'] as $tier) {
            if (str_contains($collector, $tier)) {
                return $tier;
            }
        }

        return null;
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.jba_api.base_url'), '/').'/'.ltrim($path, '/');
    }

    private function adminRequest()
    {
        return Http::acceptJson()
            ->withToken((string) config('services.jba_api.clients.marketplace.key'))
            ->timeout(30);
    }

}
