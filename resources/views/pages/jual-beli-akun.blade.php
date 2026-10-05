@extends('layouts.topup')

@section('title', 'Jual Beli Akun - ' . config('app.name'))

@section('content')
@php
  $jbaBanner  = \App\Models\SiteSetting::get('jba_hero_banner');
  $jbaBanner2 = \App\Models\SiteSetting::get('jba_hero_banner_2');
  $jbaBanner3 = \App\Models\SiteSetting::get('jba_hero_banner_3');
  $jbaBanners = array_filter([$jbaBanner, $jbaBanner2, $jbaBanner3]);
@endphp

<div class="jba-page">
  <section class="hero-section" id="jba-hero" style="position:relative;overflow:hidden;border-radius:20px;display:flex;align-items:center;justify-content:center;background:var(--bg-soft)">
    @if(count($jbaBanners))
      <div class="hero-banner-track">
        <img src="{{ media_url($jbaBanners[0]) }}" alt=""
             data-banners='{{ json_encode(array_map(fn($b) => media_url($b), $jbaBanners)) }}'
             class="hero-banner-img hero-banner-img-a"
             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center">
        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt=""
             class="hero-banner-img hero-banner-img-b"
             style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center">
      </div>
    @else
      <div style="position:absolute;inset:0;background:var(--bg-soft)"></div>
      <div style="position:relative;z-index:1;text-align:center;padding:2rem">
        <p style="color:var(--text-mute);font-size:.82rem">Tambahkan banner di Pengaturan → Hero Banner (Jual Beli Akun)</p>
      </div>
    @endif

    <button class="hero-arrow hero-arrow-left" data-banner-prev aria-label="Sebelumnya">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <button class="hero-arrow hero-arrow-right" data-banner-next aria-label="Selanjutnya">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
  </section>

  @if(!$activeSlug)
  <div class="jba-transfer-info">
    <div class="jba-transfer-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 9v6"/><path d="M9 12h6"/></svg>
    </div>
    <div class="jba-transfer-text">
      <strong>Informasi Transfer</strong>
      <span>Pembayaran hanya melalui transfer ke rekening <strong>BRI 114101707070565 a/n JOSIA HENDRICO SIMANUNGKALIT</strong>. Kami tidak pernah meminta transfer ke rekening lain.</span>
    </div>
  </div>
@endif

  @if($flashSaleBanners->isNotEmpty())
    <div class="jba-flash-sale">
      <div class="jba-flash-sale-banners">
        @foreach($flashSaleBanners as $banner)
          <div class="jba-flash-sale-banner">
            @if($banner->link)
              <a href="{{ $banner->link }}" target="_blank" rel="noopener noreferrer" class="jba-flash-sale-banner-link">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? 'Flash Sale' }}">
              </a>
            @else
              <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? 'Flash Sale' }}">
            @endif
            @if($banner->ends_at)
              <div class="jba-countdown" data-ends-at="{{ $banner->ends_at->getTimestamp() }}">
                <span class="jba-countdown-label">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg>
                  Berakhir Dalam
                </span>
                <span class="jba-countdown-boxes">
                  <span class="jba-cd-box"><b data-cd="d">00</b><em>Hari</em></span>
                  <span class="jba-cd-box"><b data-cd="h">00</b><em>Jam</em></span>
                  <span class="jba-cd-box"><b data-cd="m">00</b><em>Menit</em></span>
                  <span class="jba-cd-box"><b data-cd="s">00</b><em>Detik</em></span>
                </span>
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  @endif

<script>
(function(){
  document.querySelectorAll('[data-ends-at]').forEach(function(panel) {
    const endMs = Number(panel.dataset.endsAt) * 1000;
    const els = {
      d: panel.querySelector('[data-cd="d"]'),
      h: panel.querySelector('[data-cd="h"]'),
      m: panel.querySelector('[data-cd="m"]'),
      s: panel.querySelector('[data-cd="s"]'),
    };
    if (!els.d) return;
    function tick() {
      let diff = Math.max(0, endMs - Date.now());
      const d = Math.floor(diff / 86400000); diff -= d * 86400000;
      const h = Math.floor(diff / 3600000);  diff -= h * 3600000;
      const m = Math.floor(diff / 60000);    diff -= m * 60000;
      const s = Math.floor(diff / 1000);
      els.d.textContent = String(d).padStart(2, '0');
      els.h.textContent = String(h).padStart(2, '0');
      els.m.textContent = String(m).padStart(2, '0');
      els.s.textContent = String(s).padStart(2, '0');
    }
    tick();
    setInterval(tick, 1000);
  });
})();
</script>

  <div class="jba-hero">
    <h1>Jual Beli Akun Game</h1>
    <p>Temukan akun game terbaik dengan harga terbaik. Semua akun sudah diverifikasi.</p>
  </div>

  <div class="games-grid" id="jba-game-grid">
    @foreach($popularGames as $i => $brand)
      @php
        $count = $listings->get($brand->name)?->count() ?? 0;
        $slug  = $gameSlugs[$brand->name] ?? null;
      @endphp
      <a class="jba-game-btn" href="{{ $slug ? '/jual-beli-akun/' . $slug : '#' }}"
         data-game="{{ $brand->name }}" @if($slug)data-slug="{{ $slug }}"@endif>
        <div class="jba-game-btn-icon">
          @if($brand->thumbnail_url)
            <img src="{{ $brand->thumbnail_url }}" alt="{{ $brand->name }}">
          @else
            <span style="font-size:2.4rem">🎮</span>
          @endif
        </div>
        <div class="jba-game-btn-overlay"></div>
        <div class="jba-game-btn-info">
          <div class="jba-game-btn-name">{{ $brand->name }}</div>
          <div class="jba-game-btn-cat">{{ $count }} akun tersedia</div>
        </div>
      </a>
    @endforeach
  </div>

  <div id="jba-game-section" class="jba-game-section" style="display:none">
    <div class="jba-game-hero" id="jbaGameHero">
      <img class="jba-game-hero-bg" id="jbaGameHeroBg" alt=""
           src="{{ $activeSlug && !empty($gameBanners[$activeSlug]) ? $gameBanners[$activeSlug] : '' }}"
           style="{{ $activeSlug && !empty($gameBanners[$activeSlug]) ? '' : 'display:none' }}">
      <div class="jba-game-hero-overlay"></div>
      <div class="jba-game-hero-content">
        <div class="jba-game-hero-head">
          <button class="jba-back-btn" id="jbaBackBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Kembali
          </button>
        </div>
        <div class="jba-game-hero-body">
          <div class="jba-game-hero-text">
            <h2 class="jba-game-title" id="jbaGameTitle">{{ $activeGame }}</h2>
            <p class="jba-game-desc" id="jbaGameDesc"></p>
            <div class="jba-game-stats">
              <span class="jba-stat"><b id="jbaStatRating">{{ $jbaRating }}</b>/5</span>
              <span class="jba-stat-dot"></span>
              <span class="jba-stat"><b id="jbaStatCount">{{ count($listings[$activeGame] ?? []) }}</b> akun tersedia</span>
              <span class="jba-stat-dot"></span>
              <span class="jba-stat">Garansi 100%</span>
            </div>
          </div>
          <button type="button" class="jba-testi-link" id="jbaTestiBtn">
            Lihat Testimoni
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>
    </div>

