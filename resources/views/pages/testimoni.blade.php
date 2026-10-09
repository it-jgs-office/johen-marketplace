@extends('layouts.topup')

@section('title', 'Testimoni Pelanggan - ' . config('app.name'))

@section('content')
<div class="testi-page">
  <div class="testi-page-hero">
    <h1>APA KATA MEREKA?</h1>
    <p>Ribuan orang telah mempercayai Johen Gaming. Simak pengalaman mereka berikut ini.</p>
  </div>

  <div class="testi-page-filters">
    <a href="{{ route('testimoni') }}" class="testi-filter-btn {{ !$activeLayanan ? 'active' : '' }}">Semua</a>
    <a href="{{ route('testimoni', ['layanan' => 'topup']) }}" class="testi-filter-btn {{ $activeLayanan === 'topup' ? 'active' : '' }}">Top Up</a>
    <a href="{{ route('testimoni', ['layanan' => 'jual-beli-akun']) }}" class="testi-filter-btn {{ $activeLayanan === 'jual-beli-akun' ? 'active' : '' }}">Jual Beli Akun</a>
    <a href="{{ route('testimoni', ['layanan' => 'joki']) }}" class="testi-filter-btn {{ $activeLayanan === 'joki' ? 'active' : '' }}">Joki MLBB</a>
  </div>

  <div class="testi-page-grid">
    @foreach($testimonials as $t)
    @php
      $testiGame = preg_replace('/^(Top Up|Joki Rank|Jual Akun)\s*-\s*/i', '', $t['game']);
      $testiRating = max(0, min(5, (int) ($t['rating'] ?? 5)));
    @endphp
    <article class="home-testi-card testi-page-card">
      <div class="home-testi-card-head">
        <div class="home-testi-brand"><span>{{ $testiGame ?: $t['game'] }}</span></div>
        <div class="home-testi-rating" aria-label="Rating {{ $testiRating }} dari 5">
          <span aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)<span class="{{ $i > $testiRating ? 'is-empty' : '' }}">★</span>@endfor
          </span>
          <span>{{ number_format($testiRating, 1) }}</span>
        </div>
      </div>
      <p class="home-testi-quote">{{ $t['quote'] }}</p>
      <div class="home-testi-user">
        <img class="home-testi-avatar" src="{{ asset('assets/icon/icon-testimoni.png') }}" alt="" loading="lazy">
        <div class="testi-page-user-copy">
          <div class="home-testi-name">{{ $t['name'] }}</div>
          <div class="home-testi-game">Pembeli terverifikasi</div>
        </div>
      </div>
      <div class="testi-page-time">{{ $t['date'] }}</div>
    </article>
    @endforeach
  </div>
</div>

<style>
.testi-page {
  width: 100%;
  max-width: var(--layout-max);
  margin: 0 auto;
  padding: 3rem var(--layout-gutter) 5rem;
}
.testi-page-hero{text-align:center;margin-bottom:1.5rem}
.testi-page-hero h1{font-size:1.5rem;font-weight:700;letter-spacing:-.02em;margin-bottom:.4rem}
.testi-page-hero p{color:var(--text-mute);font-size:.88rem;max-width:600px;margin:0 auto}
.testi-page-filters{display:flex;gap:.5rem;justify-content:center;margin-bottom:1.5rem;flex-wrap:wrap}
.testi-filter-btn{display:inline-flex;padding:.42rem 1rem;border-radius:999px;font-size:.78rem;font-weight:700;border:1px solid var(--border-strong);color:var(--text-dim);text-decoration:none;transition:all .16s ease;background:var(--surface)}
.testi-filter-btn:hover{border-color:var(--purple-light);color:var(--purple-light)}
.testi-filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple);box-shadow:0 0 16px -4px rgba(157,92,245,.3)}
.testi-page-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,280px),1fr));gap:.85rem;}
.testi-page-card{width:auto;min-height:238px;height:100%;padding:1rem;}
.testi-page-card .home-testi-rating{margin-right:0;}
.testi-page-card .home-testi-quote{flex:1;}
.testi-page-user-copy{min-width:0;}
.testi-page-time{margin:0 0 0 3.05rem;padding-top:.55rem;border-top:1px solid color-mix(in srgb,var(--border) 82%,transparent);color:var(--text-mute);font-size:.62rem;line-height:1.3;}

@media (max-width: 640px) {
  .testi-page{padding:2rem var(--layout-gutter) 3rem}
  .testi-page-grid{grid-template-columns:1fr;gap:.7rem}
  .testi-page-card{min-height:220px}
}
</style>

@endsection
