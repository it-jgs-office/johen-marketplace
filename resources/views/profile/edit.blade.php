@extends('layouts.topup')

@section('title', 'Profil Saya - Johen Gaming')

@push('styles')
<link rel="stylesheet" href="{{ pwa_asset('css/profile.css') }}&f={{ filemtime(public_path('css/profile.css')) }}">
@endpush

@section('content')
@php($profileAvatarUrl = $user->avatar ? media_url($user->avatar) : null)
<section class="account-page">
    <div class="account-shell">
        <div class="account-dashboard">
            @include('profile.partials.account-navigation')

            <main class="account-main">
                <section class="account-security-banner" aria-labelledby="account-security-title">
                    <div>
                        <span class="account-security-kicker">Keamanan akun</span>
                        <h1 id="account-security-title">Jaga akunmu tetap aman.</h1>
                        <p>Gunakan password yang kuat dan perbarui secara berkala.</p>
                        <a href="#keamanan-akun">Buka pengaturan keamanan</a>
                    </div>
                    <svg class="account-security-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.8 7 10 4.1-1.2 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                </section>

                <div class="account-summary-grid">
                    <header class="account-hero">
                        <div class="account-hero-main">
                            <div class="account-avatar" aria-hidden="true">
                                @if($profileAvatarUrl)
                                    <img src="{{ $profileAvatarUrl }}" alt="">
                                @else
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="account-identity">
                                <div class="account-eyebrow">Profil Saya</div>
                                <h2>{{ $user->name }}</h2>
                                <span class="account-role">{{ $user->isAdmin() ? 'Administrator' : 'Member' }}</span>
                            </div>
                        </div>
                        <div class="account-contact-row">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            <span>{{ $user->email }}</span>
                        </div>
                    </header>

                    <section class="account-overview" aria-label="Status akun">
                        <div class="account-overview-head">
                            <span class="account-overview-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 5 6v5c0 4.6 2.9 8.8 7 10 4.1-1.2 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                            </span>
                            <div><small>Status akun</small><strong>Aktif dan terlindungi</strong></div>
                            <span class="account-status">Aktif</span>
                        </div>
                        <div class="account-overview-actions">
                            <a href="#informasi-profil">Edit profil</a>
                            <a href="#keamanan-akun">Ubah password</a>
                        </div>
                    </section>
                </div>

                <section class="account-shortcuts" aria-labelledby="account-shortcuts-title">
                    <div class="account-section-heading">
                        <div>
                            <span class="account-section-eyebrow">AKUN SAYA</span>
                            <h2 id="account-shortcuts-title">Aktivitas Saya</h2>
                        </div>
                        <p>Semua aktivitas akunmu dalam satu tempat.</p>
                    </div>
                    <div class="account-shortcuts-grid">
                @unless($user->isAdmin())
                <a class="account-shortcut" href="{{ route('points.index') }}">
                    <span class="account-shortcut-icon" aria-hidden="true"><x-johen-point-icon size="28" /></span>
                    <span class="account-shortcut-copy"><strong>{{ number_format($pointBalance, 0, ',', '.') }} Johen Point</strong><small>1 point = Rp1 · lihat saldo dan seluruh mutasi.</small></span>
                    <svg class="account-shortcut-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a class="account-shortcut" href="{{ route('orders.my') }}">
                    <span class="account-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l2 4v13H4V7l2-4Z"/><path d="M4 8h16M9 12h6"/></svg></span>
                    <span class="account-shortcut-copy"><strong>Pesanan Saya</strong><small>Lihat status top up dan pembayaran.</small></span>
                    <svg class="account-shortcut-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a class="account-shortcut" href="{{ route('jual-beli-akun.orders') }}">
                    <span class="account-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M5 17c.5-2 2-3 3-3s2.5 1 3 3M14 9h5M14 13h5"/></svg></span>
                    <span class="account-shortcut-copy"><strong>Pesanan Akun</strong><small>Pantau pembelian akun game.</small></span>
                    <svg class="account-shortcut-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a class="account-shortcut" href="{{ route('vouchers.index') }}">
                    <span class="account-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v5a2 2 0 0 0 0 4v5H4v-5a2 2 0 0 0 0-4V5Z"/><path d="M12 5v14" stroke-dasharray="2 3"/></svg></span>
                    <span class="account-shortcut-copy"><strong>Voucher Saya</strong><small>Lihat voucher yang bisa digunakan.</small></span>
                    <svg class="account-shortcut-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a class="account-shortcut" href="{{ route('reviews.my') }}">
                    <span class="account-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10Z"/><path d="m12 6 1.2 2.4 2.7.4-2 1.9.5 2.7-2.4-1.3-2.4 1.3.5-2.7-2-1.9 2.7-.4L12 6Z"/></svg></span>
                    <span class="account-shortcut-copy"><strong>Ulasan Saya</strong><small>Baca kembali ulasan yang dikirim.</small></span>
                    <svg class="account-shortcut-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a class="account-shortcut" href="{{ route('my-inquiries') }}">
                    <span class="account-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                    <span class="account-shortcut-copy"><strong>Riwayat Pesan</strong><small>Lihat pesan dan balasan bantuan.</small></span>
                    <svg class="account-shortcut-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                @endunless
                @if($user->isAdmin())
                    <a class="account-shortcut" href="{{ route('admin.dashboard') }}">
                        <span class="account-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3zM3 9h18M9 9v12"/></svg></span>
                        <span class="account-shortcut-copy"><strong>Admin Panel</strong><small>Buka pengelolaan toko.</small></span>
                        <svg class="account-shortcut-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                @endif
                    </div>
                </section>

                <div class="account-section-heading account-settings-heading" id="pengaturan-akun">
                    <div>
                        <span class="account-section-eyebrow">PENGATURAN</span>
                        <h2>Pengaturan Akun</h2>
                    </div>
                    <p>Kelola informasi dan keamanan akunmu.</p>
                </div>

                <div class="account-content">
                    @include('profile.partials.update-profile-information-form')
                    @include('profile.partials.update-password-form')
                    @include('profile.partials.delete-user-form')
                </div>
            </main>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    var avatarInput = document.getElementById('avatar');
    var avatarPreview = document.getElementById('profilePhotoPreview');
    var avatarFallback = document.getElementById('profilePhotoFallback');
    var removeAvatarInput = document.getElementById('remove_avatar');
    var removeAvatarButton = document.getElementById('removeProfilePhoto');
    var originalAvatar = avatarPreview ? avatarPreview.getAttribute('data-original-src') : '';

    function showAvatarPreview(src) {
        if (!avatarPreview || !avatarFallback) return;
        if (src) {
            avatarPreview.src = src;
            avatarPreview.hidden = false;
            avatarFallback.hidden = true;
        } else {
            avatarPreview.removeAttribute('src');
            avatarPreview.hidden = true;
            avatarFallback.hidden = false;
        }
    }

    avatarInput?.addEventListener('change', function () {
        var file = avatarInput.files && avatarInput.files[0];
        if (!file) {
            showAvatarPreview(originalAvatar);
            return;
        }

        if (removeAvatarInput) removeAvatarInput.value = '0';
        var reader = new FileReader();
        reader.addEventListener('load', function () { showAvatarPreview(reader.result); });
        reader.readAsDataURL(file);
    });

    removeAvatarButton?.addEventListener('click', function () {
        avatarInput.value = '';
        removeAvatarInput.value = '1';
        showAvatarPreview('');
    });

    var dialog = document.getElementById('deleteAccountDialog');
    var openButton = document.getElementById('openDeleteAccountDialog');
    var closeButton = document.getElementById('closeDeleteAccountDialog');

    if (!dialog || !openButton || !closeButton) return;

    openButton.addEventListener('click', function () { dialog.showModal(); });
    closeButton.addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) dialog.close();
    });

    @if($errors->userDeletion->isNotEmpty())
        dialog.showModal();
    @endif
})();
</script>
@endpush
