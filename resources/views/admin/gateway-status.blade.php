@extends('admin.layouts.app')

@section('title', 'Status Gateway Pembayaran')

@section('content')
@php
    $allOk = collect($checks)->every(fn($c) => $c['ok']);
@endphp

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-lg font-semibold">Status Gateway Pembayaran</h2>
        <p class="text-sm" style="color:var(--text-muted)">Cek kesiapan {{ $gatewayLabel }} & Digiflazz sebelum menerima pembayaran live.</p>
    </div>
    <span class="badge {{ $allOk ? 'badge-success' : 'badge-error' }}" style="font-size:0.8rem">
        <i class="fas {{ $allOk ? 'fa-check-circle' : 'fa-exclamation-triangle' }}"></i>
        {{ $allOk ? 'Siap Live' : 'Belum Semua Siap' }}
    </span>
</div>

<div class="mb-4">
    <div class="card-glass p-4">
        <div class="flex items-center gap-3">
            <i class="fas fa-globe" style="color:var(--accent);font-size:1.2rem"></i>
            <div>
                <div class="text-sm" style="color:var(--text-muted)">Domain/URL Aplikasi (APP_URL)</div>
                <div class="font-semibold">{{ $appUrl }}</div>
            </div>
        </div>
    </div>
</div>

<div class="space-y-3">
    @foreach($checks as $key => $check)
    <div class="card-glass p-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="badge {{ $check['ok'] ? 'badge-success' : 'badge-error' }}" style="min-width:64px;justify-content:center">
                    <i class="fas {{ $check['ok'] ? 'fa-check' : 'fa-times' }}"></i>
                    {{ $check['ok'] ? 'OK' : 'Gagal' }}
                </span>
                <span class="font-semibold">{{ $check['label'] }}</span>
            </div>
        </div>
        @if($check['detail'])
        <p class="text-sm mt-2" style="color:var(--text-muted)">{{ $check['detail'] }}</p>
        @endif
    </div>
    @endforeach
</div>

<div class="card-glass p-5 mt-6">
    <h3 class="font-semibold mb-3">
        <i class="fas fa-wallet" style="color:var(--accent)"></i> Saldo Digiflazz
    </h3>
    @if(!$digiflazzConfigured)
        <p class="text-sm" style="color:var(--text-muted)">
            Digiflazz belum dikonfigurasi. Isi <code>DIGIFLAZZ_USERNAME</code> dan <code>DIGIFLAZZ_KEY</code> di <code>.env</code>, lalu jalankan <code>php artisan config:clear</code>.
        </p>
    @elseif($digiflazzBalance === null)
        <p class="text-sm" style="color:var(--text-muted)">
            Saldo tidak dapat dibaca saat ini. Periksa koneksi ke <code>api.digiflazz.com</code>.
        </p>
    @else
        <div class="grid grid-cols-3 gap-3">
            <div class="stat-card" style="text-align:center">
                <div class="text-2xl font-bold">{{ $digiflazzBalance }}</div>
                <div class="text-xs" style="color:var(--text-muted)">Saldo tersedia</div>
            </div>
            <div class="stat-card" style="text-align:center">
                <div class="text-2xl font-bold">{{ $digiflazzProductCount }}</div>
                <div class="text-xs" style="color:var(--text-muted)">Produk tersinkron</div>
            </div>
            <div class="stat-card" style="text-align:center">
                <div class="text-2xl font-bold">{{ $digiflazzMarginPercent }}%</div>
                <div class="text-xs" style="color:var(--text-muted)">Margin</div>
            </div>
        </div>
        @if($digiflazzLastSync)
            <p class="text-sm mt-3" style="color:var(--text-muted)">
                Sinkron terakhir: <strong>{{ \Carbon\Carbon::parse($digiflazzLastSync)->diffForHumans() }}</strong>
            </p>
        @endif
        @if($digiflazzBalanceNumber < 100000)
            <p class="text-sm mt-2" style="color:var(--warning)">
                <i class="fas fa-exclamation-triangle"></i> Saldo di bawah Rp100.000 — top up agar pesanan tidak gagal diproses.
            </p>
        @endif
    @endif
</div>

