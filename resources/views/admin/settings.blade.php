@extends('admin.layouts.app')
@section('title', 'Pengaturan Situs')
@section('content')
<div class="flex items-center gap-3 mb-5">
    <i class="fas fa-cog" style="color:var(--accent);font-size:1.1rem"></i>
    <h2 class="text-lg font-semibold">Pengaturan Situs</h2>
</div>

@php
    $digiflazzSvc = app(\App\Services\DigiflazzService::class);
    $digiflazzConfigured = $digiflazzSvc->isConfigured();
    $lastSync = $settings['digiflazz_last_sync'] ?? null;
    $productCount = $settings['digiflazz_product_count'] ?? '0';
    $digiflazzKey = $digiflazzSvc->getKey();
    $digiflazzKeyMasked = $digiflazzKey !== ''
        ? str_repeat('*', 8) . substr($digiflazzKey, -4)
        : '';
@endphp

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <!-- ROW 1: 2 KOLOM -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- KIRI: Digiflazz + Informasi Situs -->
        <div class="space-y-5">

            <!-- DIGIFLAZZ -->
            <div class="card-glass p-4">
                <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                    <i class="fas fa-database" style="color:#10b981;font-size:0.85rem"></i>
                    <span>Digiflazz API</span>
                </h3>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Username</label>
                        <input type="text" value="{{ $digiflazzSvc->getUsername() }}" class="input-field text-sm" readonly placeholder="username">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Key</label>
                        <input type="text" value="{{ $digiflazzKeyMasked }}" class="input-field text-sm" readonly placeholder="key">
                    </div>
                </div>
                <div class="alert mb-3" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.25);color:var(--text-dim);font-size:0.74rem">
                    <i class="fas fa-lock" style="margin-right:0.25rem"></i>
                    Kredensial &amp; mode dibaca dari <code>.env</code>
                    (<code>DIGIFLAZZ_USERNAME</code>, <code>DIGIFLAZZ_KEY</code>, <code>DIGIFLAZZ_PRODUCTION</code>).
                    Diubah di server, lalu jalankan <code>php artisan config:clear</code>.
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <div>
                        <label class="block text-xs font-medium mb-1">Mode</label>
                        <select class="input-field text-sm" style="width:auto;min-width:140px" disabled>
                            <option value="0" {{ $digiflazzSvc->isProduction() ? '' : 'selected' }}>Sandbox</option>
                            <option value="1" {{ $digiflazzSvc->isProduction() ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Margin (%)</label>
                        <input type="number" name="digiflazz_margin_percent" value="{{ old('digiflazz_margin_percent', $digiflazzSvc->getMarginPercent()) }}"
                               min="0" max="100" step="0.1" class="input-field text-sm" style="width:100px">
                    </div>
                    <span class="badge {{ $digiflazzConfigured ? 'badge-success' : 'badge-error' }}" style="font-size:0.7rem">
                        {{ $digiflazzConfigured ? 'Terkonfigurasi' : 'Belum config' }}
                    </span>
                    @if($digiflazzConfigured)
                        <button type="button" class="btn btn-ghost btn-xs" id="testDigiflazzBtn" onclick="testDigiflazz()">
                            <i class="fas fa-plug"></i> Uji
                        </button>
                    @endif
                </div>
                @if($digiflazzConfigured)
                    <div class="flex items-center gap-2 mt-2" style="color:var(--text-dim);font-size:0.72rem">
                        @if($lastSync)
                            Sinkron: {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}
                        @else
                            Belum pernah sinkron
                        @endif
                        &middot; {{ $productCount }} produk
                    </div>
                    <div id="digiflazzTestResult" style="display:none;margin-top:0.5rem" class="alert"></div>
                @endif
            </div>

            <!-- INFORMASI SITUS -->
            <div class="card-glass p-4">
                <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                    <i class="fas fa-globe" style="color:var(--accent);font-size:0.85rem"></i>
                    <span>Informasi Situs</span>
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Nama Situs</label>
                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? 'Johen Gaming') }}" required class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Tagline</label>
                        <input type="text" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}" class="input-field text-sm">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-medium mb-1">Deskripsi</label>
                    <textarea name="site_description" rows="2" class="input-field text-sm">{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-medium mb-1">Logo</label>
                    <div class="flex items-center gap-3">
                        <div style="width:44px;height:44px;border-radius:10px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                            @if(!empty($settings['site_logo']))
                                <img src="{{ media_url($settings['site_logo']) }}" alt="Logo" style="width:100%;height:100%;object-fit:contain">
                            @else
                                <span style="font-size:0.65rem;color:var(--text-dim)">Logo</span>
                            @endif
</div>
                        <input type="file" name="site_logo" accept="image/jpeg,image/png,image/svg+xml" class="text-sm w-full" style="color:var(--text-muted)">
                    </div>
                </div>
            </div>
        </div>

        <!-- KANAN: Kontak + Tampilan -->
        <div class="space-y-5">

            <!-- KONTAK -->
            <div class="card-glass p-4">
                <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                    <i class="fas fa-headset" style="color:#3b82f6;font-size:0.85rem"></i>
                    <span>Kontak</span>
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Nama Badan Usaha</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $settings['company_name'] ?? '') }}" placeholder="PT. Johen Sukses Abadi" class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Alamat</label>
                        <textarea name="company_address" rows="2" placeholder="Jalan, nomor, kota, kode pos, provinsi" class="input-field text-sm">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">NPWP</label>
                        <input type="text" name="company_npwp" value="{{ old('company_npwp', $settings['company_npwp'] ?? '') }}" placeholder="00.000.000.0-000.000" class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="admin@johen.com" class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">WhatsApp</label>
                        <input type="text" name="contact_whatsapp" value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '') }}" placeholder="62812xxxxxxx" class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Tampilan Nomor Telepon</label>
                        <input type="text" name="contact_phone_display" value="{{ old('contact_phone_display', $settings['contact_phone_display'] ?? '') }}" placeholder="+62 812-xxxx-xxxx" class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Email Customer Service</label>
                        <input type="email" name="contact_cs_email" value="{{ old('contact_cs_email', $settings['contact_cs_email'] ?? '') }}" placeholder="cs@johengaming.store" class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Jam Layanan</label>
                        <input type="text" name="contact_cs_hours" value="{{ old('contact_cs_hours', $settings['contact_cs_hours'] ?? '') }}" placeholder="Setiap hari 24 jam (00.00 - 23.59 WIB)" class="input-field text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Instagram</label>
                        <input type="text" name="contact_instagram" value="{{ old('contact_instagram', $settings['contact_instagram'] ?? '') }}" placeholder="@johengaming" class="input-field text-sm">
                    </div>
                </div>
            </div>

            <!-- TAMPILAN -->
            <div class="card-glass p-4">
                <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                    <i class="fas fa-palette" style="color:#f59e0b;font-size:0.85rem"></i>
                    <span>Tampilan</span>
                </h3>
                <div>
                    <label class="block text-xs font-medium mb-1">Footer Text</label>
                    <input type="text" name="footer_text" value="{{ old('footer_text', $settings['footer_text'] ?? '') }}" class="input-field text-sm">
                </div>
            </div>

            <!-- QRIS -->
            <div class="card-glass p-4">
                <h3 class="font-semibold mb-3 flex items-center gap-2" style="font-size:0.9rem">
                    <i class="fas fa-qrcode" style="color:#8b5cf6;font-size:0.85rem"></i>
                    <span>QRIS Pembayaran (Jual Beli Akun)</span>
                </h3>
                <p style="color:var(--text-dim);font-size:0.72rem;margin-bottom:0.75rem">
                    Gambar QRIS statis milikmu. Pelanggan scan & bayar sesuai nominal otomatis, admin konfirmasi manual di menu Pesanan Akun.
                </p>
                <div class="flex items-center gap-3">
                    <div style="width:96px;height:96px;border-radius:10px;background:#fff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        @if(!empty($settings['qris_image']))
                            <img src="{{ media_url($settings['qris_image']) }}" alt="QRIS" style="width:100%;height:100%;object-fit:contain">
                        @else
                            <span style="font-size:0.6rem;color:var(--text-dim);text-align:center">Belum upload</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="block text-xs font-medium mb-0.5">Gambar QRIS</label>
                        <input type="file" name="qris_image" accept="image/jpeg,image/png,image/webp" class="text-sm w-full" style="color:var(--text-muted)">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 2: BANNER HORIZONTAL -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">

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
                <div style="width:112px;aspect-ratio:64/25;border-radius:8px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                    @if(!empty($settings[$bannerKeys[$i]]))
                        <img src="{{ media_url($settings[$bannerKeys[$i]]) }}" alt="{{ $label }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                        <span style="font-size:0.6rem;color:var(--text-dim);text-align:center">Kosong</span>
                    @endif
                </div>
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
                <span>Hero Banner (Jual Beli Akun)</span>
            </h3>
            <p style="color:var(--text-dim);font-size:0.72rem;margin-bottom:0.75rem">Slider halaman Jual Beli Akun. Maks 3 banner. Rekomendasi 1920 × 750 px (rasio 64:25).</p>
            @php
                $jbaBannerLabels = ['Banner 1 (Utama)', 'Banner 2', 'Banner 3'];
                $jbaBannerKeys = ['jba_hero_banner', 'jba_hero_banner_2', 'jba_hero_banner_3'];
            @endphp
            @foreach($jbaBannerLabels as $i => $label)
            <div class="flex items-center gap-3 {{ $i > 0 ? 'mt-3 pt-3 border-t' : '' }}" style="border-color:var(--border)">
                <div style="width:112px;aspect-ratio:64/25;border-radius:8px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                    @if(!empty($settings[$jbaBannerKeys[$i]]))
                        <img src="{{ media_url($settings[$jbaBannerKeys[$i]]) }}" alt="{{ $label }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                        <span style="font-size:0.6rem;color:var(--text-dim);text-align:center">Kosong</span>
                    @endif
                </div>
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
            <p style="color:var(--text-dim);font-size:0.72rem;margin-bottom:0.75rem">Foto banner untuk filter pengelompokan budget (Budget Pelajar, UMR, Sultan, Financial Freedom) di halaman Jual Beli Akun &mdash; Mobile Legends.</p>
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
                <div style="width:88px;height:50px;border-radius:8px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                    @if(!empty($settings[$key]))
                        <img src="{{ media_url($settings[$key]) }}" alt="{{ $label }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                        <span style="font-size:0.6rem;color:var(--text-dim);text-align:center">Kosong</span>
                    @endif
                </div>
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
                <span>Header Banner Halaman Game (Jual Beli Akun)</span>
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
                <div style="width:88px;height:50px;border-radius:8px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                    @if(!empty($settings[$key]))
                        <img src="{{ media_url($settings[$key]) }}" alt="{{ $label }}" style="width:100%;height:100%;object-fit:cover">
                    @else
                        <span style="font-size:0.6rem;color:var(--text-dim);text-align:center">Kosong</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-medium mb-0.5">{{ $label }}</label>
                    <input type="file" name="{{ $key }}" accept="image/jpeg,image/png,image/webp" class="text-sm w-full" style="color:var(--text-muted)">
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end mt-5">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Simpan Pengaturan
        </button>
    </div>
</form>

@push('scripts')
<script>
async function testDigiflazz() {
    const btn = document.getElementById('testDigiflazzBtn');
    const result = document.getElementById('digiflazzTestResult');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menguji...';
    result.style.display = 'none';

    try {
        const res = await fetch('{{ route('admin.digiflazz.test') }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await res.json();
        result.style.display = 'flex';
        result.className = data.success ? 'alert alert-success' : 'alert alert-error';
        result.innerHTML = `<i class="fas ${data.success ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${data.message}`;
    } catch (e) {
        result.style.display = 'flex';
        result.className = 'alert alert-error';
        result.innerHTML = '<i class="fas fa-exclamation-circle"></i> Gagal menguji koneksi.';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plug"></i> Uji Koneksi';
}
</script>
@endpush
@endsection
