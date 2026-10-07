@extends('layouts.topup')

@section('content')
@php
  $biz = business_info();
  $banner  = \App\Models\SiteSetting::get('site_hero_banner');
  $banner2 = \App\Models\SiteSetting::get('site_hero_banner_2');
  $banner3 = \App\Models\SiteSetting::get('site_hero_banner_3');
  $banners = array_filter([$banner, $banner2, $banner3]);
  $themeBannerUrl = null;
  if (!empty($activeTheme) && $activeTheme->banner_image) {
      $themeBannerUrl = media_url($activeTheme->banner_image);
  }
@endphp

<!-- ===== HERO BANNER ===== -->
<section class="hero-section" id="joki" style="position:relative;overflow:hidden;border-radius:20px;display:flex;align-items:center;justify-content:center;background:var(--bg-soft)">
  @if($themeBannerUrl)
    <div class="hero-banner-track">
      <img src="{{ $themeBannerUrl }}" alt=""
           class="hero-banner-img hero-banner-img-a"
           style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center">
    </div>
  @elseif(count($banners))
    <div class="hero-banner-track">
      <img src="{{ media_url($banners[0]) }}" alt=""
           data-banners='{{ json_encode(array_map(fn($b) => media_url($b), $banners)) }}'
           class="hero-banner-img hero-banner-img-a"
           style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center">
      <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt=""
           class="hero-banner-img hero-banner-img-b"
           style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center">
    </div>
  @else
    <div style="position:absolute;inset:0;background:var(--bg-soft)"></div>
    <div style="position:relative;z-index:1;text-align:center;padding:2rem">
      <h1 style="font-size:2rem;font-weight:800;margin-bottom:.5rem">JOHEN GAMING</h1>
      <p style="color:var(--text-dim);font-size:1rem">Top Up Game Termurah & Terpercaya</p>
      <p style="color:var(--text-mute);font-size:.82rem;margin-top:1rem">Tambahkan banner di Pengaturan → Hero Banner</p>
    </div>
  @endif

  @if(!$themeBannerUrl)
  <button class="hero-arrow hero-arrow-left" data-banner-prev aria-label="Sebelumnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <button class="hero-arrow hero-arrow-right" data-banner-next aria-label="Selanjutnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  @endif
</section>

<!-- ===== FLASH DEAL ===== -->
@if(!empty($flashDeals) && $flashDeals->count())
<section class="flash-section">
  <div class="flash-panel">
    <div class="flash-head">
      <div class="flash-head-left">
        <span class="flash-live-dot"></span>
        <h2 class="flash-title">FLASH DEAL</h2>
        <span class="flash-live-badge">LIVE</span>
      </div>
      <div class="flash-countdown">
        <span class="flash-cd-label">Berakhir dalam</span>
        <div class="flash-cd-boxes">
          <span class="flash-cd-box" id="flashCdH">00</span><i class="flash-cd-sep">:</i>
          <span class="flash-cd-box" id="flashCdM">00</span><i class="flash-cd-sep">:</i>
          <span class="flash-cd-box" id="flashCdS">00</span>
        </div>
      </div>
    </div>
    <div class="flash-strip">
      @foreach($flashDeals as $deal)
        <a href="{{ route('games.show', $deal->product->brand) . '?product=' . $deal->product->id }}"
           class="flash-s-card"
           data-ends-at="{{ $deal->ends_at ? $deal->ends_at->getTimestamp() * 1000 : 0 }}">
          <div class="flash-s-img">
            @if($deal->image_url)
              <img src="{{ $deal->image_url }}" alt="{{ $deal->product->product_name }}" loading="lazy">
            @else
              <span class="flash-s-fallback">⚡</span>
            @endif
            <span class="flash-s-disc">-{{ rtrim(rtrim(number_format($deal->discount_percent, 0), '0'), ',') }}%</span>
          </div>
          <div class="flash-s-body">
            <span class="flash-s-brand">{{ $deal->product->brand }}</span>
            <span class="flash-s-name">{{ $deal->product->product_name }}</span>
            <div class="flash-s-price">
              <span class="flash-s-old">Rp {{ number_format($deal->original_price, 0, ',', '.') }}</span>
              <span class="flash-s-new">Rp {{ number_format($deal->flash_price, 0, ',', '.') }}</span>
            </div>
            <div class="flash-s-foot">
              <span class="flash-s-badge">🔥 FLASH DEAL</span>
              <span class="flash-s-stock">Tersisa : <b>{{ $deal->stock }}</b></span>
            </div>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- ===== CATEGORY TABS ===== -->