<div class="jba-filter-bar" id="jbaFilterBar" style="display:none">
  <div class="jba-deal-tabs" id="jbaDealTabs">
    <button type="button" class="jba-deal-tab jba-is-active" data-deal="normal">Normal Deal</button>
    <button type="button" class="jba-deal-tab" data-deal="bundle">Bundle Deal</button>
  </div>

  <div class="jba-filter-search">
    <svg class="jba-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
    <input type="text" id="jbaFilterSearch" placeholder="Cari nama akun / owner..." autocomplete="off">
  </div>

  <div class="jba-filter-group" data-filter="price">
    <button class="jba-filter-trigger" id="jbaPriceTrigger" type="button">
      <span class="jba-filter-trigger-label">Harga</span>
      <svg class="jba-filter-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="jba-filter-panel" id="jbaPricePanel">
      <div class="jba-filter-header">Urutkan / Filter Harga</div>
      <div class="jba-filter-field">
        <label>Min (Rp)</label>
        <input type="number" id="jbaPriceMin" min="0" step="1" placeholder="Min">
      </div>
      <div class="jba-filter-field">
        <label>Max (Rp)</label>
        <input type="number" id="jbaPriceMax" min="0" step="1" placeholder="Max">
      </div>
      <div class="jba-filter-actions">
        <button type="button" class="jba-filter-btn jba-filter-btn--apply" id="jbaPriceApply">Terapkan</button>
        <button type="button" class="jba-filter-btn jba-filter-btn--reset" id="jbaPriceReset">Reset</button>
      </div>
    </div>
  </div>

  <div class="jba-filter-group" data-filter="collector">
    <button class="jba-filter-trigger" id="jbaCollectorTrigger" type="button">
      <span class="jba-filter-trigger-label">Kolektor</span>
      <svg class="jba-filter-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="jba-filter-panel" id="jbaCollectorPanel">
      <button type="button" class="jba-filter-option jba-is-selected" data-tier="">Semua Kolektor</button>
      <button type="button" class="jba-filter-option" data-tier="ternama">Kolektor Ternama</button>
      <button type="button" class="jba-filter-option" data-tier="terhormat">Kolektor Terhormat</button>
      <button type="button" class="jba-filter-option" data-tier="juragan">Kolektor Juragan</button>
      <button type="button" class="jba-filter-option" data-tier="sultan">Kolektor Sultan</button>
    </div>
  </div>

  <div class="jba-budget-grid" id="jbaBudgetGrid">
    @foreach($budgetBanners as $budget)
      <button type="button" class="jba-budget-card" data-budget="{{ $budget['id'] }}"
              data-min="{{ $budget['min'] }}" data-max="{{ $budget['max'] }}"
              title="{{ $budget['label'] }} ({{ $budget['sub'] }})" aria-label="{{ $budget['label'] }}">
        <span class="jba-budget-img">
          @if($budget['image_url'])
            <img src="{{ $budget['image_url'] }}" alt="{{ $budget['label'] }}">
          @else
            <span class="jba-budget-img-fallback"></span>
          @endif
        </span>
      </button>
    @endforeach
  </div>
</div>

<div class="jba-results-head">
  <div class="jba-results-title">
    <span>Daftar Akun</span>
  </div>
  <span class="jba-results-count" id="jbaResultCount">{{ $serverTotal }} akun</span>
</div>

{{-- Fallback server-rendered: tetap terlihat walau JavaScript tidak jalan,
     dan memberi mesin pencari konten produk + harga. JS menimpanya saat aktif. --}}
<div class="jba-grid" id="jbaGrid">
  @forelse($serverListings as $l)
    @php
      $isSoldServer = (bool) $l->is_sold;
      $priceServer = 'Rp ' . number_format((float) $l->price, 0, ',', '.');
      $origServer = $l->original_price ? 'Rp ' . number_format((float) $l->original_price, 0, ',', '.') : null;
    @endphp
    <a href="{{ $isSoldServer ? '#' : route('jual-beli-akun.detail', $l) }}"
       class="jba-card{{ $isSoldServer ? ' jba-card--sold' : '' }}"
       @if($isSoldServer) tabindex="-1" aria-disabled="true" @endif>
      <div class="jba-card-img">
        @if($l->photo_url)
          <img src="{{ $l->photo_url }}" alt="{{ $l->product_name }}" class="{{ $isSoldServer ? 'jba-img-sold' : '' }}" loading="lazy">
        @else
          <div class="jba-card-img-fallback">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          </div>
        @endif
        @if($isSoldServer)
          <div class="jba-sold-badge">SOLD</div>
        @endif
      </div>
      <div class="jba-card-body">
        <div class="jba-card-game">{{ $l->game }}</div>
        <h3 class="jba-card-title">{{ $l->product_name }}</h3>
        @if($l->owner_name)
          <div class="jba-card-owner">{{ $l->owner_name }}</div>
        @endif
        <div class="jba-card-prices">
          @if($origServer)
            <span class="jba-card-original">{{ $origServer }}</span>
          @endif
          <div class="jba-card-price-row">
            <span class="jba-card-price">{{ $priceServer }}</span>
          </div>
        </div>
      </div>
    </a>
  @empty
    <div class="jba-empty">
      <h3>Belum Ada Akun</h3>
      <p>Daftar akun untuk game ini sedang diperbarui. Silakan cek kembali beberapa saat lagi.</p>
    </div>
  @endforelse
</div>
  </div>
</div>

