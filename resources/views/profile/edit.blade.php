@extends('layouts.topup')

@section('title', 'Pengaturan Akun - Johen Gaming')

@push('styles')
<link rel="stylesheet" href="{{ pwa_asset('css/profile.css') }}&f={{ filemtime(public_path('css/profile.css')) }}">
<style>
    .account-page {
        width: 100%;
        max-width: var(--layout-max);
        margin-inline: auto;
    }

    .account-shell {
        width: 100%;
        max-width: none;
        margin-inline: 0;
    }

    .account-layout {
        display: block;
    }

    .account-content {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        width: 100%;
    }
</style>
@endpush

@section('content')
<section class="account-page">
    <div class="account-shell">
        <nav class="account-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Beranda</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            <span>Pengaturan Akun</span>
        </nav>

        <header class="account-hero">
            <div class="account-hero-main">
                <div class="account-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div>
                    <div class="account-eyebrow">Johen Gaming ID</div>
                    <h1>Pengaturan Akun</h1>
                    <p>Kelola profil dan keamanan akun {{ $user->name }}.</p>
                </div>
            </div>
            <div class="account-status">Akun aktif</div>
        </header>

        <div class="account-layout">
            <div class="account-content">
                @include('profile.partials.update-profile-information-form')
                @include('profile.partials.update-password-form')
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
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
