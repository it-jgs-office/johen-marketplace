<?php

namespace App\Services;

use App\Models\AccountListing;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Impor listing Jual Beli Akun dari halaman publik johengaming.id
 * ke tabel account_listings marketplace.
 *
 * Idempoten: baris dikunci lewat (source, source_id). Listing lokal
 * (source = null) tidak pernah disentuh. is_sold tidak pernah
 * dikembalikan ke false setelah terjual.
 */
class JohenGamingSyncService
{
    public const SOURCE = 'johengaming';

    public const BASE_URL = 'https://johengaming.id';

    /**
     * Slug game di website lama => [slug lokal, nama game lokal].
     */
    private const GAMES = [
        'ml' => ['mlbb', 'Mobile Legends'],
        'pubg' => ['pubg', 'PUBG Mobile'],
        'fc_mobile' => ['fcm', 'FC Mobile'],
        'ff' => ['ff', 'Free Fire'],
        'roblox' => ['roblox', 'Roblox'],
        'valorant' => ['valorant', 'Valorant'],
        'efootball' => ['efootball', 'E-Football'],
    ];

    private const OWNERS = [
        'mlbb' => 'Johen MLBB',
        'pubg' => 'Johen PUBG',
        'fcm' => 'Johen FCM',
        'ff' => 'Johen FF',
        'roblox' => 'Johen Roblox',
        'valorant' => 'Johen Valorant',
        'efootball' => 'Johen E-Football',
    ];

    private const BADGE_TO_PROMO = [
        'HOT' => 'hot',
        'Promo' => 'promo',
        'Rekomen' => 'best_seller',
        'Flash Sale' => 'flash_sale',
        'Diskon' => 'diskon',
        'New' => 'new',
        'Limited' => 'limited',
        'Best Seller' => 'best_seller',
    ];

    private array $options = [
        'no_images' => false,
        'force_images' => false,
        'deactivate_missing' => false,
        'dry_run' => false,
        'pause_ms' => 100,
        'retries' => 3,
    ];

    private array $stats = [
        'created' => 0,
        'updated' => 0,
        'unchanged' => 0,
        'sold' => 0,
        'errors' => 0,
        'games' => [],
        'failed' => [],
    ];

    private array $seenIds = [];

    private string $gameSlug = '';

    private string $localGame = '';

    private string $localSlug = '';

    private ?string $lastFetchError = null;

    public static function gameSlugsSupported(): array
    {
        return array_keys(static::GAMES);
    }

    public function sync(?string $gameFilter = null, array $options = []): array
    {
        $this->options = array_merge($this->options, $options);
        $this->stats = [
            'created' => 0, 'updated' => 0, 'unchanged' => 0, 'sold' => 0, 'errors' => 0,
            'games' => [], 'failed' => [],
        ];

        foreach ($this->gameSlugs() as $slug) {
            if ($gameFilter && $gameFilter !== $slug) {
                continue;
            }

            if (! isset(static::GAMES[$slug])) {
                continue;
            }

            if (! isset($this->stats['games'][$slug])) {
                $this->stats['games'][$slug] = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'sold' => 0, 'errors' => 0];
            }

            $this->syncGame($slug);

            if ((int) $this->options['pause_ms'] > 0) {
                usleep((int) $this->options['pause_ms'] * 1000);
            }
        }