<style>
:root {
  --jba-accent: #9d5cf5;
  --jba-accent-glow: rgba(157, 92, 245, .35);
}
.jba-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 1.5rem;
}
.jba-page .games-grid {
  align-items: start;
}
.jba-transfer-info {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .85rem 1rem;
  margin: 1.6rem 0 1.75rem;
  border-radius: 12px;
  background: linear-gradient(135deg, #9d5cf5, #7c3aed);
  border: 1px solid rgba(255, 255, 255, .18);
  animation: jbaGlowPulse 2s ease-in-out infinite;
}
@keyframes jbaGlowPulse {
  0%, 100% { border-color: rgba(255, 255, 255, .18); box-shadow: 0 0 6px rgba(157, 92, 245, .25); }
  50% { border-color: rgba(255, 255, 255, .55); box-shadow: 0 0 16px rgba(157, 92, 245, .5); }
}
.jba-transfer-icon {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, .2);
  color: #fff;
}
.jba-transfer-text {
  font-size: .8rem;
  color: rgba(255, 255, 255, .92);
  line-height: 1.5;
}
.jba-transfer-text strong {
  color: #fff;
}
.jba-orders-btn {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .55rem 1.15rem;
  border-radius: 10px;
  background: var(--jba-accent);
  color: #fff;
  font-size: .8rem;
  font-weight: 700;
  text-decoration: none;
  transition: all .2s;
  box-shadow: 0 4px 14px -4px var(--jba-accent-glow);
}
.jba-orders-btn:hover {
  transform: translateY(-2px);
  filter: brightness(1.1);
}
.jba-hero {
  text-align: left;
  margin: 1.2rem 0 1.5rem;
}
.jba-hero h1 {
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: .15rem;
  color: var(--text);
}
.jba-hero p {
  color: var(--text-dim);
  font-size: .82rem;
  margin: 0;
}

.jba-flash-sale {
  margin: 1.75rem 0 .2rem;
}
.jba-flash-sale-banners {
  display: flex;
  flex-direction: column;
  gap: .9rem;
}
.jba-flash-sale-banner {
  position: relative;
  display: block;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--border);
  background: var(--surface-2);
  transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  line-height: 0;
}
.jba-flash-sale-banner:hover {
  transform: translateY(-3px);
  box-shadow: 0 18px 40px -14px rgba(0,0,0,.6);
  border-color: var(--gold);
}
.jba-flash-sale-banner-link {
  display: block;
}
.jba-flash-sale-banner img {
  width: 100%;
  height: auto;
  display: block;
}
.jba-countdown {
  position: absolute;
  top: 45%;
  right: 1.6rem;
  transform: translateY(-50%);
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .45rem;
  padding: .95rem 1.7rem;
  border-radius: 16px;
  background: rgba(8, 10, 24, .76);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, .14);
  color: #fff;
  text-align: center;
  line-height: normal;
  box-shadow: 0 14px 34px -12px rgba(0,0,0,.6);
  animation: jbaCdIn .5s ease both;
}
@keyframes jbaCdIn {
  from { opacity: 0; transform: translate(10px, -50%); }
  to { opacity: 1; transform: translate(0, -50%); }
}
.jba-countdown-label {
  display: flex;
  align-items: center;
  gap: .35rem;
  font-size: .72rem;
  font-weight: 800;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: #fbbf24;
  white-space: nowrap;
}
.jba-countdown-boxes {
  display: flex;
  gap: .55rem;
}
.jba-cd-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .16rem;
  min-width: 70px;
  padding: .45rem .35rem .38rem;
  border-radius: 10px;
  background: rgba(255, 255, 255, .07);
  border: 1px solid rgba(255, 255, 255, .12);
}
.jba-cd-box b {
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  color: #fff;
}
.jba-cd-box em {
  font-style: normal;
  font-size: .56rem;
  color: rgba(255, 255, 255, .72);
  text-transform: uppercase;
  letter-spacing: .05em;
}
@media (max-width: 640px) {
  .jba-countdown {
    top: 40%;
    right: .8rem;
    padding: .7rem 1.15rem;
    gap: .4rem;
  }
  .jba-countdown-label { font-size: .55rem; letter-spacing: .12em; }
  .jba-cd-box { min-width: 46px; padding: .32rem .24rem .3rem; }
  .jba-cd-box b { font-size: 1.05rem; }
  .jba-cd-box em { font-size: .5rem; }
}

.jba-game-btn {
  border-radius: var(--radius-md);
  overflow: hidden;
  position: relative;
  cursor: pointer;
  transition: transform .25s ease, box-shadow .25s ease;
  display: block;
  aspect-ratio: 3 / 4;
  width: 100%;
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: inherit;
  text-decoration: none;
}
.jba-game-btn:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 20px 40px -14px rgba(0,0,0,.65);
  border-color: var(--purple-light);
}
.jba-game-btn-icon {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}
.jba-game-btn-icon img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
  transition: transform .3s ease;
}
.jba-game-btn:hover .jba-game-btn-icon img {
  transform: scale(1.08);
}
.jba-game-btn-icon span {
  display: flex; align-items: center; justify-content: center;
  font-size: 3.6rem;
  min-height: 170px;
}
.jba-game-btn-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(180deg, transparent 35%, rgba(0,0,0,.85) 80%);
  pointer-events: none;
}
.jba-game-btn-info {
  position: absolute; bottom: 0; left: 0;
  z-index: 2;
  padding: .8rem;
  width: 100%;
}
.jba-game-btn-name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: .88rem;
  line-height: 1.15;
  color: #fff;
}
.jba-game-btn-cat {
  font-size: .66rem;
  color: rgba(255,255,255,.7);
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-top: .15rem;
}

