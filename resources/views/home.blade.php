@extends('layouts.topup')

@section('content')
@php
  $biz = business_info();
  $banner  = \App\Models\SiteSetting::get('site_hero_banner');
  $banner2 = \App\Models\SiteSetting::get('site_hero_banner_2');
  $banner3 = \App\Models\SiteSetting::get('site_hero_banner_3');
  $banners = array_values(array_filter([$banner, $banner2, $banner3]));
  $themeBannerUrl = null;
  if (!empty($activeTheme) && $activeTheme->banner_image) {
      $themeBannerUrl = media_url($activeTheme->banner_image);
  }
  if ($themeBannerUrl) $banners = [$themeBannerUrl];
@endphp

<!-- ===== HERO BANNER ===== -->
<section class="hero-section hero-peek-carousel" id="joki">
  @if(count($banners))
    <div class="hero-banner-track{{ count($banners) === 1 ? ' is-single' : '' }}"
         data-banners='{{ json_encode(array_map(fn($b) => media_url($b), $banners)) }}'>
      <button type="button" class="hero-banner-card hero-banner-card-prev" data-banner-slot="prev" aria-label="Lihat banner sebelumnya">
        <img src="{{ media_url($banners[count($banners) > 1 ? count($banners) - 1 : 0]) }}" alt="Banner promo sebelumnya">
      </button>
      <button type="button" class="hero-banner-card hero-banner-card-active" data-banner-slot="active" aria-live="polite" tabindex="-1">
        <img src="{{ media_url($banners[0]) }}" alt="Banner promo 1">
      </button>
      <button type="button" class="hero-banner-card hero-banner-card-next" data-banner-slot="next" aria-label="Lihat banner selanjutnya">
        <img src="{{ media_url($banners[1] ?? $banners[0]) }}" alt="Banner promo selanjutnya">
      </button>
    </div>
    @if(count($banners) > 1)<div class="hero-banner-dots" data-banner-dots aria-label="Pilih banner"></div>@endif
  @else
    <div class="hero-banner-empty">
      <h1 style="font-size:2rem;font-weight:800;margin-bottom:.5rem">JOHEN GAMING</h1>
      <p style="color:var(--text-dim);font-size:1rem">Top Up Game Termurah & Terpercaya</p>
      <p style="color:var(--text-mute);font-size:.82rem;margin-top:1rem">Tambahkan banner di Pengaturan → Hero Banner</p>
    </div>
  @endif

  @if(count($banners) > 1)
  <button class="hero-arrow hero-arrow-left" data-banner-prev aria-label="Sebelumnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <button class="hero-arrow hero-arrow-right" data-banner-next aria-label="Selanjutnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  @endif
</section>

<!-- ===== STOCK AKUN TERBARU ===== -->
@if($latestAccountStock->isNotEmpty())
<section class="stock-showcase" id="stock-akun" aria-labelledby="stockShowcaseTitle" data-stock-showcase>
  <div class="stock-showcase-heading">
    <div>
      <span class="stock-showcase-eyebrow">JUAL BELI AKUN</span>
      <h2 id="stockShowcaseTitle">STOCK AKUN TERBARU</h2>
    </div>
    <a class="stock-showcase-all" href="{{ route('jual-beli-akun') }}">
      Lihat semua akun <span aria-hidden="true">&rarr;</span>
    </a>
  </div>

  <div class="stock-showcase-frame">
    @foreach($latestAccountStock as $categoryIndex => $category)
      <div class="stock-showcase-slide{{ $categoryIndex === 0 ? ' is-active' : '' }}"
           data-stock-slide data-stock-slug="{{ $category['slug'] }}" data-stock-game="{{ $category['game'] }}"
           data-stock-href="{{ route('jual-beli-akun.game', $category['slug']) }}"
           @if($categoryIndex !== 0) aria-hidden="true" inert @endif>
        <div class="stock-showcase-art" aria-hidden="true">
          <span class="stock-showcase-halo"></span>
          <img class="stock-showcase-art-base" src="{{ pwa_asset($category['artwork']) }}" alt="" loading="{{ $categoryIndex === 0 ? 'eager' : 'lazy' }}">
          <img class="stock-showcase-art-motion" src="{{ pwa_asset($category['artwork']) }}" alt="" aria-hidden="true" loading="{{ $categoryIndex === 0 ? 'eager' : 'lazy' }}">
        </div>

        <div class="stock-showcase-window">
          <div class="stock-showcase-track" style="--stock-marquee-duration:{{ max(30, $category['listings']->count() * 7) }}s">
            @foreach([false, true] as $duplicate)
              <div class="stock-showcase-group" @if($duplicate) aria-hidden="true" @endif>
                @foreach($category['listings'] as $listing)
                  <a class="stock-showcase-card" href="{{ route('jual-beli-akun.detail', $listing) }}"
                     @if($duplicate) tabindex="-1" @endif
                     aria-label="{{ $listing->product_name }} - Rp {{ number_format((float) $listing->price, 0, ',', '.') }}">
                    <span class="stock-showcase-card-image">
                      @if($listing->photo_url)
                        <img src="{{ $listing->photo_url }}" alt="" loading="lazy">
                      @else
                        <span class="stock-showcase-card-fallback">{{ $category['game'] }}</span>
                      @endif
                    </span>
                    <span class="stock-showcase-card-body">
                      <span class="stock-showcase-card-title">{{ $listing->product_name }}</span>
                      <strong>Rp {{ number_format((float) $listing->price, 0, ',', '.') }}</strong>
                    </span>
                  </a>
                @endforeach
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach

    <div class="stock-showcase-controls">
      <button type="button" data-stock-prev aria-label="Kategori game sebelumnya">
        <span aria-hidden="true">&#8249;</span>
      </button>
      <a data-stock-category href="{{ route('jual-beli-akun.game', $latestAccountStock->first()['slug']) }}"
         aria-live="polite">{{ $latestAccountStock->first()['game'] }}</a>
      <button type="button" data-stock-next aria-label="Kategori game selanjutnya">
        <span aria-hidden="true">&#8250;</span>
      </button>
    </div>
  </div>
