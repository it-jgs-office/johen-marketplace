@extends('layouts.topup')

@section('title', 'Johen Point - Johen Gaming')

@section('content')
<div class="vc-page point-page">
    @include('profile.partials.account-navigation')
    <main class="account-hub-content">
        <header class="account-hub-heading">
            <div><span class="account-hub-eyebrow">LOYALTY WALLET</span><h1><x-johen-point-icon size="34" /> Johen Point</h1><p>1 Johen Point = Rp1. Gunakan untuk top up, joki, dan beli akun.</p></div>
        </header>

        <section class="point-hero">
            <div><span><x-johen-point-icon size="18" /> Point tersedia</span><strong>{{ number_format($balance, 0, ',', '.') }} <small>JP</small></strong><p>Point tidak dapat dicairkan atau ditarik tunai.</p></div>
            <a href="{{ route('home') }}" class="point-cta">Belanja Sekarang</a>
        </section>

        <section class="point-stats">
            <article><span>Total point masuk</span><strong>+{{ number_format($earned, 0, ',', '.') }}</strong></article>
            <article><span>Total point digunakan</span><strong>-{{ number_format($used, 0, ',', '.') }}</strong></article>
            <article><span>Total mutasi</span><strong>{{ number_format($transactions->total(), 0, ',', '.') }}</strong></article>
        </section>

        <section class="point-history">
            <div class="point-history-head"><div><h2><x-johen-point-icon size="24" /> Riwayat Johen Point</h2><p>Semua penggunaan dan refund tercatat di sini.</p></div></div>
            @forelse($transactions as $tx)
                <article class="point-row">
                    <span class="point-row-icon {{ (float) $tx->amount >= 0 ? 'in' : 'out' }}"><x-johen-point-icon size="28" /></span>
                    <div><strong>{{ $tx->typeLabel() }}</strong><p>{{ $tx->description }}</p><small>{{ $tx->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</small></div>
                    <div class="point-row-value {{ (float) $tx->amount >= 0 ? 'in' : 'out' }}">{{ (float) $tx->amount >= 0 ? '+' : '' }}{{ number_format($tx->amount, 0, ',', '.') }} JP<small>Saldo {{ number_format($tx->balance_after, 0, ',', '.') }} JP</small></div>
                </article>
            @empty
                <div class="point-empty">Belum ada mutasi Johen Point.</div>
            @endforelse
            {{ $transactions->links() }}
        </section>
    </main>
</div>
@endsection