<div class="card-glass p-5 mt-6">
    <h3 class="font-semibold mb-3">
        <i class="fas fa-bell" style="color:var(--accent)"></i> Notifikasi Push Chat ({{ $pushStats['configured'] ? 'Terkonfigurasi' : 'VAPID key belum diisi' }})
    </h3>
    <p class="text-sm mb-3" style="color:var(--text-muted)">
        Perangkat yang sudah aktif menerima notifikasi live chat. Angka 0 berarti belum ada browser yang menekan tombol
        <strong>"Aktifkan Notifikasi"</strong> (di admin panel atau di widget live chat toko). Notifikasi push hanya berjalan di
        <strong>HTTPS</strong> (atau localhost).
    </p>
    <div class="grid grid-cols-3 gap-3">
        <div class="stat-card" style="text-align:center">
            <div class="text-2xl font-bold">{{ $pushStats['admin'] }}</div>
            <div class="text-xs text-sm" style="color:var(--text-muted)">Admin panel</div>
        </div>
        <div class="stat-card" style="text-align:center">
            <div class="text-2xl font-bold">{{ $pushStats['lcadmin'] }}</div>
            <div class="text-xs" style="color:var(--text-muted)">Live Chat Admin</div>
        </div>
        <div class="stat-card" style="text-align:center">
            <div class="text-2xl font-bold">{{ $pushStats['web'] }}</div>
            <div class="text-xs" style="color:var(--text-muted)">User toko</div>
        </div>
    </div>
    @if($pushStats['admin'] + $pushStats['lcadmin'] + $pushStats['web'] > 0)
    <p class="text-sm mt-3" style="color:var(--success)">
        <i class="fas fa-check-circle"></i> Ada perangkat terdaftar — coba tombol <strong>"Uji"</strong> di indikator notifikasi kanan-bawah halaman admin.
    </p>
    @endif
</div>

<div class="card-glass p-5 mt-6">
    <h3 class="font-semibold mb-3"><i class="fas fa-clipboard-check" style="color:var(--accent)"></i> Urutan Persiapan Live</h3>
    <ol class="list-decimal list-inside space-y-1 text-sm" style="color:var(--text-muted)">
        <li>Deploy aplikasi ke hosting + domain + SSL, set <code>APP_URL=https://domainmu</code>.</li>
        @if(config('services.payment.gateway') === 'doku')
        <li>Di DOKU Back Office sesuai environment, set Notification URL VA/gerai ke <code>{{ route('payment.doku.notification') }}</code>.</li>
        <li>Aktifkan kanal Direct API, lalu isi kode kanal yang disetujui di <code>DOKU_DIRECT_METHODS</code>. Aktifkan juga metode tersebut pada pengaturan pembayaran.</li>
        <li>Untuk QRIS/VA, isi <code>DOKU_PRIVATE_KEY_PATH</code> dan daftarkan pasangan public key di DOKU. Lengkapi Merchant ID, Terminal ID, kode pos QRIS atau partner service ID dan customer prefix setiap bank sesuai DOKU.</li>
        <li>Jalankan scheduler Laravel dan queue worker. Status disinkronkan melalui notifikasi dan <code>doku:reconcile</code>, termasuk ketika halaman pelanggan sudah ditutup.</li>
        <li>Selesaikan pengujian Sandbox dan verifikasi notifikasi sukses.</li>
        <li>Setelah siap live, gunakan credential production dan set <code>DOKU_ENVIRONMENT=production</code>.</li>
        @else
        <li>Set webhook <strong>QR Code paid</strong> &amp; <strong>Invoice paid</strong> di dashboard Xendit ke <code>{{ route('payment.notification') }}</code> dengan Callback Token sesuai <code>XENDIT_CALLBACK_TOKEN</code>.</li>
        <li>Aktifkan produk QRIS/VA/dll di akun Xendit.</li>
        <li>Ajukan verifikasi business Xendit &amp; tunggu approve.</li>
        <li>Setelah approve: ganti ke secret key live, set <code>XENDIT_IS_PRODUCTION=true</code>.</li>
        @endif
        <li>Set key &amp; mode Digiflazz konsisten (key live, <code>DIGIFLAZZ_PRODUCTION=true</code>), set webhook Digiflazz ke <code>{{ route('payment.digiflazz.callback') }}</code>.</li>
        <li>Set <code>PAYMENT_SIMULATION=false</code> (kecuali ingin menguji aman).</li>
    </ol>
</div>
@endsection