/* Game section */
.jba-game-section {
  animation: jbaFadeIn .25s ease;
}
@keyframes jbaFadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Header banner game */
.jba-game-hero {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  margin-bottom: 1rem;
  min-height: 230px;
  background: linear-gradient(135deg, #4c1d95, #7c3aed 55%, #1e1b4b);
  display: flex;
  align-items: stretch;
}
.jba-game-hero-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.jba-game-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(100deg, rgba(0,0,0,.72) 0%, rgba(0,0,0,.5) 40%, rgba(0,0,0,.28) 100%);
}
.jba-game-hero-content {
  position: relative;
  z-index: 1;
  width: 100%;
  padding: 1rem 1.25rem 1.25rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.jba-game-hero-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.jba-back-btn {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .4rem .85rem;
  border-radius: 999px;
  font-size: .82rem;
  font-weight: 600;
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.35);
  color: #fff;
  cursor: pointer;
  transition: all .18s ease;
  margin: 0;
  font-family: inherit;
  backdrop-filter: blur(6px);
}
.jba-back-btn:hover {
  background: rgba(255,255,255,.28);
  border-color: rgba(255,255,255,.6);
  color: #fff;
}
.jba-game-hero-body {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.jba-game-hero-text {
  min-width: 0;
  max-width: 620px;
}
.jba-game-title {
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  padding: 0;
  border: none;
  line-height: 1.1;
  font-family: var(--font-display);
}
.jba-game-desc {
  font-size: .88rem;
  color: rgba(255,255,255,.82);
  margin-top: .45rem;
  line-height: 1.55;
  max-width: 560px;
}
.jba-game-stats {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-top: .75rem;
  flex-wrap: wrap;
}
.jba-stat {
  display: inline-flex;
  align-items: center;
  gap: .2rem;
  font-size: .78rem;
  font-weight: 600;
  color: rgba(255,255,255,.9);
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.22);
  padding: .3rem .65rem;
  border-radius: 999px;
  backdrop-filter: blur(6px);
}
.jba-stat b {
  color: #fff;
  font-weight: 800;
}
.jba-stat-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: rgba(255,255,255,.45);
}
.jba-testi-link {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  padding: .6rem 1.1rem;
  border-radius: 999px;
  font-size: .85rem;
  font-weight: 700;
  color: #7c3aed;
  background: #fff;
  text-decoration: none;
  transition: all .18s ease;
  box-shadow: 0 10px 24px -10px rgba(0,0,0,.5);
  flex-shrink: 0;
}
.jba-testi-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 30px -12px rgba(0,0,0,.55);
  background: #f5f0ff;
}
@media (max-width: 640px) {
  .jba-game-hero {
    min-height: 250px;
  }
  .jba-game-hero-content {
    padding: .8rem .9rem 1rem;
    justify-content: flex-start;
    gap: 1rem;
  }
  .jba-game-title {
    font-size: 1.45rem;
    margin-top: .2rem;
  }
  .jba-game-desc {
    font-size: .74rem;
    line-height: 1.5;
  }
  .jba-game-stats {
    margin-top: .9rem;
  }
  .jba-game-hero-body {
    align-items: flex-start;
    flex-direction: column;
    gap: .5rem;
  }
}
.jba-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  align-items: start;
}
.jba-card {
  border-radius: 12px;
  overflow: hidden;
  transition: transform .25s ease, box-shadow .25s ease;
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
  background: var(--bg-card);
  border: 1.5px solid rgba(157, 92, 245, .2);
}
.jba-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px -14px rgba(0,0,0,.65);
  border-color: var(--jba-accent);
}
.jba-card-img {
  width: 100%;
  overflow: hidden;
  background: var(--bg-soft);
  position: relative;
  line-height: 0;
}
.jba-card-img img {
  width: 100%;
  height: auto;
  object-fit: contain;
  display: block;
}
.jba-card-img-fallback {
  width: 100%;
  min-height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-soft);
  color: #555;
}
.jba-ribbon {
  position: absolute;
  top: 14px;
  left: -30px;
  background: linear-gradient(135deg, #E53935 0%, #c62828 100%);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 2px 36px;
  z-index: 3;
  text-align: center;
  text-transform: uppercase;
  white-space: nowrap;
  letter-spacing: 0.04em;
  line-height: 1.6;
  transform: rotate(-45deg);
  box-shadow: 0 3px 10px rgba(0,0,0,0.3);
  pointer-events: none;
}
.jba-card:hover .jba-ribbon {
  transform: rotate(-45deg) scale(1.06);
  box-shadow: 0 4px 16px rgba(229, 57, 53, 0.5);
}
.jba-ribbon::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  border: 5px solid #a82121;
  border-left-color: transparent;
  border-bottom-color: transparent;
}
.jba-ribbon::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  border: 5px solid #a82121;
  border-right-color: transparent;
  border-top-color: transparent;
}
.jba-card--sold {
  pointer-events: none;
  cursor: default;
  opacity: .75;
}
.jba-card--sold .jba-card-game,
.jba-card--sold .jba-card-title,
.jba-card--sold .jba-card-owner {
  color: var(--text-dim) !important;
  opacity: .5;
}
.jba-card--sold .jba-card-prices * {
  filter: grayscale(1);
  opacity: .4;
}
.jba-card--sold .jba-card-img::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1;
}
.jba-img-sold {
  filter: grayscale(1) brightness(.55);
}
.jba-sold-badge {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-14deg);
  z-index: 4;
  width: 116px;
  height: 116px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ff6b6b;
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 1.12rem;
  letter-spacing: .16em;
  text-transform: uppercase;
  text-indent: .16em;
  text-shadow: 0 1px 3px rgba(0,0,0,.25);
  background: rgba(239,68,68,.13);
  border: 2px solid rgba(239,68,68,.55);
  backdrop-filter: blur(3px);
  box-shadow: 0 0 0 5px rgba(239,68,68,.1), inset 0 0 0 2px rgba(239,68,68,.18), 0 12px 28px -12px rgba(0,0,0,.35);
  white-space: nowrap;
  pointer-events: none;
}
.jba-sold-badge::before {
  content: '';
  position: absolute;
  inset: -7px;
  border-radius: 50%;
  border: 2px dashed rgba(239,68,68,.4);
}
@media (max-width: 640px) {
  .jba-sold-badge {
    width: 88px;
    height: 88px;
    font-size: .85rem;
  }
}
.jba-card-body {
  padding: .85rem 1rem;
}
.jba-card-game {
  font-size: .65rem;
  color: var(--jba-accent);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: .15rem;
  opacity: .7;
}
.jba-card-title {
  font-size: .88rem;
  font-weight: 600;
  margin-bottom: .15rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.3;
  color: var(--text);
}
.jba-card-owner {
  font-size: .75rem;
  color: var(--text-dim);
  font-weight: 400;
  margin-bottom: .35rem;
}
.jba-card-prices {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: .25rem;
}
.jba-card-original {
  font-size: .78rem;
  color: #f87171;
  text-decoration: line-through;
  opacity: .75;
  line-height: 1.2;
}
.jba-card-price-row {
  display: flex;
  align-items: center;
  gap: .45rem;
  flex-wrap: wrap;
}
.jba-card-price {
  font-size: .95rem;
  font-weight: 700;
  color: var(--jba-accent);
}
.jba-card-save {
  display: inline-flex;
  align-items: center;
  font-size: .64rem;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, #22c55e, #15803d);
  padding: .18rem .5rem;
  border-radius: 999px;
  letter-spacing: .02em;
  white-space: nowrap;
  box-shadow: 0 4px 10px -4px rgba(34, 197, 94, .6);
}
.jba-empty {
  text-align: center;
  padding: 4rem 1rem;
  color: var(--text-dim);
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
}
@media(max-width:920px){
  .jba-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:640px){
  .jba-page{padding:1rem 1.1rem 1.5rem;}
  .jba-grid{grid-template-columns:repeat(2,1fr);gap:.7rem;}
  .jba-card-body{padding:.6rem .7rem;}
  .jba-card-title{font-size:.8rem;}
  .jba-card-price{font-size:.85rem;}
  .jba-card-original{font-size:.7rem;}
  .jba-card-game{font-size:.6rem;}
}
.jba-empty h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: .3rem;
  color: var(--text-primary);
}
.jba-empty p {
  font-size: .88rem;
}
.jba-filter-bar {
  display: flex;
  align-items: center;
  gap: .6rem;
  margin-bottom: 1rem;
  padding-left: .5rem;
  flex-wrap: wrap;
}
.jba-deal-tabs {
  display: flex;
  gap: .35rem;
  flex-basis: 100%;
  padding: .3rem;
  border-radius: 12px;
  background: var(--surface-2);
  border: 1px solid var(--border);
}
.jba-deal-tab {
  flex: 1;
  padding: .5rem .75rem;
  border-radius: 9px;
  font-size: .78rem;
  font-weight: 700;
  border: none;
  background: transparent;
  color: var(--text-dim);
  cursor: pointer;
  font-family: inherit;
  transition: all .16s ease;
}
.jba-deal-tab:hover {
  color: var(--text);
}
.jba-deal-tab.jba-is-active {
  background: var(--jba-accent);
  color: #fff;
  box-shadow: 0 6px 16px -6px rgba(157, 92, 245, .6);
}
.jba-budget-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .6rem;
  flex-basis: 100%;
}
.jba-budget-card {
  position: relative;
  display: block;
  width: 100%;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  background: var(--surface);
  cursor: pointer;
  font-family: inherit;
  text-align: left;
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, filter .2s ease;
}
.jba-budget-card:hover {
  transform: translateY(-3px);
  border-color: var(--jba-accent);
  box-shadow: 0 16px 30px -12px rgba(157, 92, 245, .45);
}
.jba-budget-img {
  position: relative;
  display: block;
  width: 100%;
  aspect-ratio: 2 / 1;
  overflow: hidden;
}
.jba-budget-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.jba-budget-img-fallback {
  width: 100%;
  height: 100%;
  display: block;
  background: linear-gradient(135deg, #7c3aed, #4c1d95);
}
.jba-budget-card::after {
  content: '';
  position: absolute;
  top: .5rem;
  right: .5rem;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(255, 255, 255, .2);
  border: 1.5px solid rgba(255, 255, 255, .6);
  opacity: 0;
  transition: opacity .15s ease;
}
.jba-budget-card.jba-is-active {
  border-color: var(--jba-accent);
  box-shadow: 0 0 0 2px rgba(157, 92, 245, .35), 0 14px 26px -12px rgba(157, 92, 245, .5);
  filter: saturate(1.15);
}
.jba-budget-card.jba-is-active::after {
  opacity: 1;
  background: var(--jba-accent);
  border-color: #fff;
  box-shadow: 0 0 0 2px rgba(157, 92, 245, .35);
}
@media (max-width: 640px) {
  .jba-budget-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
.jba-results-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin: 1.55rem 0 .25rem;
  padding: 0 .15rem .7rem;
  border-bottom: 1px solid rgba(157, 92, 245, .28);
}
.jba-results-title {
  display: flex;
  align-items: center;
  gap: .55rem;
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 1.1rem;
  color: var(--text);
}
.jba-results-title::before {
  content: '';
  width: 5px;
  height: 18px;
  border-radius: 5px;
  background: linear-gradient(180deg, var(--jba-accent), #6d28d9);
  box-shadow: 0 0 12px var(--jba-accent-glow);
}
.jba-results-count {
  font-size: .76rem;
  font-weight: 700;
  color: var(--jba-accent);
  background: rgba(157, 92, 245, .12);
  border: 1px solid rgba(157, 92, 245, .35);
  padding: .28rem .7rem;
  border-radius: 999px;
  white-space: nowrap;
}
@media (max-width: 640px) {
  .jba-results-title {
    font-size: .98rem;
  }
}
.jba-filter-search {
  position: relative;
  flex: 1 1 200px;
  min-width: 200px;
}
.jba-filter-search input {
  width: 100%;
  padding: .5rem .85rem .5rem 2.1rem;
  border-radius: 9px;
  font-size: .8rem;
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text);
  font-family: inherit;
  transition: border-color .16s ease;
}
.jba-filter-search input::placeholder {
  color: var(--text-dim);
}
.jba-filter-search input:focus {
  outline: none;
  border-color: var(--jba-accent);
}
.jba-search-icon {
  position: absolute;
  top: 50%;
  left: .7rem;
  transform: translateY(-50%);
  color: var(--text-dim);
  pointer-events: none;
}
@media (max-width: 640px) {
  .jba-filter-search {
    flex-basis: 100%;
    min-width: 100%;
  }
}
.jba-filter-group {
  position: relative;
}
.jba-filter-trigger {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .45rem .85rem;
  border-radius: 9px;
  font-size: .78rem;
  font-weight: 600;
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-dim);
  cursor: pointer;
  transition: all .16s ease;
  font-family: inherit;
}
.jba-filter-trigger:hover {
  border-color: var(--jba-accent);
  color: var(--text);
}
.jba-filter-trigger.jba-is-open {
  border-color: var(--jba-accent);
  color: var(--text);
  background: rgba(157, 92, 245, .08);
}
.jba-filter-trigger.jba-is-active {
  border-color: var(--jba-accent);
  color: var(--jba-accent);
}
.jba-filter-trigger.jba-is-active .jba-filter-chev {
  color: var(--jba-accent);
}
.jba-filter-chev {
  transition: transform .16s ease;
  flex-shrink: 0;
}
.jba-filter-trigger.jba-is-open .jba-filter-chev {
  transform: rotate(180deg);
}
.jba-filter-panel {
  position: absolute;
  top: calc(100% + .4rem);
  left: 0;
  z-index: 40;
  min-width: 230px;
  padding: .9rem;
  border-radius: 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  box-shadow: 0 18px 40px -12px rgba(0,0,0,.55);
  display: none;
}
.jba-filter-panel.jba-is-open {
  display: block;
  animation: jbaFilterIn .18s ease both;
}
@keyframes jbaFilterIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
.jba-filter-header {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .12em;
  color: var(--text-dim);
  margin-bottom: .6rem;
}
.jba-filter-field {
  margin-bottom: .55rem;
}
.jba-filter-field label {
  display: block;
  font-size: .68rem;
  color: var(--text-dim);
  margin-bottom: .25rem;
  font-weight: 600;
}
.jba-filter-field input {
  width: 100%;
  padding: .45rem .6rem;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--surface-2);
  color: var(--text);
  font-size: .8rem;
  font-family: inherit;
}
.jba-filter-field input:focus {
  outline: none;
  border-color: var(--jba-accent);
}
.jba-filter-actions {
  display: flex;
  gap: .45rem;
  margin-top: .2rem;
}
.jba-filter-btn {
  flex: 1;
  padding: .45rem .6rem;
  border-radius: 8px;
  font-size: .78rem;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition: all .16s ease;
}
.jba-filter-btn--apply {
  background: var(--jba-accent);
  border: 1px solid var(--jba-accent);
  color: #fff;
}
.jba-filter-btn--apply:hover {
  filter: brightness(1.12);
}
.jba-filter-btn--reset {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-dim);
}
.jba-filter-btn--reset:hover {
  border-color: var(--jba-accent);
  color: var(--text);
}
.jba-filter-option {
  display: block;
  width: 100%;
  text-align: left;
  padding: .5rem .6rem;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--text);
  font-size: .8rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all .14s ease;
}
.jba-filter-option:hover {
  background: rgba(157, 92, 245, .1);
}
.jba-filter-option.jba-is-selected {
  background: var(--jba-accent);
  color: #fff;
}
@media (max-width: 640px) {
  .jba-filter-panel {
    position: absolute;
    top: auto;
    left: 0;
    right: auto;
  }
}
@media (max-width: 768px) {
  #jba-hero { height: 250px; border-radius: 16px; }
}
@media (max-width: 480px) {
  #jba-hero { height: 200px; }
}
@media (max-width: 640px) {
  .jba-transfer-info { padding: .6rem .8rem; margin: 1.1rem 0 1.4rem; border-radius: 10px; }
  .jba-transfer-icon { width: 30px; height: 30px; }
  .jba-transfer-text { font-size: .7rem; line-height: 1.4; }
}
</style>