<section class="tabs-section">
  <div class="tabs-wrap" id="tabsWrap">
    <button class="tab-pill active" data-filter="all">Top Up Games</button>
    <button class="tab-pill" data-filter="joki" id="jokiTab">Joki Mobile Legends</button>
  </div>
</section>

<!-- ===== SECTION HEADER ===== -->
<section class="section-heading">
  <h2>🔥 PRODUK UNGGULAN</h2>
  <p>Pilihan terbaik dengan transaksi terbanyak dan rating tertinggi dari pengguna.</p>
</section>

<!-- ===== FEATURED GAMES ===== -->
@php
  $featuredFallback = [
    'PUBG Mobile' => ['icon' => '🔫', 'publisher' => 'Tencent', 'desc' => 'Top Up UC PUBG Mobile cepat & instan.'],
    'Mobile Legends' => ['icon' => '🎮', 'publisher' => 'Moonton', 'desc' => 'Top Up diamond Mobile Legends cepat & instan.'],
    'Free Fire' => ['icon' => '🔥', 'publisher' => 'Garena', 'desc' => 'Dapatkan diamond Free Fire harga spesial.'],
    'Roblox' => ['icon' => '🧱', 'publisher' => 'Roblox Corp', 'desc' => 'Beli Robux dengan harga terbaik.'],
    'Valorant' => ['icon' => '🔫', 'publisher' => 'Riot Games', 'desc' => 'Top Up VALORANT Points murah.'],
    'E-Football' => ['icon' => '⚽', 'publisher' => 'Konami', 'desc' => 'Top up eFootball Points dengan harga terbaik.'],
    'FC Mobile' => ['icon' => '⚽', 'publisher' => 'EA Sports', 'desc' => 'Top up FC Mobile Points cepat & murah.'],
  ];
  $featuredBrands = $popularBrands->keyBy('name');
  $minPrices = $minPrices ?? [];
  // Cocokkan nama brand katalog (yang costumam kapital) ke nama brand tabel.
  $minPriceLookup = [];
  foreach ($minPrices as $brandName => $price) {
    $minPriceLookup[strtoupper((string) $brandName)] = (int) $price;
  }
  // Closure (bukan function global) supaya aman ketika view ini dirender
  // lebih dari sekali dalam satu proses PHP.
  $fgData = function ($name, $brands, $fallback) use ($minPriceLookup) {
    $b = $brands->get($name);
    $fb = $fallback[$name] ?? ['icon' => '🎮', 'publisher' => '-', 'desc' => 'Top up murah & instan.'];
    $price = $minPriceLookup[strtoupper((string) $name)] ?? null;
    return (object)[
      'name' => $b->name ?? $name,
      'icon' => $b->icon ?? $fb['icon'],
      'publisher' => $b->category ?? $fb['publisher'],
      'desc' => $fb['desc'],
      'thumb' => $b->featured_thumbnail_url ?? null,
      'bg' => $b ? $b->carousel_bg_url : null,
      'imgs' => $b ? $b->featured_img_urls : [],
      'hasProducts' => $price !== null,
      'price' => $price !== null
            ? 'Rp' . number_format($price, 0, ',', '.')
            : 'Hubungi CS',
    ];
  };
  $featuredList = $popularBrands->map(fn($b) => $fgData($b->name, $featuredBrands, $featuredFallback))->values();
  $pubgIdx = $featuredList->search(fn($c) => $c->name === 'PUBG Mobile');
  if ($pubgIdx !== false) {
    $first = $featuredList->pull($pubgIdx);
    $featuredList = $featuredList->values();
  } else {
    $first = $featuredList->shift();
  }
  $featuredList = $featuredList->take(6)->values();
  $badges = ['🔥 Populer', '🚀 Trending', '⭐ Favorit', '💎 Premium', '🏆 Best', '✨ Top', '🎯 Pilihan'];
  $colorPalette = [
    ['primary' => '#f59e0b', 'secondary' => '#2a1f0a'],
    ['primary' => '#7c3aed', 'secondary' => '#1e0f3a'],
    ['primary' => '#ef4444', 'secondary' => '#2a0f0f'],
    ['primary' => '#10b981', 'secondary' => '#0a1f15'],
    ['primary' => '#ff4655', 'secondary' => '#1a0a0f'],
    ['primary' => '#06b6d4', 'secondary' => '#0a1a2e'],
    ['primary' => '#f97316', 'secondary' => '#1f120a'],
  ];
  $fgBgStyle = function ($game, $color) {
    $img = $game->bg ?? ($game->imgs[0] ?? $game->thumb);
    if ($img) return "background-image:url('{$img}');background-size:cover;background-position:center";
    return "background:linear-gradient(160deg,{$color['primary']},{$color['secondary']})";
  };
