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
    <a href="{{ route('testimoni', ['layanan' => 'jual-beli-akun']) }}" class="testi-filter-btn {{ $activeLayanan === 'jual-beli-akun' ? 'active' : '' }}">Stock Akun</a>
    <a href="{{ route('testimoni', ['layanan' => 'joki']) }}" class="testi-filter-btn {{ $activeLayanan === 'joki' ? 'active' : '' }}">Joki MLBB</a>
  </div>

  <div class="testi-page-grid">
    @foreach($testimonials as $t)
    @php
      $testiGame = preg_replace('/^(Top Up|Joki Rank|Jual Akun|Stock Akun)\s*-\s*/i', '', $t['game']);
    @endphp
    <article class="home-testi-card testi-page-card" data-game="{{ $testiGame ?: $t['game'] }}">
      <div class="home-testi-brand"><span>{{ $testiGame ?: $t['game'] }}</span></div>
      <div class="home-testi-avatar-wrap">
        <x-user-avatar :name="$t['name']" :src="$t['avatar_url'] ?? null" :size="74" class="home-testi-avatar" />
        <span class="home-testi-verified" aria-label="Pembeli terverifikasi">&#10003;</span>
      </div>
      <div class="home-testi-name">{{ $t['name'] }}</div>
      <div class="home-testi-game">Pembeli terverifikasi</div>
      <p class="home-testi-quote">{{ $t['quote'] }}</p>
      <div class="home-testi-footer">
        <div class="home-testi-rating" aria-label="Rating 5 dari 5">
          <span aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
        </div>
        <span class="home-testi-profile">Terverifikasi &#10003;</span>
      </div>
      <div class="testi-page-time">{{ $t['date'] }}</div>
    </article>
    @endforeach
  </div>
</div>

<style>
.testi-page {
  width: 100%;
  max-width: 1520px;
  margin: 0 auto;
  padding: 3.5rem var(--layout-gutter) 5rem;
}
.testi-page-hero{text-align:center;margin-bottom:1.75rem}
.testi-page-hero h1{margin-bottom:.5rem;color:#fff;font-size:clamp(1.55rem,2.2vw,2rem);font-weight:800;letter-spacing:-.035em}
.testi-page-hero p{max-width:620px;margin:0 auto;color:#c5d8ff;font-size:.9rem;line-height:1.6}
.testi-page-filters{display:flex;gap:.6rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap}
.testi-filter-btn{display:inline-flex;padding:.42rem 1rem;border-radius:999px;font-size:.78rem;font-weight:700;border:1px solid var(--border-strong);color:var(--text-dim);text-decoration:none;transition:all .16s ease;background:var(--surface)}
.testi-filter-btn:hover{border-color:var(--purple-light);color:var(--purple-light)}
.testi-filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple);box-shadow:0 0 16px -4px rgba(157,92,245,.3)}
.testi-page-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1rem;align-items:stretch;}
.testi-page-grid>.home-testi-card.testi-page-card{width:100%;max-width:none;height:310px;min-height:310px;}
.testi-page-card .home-testi-rating{margin-right:0;}
.testi-page-card .home-testi-quote{flex:1;}
.testi-page-user-copy{min-width:0;}
.testi-page-time{position:relative;z-index:1;margin-top:.45rem;color:#c5d8ff;font-size:.58rem;line-height:1.3;text-align:right;}

@media (max-width: 1400px) {
  .testi-page-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
}
@media (max-width: 1080px) {
  .testi-page-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media (max-width: 760px) {
  .testi-page{padding:2.5rem max(.85rem,var(--layout-gutter)) 3.5rem}
  .testi-page-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem}
  .testi-page-grid>.home-testi-card.testi-page-card{min-width:0;height:270px;min-height:270px;padding:.65rem}
  .testi-page-time{margin-left:0;padding-top:.4rem}
}
@media (max-width: 520px) {
  .testi-page{padding-right:.85rem;padding-left:.85rem}
  .testi-page-filters{gap:.4rem;margin-bottom:1.4rem}
  .testi-filter-btn{padding:.4rem .72rem;font-size:.7rem}
  .testi-page-grid{grid-template-columns:1fr;gap:.75rem}
  .testi-page-grid>.home-testi-card.testi-page-card{height:285px;min-height:285px}
}
</style>

@endsection