<script>
(function(){
  const gameGrid = document.getElementById('jba-game-grid');
  const gameSection = document.getElementById('jba-game-section');
  const gameTitle = document.getElementById('jbaGameTitle');
  const gameDesc = document.getElementById('jbaGameDesc');
  const statCount = document.getElementById('jbaStatCount');
  const heroBg = document.getElementById('jbaGameHeroBg');
  const resultCount = document.getElementById('jbaResultCount');
  const backBtn = document.getElementById('jbaBackBtn');
  const grid = document.getElementById('jbaGrid');

  const allListings = @json($listings);
  const gameSlugs = @json($gameSlugs);
  const gameBanners = @json($gameBanners);
  const baseUrl = '/jual-beli-akun';
  const slugToGame = {};
  Object.keys(gameSlugs).forEach(function(name) { slugToGame[gameSlugs[name]] = name; });
  let currentGame = null;
  let searchQuery = '';
  let dealType = 'normal';
  let priceMin = null;
  let priceMax = null;
  let collectorTier = null;
  let budgetId = null;
  const budgetRange = {};

  function applyFilters(listings) {
    const arr = [...(listings || [])];
    return arr.filter(function(l) {
      const p = Number(l.price);
      const dt = (l.deal_type || 'normal');
      if (dt !== dealType) return false;
      if (budgetId && budgetRange[budgetId]) {
        if (p < budgetRange[budgetId].min || p > budgetRange[budgetId].max) return false;
      }
      if (priceMin !== null && p < priceMin) return false;
      if (priceMax !== null && p > priceMax) return false;
      if (collectorTier && l.collector_tier !== collectorTier) return false;
      if (searchQuery) {
        const hay = ((l.product_name || '') + ' ' + (l.owner_name || '') + ' ' + (l.game || '')).toLowerCase();
        if (hay.indexOf(searchQuery.toLowerCase()) === -1) return false;
      }
      return true;
    });
  }

  function renderCards(listings) {
    grid.innerHTML = '';
    let visible = 0;

    (listings || []).forEach(function(l) {
      visible++;

      const isSold = l.is_sold;
      const price = 'Rp ' + Number(l.price).toLocaleString('id-ID');
      const orig = l.original_price ? 'Rp ' + Number(l.original_price).toLocaleString('id-ID') : null;
      let saveBadge = '';
      if (l.original_price && Number(l.original_price) > Number(l.price)) {
        saveBadge = '<span class="jba-card-save">Hemat ' + fmtCompact(Number(l.original_price) - Number(l.price)) + '</span>';
      }

      let badgeHtml = '';
      if (isSold) {
        badgeHtml = '<div class="jba-sold-badge">SOLD</div>';
      } else if (l.promo_type && l.promo_type !== 'none') {
        let text = '';
        if (l.promo_type === 'diskon' && l.discount_percent) text = '-' + l.discount_percent + '%';
        else if (l.promo_type === 'promo') text = 'Promo';
        else if (l.promo_type === 'flash_sale') text = 'Flash Sale';
        else if (l.promo_type === 'best_seller') text = 'Best Seller';
        else if (l.promo_type === 'hot') text = 'Hot';
        else if (l.promo_type === 'new') text = 'New';
        else if (l.promo_type === 'limited') text = 'Limited';
        badgeHtml = '<div class="jba-ribbon">' + text + '</div>';
      }

      const thumbHtml = l.photo_url
        ? '<img src="' + l.photo_url + '" alt="' + l.product_name + '" class="' + (isSold ? 'jba-img-sold' : '') + '">'
        : '<div class="jba-card-img-fallback"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></div>';

      const href = isSold ? '#' : '/jual-beli-akun/' + l.id;

      const card = document.createElement('a');
      card.href = href;
      card.className = 'jba-card' + (isSold ? ' jba-card--sold' : '');
      if (isSold) { card.setAttribute('tabindex', '-1'); card.setAttribute('aria-disabled', 'true'); }
      card.innerHTML = '<div class="jba-card-img">' + thumbHtml + badgeHtml + '</div>' +
        '<div class="jba-card-body">' +
        '<div class="jba-card-game">' + l.game + '</div>' +
        '<h3 class="jba-card-title">' + l.product_name + '</h3>' +
        (l.owner_name ? '<div class="jba-card-owner">' + l.owner_name + '</div>' : '') +
        '<div class="jba-card-prices">' +
        (orig ? '<span class="jba-card-original">' + orig + '</span>' : '') +
        '<div class="jba-card-price-row">' +
        '<span class="jba-card-price">' + price + '</span>' +
        saveBadge +
        '</div>' +
        '</div></div>';
      grid.appendChild(card);
    });

    if (visible === 0) {
      grid.innerHTML = '<div class="jba-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:#555;margin-bottom:.75rem"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><h3>Tidak Ada Akun</h3><p>Tidak ada akun untuk filter ini.</p></div>';
    }
    if (resultCount) resultCount.textContent = visible + ' akun';
  }

  function fmtCompact(n) {
    n = Number(n) || 0;
    if (n >= 1000000000) return trimZero(n / 1000000000) + 'M';
    if (n >= 1000000) return trimZero(n / 1000000) + 'jt';
    if (n >= 1000) return trimZero(n / 1000) + 'rb';
    return String(n);
  }
  function trimZero(v) {
    return Number(v.toFixed(1)) === Math.floor(v) ? String(Math.floor(v)) : v.toFixed(1).replace('.', ',');
  }

  const jbaHero = document.querySelector('.jba-hero');
  const jbaBanner = document.getElementById('jba-hero');
  const jbaFlash = document.querySelector('.jba-flash-sale');
  const jbaTransfer = document.querySelector('.jba-transfer-info');
  const jbaFilterBar = document.getElementById('jbaFilterBar');

  const collectorLabels = {
    ternama: 'Kolektor Ternama',
    terhormat: 'Kolektor Terhormat',
    juragan: 'Kolektor Juragan',
    sultan: 'Kolektor Sultan'
  };

  function fmtRp(n) {
    return 'Rp' + Number(n).toLocaleString('id-ID');
  }

  function closePanels() {
    document.querySelectorAll('.jba-filter-panel').forEach(function(p) { p.classList.remove('jba-is-open'); });
    document.querySelectorAll('.jba-filter-trigger').forEach(function(t) { t.classList.remove('jba-is-open'); });
  }

  function resetFilters() {
    searchQuery = '';
    dealType = 'normal';
    priceMin = null;
    priceMax = null;
    collectorTier = null;
    budgetId = null;
    document.querySelectorAll('.jba-budget-card').forEach(function(b) { b.classList.remove('jba-is-active'); });
    document.querySelectorAll('.jba-deal-tab').forEach(function(t) {
      t.classList.toggle('jba-is-active', t.dataset.deal === 'normal');
    });
    const searchEl = document.getElementById('jbaFilterSearch'); if (searchEl) searchEl.value = '';
    const minEl = document.getElementById('jbaPriceMin'); if (minEl) minEl.value = '';
    const maxEl = document.getElementById('jbaPriceMax'); if (maxEl) maxEl.value = '';
    const priceT = document.getElementById('jbaPriceTrigger');
    if (priceT) {
      priceT.classList.remove('jba-is-active');
      const lbl = priceT.querySelector('.jba-filter-trigger-label');
      if (lbl) lbl.textContent = 'Harga';
    }
    const collT = document.getElementById('jbaCollectorTrigger');
    if (collT) {
      collT.classList.remove('jba-is-active');
      const lbl = collT.querySelector('.jba-filter-trigger-label');
      if (lbl) lbl.textContent = 'Kolektor';
    }
    document.querySelectorAll('.jba-filter-option').forEach(function(o) { o.classList.remove('jba-is-selected'); });
    const allOpt = document.querySelector('.jba-filter-option[data-tier=""]');
    if (allOpt) allOpt.classList.add('jba-is-selected');
  }

  function renderFiltered() {
    if (!currentGame) return;
    renderCards(applyFilters(allListings[currentGame] || []));
  }

  function updatePriceTrigger() {
    const priceT = document.getElementById('jbaPriceTrigger');
    const lbl = priceT.querySelector('.jba-filter-trigger-label');
    if (priceMin !== null || priceMax !== null) {
      let txt = 'Harga';
      if (priceMin !== null && priceMax !== null) txt = fmtRp(priceMin) + ' – ' + fmtRp(priceMax);
      else if (priceMin !== null) txt = '≥ ' + fmtRp(priceMin);
      else if (priceMax !== null) txt = '≤ ' + fmtRp(priceMax);
      lbl.textContent = txt;
      priceT.classList.add('jba-is-active');
    } else {
      lbl.textContent = 'Harga';
      priceT.classList.remove('jba-is-active');
    }
  }

  function showGame(game) {
    currentGame = game;
    gameGrid.style.display = 'none';
    gameSection.style.display = '';
    if (jbaHero) jbaHero.style.display = 'none';
    if (jbaBanner) jbaBanner.style.display = 'none';
    if (jbaFlash) jbaFlash.style.display = 'none';
    if (jbaTransfer) jbaTransfer.style.display = 'none';
    gameTitle.textContent = game;
    if (heroBg) {
      const slug = gameSlugs[game] || '';
      const url = gameBanners[slug] || null;
      if (url) {
        heroBg.src = url;
        heroBg.style.display = '';
      } else {
        heroBg.removeAttribute('src');
        heroBg.style.display = 'none';
      }
    }
    gameDesc.textContent = 'Pusat jual beli akun ' + game + ' resmi tanpa ribet \u2014 terverifikasi, cepat, dan 100% aman dibantu langsung oleh admin.';
    statCount.textContent = (allListings[game] || []).length;
    resetFilters();
    if (jbaFilterBar) {
      jbaFilterBar.style.display = String(game).toLowerCase() === 'mobile legends' ? '' : 'none';
    }
    renderCards(applyFilters(allListings[game] || []));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function showGameGrid() {
    gameSection.style.display = 'none';
    gameGrid.style.display = '';
    if (jbaHero) jbaHero.style.display = '';
    if (jbaBanner) jbaBanner.style.display = '';
    if (jbaFlash) jbaFlash.style.display = '';
    if (jbaTransfer) jbaTransfer.style.display = '';
    resetFilters();
    if (jbaFilterBar) jbaFilterBar.style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function slugFromPath(p) {
    const m = p.match(/^\/jual-beli-akun\/([a-z0-9-]+)$/);
    return m ? m[1] : null;
  }

  window.addEventListener('popstate', function() {
    const slug = slugFromPath(location.pathname);
    if (slug && slugToGame[slug]) {
      showGame(slugToGame[slug]);
    } else {
      showGameGrid();
    }
  });

  /* ---------- filter dropdowns ---------- */
  const jbaTestiBtn = document.getElementById('jbaTestiBtn');
  if (jbaTestiBtn) {
    jbaTestiBtn.addEventListener('click', function() {
      const sec = document.getElementById('jbaTestiSection');
      if (sec) sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  document.querySelectorAll('.jba-filter-trigger').forEach(function(trig) {
    trig.addEventListener('click', function(e) {
      e.stopPropagation();
      const group = trig.closest('.jba-filter-group');
      const panel = group.querySelector('.jba-filter-panel');
      const willOpen = !panel.classList.contains('jba-is-open');
      closePanels();
      if (willOpen) {
        panel.classList.add('jba-is-open');
        trig.classList.add('jba-is-open');
      }
    });
  });

  document.addEventListener('click', function() { closePanels(); });

  const searchInput = document.getElementById('jbaFilterSearch');
  let searchTimer = null;
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      searchQuery = this.value.trim();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(renderFiltered, 150);
    });
  }

  document.querySelectorAll('.jba-deal-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      if (tab.classList.contains('jba-is-active')) return;
      document.querySelectorAll('.jba-deal-tab').forEach(function(t) { t.classList.remove('jba-is-active'); });
      tab.classList.add('jba-is-active');
      dealType = tab.dataset.deal;
      renderFiltered();
    });
  });

  document.querySelectorAll('.jba-budget-card').forEach(function(card) {
    const id = card.dataset.budget;
    budgetRange[id] = { min: Number(card.dataset.min), max: Number(card.dataset.max) };
    card.addEventListener('click', function() {
      const isActive = card.classList.contains('jba-is-active');
      budgetId = isActive ? null : id;
      document.querySelectorAll('.jba-budget-card').forEach(function(c) { c.classList.remove('jba-is-active'); });
      if (budgetId) card.classList.add('jba-is-active');
      renderFiltered();
    });
  });

  document.getElementById('jbaPriceApply').addEventListener('click', function(e) {
    e.stopPropagation();
    const minV = document.getElementById('jbaPriceMin').value.trim();
    const maxV = document.getElementById('jbaPriceMax').value.trim();
    priceMin = minV === '' ? null : Number(minV);
    priceMax = maxV === '' ? null : Number(maxV);
    if (priceMin !== null && priceMax !== null && priceMin > priceMax) {
      const t = priceMin;
      priceMin = priceMax;
      priceMax = t;
    }
    updatePriceTrigger();
    renderFiltered();
    closePanels();
  });

  document.getElementById('jbaPriceReset').addEventListener('click', function(e) {
    e.stopPropagation();
    priceMin = null;
    priceMax = null;
    document.getElementById('jbaPriceMin').value = '';
    document.getElementById('jbaPriceMax').value = '';
    updatePriceTrigger();
    renderFiltered();
  });

  document.querySelectorAll('.jba-filter-option').forEach(function(opt) {
    opt.addEventListener('click', function() {
      const tier = opt.dataset.tier;
      collectorTier = (collectorTier === tier) ? null : tier;
      const collT = document.getElementById('jbaCollectorTrigger');
      const lbl = collT.querySelector('.jba-filter-trigger-label');
      document.querySelectorAll('.jba-filter-option').forEach(function(o) { o.classList.remove('jba-is-selected'); });
      if (collectorTier) {
        opt.classList.add('jba-is-selected');
        lbl.textContent = collectorLabels[collectorTier];
        collT.classList.add('jba-is-active');
      } else {
        document.querySelector('.jba-filter-option[data-tier=""]').classList.add('jba-is-selected');
        lbl.textContent = 'Kolektor';
        collT.classList.remove('jba-is-active');
      }
      renderFiltered();
      closePanels();
    });
  });

  document.querySelectorAll('.jba-game-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      const game = this.dataset.game;
      const slug = gameSlugs[game] || null;
      e.preventDefault();
      showGame(game);
      if (slug) {
        history.pushState({ game: game }, '', baseUrl + '/' + slug);
      } else {
        history.pushState({ game: game }, '', baseUrl);
      }
    });
  });

  backBtn?.addEventListener('click', function() {
    showGameGrid();
    history.pushState({ game: null }, '', baseUrl);
  });

  // auto-open game dari slug path (direct load / refresh)
  const activeGame = @json($activeGame);
  if (activeGame) {
    showGame(activeGame);
  }
})();
</script>

