<?php

if (! function_exists('media_url')) {
    /**
     * URL untuk gambar yang tersimpan di database (tabel media).
     * Menerima path relatif storage, path ber-prefix "storage/", maupun URL lengkap.
     */
    function media_url($path = null)
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = (string) $path;

        if (preg_match('#^https?://#i', $path)) {
            $host = parse_url($path, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            if ($host && $host !== $appHost) {
                return $path;
            }

            $path = parse_url($path, PHP_URL_PATH) ?? $path;
            $path = preg_replace('#^(/)?storage/#', '', $path);
        } else {
            $path = preg_replace('#^(/)?storage/#', '', $path);
        }

        $path = ltrim($path, '/');

        if ($path === '') {
            return null;
        }

        // Artwork bawaan yang di-seed dari public/assets tidak melalui media
        // storage agar dapat langsung tersedia setelah database di-seed.
        if (str_starts_with($path, 'assets/') && is_file(public_path($path))) {
            return asset($path);
        }

        return url('media/'.$path);
    }
}

if (! function_exists('business_info')) {
    /**
     * Data identitas bisnis & kontak yang dipakai di footer, halaman kontak,
     * halaman legal, dan beranda. Nilainya diambil dari SiteSetting dengan
     * fallback supaya halaman tidak pernah kosong.
     *
     * @return array{name:string,address:string,npwp:string,phone:string,email:string,cs_email:string,cs_hours:string,wa_digits:string,wa_href:?string}
     */
    function business_info()
    {
        $get = static function ($key, $default = '') {
            return \App\Models\SiteSetting::get($key, $default);
        };

        $name = $get('company_name', 'PT. Johen Sukses Abadi');
        $address = $get('company_address', 'Ruko Topaz No 60, Summarecon, Bandung 40295, Jawa Barat, Indonesia');
        $npwp = $get('company_npwp', '');
        $wa = $get('contact_whatsapp', '6282260707012');
        $phone = $get('contact_phone_display', '+62 822-6070-7012');
        $email = $get('contact_email', 'corporate@johengaming.store');
        $csEmail = $get('contact_cs_email', 'cs@johengaming.store');
        $csHours = $get('contact_cs_hours', 'Setiap hari 24 jam (00.00 - 23.59 WIB)');

        $waDigits = preg_replace('/\D+/', '', (string) $wa);
        if (str_starts_with((string) $waDigits, '0')) {
            $waDigits = '62'.substr((string) $waDigits, 1);
        }

        return [
            'name' => $name,
            'address' => $address,
            'npwp' => $npwp,
            'phone' => $phone,
            'email' => $email,
            'cs_email' => $csEmail,
            'cs_hours' => $csHours,
            'wa_digits' => $waDigits,
            'wa_href' => $waDigits ? 'https://wa.me/'.$waDigits : null,
        ];
    }
}

if (! function_exists('pwa_build')) {
    /**
     * Stamp build PWA. Nilainya ikut berubah setiap ada file publik yang
     * berubah, dipakai sebagai cache-buster service worker supaya aplikasi
     * yang sudah ter-install langsung memakai aset hasil deploy terbaru.
     */
    function pwa_build(): string
    {
        return \App\Services\PwaBuildService::stamp();
    }
}

if (! function_exists('pwa_asset')) {
    /**
     * URL aset publik dengan cache-buster otomatis dari stamp build.
     *
     * Dipakai menggantikan query versi manual (?v=34). Setiap kali ada file
     * publik yang berubah, URL-nya ikut berubah, jadi tidak ada aset lama
     * yang masih tertinggal di cache browser - termasuk di PWA yang sudah
     * ter-install dan di browser bawaan HP.
     */
    function pwa_asset(string $path): string
    {
        return asset($path).'?v='.pwa_build();
    }
}

if (! function_exists('payment_logo_asset')) {
    /**
     * Logo kanal pembayaran dari aset publik lokal.
     * Tidak menggunakan foto metode pembayaran yang diunggah admin supaya
     * tampilan checkout konsisten dan tetap tersedia tanpa URL eksternal.
     */
    function payment_logo_asset(?string $code): ?string
    {
        $code = strtolower(trim((string) $code));
        $logos = [
            'qris' => 'qris.webp',
            'gopay' => 'gopay.webp',
            'dana' => 'dana.webp',
            'ovo' => 'ovo.webp',
            'linkaja' => 'LinkAja.webp',
            'bca' => 'bca.webp',
            'bca_va' => 'bca.webp',
            'bri_va' => 'bri.webp',
            'bni' => 'bni.webp',
            'bni_va' => 'bni.webp',
            'cimb_va' => 'cimb.webp',
            'mandiri' => 'mandiri.webp',
            'mandiri_va' => 'mandiri.webp',
            'permata' => 'permata-bank.webp',
            'permata_va' => 'permata-bank.webp',
            'alfamart' => 'alfamanrt.webp',
            'indomaret' => 'Logo_Indomaret.webp',
        ];

        $filename = $logos[$code] ?? null;
        if (! $filename || ! is_file(public_path('assets/payment/'.$filename))) {
            return null;
        }

        return pwa_asset('assets/payment/'.$filename);
    }
}