@endphp
@if($first)
<section class="featured-grid-section" id="topup">
  <div class="featured-grid-inner">
    <a href="{{ route('games.show', $first->name) }}" class="bento-card bento-featured"
       data-featured-imgs='{{ json_encode($first->imgs) }}'>
      <div class="bento-card-bg" style="{{ $fgBgStyle($first, $colorPalette[0]) }}"></div>
      <div class="bento-overlay"></div>
      <div class="bento-featured-content">
        <span class="bento-badge bento-badge-best">🔥 Best Seller</span>
        <h3 class="bento-featured-title">{{ $first->name }}</h3>
        <p class="bento-featured-sub">{{ $first->desc }}</p>
        <div class="bento-stats">
          @if($first->hasProducts)
          <span class="bento-stat">⚡ Proses &lt; 1 Menit</span>
          <span class="bento-stat">🕒 {{ $biz['cs_hours'] }}</span>
          @else
          <span class="bento-stat">Katalog sedang disiapkan</span>
          @endif
        </div>
        <div class="bento-price">
          @if($first->hasProducts)
          <span class="bento-price-label">Mulai dari</span>
          <span class="bento-price-value">{{ $first->price }}</span>
          @else
          <span class="bento-price-label">Katalog</span>
          <span class="bento-price-value">Hubungi CS</span>
          @endif
        </div>
        <span class="bento-cta">⚡ Top Up Sekarang</span>
      </div>
    </a>

    <div class="bento-grid-right">
      @foreach($featuredList as $i => $card)
      <a href="{{ route('games.show', $card->name) }}" class="bento-card bento-small-card"
         data-featured-imgs='{{ json_encode($card->imgs) }}'>
        <div class="bento-card-bg" style="{{ $fgBgStyle($card, $colorPalette[($i + 1) % count($colorPalette)]) }}"></div>
        <div class="bento-overlay"></div>
        <div class="bento-small-content">
          <span class="bento-small-badge">{{ $badges[$i % count($badges)] }}</span>
          <h4 class="bento-small-title">{{ $card->name }}</h4>
          <div class="bento-small-meta">
            @if($card->hasProducts)
            <span>Mulai {{ $card->price }}</span>
            @else
            <span>Katalog segera hadir</span>
            @endif
          </div>
          <span class="bento-small-cta">Top Up →</span>
        </div>
      </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- ===== SECTION HEADER PRODUK LAINNYA ===== -->