        return $this->stats;
    }

    /**
     * Daftar slug game. Prioritas: konstanta; kategori baru yang ditemukan
     * di halaman index ditambahkan agar tetap ikut tersinkron.
     */
    private function gameSlugs(): array
    {
        $slugs = static::gameSlugsSupported();

        if ($html = $this->fetchHtml(static::BASE_URL.'/produk/jual-beli-akun')) {
            preg_match_all('#/produk/jual-beli-akun/([a-z0-9_]+)#i', $html, $m);
            foreach (array_unique($m[1] ?? []) as $candidate) {
                $candidate = strtolower($candidate);
                if (isset(static::GAMES[$candidate]) && ! in_array($candidate, $slugs, true)) {
                    $slugs[] = $candidate;
                }
            }
        }

        return $slugs;
    }

    private function syncGame(string $slug): void
    {
        $this->gameSlug = $slug;
        $this->localGame = static::GAMES[$slug][1];
        $this->localSlug = static::GAMES[$slug][0];
        $this->seenIds = [];

        $html = $this->fetchHtml(static::BASE_URL."/produk/jual-beli-akun/{$slug}");
        if ($html === null) {
            $this->fail("{$slug}: halaman kategori tidak dapat diambil ({$this->lastFetchError}).");

            return;
        }

        $cards = $this->parseCategoryCards($html, $slug);
        if ($cards === []) {
            $this->fail("{$slug}: tidak ditemukan kartu produk; struktur halaman sumber mungkin berubah.");

            return;
        }

        foreach ($cards as $card) {
            // Kartu non-link dengan tanda "Habis"/Selesai: tandai akun terkait.
            if ($card['id'] === null) {
                $this->markSoldByName($card);

                continue;
            }

            $this->seenIds[$card['source_id']] = true;
            $detailHtml = $this->fetchHtml($card['href']);
            $detail = $detailHtml ? $this->parseDetail($detailHtml) : [];
            $data = $this->buildListingData($card, $detail);

            try {
                $existing = AccountListing::where('source', static::SOURCE)
                    ->where('source_id', $data['source_id'])
                    ->first();
                $this->downloadImages($data, $existing, $this->imageSources($card, $detail));
                $this->persist($data, $existing);
            } catch (\Throwable $e) {
                $this->fail("{$card['source_id']}: {$e->getMessage()}");
            }
        }

        if ($this->options['deactivate_missing']) {
            $this->deactivateMissing();
        }
    }

    /**
     * Parse kartu akun dari halaman kategori.
     *
     * Kartu aktif: <a class="jbd-card" href="https://johengaming.id/produk/...
     * /{slug}/{id}" data-record=... data-price=... data-status="tersedia">.
     * Kartu terjual: <div class="jbd-card habis" data-status="habis" ...> (tanpa link).
     * Data primer diambil dari atribut data-*.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseCategoryCards(string $html, string $slug): array
    {
        $doc = $this->dom($html);
        $xpath = new DOMXPath($doc);
        $cards = [];

        $pattern = '#^https://johengaming\.id/produk/jual-beli-akun/'.preg_quote($slug, '#').'/(\d+)$#i';
        $patternRel = '#^/produk/jual-beli-akun/'.preg_quote($slug, '#').'/(\d+)$#i';

        // Hanya elemen yang benar-benar kartu (token class "jbd-card", bukan "jbd-card-body" dll).
        $nodes = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," jbd-card ")]');
        foreach ($nodes as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $card = $this->parseCard($xpath, $node);
            if ($card === null) {
                continue;
            }

            $href = $node->getAttribute('href');
            $id = null;

            if (! $card['sold'] && $href !== '') {
                foreach ([$pattern, $patternRel] as $p) {
                    if (preg_match($p, $href, $m)) {
                        $id = (int) $m[1];
                        break;
                    }
                }
            }

            if ($id !== null) {
                $card['id'] = $id;
                $card['href'] = $this->absoluteUrl($href);
                $card['source_id'] = "{$slug}:{$id}";
                $card['sold'] = false;
                $cards[] = $card;

                continue;
            }

            // Kartu "Habis"/terjual: tanpa link, ditandai berdasar nama produk.
            $card['id'] = null;
            $card['href'] = null;
            $card['source_id'] = null;
            $card['sold'] = true;
            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * Ekstrak field dari satu kartu. Nilai presisi dari atribut data-*;
     * bila tidak ada (markup lama), fallback ke teks "Rp N".
     *
     * @return array<string, mixed>|null
     */
    private function parseCard(DOMXPath $xpath, \DOMElement $node): ?array
    {
        $title = trim((string) $node->getAttribute('data-record'));
        if ($title === '') {
            $titleNode = $xpath->query('.//h3', $node)->item(0);
            $title = $titleNode !== null ? trim($titleNode->textContent) : '';
        }
        if ($title === '') {
            return null;
        }

        $image = null;
        foreach ($xpath->query('.//img[contains(@src, "/storage/jba/")]', $node) as $img) {
            $src = $img->getAttribute('src');
            if ($src !== '') {
                $image = $src;
                break;
            }
        }

        $nodeHtml = (string) $xpath->document->saveHTML($node);
        $text = (string) $node->textContent;
        $prices = $this->extractPrices($text);

        $final = $this->intAttr($node->getAttribute('data-price'));
        $original = $this->intAttr($this->jbaAttr($xpath, $node, 'jbd-price-old'));
        $hemat = $this->intAttr($this->jbaAttr($xpath, $node, 'jbd-hemat'));
        $final = $final ?? $this->intAttr($this->jbaAttr($xpath, $node, 'jbd-price-new'));

        if ($final === null && $prices !== []) {
            $final = (int) end($prices);
        }
        if ($original === null && count($prices) >= 2) {
            $original = (int) $prices[0];
        }

        $badge = trim((string) $node->getAttribute('data-label'));
        if ($badge === '') {
            $badge = (string) ($this->extractBadge($nodeHtml) ?? '');
        }

        $classes = strtolower((string) $node->getAttribute('class'));
        $status = strtolower((string) $node->getAttribute('data-status'));

        return [
            'title' => $title,
            'image' => $image ? $this->absoluteUrl($image) : null,
            'price_final' => $final,
            'price_original' => $original,
            'price_hemat' => $hemat,
            'badge' => $badge !== '' ? $badge : null,
            'store' => trim((string) $node->getAttribute('data-store')) ?: null,
            'collector_raw' => trim((string) $node->getAttribute('data-kolektor')) ?: null,
            'deal_type_raw' => trim((string) $node->getAttribute('data-deal-type')) ?: null,
            'idgame' => (string) $node->getAttribute('data-idgame'),
            'sold' => $status === 'habis'
                || str_contains($classes, 'habis')
                || str_contains($nodeHtml, 'sold.png')
                || preg_match('/\b(Selesai|Habis)\b/i', $text) === 1,
        ];
    }

    /**
     * Nilai data-jba-amt dari anak dengan class tertentu (span harga redesign).
     */
    private function jbaAttr(DOMXPath $xpath, \DOMElement $node, string $class): ?string
    {
        $target = $xpath->query(
            './/*[contains(concat(" ",normalize-space(@class)," ")," '.$class.' ") and @data-jba-amt]',
            $node
        )->item(0);

        return $target instanceof \DOMElement ? $target->getAttribute('data-jba-amt') : null;
    }

    /**
     * Ubah string angka ("45.000.000") menjadi int; null bila tak valid.
     */
    private function intAttr(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = (int) str_replace([',', '.'], '', $value);

        return $v > 0 ? $v : null;
    }

    /**
     * "Habis"/Selesai tanpa link: set is_sold=true pada listing sumber yang
     * cocok (game + nama produk). Tidak membuat akun baru (tidak ada id).
     */
    private function markSoldByName(array $card): void
    {
        $existing = AccountListing::where('source', static::SOURCE)
            ->where('game', $this->localGame)
            ->where('product_name', $card['title'])
            ->where('is_sold', false)
            ->get();

        foreach ($existing as $listing) {
            if ($this->options['dry_run']) {
                $this->bump('sold');

                continue;
            }

            $listing->update(['is_sold' => true]);
            $this->bump('sold');
        }
    }

    /**
     * Parse halaman detail (string offset, karena region antar-section).
     *
     * @return array<string, mixed>
     */
    private function parseDetail(string $html): array
    {
        $text = preg_replace('/[\s\x{00A0}]+/u', ' ', trim(strip_tags($html))) ?? '';

        $spPos = mb_strpos($text, 'Spesifikasi Produk');
        $limit = $spPos !== false ? min($spPos, mb_strlen($text)) : mb_strlen($text);

        $section = mb_substr($text, 0, $limit);

        $idPos = mb_strpos($section, 'ID Akun');
        $priceBlock = mb_substr($section, $idPos !== false ? $idPos : 0);

        $prices = $this->extractPrices($priceBlock);
        $hemat = $this->extractHemat($priceBlock);

        $specs = null;
        if ($spPos !== false) {
            $rest = mb_substr($text, $spPos + mb_strlen('Spesifikasi Produk'));
            $end = mb_strpos($rest, 'Pesan Sekarang');
            if ($end === false) {
                $end = mb_strpos($rest, 'Hubungi Admin');
            }
            $candidate = $end !== false ? mb_substr($rest, 0, $end) : $rest;
            $specs = trim($candidate);
            if ($specs === '' || mb_strlen($specs) < 10) {
                $specs = null;
            }
        }

        // Region utama (sebelum "Spesifikasi Produk") diukur pada HTML mentah.
        $spPosRaw = strpos($html, 'Spesifikasi Produk');

        return [
            'prices' => $prices ?: $this->extractPrices(mb_substr($section, 0, $idPos === false ? mb_strlen($section) : $idPos)),
            'hemat' => $hemat,
            'specs' => $specs,
            'gallery' => $this->extractGalleryImages($html, $spPosRaw === false ? false : $spPosRaw),
            'video_url' => $this->extractVideoUrl($html, $spPosRaw === false ? false : $spPosRaw),
        ];
    }

    /**
     * Susun data akhir untuk satu listing.
     *
     * @param  array<string, mixed>  $card
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function buildListingData(array $card, array $detail): array
    {
        $prices = $detail['prices'] ?: [];

        $final = $card['price_final'] ?? ($prices !== [] ? (int) end($prices) : 0);
        $original = $card['price_original'] ?? (count($prices) >= 2 ? (int) $prices[0] : null);

        $hemat = $card['price_hemat'] ?? null;
        if ($original === null && $hemat !== null) {
            $original = $final + $hemat;
        }
        if ($original !== null && $original <= $final) {
            $original = null;
        }

        $discount = ($original !== null && $original > $final)
            ? (int) round(($original - $final) / $original * 100)
            : null;

        $title = $card['title'];
        $dealType = strtolower((string) ($card['deal_type_raw'] ?? ''));
        $isBundle = match ($dealType) {
            'bundle', 'bundel' => true,
            'normal' => false,
            default => stripos($title, 'bundle') !== false || stripos($title, 'JGM') === 0,
        };

        $idPart = $card['idgame'] !== '' ? 'ID Akun: '.$card['idgame'].PHP_EOL : '';

        return [
            'source' => static::SOURCE,
            'source_id' => $card['source_id'],
            'source_url' => $card['href'],
            'game' => $this->localGame,
            'product_name' => $title,
            'specifications' => $idPart.($detail['specs'] ?: $title),
            'price' => $final,
            'original_price' => $original,
            'discount_percent' => $discount,
            'owner_name' => $card['store'] ?: (static::OWNERS[$this->localSlug] ?? null),
            'whatsapp' => null,
            'promo_type' => $this->normalizePromo($card['badge'] ?? null),
            'collector_tier' => $this->mapCollector($card['collector_raw'] ?? null),
            'deal_type' => $isBundle ? 'bundle' : 'normal',
            'is_active' => true,
            'sold_from_source' => (bool) ($card['sold'] ?? false),
            'video_url' => $detail['video_url'] ?? null,
            'photo' => null,
            'detail_photo_1' => null,
            'detail_photo_2' => null,
            'detail_photo_3' => null,
            'detail_photo_4' => null,
        ];
    }

    /**
     * Label promo bebas ("HOT", "Best Seller", dll) => enum lokal.
     */
    private function normalizePromo(?string $label): string
    {
        $allowed = ['none', 'promo', 'flash_sale', 'diskon', 'best_seller', 'hot', 'new', 'limited'];

        if ($label === null) {
            return 'none';
        }

        if (isset(static::BADGE_TO_PROMO[$label])) {
            return static::BADGE_TO_PROMO[$label];
        }

        $v = strtolower(str_replace([' ', '-'], '_', $label));

        return in_array($v, $allowed, true) ? $v : 'none';
    }

    /**
     * "Kolektor Sultan" => "sultan" (ternama, terhormat, juragan, sultan).
     */
    private function mapCollector(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $v = strtolower($raw);
        foreach (['sultan', 'juragan', 'ternama', 'terhormat'] as $tier) {
            if (str_contains($v, $tier)) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Unduh gambar kartu + galeri ke public storage dan daftarkan di tabel media.
     * Dilewati bila gambar sudah ada (kecuali force_images), opsi no_images,
     * atau dry-run. Bila dilewati, nilai foto lama dipertahankan.
     *
     * @param  array<string, mixed>  $data
     */
    private function downloadImages(array &$data, ?AccountListing $existing, array $sources): void
    {
        $skip = $this->options['dry_run']
            || $this->options['no_images']
            || (! $this->options['force_images'] && $existing && $existing->photo);

        if ($skip) {
            if ($existing) {
                $data['photo'] = $existing->photo;
                $data['detail_photo_1'] = $existing->detail_photo_1;
                $data['detail_photo_2'] = $existing->detail_photo_2;
                $data['detail_photo_3'] = $existing->detail_photo_3;
                $data['detail_photo_4'] = $existing->detail_photo_4;
            }

            return;
        }

        $paths = [];
        foreach (array_slice($sources, 0, 5) as $n => $url) {
            $key = str_replace(':', '-', (string) $data['source_id'])."-{$n}";
            $paths[] = $this->storeRemoteImage($url, $key);
        }

        $data['photo'] = $paths[0] ?? null;
        $data['detail_photo_1'] = $paths[1] ?? null;
        $data['detail_photo_2'] = $paths[2] ?? null;
        $data['detail_photo_3'] = $paths[3] ?? null;
        $data['detail_photo_4'] = $paths[4] ?? null;
    }

    /**
     * URL gambar kartu + galeri, unik & urut.
     *
     * @param  array<string, mixed>  $card
     * @param  array<string, mixed>  $detail
     * @return array<int, string>
     */
    private function imageSources(array $card, array $detail): array
    {
        return array_values(array_unique(array_filter(array_merge(
            [$card['image'] ?? null],
            $detail['gallery'] ?? [],
        ))));
    }

    /**
     * Simpan byte gambar remote; kembalikan path relatif public storage.
     */
    private function storeRemoteImage(string $url, string $key): ?string
    {
        $bytes = $this->fetchBytes($url);
        if ($bytes === null) {
            return null;
        }

        $extension = strtolower((string) pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $path = "account-listings/johengaming-{$key}.{$extension}";
        Storage::disk('public')->put($path, $bytes);
        MediaStore::import($path);

        return $path;
    }

    /**
     * Upsert satu listing dengan kunci (source, source_id).
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(array $data, ?AccountListing $existing): void
    {
        $soldFromSource = (bool) ($data['sold_from_source'] ?? false);
        unset($data['sold_from_source']);

        // is_sold hanya boleh menjadi true, tidak pernah revert.
        $data['is_sold'] = ($existing?->is_sold ?? false) || $soldFromSource;

        if ($this->options['dry_run']) {
            $this->bump($existing ? 'updated' : 'created');

            return;
        }

        if (! $existing) {
            AccountListing::create($data);
            $this->bump('created');

            return;
        }

        if ($this->hasChanges($existing, $data)) {
            $existing->update($data);
            $this->bump('updated');

            return;
        }

        $this->bump('unchanged');
    }

    /**
     * Bandingkan kolom non-primary untuk menentukan perlu update.
     *
     * @param  array<string, mixed>  $data
     */
    private function hasChanges(AccountListing $listing, array $data): bool
    {
        foreach ($data as $field => $value) {
            if (in_array($field, ['source', 'source_id', 'source_url'], true)) {
                continue;
            }

            $current = $listing->{$field};

            if ($field === 'discount_percent' || $field === 'original_price' || $field === 'price') {
                $current = (float) ($current ?? 0);
                $value = (float) ($value ?? 0);
                if (abs($current - $value) > 0.001) {
                    return true;
                }

                continue;
            }

            if ((string) $current !== (string) $value) {
                return true;
            }
        }

        return false;
    }

    private function deactivateMissing(): void
    {
        $ids = array_keys(array_filter($this->seenIds, fn ($v) => $v === true));

        if ($this->options['dry_run']) {
            return;
        }

        AccountListing::where('source', static::SOURCE)
            ->where('game', $this->localGame)
            ->whereNotNull('source_id')
            ->where('is_active', true)
            ->where('is_sold', false)
            ->get()
            ->each(function (AccountListing $listing) use ($ids) {
                if (! in_array($listing->source_id, $ids, true)) {
                    $listing->update(['is_active' => false]);
                }
            });
    }

    private function bump(string $key): void
    {
        $this->stats[$key]++;
        $this->stats['games'][$this->gameSlug][$key]++;
    }

    private function fail(string $message): void
    {
        $this->stats['errors']++;
        $this->stats['games'][$this->gameSlug]['errors']++;
        $this->stats['failed'][] = $message;
    }

    private function fetchHtml(string $url): ?string
    {
        $this->lastFetchError = null;

        try {
            $resp = $this->client()->get($url);

            if (! $resp->successful()) {
                $this->lastFetchError = 'HTTP '.$resp->status();

                return null;
            }

            return $resp->body();
        } catch (\Throwable $e) {
            $this->lastFetchError = $e->getMessage();

            return null;
        }
    }

    private function fetchBytes(string $url): ?string
    {
        try {
            $resp = $this->client()
                ->timeout(45)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);

            if (! $resp->successful()) {
                return null;
            }

            $type = strtolower((string) $resp->header('Content-Type', ''));
            if (str_contains($type, 'image/')) {
                return $resp->body();
            }

            // Beberapa CDN mengabaikan Content-Type; validasi magic bytes ringan.
            $body = $resp->body();
            if ($body !== '' && in_array($body[0], ["\xFF", "\x89", "\x52", "\x47", "\x42", "\x49"], true)) {
                return $body;
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function client()
    {
        return Http::timeout(30)
            ->retry((int) $this->options['retries'], 300, null, false)
            ->withOptions([
                'verify' => true,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                ],
            ]);
    }

    private function dom(string $html): DOMDocument
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return $doc;
    }

    private function absoluteUrl(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : static::BASE_URL.$url;
    }

    /**
     * Ambil semua nilai "Rp N" dari teks (urutan kemunculan).
     *
     * @return array<int, int>
     */
    private function extractPrices(string $text): array
    {
        $values = [];
        if (preg_match_all('#Rp\s*([\d\.]+)#i', $text, $m)) {
            foreach ($m[1] as $raw) {
                $v = (int) str_replace('.', '', $raw);
                if ($v > 0) {
                    $values[] = $v;
                }
            }
        }

        return $values;
    }

    private function extractHemat(string $text): ?int
    {
        if (preg_match('#Hemat\s*Rp\s*([\d\.]+)#i', $text, $m)) {
            $v = (int) str_replace('.', '', $m[1]);

            return $v > 0 ? $v : null;
        }

        return null;
    }

    private function extractBadge(string $nodeHtml): ?string
    {
        foreach (array_keys(static::BADGE_TO_PROMO) as $label) {
            if (preg_match('#>\s*'.preg_quote($label, '#').'(?:\s+Selesai)?\s*<#i', $nodeHtml)) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Gambar utama + galeri dari region sebelum "Spesifikasi Produk".
     *
     * @return array<int, string>
     */
    private function extractGalleryImages(string $html, int|false $limit): array
    {
        $end = $limit === false ? strlen($html) : $limit;
        $end = min($end, strlen($html));
        $head = substr($html, 0, $end);

        preg_match_all('#(?:src|href)=(["\'])(/storage/jba/[^"\']+)\1#i', $head, $m);

        $urls = [];
        foreach (array_unique($m[2] ?? []) as $src) {
            $urls[] = $this->absoluteUrl($src);
        }

        return $urls;
    }

    private function extractVideoUrl(string $html, int|false $limit): ?string
    {
        $end = $limit === false ? strlen($html) : $limit;
        $end = min($end, strlen($html));
        $head = substr($html, 0, $end);

        if (preg_match('#<video[^>]*\ssrc=(["\'])(.*?)\1#is', $head, $m)) {
            return $this->absoluteUrl(html_entity_decode($m[2]));
        }

        if (preg_match('#<source[^>]*\ssrc=(["\'])(.*?)\1#is', $head, $m)) {
            return $this->absoluteUrl(html_entity_decode($m[2]));
        }

        return null;
    }
}
