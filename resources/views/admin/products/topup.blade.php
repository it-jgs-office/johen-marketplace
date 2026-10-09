@extends('admin.layouts.app')

@section('title', 'Top Up Game')

@section('content')
@php
    $lastSync = \App\Models\SiteSetting::get('digiflazz_last_sync');
    $digiflazzReady = app(\App\Services\DigiflazzService::class)->isConfigured();
    $productTotal = $games->sum('product_count');
@endphp

<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold">Top Up</h2>
            <span class="badge badge-neutral">{{ $games->count() }} game</span>
            <span class="badge badge-success">{{ $productTotal }} produk aktif</span>
        </div>
        <p style="color:var(--text-dim);font-size:.82rem;margin-top:.35rem">
            Pilih game untuk membuka daftar nominal dari Digiflazz.
            @if($lastSync) Terakhir disinkronkan {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}. @endif
        </p>
    </div>

    <div class="flex items-center gap-3">
        @if(!$digiflazzReady)
            <span class="badge badge-error" style="font-size:.75rem"><i class="fas fa-exclamation-triangle"></i> Digiflazz belum config</span>
        @endif
        <form action="{{ route('admin.products.sync') }}" method="POST">
            @csrf
            <input type="hidden" name="force" value="1">
            <button type="submit" class="btn btn-ghost" {{ $digiflazzReady ? '' : 'disabled' }}>
                <i class="fas fa-sync"></i><span>Sinkronisasi Digiflazz</span>
            </button>
        </form>
    </div>
</div>

@if($games->isNotEmpty())
    <div class="topup-table-toolbar mb-4">
        <i class="fas fa-search" aria-hidden="true"></i>
        <input type="search" id="gameSearch" placeholder="Cari game..." autocomplete="off" aria-label="Cari game top up">
        <span id="gameSearchCount">{{ $games->count() }} game tersedia</span>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th class="text-left">Produk Aktif</th>
                        <th>Status</th>
                        <th class="text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody id="gameTableBody">
                    @foreach($games as $game)
                        <tr data-game-name="{{ mb_strtolower($game->name) }}">
                            <td>
                                <div class="flex items-center gap-3 min-w-[190px]">
                                    <div class="topup-game-thumb" aria-hidden="true">
                                        @if($game->thumbnail_url)
                                            <img src="{{ $game->thumbnail_url }}" alt="">
                                        @else
                                            <i class="fas fa-gamepad"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="topup-game-name-row">
                                            <span class="font-semibold">{{ $game->name }}</span>
                                            @if($game->is_topup_popular)
                                                <span class="topup-popular-badge"><i class="fas fa-gem" aria-hidden="true"></i> Populer</span>
                                            @endif
                                            @if($game->detail_bg_url)
                                                <span class="topup-cover-badge"><i class="fas fa-image" aria-hidden="true"></i> Sampul</span>
                                            @endif
                                        </div>
                                        <div style="font-size:.74rem;color:var(--text-dim)">Katalog Digiflazz</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-left"><span class="badge badge-neutral">{{ number_format($game->product_count) }} nominal</span></td>
                            <td><span class="badge badge-success"><i class="fas fa-circle-check"></i> Aktif</span></td>
                            <td class="text-left">
                                <div class="flex items-center justify-start gap-2">
                                    <a href="{{ route('admin.products.game', $game) }}" class="btn btn-primary btn-xs">
                                        <i class="fas fa-list"></i> Lihat Produk
                                    </a>
                                    <a href="{{ route('admin.brands.edit', $game) }}#detailCoverTitle" class="btn btn-ghost btn-xs" title="Kelola gambar dan informasi game">
                                        <i class="fas fa-images"></i> Kelola Tampilan
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div id="gameSearchEmpty" class="empty-state" hidden>
        <i class="fas fa-search"></i>
        <p>Game tidak ditemukan pada katalog top up.</p>
    </div>
@else
    <div class="empty-state">
        <i class="fas fa-gamepad"></i>
        <p>Belum ada game top up dari Digiflazz. Jalankan sinkronisasi untuk mengambil katalog.</p>
    </div>
@endif
@endsection

@push('styles')
<style>
.topup-table-toolbar{display:flex;align-items:center;gap:.7rem;max-width:480px;padding:.68rem .9rem;border:1px solid var(--glass-border);border-radius:12px;background:rgba(13,31,61,.58)}
.topup-table-toolbar>i{color:var(--accent);font-size:.84rem}.topup-table-toolbar input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:var(--text);font-size:.86rem}.topup-table-toolbar input::placeholder{color:var(--text-dim)}.topup-table-toolbar span{padding-left:.7rem;border-left:1px solid var(--glass-border);color:var(--text-dim);font-size:.72rem;white-space:nowrap}
.topup-game-thumb{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;overflow:hidden;border:1px solid rgba(148,163,184,.24);border-radius:11px;background:linear-gradient(135deg,rgba(124,58,237,.36),rgba(14,165,233,.18));color:#c4b5fd}.topup-game-thumb img{width:100%;height:100%;object-fit:cover}
.topup-game-name-row{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap}.topup-popular-badge{display:inline-flex;align-items:center;gap:.28rem;padding:.17rem .46rem;border:1px solid rgba(251,191,36,.3);border-radius:999px;background:linear-gradient(135deg,rgba(245,158,11,.2),rgba(251,191,36,.08));color:#fbbf24;font-size:.62rem;font-weight:800;line-height:1.2;white-space:nowrap}.topup-popular-badge i{font-size:.58rem}
.topup-cover-badge{display:inline-flex;align-items:center;gap:.28rem;padding:.17rem .46rem;border:1px solid rgba(56,189,248,.3);border-radius:999px;background:rgba(14,165,233,.1);color:#7dd3fc;font-size:.62rem;font-weight:800;line-height:1.2;white-space:nowrap}.topup-cover-badge i{font-size:.58rem}
@media (max-width:640px){.topup-table-toolbar{max-width:none}.topup-table-toolbar span{display:none}}
</style>
@endpush

@push('scripts')
<script>
const gameSearch = document.getElementById('gameSearch');
if (gameSearch) {
    const rows = [...document.querySelectorAll('[data-game-name]')];
    const counter = document.getElementById('gameSearchCount');
    const empty = document.getElementById('gameSearchEmpty');
    gameSearch.addEventListener('input', () => {
        const term = gameSearch.value.trim().toLocaleLowerCase('id-ID');
        const shown = rows.filter(row => {
            const visible = row.dataset.gameName.includes(term);
            row.hidden = !visible;
            return visible;
        }).length;
        counter.textContent = shown + ' game tersedia';
        empty.hidden = shown !== 0;
    });
}
</script>
@endpush