<section class="section-heading">
  <h2>PRODUK LAINNYA</h2>
  <p>Jelajahi game populer lainnya yang tersedia di Johen Gaming.</p>
</section>

<!-- ===== GAMES GRID ===== -->
<section class="games-section" id="games">
  @php
    $gradients = [
      'linear-gradient(160deg,#1e3a5f,#0f1c2e)',
      'linear-gradient(160deg,#3b2465,#1a1030)',
      'linear-gradient(160deg,#5c1f2e,#240d13)',
      'linear-gradient(160deg,#1f4d2e,#0d1f13)',
      'linear-gradient(160deg,#4a1f5c,#1a0d24)',
    ];
  @endphp

  <div class="games-grid" id="gamesGrid">
    @foreach($brands as $i => $brand)
      @php
        $icon = $brand->icon ?? '🎮';
        $bg = $gradients[$i % count($gradients)];
        $hidden = $i >= 10 ? 'style=display:none' : '';
      @endphp
      <a href="{{ route('games.show', $brand->name) }}" class="game-card"
         aria-label="Top up {{ $brand->name }}"
         data-brand="{{ $brand->name }}"
         data-category="{{ $brand->category ?? 'other' }}"
         data-service-type="{{ $brand->service_type ?? 'topup' }}"
         data-icon="{{ $icon }}"
         data-thumbnail="{{ $brand->thumbnail_url ?? '' }}"
         data-index="{{ $i }}"
         {!! $hidden !!}
         style="background:{{ $bg }};animation:cardIn .5s ease forwards;animation-delay:{{ $i * 0.04 }}s;opacity:0;transform:translateY(16px);{{ $i >= 10 ? 'display:none;' : '' }}">
        <div class="game-card-icon">
          @if($brand->thumbnail_url)
            <img src="{{ $brand->thumbnail_url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
          @else
            {{ $icon }}
          @endif
        </div>
        <span class="game-card-search-data">{{ $brand->name }} {{ $brand->category ?? 'other' }}</span>
      </a>
    @endforeach
  </div>

  <div class="load-more-wrap" id="loadMoreWrap">
    <button class="btn btn-outline btn-load-more" id="loadMoreBtn">Tampilkan Lainnya</button>
  </div>
</section>

<style>
@keyframes cardIn{to{opacity:1;transform:translateY(0);}}
@media (max-width: 768px) {
  #joki { height: 250px; border-radius: 16px; }
}
@media (max-width: 480px) {
  #joki { height: 200px; }
}
</style>

@push('scripts')
<script>
/* ===== FLASH DEAL COUNTDOWN ===== */
(function(){
    const cards = Array.from(document.querySelectorAll('.flash-s-card[data-ends-at]'));
    const cdH = document.getElementById('flashCdH');
    const cdM = document.getElementById('flashCdM');
    const cdS = document.getElementById('flashCdS');
    if (!cards.length || !cdH) return;

    const endsAt = Math.min.apply(null, cards.map(c => parseInt(c.dataset.endsAt, 10) || 0));
    if (!endsAt) return;

    const pad = n => String(Math.max(0, Math.floor(n))).padStart(2, '0');

    function tick() {
        let diff = Math.floor((endsAt - Date.now()) / 1000);
        if (diff <= 0) {
            cdH.textContent = '00'; cdM.textContent = '00'; cdS.textContent = '00';
            clearInterval(timer);
            setTimeout(() => location.reload(), 3000);
            return;
        }
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        cdH.textContent = pad(h);
        cdM.textContent = pad(m);
        cdS.textContent = pad(s);
    }

    tick();
    const timer = setInterval(tick, 1000);
})();

let loadMoreIndex = 10;
const step = 5;
const allCards = Array.from(document.querySelectorAll('.game-card'));
const loadMoreBtn = document.getElementById('loadMoreBtn');
const loadMoreWrap = document.getElementById('loadMoreWrap');