if (! function_exists('topup_game_icon_asset')) {
    /**
     * Ikon nominal top up yang konsisten untuk game yang sudah memiliki
     * aset lokal di public/assets/icon-topup.
     */
    function topup_game_icon_asset(?string $gameName): ?string
    {
        $gameName = mb_strtolower(trim((string) $gameName));

        $icons = [
            'mobile legends' => 'mobile-legends/diamond.webp',
            'magic chess' => 'magic-chess/diamond-magicchess.webp',
            'free fire' => 'free-fire/diamond-ff.webp',
            'call of duty' => 'call-of-duty-mobile/cp-codm.webp',
            'codm' => 'call-of-duty-mobile/cp-codm.webp',
            'pubg' => 'pubg-mobile/uc-pubg.webp',
            'valorant' => 'valorant/valorant-points.webp',
        ];

        foreach ($icons as $name => $file) {
            if (str_contains($gameName, $name)) {
                return pwa_asset('assets/icon-topup/'.$file);
            }
        }

        $gameFolder = \Illuminate\Support\Str::slug($gameName);
        foreach (['webp', 'png', 'jpg', 'jpeg'] as $extension) {
            $file = 'assets/icon-topup/'.$gameFolder.'/item.'.$extension;
            if (is_file(public_path($file))) {
                return pwa_asset($file);
            }
        }

        return null;
    }
}

if (! function_exists('topup_product_icon_asset')) {
    function topup_product_icon_asset(
        ?string $gameName,
        ?string $productName,
        ?string $productType = null
    ): ?string {
        $gameName = mb_strtolower(trim((string) $gameName));

        $product = mb_strtolower(trim((string) $productName).' '.trim((string) $productType));

        $gameProductIcons = [];
        $gameFolder = null;

        if (str_contains($gameName, 'mobile legends')) {
            $gameFolder = 'mobile-legends';
            $gameProductIcons = [
                'weekly-diamond-pass.png' => ['weekly diamond pass', 'weekly pass', 'wdp'],
                'twilight-pass.webp' => ['twilight'],
                'limited-elite-bundle.png' => ['limited elite bundle', 'elite bundle'],
                'recharge-bonus.webp' => ['2x recharge', 'recharge bonus', 'double diamond', 'first topup'],
                'top-up-global.png' => ['top up global', 'topup global'],
            ];
        } elseif (str_contains($gameName, 'free fire')) {
            $gameFolder = 'free-fire';
            $gameProductIcons = [
                'membership-weekly-lite.png' => ['weekly lite', 'mingguan lite'],
                'membership-monthly.png' => ['monthly', 'bulanan'],
                'membership-weekly.png' => ['weekly', 'mingguan', 'membership'],
            ];
        } elseif (str_contains($gameName, 'magic chess')) {
            $gameFolder = 'magic-chess';
            $gameProductIcons = [
                'weekly-card.png' => ['weekly card'],
                'membership.png' => ['membership', 'battle bounty', 'lukas'],
            ];
        } elseif (str_contains($gameName, 'pubg')) {
            $gameFolder = 'pubg-mobile';
            $gameProductIcons = [
                'elite-pass-plus.webp' => ['elite pass plus'],
                'royale-pass.webp' => ['royale pass', 'elite pass'],
                'weekly-deal-pack-1.webp' => ['weekly deal pack 1'],
                'weekly-deal-pack-2.webp' => ['weekly deal pack 2'],
                'weekly-mythic-emblem.webp' => ['weekly mythic emblem'],
                'prime-plus.webp' => ['prime plus', 'membership'],
            ];
        }

        foreach ($gameProductIcons as $file => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($product, $keyword)) {
                    return pwa_asset('assets/icon-topup/'.$gameFolder.'/'.$file);
                }
            }
        }

        $gameIcon = topup_game_icon_asset($gameName);
        if ($gameIcon) {
            return $gameIcon;
        }

        $currencyIcons = [
            'token.svg' => ['token', 'goldstar', 'fmp', 'tft coin', 'wild core', 'lattice', 'aeos'],
            'diamond.svg' => ['diamond'],
            'coin.svg' => ['coin', 'gold', 'silver', 'cash', 'credit', 'kredit', 'points', ' point', ' rp', ' vp', ' cp', ' ic', ' jc', 'w-gold', 'bitgold', 'zeny', 'ingot'],
            'crystal.svg' => ['crystal', 'gem', 'ruby', 'rubi', 'jade', 'quartz', 'lunite', 'monochrome', 'platinum', 'opal', 'aurion', 'citrine', 'prismite', 'flower', 'memory', 'spiral', 'tanium', 'bead', 'frost star'],
            'voucher.svg' => ['voucher', 'coupon', 'ticket', 'pass', 'membership', 'pack', 'set rb', 'key', 'candy', 'candies', 'cake'],
        ];

        foreach ($currencyIcons as $file => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($product, $keyword)) {
                    return pwa_asset('assets/icon-topup/common/'.$file);
                }
            }
        }

        return pwa_asset('assets/icon-topup/common/item.svg');
    }
}