</section>
@endif

<!-- ===== TOP UP GAME POPULER ===== -->
@if(isset($popularTopupGames) && $popularTopupGames->isNotEmpty())
<section class="popular-topup-section" aria-labelledby="popularTopupTitle">
  <div class="popular-topup-heading">
    <div>
      <span class="popular-topup-eyebrow">PILIHAN FAVORIT</span>
      <h2 id="popularTopupTitle">TOP UP GAME POPULER</h2>
    </div>
  </div>
  <div class="popular-topup-grid">
    @foreach($popularTopupGames as $popularGame)
      <a class="popular-topup-card" href="{{ route('games.show', $popularGame) }}" aria-label="Top up {{ $popularGame->name }}">
        <img class="popular-topup-art" src="{{ $popularGame->topup_popular_image_url }}" alt="" loading="lazy">
        <span class="popular-topup-gradient" aria-hidden="true"></span>
        @if($popularGame->topup_popular_logo_url)
          <img class="popular-topup-logo" src="{{ $popularGame->topup_popular_logo_url }}" alt="Logo {{ $popularGame->name }}" loading="lazy">
        @endif
        <span class="popular-topup-copy">
          <span class="popular-topup-name">{{ $popularGame->name }}</span>
          <span class="popular-topup-caption">Top up tersedia</span>
        </span>
        <span class="popular-topup-arrow" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
      </a>
    @endforeach
  </div>
</section>
@endif

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
      @endphp
      <a href="{{ route('games.show', $brand->name) }}" class="game-card{{ $brand->topup_character_image_url ? ' game-card--character' : '' }}"
         aria-label="Top up {{ $brand->name }}"
         data-brand="{{ $brand->name }}"
         data-category="{{ $brand->category ?? 'other' }}"
         data-service-type="{{ $brand->service_type ?? 'topup' }}"
         data-icon="{{ $icon }}"
         data-thumbnail="{{ $brand->thumbnail_url ?? '' }}"
         data-index="{{ $i }}"
         style="background:{{ $bg }};animation:cardIn .5s ease forwards;animation-delay:{{ $i * 0.04 }}s;opacity:0;{{ $i >= 12 ? 'display:none;' : '' }}">
        @if($brand->topup_character_image_url)
          @if($brand->thumbnail_url)
            <img class="game-card-backdrop" src="{{ $brand->thumbnail_url }}" alt="" aria-hidden="true"
                 loading="lazy" decoding="async" fetchpriority="low" width="640" height="640">
          @endif
          <span class="game-card-character-glow" aria-hidden="true"></span>
          <img class="game-card-character" src="{{ $brand->topup_character_image_url }}" alt="" aria-hidden="true"
               loading="lazy" decoding="async" fetchpriority="low" width="1200" height="1600">
        @else
          <div class="game-card-icon">
            @if($brand->thumbnail_url)
              <img src="{{ $brand->thumbnail_url }}" alt="" loading="lazy" decoding="async" fetchpriority="low"
                   width="640" height="640" style="width:100%;height:100%;object-fit:cover;">
            @else
              {{ $icon }}
            @endif
          </div>
        @endif
        <span class="game-card-search-data">{{ $brand->name }} {{ $brand->category ?? 'other' }}</span>
      </a>
    @endforeach
  </div>

  <div class="load-more-wrap" id="loadMoreWrap">
    <button class="btn btn-outline btn-load-more" id="loadMoreBtn">Tampilkan Lainnya</button>
  </div>
</section>

<style>
@keyframes cardIn{from{opacity:0;translate:0 16px;}to{opacity:1;translate:0 0;}}
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