<!-- ===== TESTIMONIALS ===== -->
<section class="testi-section" id="jbaTestiSection">
  <h2>APA KATA MEREKA?</h2>
  <p class="testi-sub">Ribuan orang telah mempercayai Transaksi mereka di Johen Gaming</p>
  <div class="testi-carousel">
    <button class="testi-arrow testi-arrow-left" onclick="prevTestiJba()" aria-label="Sebelumnya">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <div class="testi-track" id="testiTrackJba"></div>
    <button class="testi-arrow testi-arrow-right" onclick="nextTestiJba()" aria-label="Selanjutnya">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
  </div>
  <div class="testi-dots" id="testiDotsJba"></div>
  <div class="load-more-wrap" style="margin-top:1.2rem">
    <a href="{{ route('testimoni', ['layanan' => 'jual-beli-akun']) }}" class="btn btn-outline btn-load-more">Lihat Selengkapnya</a>
  </div>
</section>

<script>
// ============ TESTIMONIALS CAROUSEL (JBA) ============
const testiTrackJba = document.getElementById('testiTrackJba');
const testiDotsJba = document.getElementById('testiDotsJba');
let testiJbaCurrent = 0;
let testiJbaTimer = null;

function createTestiCardJba(t) {
  const card = document.createElement('div');
  card.className = 'testi-card';
  card.innerHTML = `
    <div class="testi-user">
      <div class="testi-avatar">${t.avatar}</div>
      <div>
        <div class="testi-name">${t.name}</div>
        <div class="testi-game">${t.game}</div>
      </div>
    </div>
    <p class="testi-quote">"${t.quote}"</p>`;
  return card;
}

