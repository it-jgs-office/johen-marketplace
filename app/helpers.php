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
            $path = parse_url($path, PHP_URL_PATH) ?? $path;
            $path = preg_replace('#^(/)?storage/#', '', $path);
        } else {
            $path = preg_replace('#^(/)?storage/#', '', $path);
        }

        $path = ltrim($path, '/');

        if ($path === '') {
            return null;
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
            'dana' => 'dana.webp',
            'ovo' => 'ovo.webp',
            'linkaja' => 'LinkAja.webp',
            'bca' => 'bca.webp',
            'bca_va' => 'bca.webp',
            'bni' => 'bni.webp',
            'bni_va' => 'bni.webp',
            'mandiri' => 'mandiri.webp',
            'mandiri_va' => 'mandiri.webp',
            'permata' => 'permata-bank.webp',
            'permata_va' => 'permata-bank.webp',
            'alfamart' => 'alfamanrt.webp',
            'indomaret' => 'Logo_Indomaret.webp',
        ];

        return isset($logos[$code]) ? pwa_asset('assets/payment/'.$logos[$code]) : null;
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
            'mobile legends' => 'diamond-ml.webp',
            'magic chess' => 'diamond-magicchess.webp',
            'free fire' => 'diamond-ff.webp',
            'call of duty' => 'cp-codm.webp',
            'codm' => 'cp-codm.webp',
            'pubg' => 'uc-pubg.webp',
        ];

        foreach ($icons as $name => $file) {
            if (str_contains($gameName, $name)) {
                return pwa_asset('assets/icon-topup/'.$file);
            }
        }

        return null;
    }
}
