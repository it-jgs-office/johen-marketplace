@extends('admin.layouts.app')

@section('title', 'Detail Produk')

@section('content')
@php($raw = (array) $accountListing->getAttribute('jba_raw'))

<div class="flex items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.account-listings') }}" class="btn btn-ghost btn-xs" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-lg font-semibold">Detail Produk</h2>
            <p style="color:var(--text-muted);font-size:.8rem">Data ditampilkan dari API utama.</p>
        </div>
    </div>
    <span class="badge {{ $accountListing->is_sold ? 'badge-danger' : 'badge-success' }}">
        {{ $accountListing->is_sold ? 'Terjual' : 'Tersedia' }}
    </span>
</div>

<div class="product-detail-grid">
    <section class="card-glass product-preview">
        @if($accountListing->photo_url)
            <img src="{{ $accountListing->photo_url }}" alt="{{ $accountListing->product_name }}">
        @else
            <div class="product-preview-empty"><i class="fas fa-image"></i><span>Foto tidak tersedia</span></div>
        @endif
    </section>

    <section class="card-glass product-info">
        <div class="product-kicker">{{ $accountListing->game }}</div>
        <h3>{{ $accountListing->product_name }}</h3>
        <div class="product-price">Rp {{ number_format($accountListing->price, 0, ',', '.') }}</div>

        <dl class="product-meta">
            <div><dt>ID Game</dt><dd>{{ $raw['id_game'] ?? '–' }}</dd></div>
            <div><dt>Penjual</dt><dd>{{ $accountListing->owner_name ?: '–' }}</dd></div>
            <div><dt>Urutan</dt><dd>{{ $raw['urutan'] ?? '–' }}</dd></div>
            <div><dt>Status</dt><dd>{{ $accountListing->is_sold ? 'Terjual' : 'Tersedia' }}</dd></div>
        </dl>

        <div class="product-description">
            <span>Spesifikasi</span>
            <p>{{ $accountListing->specifications ?: 'Tidak ada spesifikasi.' }}</p>
        </div>
    </section>
</div>

@if(count($accountListing->detail_photo_urls))
<section class="card-glass product-gallery">
    <h3>Foto detail</h3>
    <div>
        @foreach($accountListing->detail_photo_urls as $url)
            <img src="{{ $url }}" alt="Foto detail {{ $accountListing->product_name }}" loading="lazy">
        @endforeach
    </div>
</section>
@endif
@endsection

@push('styles')
<style>
    .product-detail-grid { display:grid; grid-template-columns:minmax(260px,.85fr) minmax(0,1.4fr); gap:1rem; }
    .product-preview, .product-info, .product-gallery { padding:1rem; }
    .product-preview { min-height:340px; display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .product-preview img { width:100%; max-height:440px; object-fit:contain; border-radius:12px; }
    .product-preview-empty { display:flex; flex-direction:column; align-items:center; gap:.75rem; color:var(--text-dim); }
    .product-preview-empty i { font-size:2rem; }
    .product-kicker { color:var(--accent); font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
    .product-info h3 { margin:.4rem 0; font-size:1.35rem; }
    .product-price { color:var(--accent); font-size:1.15rem; font-weight:800; margin-bottom:1.25rem; }
    .product-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
    .product-meta div { padding:.75rem; border:1px solid var(--glass-border); border-radius:10px; background:var(--bg-input); }
    .product-meta dt { color:var(--text-dim); font-size:.72rem; margin-bottom:.2rem; }
    .product-meta dd { font-size:.86rem; font-weight:600; overflow-wrap:anywhere; }
    .product-description { margin-top:1rem; padding-top:1rem; border-top:1px solid var(--glass-border); }
    .product-description span, .product-gallery h3 { display:block; font-size:.82rem; font-weight:700; margin-bottom:.55rem; }
    .product-description p { color:var(--text-muted); font-size:.86rem; line-height:1.7; white-space:pre-line; }
    .product-gallery { margin-top:1rem; }
    .product-gallery > div { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; }
    .product-gallery img { width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:10px; border:1px solid var(--glass-border); }
    @media (max-width:800px) {
        .product-detail-grid { grid-template-columns:1fr; }
        .product-preview { min-height:240px; }
        .product-gallery > div { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
</style>
@endpush