function updateLoadMoreBtn() {
  const totalInFilter = allCards.filter(c => {
    const tab = document.querySelector('.tab-btn.active')?.dataset?.tab || 'all';
    if (tab === 'all') return true;
    if (tab === 'joki') return c.dataset.brand === 'Mobile Legends';
    return c.dataset.brand === tab;
  }).length;
  const visibleCount = allCards.filter(c => c.style.display !== 'none' && c.style.display !== 'display:none').length;
  if (visibleCount >= totalInFilter) {
    loadMoreBtn.textContent = 'Sembunyikan Lainnya';
  } else {
    loadMoreBtn.textContent = 'Tampilkan Lainnya';
  }
}

if (loadMoreBtn) {
  loadMoreBtn.addEventListener('click', function() {
    const currentFilter = document.querySelector('.tab-btn.active')?.dataset?.tab || 'all';

    if (loadMoreBtn.textContent === 'Sembunyikan Lainnya') {
      allCards.forEach((card, i) => {
        if (i >= 10) card.style.display = 'none';
      });
      loadMoreIndex = 10;
      loadMoreBtn.textContent = 'Tampilkan Lainnya';
      return;
    }

    let hidden = [];
    allCards.forEach(card => {
      if (currentFilter === 'all' || card.dataset.brand === 'Mobile Legends' && currentFilter === 'joki') {
        if (parseInt(card.dataset.index) >= loadMoreIndex && (card.style.display === 'none' || card.style.display === 'display:none')) {
          hidden.push(card);
        }
      }
    });

    const toShow = hidden.slice(0, step);
    toShow.forEach(card => { card.style.display = ''; });
    loadMoreIndex += toShow.length;

    updateLoadMoreBtn();
  });
}

window.__loadMoreReset = function() {
  allCards.forEach((card, i) => {
    if (i >= 10) {
      card.style.display = 'none';
    } else {
      card.style.display = '';
    }
  });
  loadMoreIndex = 10;
  loadMoreWrap.style.display = '';
  loadMoreBtn.textContent = 'Tampilkan Lainnya';
};
</script>
@endpush

<!-- ===== TESTIMONIALS ===== -->
<section class="testi-section">
  <h2>APA KATA MEREKA?</h2>
  <p class="testi-sub">Ribuan orang telah mempercayai Top Up mereka di Johen Gaming</p>
  <div class="testi-carousel">
    <button class="testi-arrow testi-arrow-left" onclick="prevTesti()" aria-label="Sebelumnya">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <div class="testi-track" id="testiTrack"></div>
    <button class="testi-arrow testi-arrow-right" onclick="nextTesti()" aria-label="Selanjutnya">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
  </div>
  <div class="testi-dots" id="testiDots"></div>
  <div class="load-more-wrap" style="margin-top:1.2rem">
    <a href="{{ route('testimoni') }}" class="btn btn-outline btn-load-more">Lihat Selengkapnya</a>
  </div>
</section>

<!-- ===== PAYMENT METHODS ===== -->
<section class="payment-section">
  <h2>METODE PEMBAYARAN</h2>
  <p>Kami mendukung berbagai metode pembayaran seperti QRIS, e-wallet, virtual account dan minimarket.</p>
  <div class="payment-track-wrap">
    <div class="payment-track" id="paymentTrack"></div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="cta-section">
  <div class="cta-card">
    <span class="cta-glow-2"></span>
    <a href="https://www.johengaming.id" target="_blank" rel="noopener noreferrer" class="cta-logo-link">
      <img src="{{ pwa_asset('logo.png') }}" alt="Johen Gaming" class="cta-logo">
    </a>
    <h2>Kunjungi Website Profile Kami</h2>
    <p>Dapatkan informasi lengkap tentang layanan, promo terbaru, dan update seputar Johen Gaming.</p>
    <a href="https://www.johengaming.id" target="_blank" rel="noopener noreferrer" class="cta-btn">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      Kunjungi johengaming.id
    </a>
  </div>
</section>

@endsection