function getTestiOffsetJba(i) {
  const cw = testiTrackJba.parentElement.getBoundingClientRect().width;
  const card = testiTrackJba.children[i + 1];
  return card.offsetLeft - (cw - card.offsetWidth) / 2;
}

function applyTestiCenterJba(i) {
  testiTrackJba.querySelectorAll('.testi-card').forEach((c, idx) => c.classList.toggle('center', idx === i + 1));
  if (testiDotsJba) Array.from(testiDotsJba.children).forEach((d, idx) => d.classList.toggle('active', idx === i));
}

function goTestiJba(i) {
  testiJbaCurrent = i;
  if (i > testiJbaTotal - 1) { testiJbaCurrent = 0; }
  if (i < 0) { testiJbaCurrent = testiJbaTotal - 1; }
  testiTrackJba.style.transform = 'translateX(' + (-getTestiOffsetJba(testiJbaCurrent)) + 'px)';
  applyTestiCenterJba(testiJbaCurrent);
}

function prevTestiJba() { goTestiJba(testiJbaCurrent - 1); resetTestiJbaTimer(); }
function nextTestiJba() { goTestiJba(testiJbaCurrent + 1); resetTestiJbaTimer(); }
function resetTestiJbaTimer() {
  if (testiJbaTimer) clearInterval(testiJbaTimer);
  testiJbaTimer = setInterval(function() { goTestiJba(testiJbaCurrent + 1); }, 5000);
}

