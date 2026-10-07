<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class GameAccountService
{
    /**
     * Deteksi keberadaan akun game.
     *
     * @return array{valid: bool, nickname: ?string, checked: bool, region: ?string}
     *         checked=false berarti brand tidak punya validator / input belum layak cek
     *         (frontend memperlakukan sebagai netral).
     */
    public function check(string $brand, string $userId, ?string $zoneId = null): array
    {
        $userId = trim($userId);
        $zoneId = trim((string) $zoneId);

        if ($this->formatValid($userId, $brand, $zoneId) === false) {
            return $this->result(false, null, false);
        }

        $resolver = $this->matchBrand($brand);
        $isMl = ($resolver['type'] ?? null) === 'digiflazz_ml';

        // Mode simulasi pembayaran: selalu terdeteksi agar UI bisa dites lokal.
        if (config('services.payment.simulation')) {
            $suffix = str_pad((string) (abs(crc32($userId.$zoneId)) % 10000), 4, '0', STR_PAD_LEFT);

            return $this->result(true, 'PlayerSim'.$suffix, true, $isMl ? 'Indonesia' : null);
        }

        if ($resolver === null) {
            return $this->result(false, null, false);
        }

        $cacheKey = 'gameaccount_'.md5($brand.'|'.$userId.'|'.$zoneId);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        if ($isMl) {
            // SKU pengecekan dapat menimbulkan biaya. Batasi request publik
            // dan cegah cek paralel untuk pasangan ID/Zone yang sama.
            $rateKey = 'gameaccount:ml:'.sha1((string) request()->ip());
            if (RateLimiter::tooManyAttempts($rateKey, 6)) {
                return $this->result(false, null, false);
            }

            $pendingKey = 'gameaccount:ml:pending:'.md5($userId.'|'.$zoneId);
            if (! Cache::add($pendingKey, true, now()->addSeconds(30))) {
                return $this->result(false, null, false);
            }

            RateLimiter::hit($rateKey, 60);
            try {
                $checked = $this->resolve($resolver, $userId, $zoneId);
            } finally {
                Cache::forget($pendingKey);
            }
        } else {
            $checked = $this->resolve($resolver, $userId, $zoneId);
        }

        // Hasil gagal-jaringan (null) tidak di-cache agar user bisa langsung coba lagi.
        if ($checked === null || $checked['checked'] === false) {
            return $this->result(false, null, false);
        }

        $ttl = $isMl ? (int) config('gameaccount.digiflazz_ml_cache_ttl', 30) : (int) config('gameaccount.cache_ttl', 5);
        Cache::put($cacheKey, $checked, now()->addMinutes($ttl));

        return $checked;
    }

    /** Validasi server untuk SKU Mobile Legends Indonesia pada kedua jalur checkout. */
    public function idnAvailabilityMessage(Product $product, string $userId, ?string $zoneId): ?string
    {
        if (! str_starts_with(mb_strtolower($product->brand), 'mobile legends')
            || ! str_ends_with(mb_strtolower($product->buyer_sku_code), '-idn')) {
            return null;
        }

        $result = $this->check($product->brand, $userId, $zoneId);
        if ($result['valid'] && $result['region'] === 'Indonesia') {
            return null;
        }

        if (! empty($result['region'])) {
            return 'Region '.$result['region'].' belum tersedia saat ini.';
        }

        return 'Region akun Mobile Legends belum dapat diverifikasi. Cek kembali User ID dan Zone ID.';
    }

    protected function formatValid(string $userId, string $brand, string $zoneId): bool
    {
        if ($userId === '' || strlen($userId) < 5 || strlen($userId) > 32 || preg_match('/\s/', $userId)) {
            return false;
        }

        if ($this->requiresZone($brand) && $zoneId === '') {
            return false;
        }

        return true;
    }

    protected function requiresZone(string $brand): bool
    {
        return str_starts_with(mb_strtolower($brand), 'mobile legends')
            || (bool) Brand::where('name', $brand)->value('requires_zone_id');
    }

    protected function matchBrand(string $brand): ?array
    {
        $brand = mb_strtolower(trim($brand));

        foreach ((array) config('gameaccount.brands', []) as $pattern => $resolver) {
            if (fnmatch(mb_strtolower($pattern), $brand)) {
                return $resolver;
            }
        }

        return null;
    }

    /**
     * @return array|null null berarti gagal jaringan/error → netral
     */
    protected function resolve(array $resolver, string $userId, ?string $zoneId): ?array
    {
        try {
            return match ($resolver['type']) {
                'digiflazz_ml' => $this->checkDigiflazzMl($userId, (string) $zoneId),
                'enka' => $this->checkEnka($resolver, $userId),
                'isan' => $this->checkIsan($resolver, $userId, $zoneId),
                'gopay' => $this->checkGopay($resolver, $userId, $zoneId),
                default => $this->result(false, null),
            };
        } catch (\Throwable $e) {
            Log::warning('GameAccount resolve failed: '.$e->getMessage(), ['type' => $resolver['type'] ?? null]);

            return null;
        }
    }

    protected function checkDigiflazzMl(string $userId, string $zoneId): array
    {
        $sku = (string) config('gameaccount.digiflazz_ml_sku', 'usrnameml-johen');
        $transactionKey = 'gameaccount:ml:transaction:'.md5($userId.'|'.$zoneId);
        $refId = Cache::get($transactionKey);
        $digiflazz = app(DigiflazzService::class);

        if (is_string($refId) && $refId !== '') {
            // Respons pending/timeout dicek ulang dengan ref yang sama;
            // jangan mengirim transaksi SKU pengecekan yang kedua.
            $response = $digiflazz->checkStatus($sku, $userId, $refId, $zoneId);
        } else {
            $refId = 'MLCHECK-'.strtoupper(Str::random(16));
            Cache::put($transactionKey, $refId, now()->addMinutes(30));
            $response = $digiflazz->topUp($sku, $userId, $refId, $zoneId);
        }
        $data = $response['data'] ?? [];

        if (! is_array($data) || ! in_array(strtolower((string) ($data['status'] ?? '')), ['sukses', 'success'], true)) {
            // Pending/error tidak boleh dibaca sebagai akun invalid atau IDN.
            return $this->result(false, null, false);
        }

        $sn = trim((string) ($data['sn'] ?? ''));
        $lookupText = $sn.' | '.trim((string) ($data['message'] ?? ''));
        $nickname = $this->firstText($data, ['nickname', 'username', 'customer_name', 'name'])
            ?? $this->labelFromSn($lookupText, '(?:username|nickname|nick|nama(?: akun)?)');
        $regionRaw = $this->firstText($data, ['region', 'country', 'country_name', 'country_code', 'server_region'])
            ?? $this->labelFromSn($lookupText, '(?:region|negara|country)');

        return $this->result(true, $nickname, true, $this->countryName($regionRaw));
    }

    private function firstText(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function labelFromSn(string $sn, string $label): ?string
    {
        if (preg_match('/\b'.$label.'\s*[:=]\s*([^|;,\/\r\n]+)/iu', $sn, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function countryName(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $names = [
            'id' => 'Indonesia', 'idn' => 'Indonesia', 'indonesia' => 'Indonesia',
            'my' => 'Malaysia', 'mys' => 'Malaysia', 'malaysia' => 'Malaysia',
            'ph' => 'Filipina', 'phl' => 'Filipina', 'philippines' => 'Filipina', 'filipina' => 'Filipina',
            'sg' => 'Singapura', 'sgp' => 'Singapura', 'singapore' => 'Singapura', 'singapura' => 'Singapura',
            'th' => 'Thailand', 'tha' => 'Thailand', 'thailand' => 'Thailand',
            'vn' => 'Vietnam', 'vnm' => 'Vietnam', 'vietnam' => 'Vietnam',
            'us' => 'Amerika Serikat', 'usa' => 'Amerika Serikat', 'united states' => 'Amerika Serikat',
        ];

        return $names[mb_strtolower($value)] ?? mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    protected function checkEnka(array $resolver, string $userId): array
    {
        $path = str_replace('{user_id}', urlencode($userId), $resolver['path']);

        $response = Http::timeout((int) config('gameaccount.timeout', 5))
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'JohenMarketplace/1.0'])
            ->get('https://enka.network/'.$path);

        if (! $response->successful()) {
            // 404 = akun tidak ditemukan; status lain tetap diperlakukan tidak valid.
            return $this->result(false, null);
        }

        $nickname = data_get($response->json(), $resolver['nickname_path']);

        if (! is_string($nickname) || trim($nickname) === '') {
            // Akun ada tapi profil privat / tanpa nama tampilan.
            return $this->result(false, null);
        }

        return $this->result(true, trim($nickname));
    }

    /**
     * Validator komunitas isan.eu.org (agregasi Codashop).
     * 200 + success=true → valid; selain itu → tidak ditemukan.
     */
    protected function checkIsan(array $resolver, string $userId, ?string $zoneId): array
    {
        $query = [];

        foreach ((array) ($resolver['params'] ?? []) as $key => $template) {
            $value = str_replace(['{user_id}', '{zone_id}'], [$userId, (string) $zoneId], $template);

            if (! str_contains($value, '{')) {
                $query[$key] = $value;
            }
        }

        $url = rtrim((string) config('gameaccount.isan_url'), '/').'/'.$resolver['game'];

        $response = Http::timeout((int) config('gameaccount.timeout', 5))
            ->acceptJson()
            ->get($url, $query);

        if (! $response->successful() || ($response->json('success')) !== true) {
            return $this->result(false, null);
        }

        $nickname = trim((string) $response->json('name'));

        if ($nickname === '') {
            return $this->result(false, null);
        }

        return $this->result(true, $nickname);
    }

    /**
     * Validator GoPay Games (gopay.co.id).
     * 2xx + success/message=success → valid, nickname di data.username dll;
     * selain itu (mis. 404 "Invalid user account") → tidak ditemukan.
     */
    protected function checkGopay(array $resolver, string $userId, ?string $zoneId): array
    {
        $response = Http::timeout((int) config('gameaccount.timeout', 5))
            ->acceptJson()
            ->post((string) config('gameaccount.gopay_url'), [
                'code' => $resolver['code'],
                'data' => [
                    'userId' => $userId,
                    'zoneId' => (string) ($zoneId ?? ''),
                ],
            ]);

        if (! $response->successful()) {
            return $this->result(false, null);
        }

        $body = $response->json() ?? [];

        $ok = ($body['success'] ?? null) === true
            || strcasecmp((string) ($body['message'] ?? ''), 'success') === 0;

        if (! $ok) {
            return $this->result(false, null);
        }

        foreach (['data.username', 'data.userAccount', 'data.nickname', 'data.name', 'username', 'userAccount'] as $path) {
            $nickname = trim((string) data_get($body, $path));

            if ($nickname !== '') {
                return $this->result(true, $nickname);
            }
        }

        return $this->result(false, null);
    }

    protected function result(bool $valid, ?string $nickname, bool $checked = true, ?string $region = null): array
    {
        return ['valid' => $valid, 'nickname' => $nickname, 'checked' => $checked, 'region' => $region];
    }
}
