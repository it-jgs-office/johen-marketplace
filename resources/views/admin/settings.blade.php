@extends('admin.layouts.app')
@section('title', 'Pengaturan Situs')
@push('styles')
<style>
    .settings-page { max-width: 1280px; margin: 0 auto; }
    .settings-intro { display: flex; align-items: center; gap: 12px; margin: 0 0 20px; }
    .settings-intro__icon, .settings-card__icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; border-radius: 12px; }
    .settings-intro__icon { width: 42px; height: 42px; color: #dbeafe; background: rgba(96,165,250,.14); border: 1px solid rgba(96,165,250,.24); }
    .settings-intro__eyebrow { margin: 0 0 2px; color: var(--accent); font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .settings-intro__copy { margin: 0; color: var(--text-muted); font-size: .82rem; }
    .settings-top-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(270px, .42fr); gap: 20px; align-items: start; }
    .settings-card { padding: 22px; }
    .settings-card__heading { display: flex; align-items: center; gap: 11px; padding-bottom: 16px; margin-bottom: 18px; border-bottom: 1px solid var(--glass-border); }
    .settings-card__heading h2 { margin: 0; color: var(--text); font-size: .96rem; font-weight: 700; }
    .settings-card__heading p { margin: 2px 0 0; color: var(--text-dim); font-size: .72rem; }
    .settings-card__icon { width: 34px; height: 34px; font-size: .85rem; }
    .settings-card__icon--blue { color: #bfdbfe; background: rgba(59,130,246,.14); }
    .settings-card__icon--gold { color: #fde68a; background: rgba(245,158,11,.14); }
    .settings-group-title { margin: 0 0 11px; color: var(--text-muted); font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .settings-group-title--spaced { padding-top: 18px; margin-top: 18px; border-top: 1px solid var(--glass-border); }
    .settings-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .settings-field--full { grid-column: 1 / -1; }
    .settings-card label, .settings-banner-grid label { display: block; margin-bottom: 6px; color: var(--text-muted); font-size: .72rem; font-weight: 600; }
    .settings-appearance-card { min-height: 185px; }
    .settings-section-heading { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin: 28px 2px 14px; }
    .settings-section-heading h2 { margin: 0; color: var(--text); font-size: 1rem; font-weight: 700; }
    .settings-section-heading > p { max-width: 360px; margin: 0; color: var(--text-dim); font-size: .75rem; line-height: 1.55; text-align: right; }
    .settings-banner-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    .settings-banner-grid > .card-glass { padding: 22px; }
    .settings-banner-grid h3 { margin-bottom: 5px !important; }
    .settings-banner-grid > .card-glass > p { min-height: 36px; line-height: 1.5; }
    .settings-savebar { display: flex; justify-content: flex-end; margin-top: 24px; padding: 14px 0 4px; border-top: 1px solid var(--glass-border); }
    @media (max-width: 900px) { .settings-top-grid, .settings-banner-grid { grid-template-columns: 1fr; } .settings-appearance-card { min-height: 0; } }
    @media (max-width: 560px) { .settings-card, .settings-banner-grid > .card-glass { padding: 16px; } .settings-fields { grid-template-columns: 1fr; } .settings-field--full { grid-column: auto; } .settings-section-heading { display: block; margin-top: 22px; } .settings-section-heading > p { margin-top: 6px; text-align: left; } .settings-savebar { position: sticky; bottom: 0; z-index: 5; padding: 12px; margin: 18px -12px 0; background: color-mix(in srgb, var(--bg-main) 94%, transparent); backdrop-filter: blur(10px); } .settings-savebar .btn { width: 100%; justify-content: center; } }
</style>
@endpush
@section('content')
<div class="settings-intro">
    <span class="settings-intro__icon" aria-hidden="true"><i class="fas fa-sliders"></i></span>
    <div>
        <p class="settings-intro__eyebrow">Konfigurasi toko</p>
        <p class="settings-intro__copy">Kelola informasi layanan dan materi banner yang tampil untuk pelanggan.</p>
    </div>
</div>

<form class="settings-page" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="settings-top-grid">
        <section class="card-glass settings-card">
            <div class="settings-card__heading">
                <span class="settings-card__icon settings-card__icon--blue" aria-hidden="true"><i class="fas fa-headset"></i></span>
                <div>
                    <h2>Kontak</h2>
                    <p>Informasi usaha dan kanal layanan pelanggan.</p>
                </div>
            </div>

            <p class="settings-group-title">Identitas usaha</p>
            <div class="settings-fields">
                <div>
                    <label for="company_name">Nama Badan Usaha</label>
                    <input id="company_name" type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" placeholder="PT. Johen Sukses Abadi" class="input-field text-sm">
                </div>
                <div>
                    <label for="company_npwp">NPWP</label>
                    <input id="company_npwp" type="text" name="company_npwp" value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}" placeholder="00.000.000.0-000.000" class="input-field text-sm">
                </div>
                <div class="settings-field--full">
                    <label for="company_address">Alamat</label>
                    <textarea id="company_address" name="company_address" rows="2" placeholder="Jalan, nomor, kota, kode pos, provinsi" class="input-field text-sm">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                </div>
            </div>

            <p class="settings-group-title settings-group-title--spaced">Layanan pelanggan</p>
            <div class="settings-fields">
                <div>
                    <label for="contact_email">Email</label>
                    <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="admin@johen.com" class="input-field text-sm">
                </div>
                <div>
                    <label for="contact_whatsapp">WhatsApp</label>
                    <input id="contact_whatsapp" type="text" name="contact_whatsapp" value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '') }}" placeholder="62812xxxxxxx" class="input-field text-sm">
                </div>
                <div>
                    <label for="contact_phone_display">Tampilan Nomor Telepon</label>
                    <input id="contact_phone_display" type="text" name="contact_phone_display" value="{{ old('contact_phone_display', $settings['contact_phone_display'] ?? '') }}" placeholder="+62 812-xxxx-xxxx" class="input-field text-sm">
                </div>
                <div>
                    <label for="contact_cs_email">Email Customer Service</label>
                    <input id="contact_cs_email" type="email" name="contact_cs_email" value="{{ old('contact_cs_email', $settings['contact_cs_email'] ?? '') }}" placeholder="cs@johengaming.store" class="input-field text-sm">
                </div>
                <div>
                    <label for="contact_cs_hours">Jam Layanan</label>
                    <input id="contact_cs_hours" type="text" name="contact_cs_hours" value="{{ old('contact_cs_hours', $settings['contact_cs_hours'] ?? '') }}" placeholder="Setiap hari 24 jam (00.00 - 23.59 WIB)" class="input-field text-sm">
                </div>
                <div>
                    <label for="contact_instagram">Instagram</label>
                    <input id="contact_instagram" type="text" name="contact_instagram" value="{{ old('contact_instagram', $settings['contact_instagram'] ?? '') }}" placeholder="@johengaming" class="input-field text-sm">
                </div>
            </div>
        </section>

        <section class="card-glass settings-card settings-appearance-card">
            <div class="settings-card__heading">
                <span class="settings-card__icon settings-card__icon--gold" aria-hidden="true"><i class="fas fa-palette"></i></span>
                <div>
                    <h2>Tampilan</h2>
                    <p>Konten ringkas untuk footer situs.</p>
                </div>
            </div>
            <div>
                <label for="footer_text">Teks Footer</label>
                <input id="footer_text" type="text" name="footer_text" value="{{ old('footer_text', $settings['footer_text'] ?? '') }}" class="input-field text-sm">
            </div>
        </section>
    </div>

    <div class="settings-section-heading">
        <div>
            <p class="settings-intro__eyebrow">Materi promosi</p>
            <h2>Banner Situs</h2>
        </div>
        <p>Unggah materi baru untuk menggantikan banner yang sedang aktif.</p>
    </div>

    <div class="settings-banner-grid">

        <!-- HERO BANNER -->
        <div class="card-glass p-4">
            <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                <i class="fas fa-image" style="color:#f59e0b;font-size:0.85rem"></i>
                <span>Hero Banner</span>
            </h3>
            <p style="color:var(--text-dim);font-size:0.72rem;margin-bottom:0.75rem">Slider halaman utama. Maks 3 banner. Rekomendasi 1920 × 750 px (rasio 64:25).</p>
            @php
                $bannerLabels = ['Banner 1 (Utama)', 'Banner 2', 'Banner 3'];
                $bannerKeys = ['site_hero_banner', 'site_hero_banner_2', 'site_hero_banner_3'];
            @endphp
            @foreach($bannerLabels as $i => $label)
            <div class="flex items-center gap-3 {{ $i > 0 ? 'mt-3 pt-3 border-t' : '' }}" style="border-color:var(--border)">
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-medium mb-0.5">{{ $label }}</label>
                    <input type="file" name="{{ $bannerKeys[$i] }}" accept="image/jpeg,image/png,image/webp" class="text-sm w-full" style="color:var(--text-muted)">
                </div>
            </div>
            @endforeach
        </div>

        <!-- HERO BANNER JBA -->
        <div class="card-glass p-4">
            <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                <i class="fas fa-image" style="color:#f59e0b;font-size:0.85rem"></i>
                <span>Hero Banner (Stock Akun)</span>
            </h3>
            <p style="color:var(--text-dim);font-size:0.72rem;margin-bottom:0.75rem">Slider halaman Stock Akun. Maks 3 banner. Rekomendasi 1920 × 750 px (rasio 64:25).</p>
            @php
                $jbaBannerLabels = ['Banner 1 (Utama)', 'Banner 2', 'Banner 3'];
                $jbaBannerKeys = ['jba_hero_banner', 'jba_hero_banner_2', 'jba_hero_banner_3'];
            @endphp
            @foreach($jbaBannerLabels as $i => $label)
            <div class="flex items-center gap-3 {{ $i > 0 ? 'mt-3 pt-3 border-t' : '' }}" style="border-color:var(--border)">
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-medium mb-0.5">{{ $label }}</label>
                    <input type="file" name="{{ $jbaBannerKeys[$i] }}" accept="image/jpeg,image/png,image/webp" class="text-sm w-full" style="color:var(--text-muted)">
                </div>
            </div>
            @endforeach
        </div>

        <!-- BANNER GRUP HARGA MLBB -->
        <div class="card-glass p-4">
            <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                <i class="fas fa-tags" style="color:#f59e0b;font-size:0.85rem"></i>
                <span>Banner Grup Harga Mobile Legends</span>
            </h3>
            <p style="color:var(--text-dim);font-size:0.72rem;margin-bottom:0.75rem">Foto banner untuk filter pengelompokan budget (Budget Pelajar, UMR, Sultan, Financial Freedom) di halaman Stock Akun &mdash; Mobile Legends.</p>
            @php
                $jbaBudgetLabels = [
                    'jba_budget_pelajar_banner' => 'Budget Pelajar (300rb - 1.999jt)',
                    'jba_budget_umr_banner' => 'Budget UMR (2jt - 5.9jt)',
                    'jba_budget_sultan_banner' => 'Budget Sultan (6jt - 19.9jt)',
                    'jba_budget_freedom_banner' => 'Financial Freedom (20jt - 50jt)',
                ];
            @endphp
            @foreach($jbaBudgetLabels as $key => $label)
            <div class="flex items-center gap-3 {{ $loop->first ? '' : 'mt-3 pt-3 border-t' }}" style="border-color:var(--border)">
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-medium mb-0.5">{{ $label }}</label>
                    <input type="file" name="{{ $key }}" accept="image/jpeg,image/png,image/webp" class="text-sm w-full" style="color:var(--text-muted)">
                </div>
            </div>
            @endforeach
        </div>

        <!-- BANNER HEADER HALAMAN GAME JBA -->
        <div class="card-glass p-4">
            <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                <i class="fas fa-gamepad" style="color:#f59e0b;font-size:0.85rem"></i>
                <span>Header Banner Halaman Game (Stock Akun)</span>
            </h3>
            <p style="color:var(--text-dim);font-size:0.72rem;margin-bottom:0.75rem">Banner di bagian paling atas halaman game (/jual-beli-akun/mlbb, /pubg, dst). Teks nama game &amp; tombol Kembali tampil di atas banner.</p>
            @php
                $jbaGameBannerLabels = [
                    'jba_game_banner_mlbb' => 'Mobile Legends',
                    'jba_game_banner_pubg' => 'PUBG Mobile',
                    'jba_game_banner_efootball' => 'E-Football',
                    'jba_game_banner_fcm' => 'FC Mobile',
                    'jba_game_banner_ff' => 'Free Fire',
                    'jba_game_banner_roblox' => 'Roblox',
                    'jba_game_banner_valorant' => 'Valorant',
                ];
            @endphp
            @foreach($jbaGameBannerLabels as $key => $label)
            <div class="flex items-center gap-3 {{ $loop->first ? '' : 'mt-3 pt-3 border-t' }}" style="border-color:var(--border)">
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-medium mb-0.5">{{ $label }}</label>
                    <input type="file" name="{{ $key }}" accept="image/jpeg,image/png,image/webp" class="text-sm w-full" style="color:var(--text-muted)">
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="settings-savebar">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan Pengaturan
        </button>
    </div>
</form>

@endsection
