@extends('admin.layouts.app')

@section('title', 'Daftar Produk')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Daftar Produk</h2>
        <span class="badge badge-neutral">{{ $listings->total() }} hasil</span>
    </div>
    <span class="badge badge-info"><i class="fas fa-eye"></i> Hanya dapat dilihat</span>
</div>

<form action="{{ route('admin.account-listings') }}" method="GET" class="listing-filter mb-5">
    <div class="listing-filter-search">
        <i class="fas fa-search"></i>
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama akun, ID game, atau penjual">
    </div>
    <select name="game" class="input-field">
        <option value="">Semua game</option>
        @foreach($games as $game)
            <option value="{{ $game }}" @selected(($filters['game'] ?? '') === $game)>{{ $game }}</option>
        @endforeach
    </select>
    <select name="status" class="input-field">
        <option value="">Semua status</option>
        <option value="tersedia" @selected(($filters['status'] ?? '') === 'tersedia')>Tersedia</option>
        <option value="habis" @selected(($filters['status'] ?? '') === 'habis')>Terjual</option>
    </select>
    <select name="sort" class="input-field">
        <option value="urutan_asc" @selected(($filters['sort'] ?? 'urutan_asc') === 'urutan_asc')>Urutkan: urutan</option>
        <option value="terbaru" @selected(($filters['sort'] ?? '') === 'terbaru')>Urutkan: terbaru</option>
        <option value="harga_asc" @selected(($filters['sort'] ?? '') === 'harga_asc')>Harga terendah</option>
        <option value="harga_desc" @selected(($filters['sort'] ?? '') === 'harga_desc')>Harga tertinggi</option>
    </select>
    <div class="listing-filter-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('admin.account-listings') }}" class="btn btn-ghost" title="Reset filter"><i class="fas fa-rotate-left"></i></a>
    </div>
</form>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-center">Urutan</th>
                    <th>Game</th>
                    <th>Nama Produk</th>
                    <th class="text-right">Harga</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($listings as $listing)
                <tr>
                    <td class="text-center">{{ data_get($listing->getAttribute('jba_raw'), 'urutan', '-') }}</td>
                    <td>{{ $listing->game }}</td>
                    <td style="font-size:0.88rem">{{ $listing->product_name }}</td>
                    <td class="text-right font-semibold" style="color:var(--accent)">Rp {{ number_format($listing->price, 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $listing->is_sold ? 'badge-danger' : 'badge-success' }}">
                            {{ $listing->is_sold ? 'Terjual' : 'Tersedia' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.account-listings.show', $listing->getAttribute('id')) }}" class="btn btn-ghost btn-xs">
                            <i class="fas fa-eye"></i> Lihat
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Tidak ada produk yang sesuai filter.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($listings->hasPages())
    <div class="listing-pagination">
        <span>Menampilkan {{ $listings->firstItem() }}–{{ $listings->lastItem() }} dari {{ $listings->total() }} produk</span>
        {{ $listings->onEachSide(1)->links() }}
    </div>
@endif
@endsection

@push('styles')
<style>
    .listing-filter {
        display: grid;
        grid-template-columns: minmax(240px, 1.7fr) repeat(3, minmax(150px, 1fr)) auto;
        gap: .75rem;
        padding: .875rem;
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        background: var(--bg-card);
    }
    .listing-filter-search { position: relative; }
    .listing-filter-search i {
        position: absolute;
        top: 50%; left: .9rem;
        transform: translateY(-50%);
        color: var(--text-dim);
        pointer-events: none;
    }
    .listing-filter-search input {
        width: 100%; height: 100%; min-height: 42px;
        padding: .65rem .8rem .65rem 2.5rem;
        border: 1px solid var(--border);
        border-radius: .5rem;
        background: var(--bg-input);
        color: var(--text-primary);
        outline: none;
    }
    .listing-filter-actions { display: flex; gap: .5rem; align-items: center; }
    .listing-filter-actions .btn { white-space: nowrap; }
    .listing-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        color: var(--text-muted);
        font-size: .82rem;
    }
    .listing-pagination nav { margin-left: auto; }
    @media (max-width: 1024px) {
        .listing-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .listing-filter-search { grid-column: span 2; }
    }
    @media (max-width: 640px) {
        .listing-filter, .listing-filter-search { grid-template-columns: 1fr; grid-column: auto; }
        .listing-filter-actions .btn:first-child { flex: 1; }
        .listing-pagination { flex-direction: column; align-items: flex-start; }
        .listing-pagination nav { margin-left: 0; width: 100%; overflow-x: auto; }
    }
</style>
@endpush