const largeGamesMedia = window.matchMedia('(min-width:1600px)');
const initialGameCount = () => largeGamesMedia.matches ? 12 : 10;
const loadMoreStep = () => largeGamesMedia.matches ? 6 : 5;
let loadMoreIndex = initialGameCount();
const allCards = Array.from(document.querySelectorAll('.game-card'));
const loadMoreBtn = document.getElementById('loadMoreBtn');
const loadMoreWrap = document.getElementById('loadMoreWrap');

/* ===== POINTER-TRACKED 3D GAME CARDS ===== */
(function initGameCardTilt() {
  const cards = document.querySelectorAll('.game-card--character');
  const canTilt = window.matchMedia('(hover:hover) and (pointer:fine)');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion:reduce)');

  cards.forEach(card => {
    let frameId = null;
    let pointerEvent = null;

    const resetCard = () => {
      if (frameId) cancelAnimationFrame(frameId);
      frameId = null;
      pointerEvent = null;
      card.classList.remove('is-tilting');
      card.style.setProperty('--card-rotate-x', '0deg');
      card.style.setProperty('--card-rotate-y', '0deg');
      card.style.setProperty('--card-character-x', '0px');
      card.style.setProperty('--card-character-y', '0px');
      card.style.setProperty('--card-bg-x', '0px');
      card.style.setProperty('--card-bg-y', '0px');
      card.style.setProperty('--card-glare-x', '50%');
      card.style.setProperty('--card-glare-y', '50%');
      card.style.setProperty('--card-shadow-x', '0px');
      card.style.setProperty('--card-shadow-y', '22px');
    };

    const renderTilt = () => {
      frameId = null;
      if (!pointerEvent) return;

      const rect = card.getBoundingClientRect();
      const xRatio = Math.max(0, Math.min(1, (pointerEvent.clientX - rect.left) / rect.width));
      const yRatio = Math.max(0, Math.min(1, (pointerEvent.clientY - rect.top) / rect.height));
      const x = (xRatio - .5) * 2;
      const y = (yRatio - .5) * 2;

      card.style.setProperty('--card-rotate-x', `${(-y * 9).toFixed(2)}deg`);
      card.style.setProperty('--card-rotate-y', `${(x * 12).toFixed(2)}deg`);
      card.style.setProperty('--card-character-x', `${(x * 11).toFixed(2)}px`);
      card.style.setProperty('--card-character-y', `${(y * 7).toFixed(2)}px`);
      card.style.setProperty('--card-bg-x', `${(-x * 15).toFixed(2)}px`);
      card.style.setProperty('--card-bg-y', `${(-y * 11).toFixed(2)}px`);
      card.style.setProperty('--card-glare-x', `${(xRatio * 100).toFixed(1)}%`);
      card.style.setProperty('--card-glare-y', `${(yRatio * 100).toFixed(1)}%`);
      card.style.setProperty('--card-shadow-x', `${(-x * 12).toFixed(2)}px`);
      card.style.setProperty('--card-shadow-y', `${(22 + y * 7).toFixed(2)}px`);
    };

    card.addEventListener('pointerenter', () => {
      if (canTilt.matches && !reduceMotion.matches) card.classList.add('is-tilting');
    });

    card.addEventListener('pointermove', event => {
      if (!canTilt.matches || reduceMotion.matches) return;
      pointerEvent = event;
      if (!frameId) frameId = requestAnimationFrame(renderTilt);
    });

    card.addEventListener('pointerleave', resetCard);
    card.addEventListener('blur', resetCard);
  });
})();

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
      const initialCount = initialGameCount();
      allCards.forEach((card, i) => {
        if (i >= initialCount) card.style.display = 'none';
      });
      loadMoreIndex = initialCount;
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

    const toShow = hidden.slice(0, loadMoreStep());
    toShow.forEach(card => { card.style.display = ''; });
    loadMoreIndex += toShow.length;

    updateLoadMoreBtn();
  });
}

window.__loadMoreReset = function() {
  const initialCount = initialGameCount();
  allCards.forEach((card, i) => {
    if (i >= initialCount) {
      card.style.display = 'none';
    } else {
      card.style.display = '';
    }
  });
  loadMoreIndex = initialCount;
  loadMoreWrap.style.display = '';
  loadMoreBtn.textContent = 'Tampilkan Lainnya';
};

largeGamesMedia.addEventListener?.('change', () => window.__loadMoreReset());
window.__loadMoreReset();
</script>
@endpush

<!-- ===== TESTIMONIALS ===== -->
<section class="testi-section home-testi-section">
  <h2>APA KATA MEREKA?</h2>
  <p class="testi-sub">Ribuan orang telah mempercayai Top Up mereka di Johen Gaming</p>
  <div class="home-testi-wall" aria-label="Testimoni pelanggan Johen Gaming">
    <div class="home-testi-lane-wrap">
      <div class="home-testi-lane" id="testiTrack"></div>
    </div>
  </div>
  <div class="load-more-wrap" style="margin-top:1.2rem">
    <a href="{{ route('testimoni') }}" class="btn btn-outline btn-load-more">Lihat Selengkapnya</a>
  </div>
</section>

@endsection