const testiJbaData = @json($testimonials);
const testiJbaTotal = testiJbaData.length;

if (testiTrackJba && testiJbaTotal > 0) {
  testiJbaData.forEach(t => testiTrackJba.appendChild(createTestiCardJba(t)));
  const clones = Array.from(testiTrackJba.children);
  testiTrackJba.appendChild(clones[0].cloneNode(true));
  testiTrackJba.insertBefore(clones[testiJbaTotal - 1].cloneNode(true), testiTrackJba.firstChild);
  testiTrackJba.style.transition = 'none';
  testiTrackJba.style.transform = 'translateX(' + (-getTestiOffsetJba(0)) + 'px)';
  void testiTrackJba.offsetHeight;
  testiTrackJba.style.transition = '';
  applyTestiCenterJba(0);
  if (testiDotsJba) {
    testiJbaData.forEach(function(_, i) {
      const dot = document.createElement('button');
      dot.className = 'testi-dot' + (i === 0 ? ' active' : '');
      dot.setAttribute('aria-label', 'Testimonial ' + (i + 1));
      dot.addEventListener('click', function() { goTestiJba(i); resetTestiJbaTimer(); });
      testiDotsJba.appendChild(dot);
    });
  }
  testiJbaTimer = setInterval(function() { goTestiJba(testiJbaCurrent + 1); }, 5000);
}
</script>

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
      <img src="{{ asset('logo.png') }}" alt="Johen Gaming" class="cta-logo">
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