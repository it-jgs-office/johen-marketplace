<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\CheckProvider;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class GameAccountService
{
    public function isEnabled(): bool
    {
        return true;
    }

    /** Hanya provider yang terbukti mengembalikan nickname asli yang boleh dicek otomatis. */
    public function supportsRealtimeCheck(string $brand): bool
    {
        $provider = $this->matchProvider($brand);

        if ($provider instanceof CheckProvider) {
            return in_array($provider->free_type, ['gopay', 'enka', 'alu'], true)
                || in_array($provider->fallback_free_type, ['gopay', 'enka', 'alu'], true);
        }

        $resolver = $this->matchBrand($brand);

        return in_array($resolver['type'] ?? null, ['gopay', 'enka', 'alu'], true);
    }

    /**
     * Deteksi keberadaan akun game.
     *
     * @param  bool  $allowPaidFallback  Izinkan fallback berbayar Digiflazz saat provider gratis gagal.
     *                                   Dipakai pada jalur checkout; indikator real-time tidak memakai ini.
     * @return array{valid: bool, nickname: ?string, checked: bool, region: ?string}
     *                                                                               checked=false berarti brand tidak punya validator / input belum layak cek
     *                                                                               (frontend memperlakukan sebagai netral).
     */
    public function check(string $brand, string $userId, ?string $zoneId = null, bool $allowPaidFallback = false): array
    {
        if (! $this->supportsRealtimeCheck($brand)) {
            return $this->result(false, null, false);
        }

        $userId = trim($userId);
        $zoneId = trim((string) $zoneId);

        if ($this->formatValid($userId, $brand, $zoneId) === false) {
            return $this->result(false, null, false);
        }

        $isMl = str_starts_with(mb_strtolower($brand), 'mobile legends');

        if ($isMl && ! filter_var(SiteSetting::get('mlbb_nickname_check_enabled', '1'), FILTER_VALIDATE_BOOLEAN)) {
            return $this->result(false, null, false);
        }

        // Simulasi pengecekan akun harus diaktifkan secara eksplisit.
        if (config('gameaccount.simulation')) {
            $suffix = str_pad((string) (abs(crc32($userId.$zoneId)) % 10000), 4, '0', STR_PAD_LEFT);

            return $this->result(true, 'PlayerSim'.$suffix, true, $isMl ? 'Indonesia' : null);
        }

        $resolver = $this->matchBrand($brand);

        // Rantai provider dari DB (aditif). Bila tidak ada baris yang cocok,
        // jatuh ke perilaku statis lama agar deployment lama tidak berubah.
        $provider = $this->matchProvider($brand);

        if ($resolver === null && $provider === null) {
            return $this->result(false, null, false);
        }

        $cacheKey = 'gameaccount_'.md5($brand.'|'.$userId.'|'.$zoneId);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            // Hasil gratis yang tersimpan tidak memicu fallback berbayar.
            return $cached;
        }

        // SKU pengecekan (Digiflazz) dapat menimbulkan biaya. Batasi request publik
        // dan cegah cek paralel untuk pasangan ID/Zone yang sama pada game yang
        // benar-benar bisa memakai fallback berbayar.
        $mayUsePaid = $isMl
            || ($allowPaidFallback
                && $provider instanceof CheckProvider
                && $provider->enabled
                && $provider->digiflazz_fallback_enabled
                && ! empty($provider->digiflazz_sku));

        if ($mayUsePaid) {
            $rateKey = 'gameaccount:paid:'.sha1((string) request()->ip());
            if (RateLimiter::tooManyAttempts($rateKey, 6)) {
                return $this->result(false, null, false);
            }

            $pendingKey = 'gameaccount:paid:pending:'.md5($brand.'|'.$userId.'|'.$zoneId);
            if (! Cache::add($pendingKey, true, now()->addSeconds(30))) {
                return $this->result(false, null, false);
            }

            RateLimiter::hit($rateKey, 60);
            try {
                $checked = $this->resolveChain($provider, $resolver, $userId, $zoneId, $allowPaidFallback);
            } finally {
                Cache::forget($pendingKey);
            }
        } else {
            $checked = $this->resolveChain($provider, $resolver, $userId, $zoneId, $allowPaidFallback);
        }

        // Hasil gagal-jaringan (null) tidak di-cache agar user bisa langsung coba lagi.
        if ($checked === null || $checked['checked'] === false) {
            return $this->result(false, null, false);
        }

        $ttl = $isMl ? (int) config('gameaccount.mobile_legends_cache_ttl', 30) : (int) config('gameaccount.cache_ttl', 5);
        Cache::put($cacheKey, $checked, now()->addMinutes($ttl));

        return $checked;
    }

    /**
     * Susun rantai resolusi: DB provider (gratis -> fallback gratis -> Digiflazz)
     * lalu, bila tidak ada, perilaku statis lama (resolver + fallback).
     *
     * @return array|null null berarti gagal jaringan/error -> netral
     */
    protected function resolveChain(?CheckProvider $provider, ?array $resolver, string $userId, string $zoneId, bool $allowPaidFallback): ?array
    {
        if ($provider instanceof CheckProvider && $provider->enabled) {
            $primary = $provider->free_type && $provider->free_type !== 'none'
                ? ['type' => $provider->free_type] + (array) ($provider->free_config ?? [])
                : null;

            $result = $primary ? $this->resolve($primary, $userId, $zoneId) : null;

            if ($this->resolvedOk($result) === false && $provider->fallback_free_type) {
                $fallbackFree = ['type' => $provider->fallback_free_type] + (array) ($provider->fallback_free_config ?? []);
                $result = $this->resolve($fallbackFree, $userId, $zoneId);
            }

            // Fallback berbayar Digiflazz hanya bila diizinkan dan gratis gagal.
            if ($this->resolvedOk($result) === false
                && $allowPaidFallback
                && $provider->digiflazz_fallback_enabled
                && ! empty($provider->digiflazz_sku)) {
                $result = $this->checkDigiflazzLookup((string) $provider->digiflazz_sku, $userId, $zoneId);
            }

            return $result;
        }

        // Perilaku statis lama (config/gameaccount.php).
        if ($resolver !== null) {
            $checked = $this->resolve($resolver, $userId, $zoneId);

            if ($checked === null && ! empty($resolver['fallback'])) {
                $checked = $this->resolve($resolver['fallback'], $userId, $zoneId);
            }

            return $checked;
        }

        return $this->result(false, null, false);
    }

    /** True bila hasil berisi akun valid. */
    protected function resolvedOk(?array $result): bool
    {
        return is_array($result) && ($result['valid'] ?? false) === true;
    }

    /** Validasi server untuk SKU Mobile Legends Indonesia pada kedua jalur checkout. */
    public function idnAvailabilityMessage(Product $product, string $userId, ?string $zoneId): ?string
    {
        return $this->availabilityMessage($product, $userId, $zoneId);
    }

    /**
     * Validasi ketersediaan akun saat checkout untuk brand apa pun.
     *
     * - Region non-Indonesia yang jelas terdeteksi → ditolak.
     * - Region tidak dapat diverifikasi (provider down / tanpa SKU) → diizinkan.
     *
     * Fallback Digiflazz berbayar hanya dijalankan bila provider game terkait
     * mengaktifkannya dan SKU-nya sudah diisi. Game tanpa konfigurasi provider
     * tetap netral agar tidak memicu biaya tak perlu.
     */
    public function availabilityMessage(Product $product, string $userId, ?string $zoneId): ?string
    {
        $result = $this->check($product->brand, $userId, $zoneId, true);

        if ($result['valid'] && $result['region'] === 'Indonesia') {
            return null;
        }

        // Region non-Indonesia yang jelas terdeteksi tetap ditolak.
        if ($result['valid'] && ! empty($result['region']) && $result['region'] !== 'Indonesia') {
            return 'Region '.$result['region'].' belum tersedia saat ini.';
        }

        // Gating dilonggarkan: bila region tidak dapat diverifikasi (provider down),
        // izinkan checkout agar pembeli tidak terhalang. Risiko salah ID ditanggung pembeli.
        return null;
    }

    protected function formatValid(string $userId, string $brand, string $zoneId): bool
    {
        if ($userId === '' || strlen($userId) < 5 || strlen($userId) > 32) {
            return false;
        }

        if (str_starts_with(mb_strtolower($brand), 'valorant')) {
            if (preg_match('/^[\p{L}\p{N}._ -]{3,16}#[A-Za-z0-9]{3,5}$/u', $userId) !== 1) {
                return false;
            }
        } elseif (preg_match('/\s/', $userId)) {
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
            || str_starts_with(mb_strtolower($brand), 'magic chess')
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

    /** Cari baris konfigurasi provider dari DB (terurut prioritas). Null bila tabel kosong. */
    protected function matchProvider(string $brand): ?CheckProvider
    {
        $brand = mb_strtolower(trim($brand));

        try {
            $providers = Cache::remember('check_providers_all', 3600, function () {
                return CheckProvider::query()
                    ->where('enabled', true)
                    ->orderBy('priority')
                    ->get()
                    ->all();
            });
        } catch (\Throwable $e) {
            // Tabel belum ada / DB error: jangan ganggu alur lama.
            return null;
        }

        foreach ($providers as $provider) {
            if ($provider instanceof CheckProvider
                && fnmatch(mb_strtolower($provider->brand_pattern), $brand)) {
                return $provider;
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
                'enka' => $this->checkEnka($resolver, $userId),
                'alu' => $this->checkAlu($userId, $zoneId),
                'isan' => $this->checkIsan($resolver, $userId, $zoneId),
                'gopay' => $this->checkGopay($resolver, $userId, $zoneId),
                default => $this->result(false, null),
            };
        } catch (\Throwable $e) {
            Log::warning('GameAccount resolve failed: '.$e->getMessage(), ['type' => $resolver['type'] ?? null]);

            return null;
        }
    }

    /** Primary MLBB nickname lookup. A null response triggers the configured fallback. */
    protected function checkAlu(string $userId, ?string $zoneId): ?array
    {
        $apiKey = trim((string) config('services.alu.api_key'));
        if ($apiKey === '') {
            return null;
        }

        $response = Http::timeout((int) config('gameaccount.timeout', 5))
            ->retry((int) config('gameaccount.free_retry', 2), (int) config('gameaccount.free_retry_delay', 300), $this->retryOnTransient(...), throw: false)
            ->acceptJson()
            ->withHeaders(['x-api-key' => $apiKey])
            ->get('https://aluu.in/api/check/mlbb-check', [
                'user_id' => $userId,
                'server_id' => (string) $zoneId,
            ]);

        if (! $response->successful() || $response->json('success') !== true) {
            return null;
        }

        $nickname = trim((string) $response->json('username'));
        if ($nickname === '') {
            return null;
        }

        $country = strtoupper(trim((string) $response->json('country')));
        $region = $country === 'ID' ? 'Indonesia' : ($country !== '' ? $country : null);

        return $this->result(true, $nickname, true, $region);
    }

    protected function checkEnka(array $resolver, string $userId): array
    {
        $path = str_replace('{user_id}', urlencode($userId), $resolver['path']);

        $response = Http::timeout((int) config('gameaccount.timeout', 5))
            ->retry((int) config('gameaccount.free_retry', 2), (int) config('gameaccount.free_retry_delay', 300), $this->retryOnTransient(...), throw: false)
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
            ->retry((int) config('gameaccount.free_retry', 2), (int) config('gameaccount.free_retry_delay', 300), $this->retryOnTransient(...), throw: false)
            ->acceptJson()
            ->get($url, $query);

        if (! $response->successful()) {
            // Rate limit and upstream errors must not be shown as an invalid player.
            if (in_array($response->status(), [400, 404], true)) {
                return $this->result(false, null);
            }

            Log::warning('MLBB nickname provider returned HTTP '.$response->status());

            return $this->result(false, null, false);
        }

        if ($response->json('success') !== true) {
            return $this->result(false, null);
        }

        $nickname = trim((string) $response->json('name'));

        if ($nickname === '') {
            return $this->result(false, null);
        }

        return $this->result(true, $nickname, true, $resolver['region'] ?? null);
    }

    /**
     * Validator GoPay Games (gopay.co.id).
     * 2xx + success/message=success → valid, nickname di data.username dll;
     * selain itu (mis. 404 "Invalid user account") → tidak ditemukan.
     */
    protected function checkGopay(array $resolver, string $userId, ?string $zoneId): array
    {
        // Endpoint publik GoPay kadang lambat/gagal sesaat; pakai timeout lebih
        // longgar + beberapa kali percobaan agar jalur gratis ini andal seperti MLBB.
        $attempts = max(1, (int) config('gameaccount.gopay_retry', 2));

        $response = Http::timeout((int) config('gameaccount.gopay_timeout', 12))
            ->connectTimeout((int) config('gameaccount.gopay_connect_timeout', 5))
            ->retry($attempts, (int) config('gameaccount.gopay_retry_delay', 400), $this->retryOnTransient(...), throw: false)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Referer' => 'https://gopay.co.id/',
                'Origin' => 'https://gopay.co.id',
            ])
            ->asJson()
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

        // Beberapa endpoint mengembalikan username = gabungan ID saja (bukan nama).
        // Itu bukan nickname, jadi jangan dianggap valid.
        $echoes = array_filter([$userId, $userId.($zoneId ?: '')]);

        foreach (['data.username', 'data.userAccount', 'data.nickname', 'data.name', 'username', 'userAccount'] as $path) {
            $raw = (string) data_get($body, $path);

            if (trim($raw) === '') {
                continue;
            }

            // GoPay mengembalikan spasi sebagai tanda '+'.
            $nickname = trim(urldecode($raw));

            if ($nickname !== '' && ! in_array($nickname, $echoes, true)) {
                $region = $this->countryName((string) data_get($body, 'data.countryOrigin'));

                return $this->result(true, $nickname, true, $region);
            }
        }

        return $this->result(false, null);
    }

    /**
     * Predikat retry untuk provider gratis: ulangi hanya saat gangguan sesaat
     * (koneksi gagal, timeout, 429, atau 5xx), bukan saat akun tidak ditemukan.
     */
    protected function retryOnTransient(\Throwable $exception, $request = null, ?string $method = null): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        // Laravel passes PendingRequest as argument two; HTTP status is carried
        // by RequestException::response, not by the pending request.
        if (! ($exception instanceof RequestException)) {
            return false;
        }

        $status = $exception->response->status();

        return $status !== null && ($status === 429 || $status >= 500);
    }

    /**
     * Fallback berbayar Digiflazz: beli SKU pengecekan lalu baca nickname/region
     * dari field data atau dari SN/message.
     */
    protected function checkDigiflazzLookup(string $sku, string $userId, string $zoneId): ?array
    {
        if (! app(DigiflazzService::class)->isConfigured()) {
            return null;
        }

        $transactionKey = 'gameaccount:digiflazz:'.md5($sku.'|'.$userId.'|'.$zoneId);
        $refId = Cache::get($transactionKey);
        $digiflazz = app(DigiflazzService::class);

        try {
            if (is_string($refId) && $refId !== '') {
                // Respons pending/timeout dicek ulang dengan ref yang sama;
                // jangan mengirim transaksi SKU pengecekan yang kedua.
                $response = $digiflazz->checkStatus($sku, $userId, $refId, $zoneId);
            } else {
                $refId = 'CHK-'.strtoupper(Str::random(16));
                Cache::put($transactionKey, $refId, now()->addMinutes(30));
                $response = $digiflazz->topUp($sku, $userId, $refId, $zoneId);
            }
        } catch (\Throwable $e) {
            Log::warning('GameAccount Digiflazz fallback failed: '.$e->getMessage(), ['sku' => $sku]);

            return null;
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

    protected function result(bool $valid, ?string $nickname, bool $checked = true, ?string $region = null): array
    {
        return ['valid' => $valid, 'nickname' => $nickname, 'checked' => $checked, 'region' => $region];
    }
}
