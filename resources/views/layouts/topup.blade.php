@php
    $themeEventSlug = !empty($activeTheme) && $activeTheme ? $activeTheme->slug : null;
    $themeLogo = $activeThemeLogoUrl
        ?? (\App\Models\SiteSetting::get('site_logo') ? media_url(\App\Models\SiteSetting::get('site_logo')) : null)
        ?? pwa_asset('logo.png');
    $themeFavicon = $activeThemeLogoUrl ?? pwa_asset('logo.png');
    $splashName = \App\Models\SiteSetting::get('site_name') ?: 'Johen Gaming';
    $splashTagline = \App\Models\SiteSetting::get('site_tagline') ?: 'Top Up & Joki Game Termurah';
    $biz = business_info();
@endphp
<!DOCTYPE html>
<html lang="id" data-event-theme="{{ $themeEventSlug }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', config('app.name', 'Johen Gaming Marketplace'))</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="icon" type="image/png" href="{{ $themeFavicon }}">
<link rel="shortcut icon" href="{{ $themeFavicon }}">
@include('partials.pwa')
  <link rel="stylesheet" href="{{ pwa_asset('css/topup.css') }}">
@if(!empty($activeThemeCss))
<style>:root{{{ $activeThemeCss }}}</style>
@endif
@include('partials.splash-head')
@stack('styles')
</head>
<body>
@include('partials.splash')
@include('partials.floating-decoration')
@include('partials.particle-effect')

<!-- ===== HEADER ===== -->
<header class="site-header" id="siteHeader">
  <div class="header-inner">
    <a href="{{ route('home') }}" class="logo">
      <img src="{{ $themeLogo }}" alt="Johen Gaming" class="logo-img">
    </a>

    <div class="search-wrap">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20L16.5 16.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      <input type="text" id="searchInput" placeholder="Cari Game atau Voucher" autocomplete="off">
      <div class="search-suggest" id="searchSuggest"></div>
    </div>

    <nav class="main-nav" id="mainNav">
      <a href="{{ route('home') }}" class="{{ request()->routeIs('home') || request()->routeIs('games.show') ? 'active' : '' }}">Top Up</a>
      <a href="{{ route('jual-beli-akun') }}" class="{{ request()->routeIs('jual-beli-akun*') ? 'active' : '' }}">Jual Beli Akun</a>
      <a href="{{ route('check.transaction') }}" class="{{ request()->routeIs('check.transaction') ? 'active' : '' }}">Cek Transaksi</a>
      <a href="{{ route('leaderboard') }}" class="{{ request()->routeIs('leaderboard') ? 'active' : '' }}">Leaderboard</a>
    </nav>

    @php
        // Hanya untuk user login: keranjang disimpan per user di database.
        $cartCount = Auth::check() ? app(\App\Services\CartService::class)->countItems(Auth::id()) : 0;
    @endphp
    <a href="{{ route('cart.index') }}" class="nav-cart-btn {{ request()->routeIs('cart.*') || request()->routeIs('checkout.*') ? 'active' : '' }}" aria-label="Keranjang">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      <span class="nav-cart-badge" data-role="cart-badge" @if($cartCount < 1) hidden @endif>{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
    </a>

    @auth
      <div class="auth-user">
        <div class="auth-dropdown">
          <button class="auth-dropdown-toggle">
            <div class="auth-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
            <span class="auth-name">{{ Auth::user()->name }}</span>
            <svg class="auth-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="auth-dropdown-menu">

            <a href="{{ route('orders.my') }}" class="auth-dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
              Pesanan Saya
            </a>
            <a href="{{ route('vouchers.index') }}" class="auth-dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h14a2 2 0 002-2v-6z"/><path d="M16 6a4 4 0 00-8 0"/><path d="M12 9v3"/></svg>
              Voucher Saya
            </a>
            <a href="{{ route('testimoni') }}" class="auth-dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="12" y1="7" x2="12" y2="13"/></svg>
              Ulasan
            </a>
            <a href="{{ route('my-inquiries') }}" class="auth-dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              Riwayat Pesan
            </a>
            @if(Auth::user()->isAdmin())
              <a href="{{ route('admin.dashboard') }}" class="auth-dropdown-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                Admin Panel
              </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
              @csrf
              <button type="submit" class="auth-dropdown-item logout" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:inherit;font-size:.82rem;padding:.55rem .7rem;border-radius:8px;display:flex;align-items:center;gap:.5rem;color:#f87171;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
              </button>
            </form>
          </div>
        </div>
      </div>
    @else
      <div class="auth-buttons">
        <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
        <a href="{{ route('register') }}" class="btn btn-solid">Daftar</a>
      </div>
    @endauth

    <button type="button" class="pwa-install-btn pwa-install-header-btn" data-pwa-install-trigger aria-label="Install App" hidden>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
      <span>Install App</span>
    </button>

    <button class="nav-theme-btn" id="themeToggle" aria-label="Ganti tema">
      <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
      <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
    </button>

    <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>

  <div class="mobile-menu" id="mobileMenu">
    <a href="{{ route('home') }}#topup" class="{{ request()->routeIs('home') || request()->routeIs('games.show') ? 'active' : '' }}">Top Up</a>
    <a href="{{ route('jual-beli-akun') }}" class="{{ request()->routeIs('jual-beli-akun*') ? 'active' : '' }}">Jual Beli Akun</a>
    <a href="{{ route('check.transaction') }}" class="{{ request()->routeIs('check.transaction') ? 'active' : '' }}">Cek Transaksi</a>
    <a href="{{ route('leaderboard') }}" class="{{ request()->routeIs('leaderboard') ? 'active' : '' }}">Leaderboard</a>
    <a href="{{ route('cart.index') }}" class="mobile-menu__cart {{ request()->routeIs('cart.*') || request()->routeIs('checkout.*') ? 'active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      <span>Keranjang</span>
      <span class="nav-cart-badge nav-cart-badge--inline" data-role="cart-badge" @if($cartCount < 1) hidden @endif>{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
    </a>
    <button type="button" class="pwa-install-btn pwa-install-mobile-btn" data-pwa-install-trigger aria-label="Install App" hidden>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
      <span>Install App</span>
    </button>
    @auth
      <a href="{{ route('orders.my') }}">Pesanan Saya</a>
      <a href="{{ route('vouchers.index') }}">Voucher Saya</a>
      <a href="{{ route('testimoni') }}">Ulasan</a>
      <a href="{{ route('my-inquiries') }}">Riwayat Pesan</a>
      @if(Auth::user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
      @endif
      <div class="mobile-auth">
        <form method="POST" action="{{ route('logout') }}" style="width:100%;">
          @csrf
          <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center;">Logout</button>
        </form>
      </div>
    @else
      <div class="mobile-auth">
        <a href="{{ route('login') }}" class="btn btn-outline" style="flex:1;justify-content:center;">Masuk</a>
        <a href="{{ route('register') }}" class="btn btn-solid" style="flex:1;justify-content:center;">Daftar</a>
      </div>
    @endauth
  </div>
</header>

@php
    $popupBanners = \App\Models\PopupBanner::activeBanners();
@endphp

<main>
  @if(session('success') || session('error'))
    <div id="flash-data" style="display:none;">{{ json_encode(['success' => session('success'), 'error' => session('error')]) }}</div>
  @endif
  @yield('content')
</main>

<!-- ===== FOOTER ===== -->
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="logo">
        <img src="{{ $themeLogo }}" alt="Johen Gaming" class="logo-img">
        <span class="logo-text">JOHEN<span>GAMING</span></span>
      </div>
      <p class="footer-about"><strong>{{ $biz['name'] }}</strong> &mdash; perusahaan digital gaming commerce terpercaya di Bandung. Spesialisasi jual beli akun game online, top up, jasa joki, live commerce, dan konten digital gaming.</p>
      <p>Top up game &amp; voucher terlaris, murah, aman legal 100% buka 24 jam dengan payment terlengkap Indonesia.</p>
    </div>
    <div class="footer-col">
      <h4>Peta Situs</h4>
      <a href="{{ route('home') }}">Beranda</a>
      <a href="{{ route('check.transaction') }}">Cek Transaksi</a>
      <a href="{{ route('kontak') }}">Hubungi Kami</a>
      <a href="{{ route('testimoni') }}">Ulasan</a>
      <a href="{{ route('vouchers.index') }}">Voucher Saya</a>
    </div>
    <div class="footer-col">
      <h4>Dukungan</h4>
      <a href="{{ route('kontak') }}">Contact Us</a>
      <a href="{{ route('faq') }}">FAQ</a>
    </div>
    <div class="footer-col">
      <h4>Legalitas</h4>
      <a href="{{ route('privacy') }}">Kebijakan Privasi</a>
      <a href="{{ route('terms') }}">Syarat & Ketentuan</a>
    </div>
    <div class="footer-col">
      <h4>Kontak Kami</h4>
      <p class="footer-contact-item">{{ $biz['address'] }}</p>
      @if($biz['npwp'])
      <p class="footer-contact-item">NPWP: {{ $biz['npwp'] }}</p>
      @endif
      <a class="footer-contact-item" href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a>
      @if($biz['cs_email'] && $biz['cs_email'] !== $biz['email'])
      <a class="footer-contact-item" href="mailto:{{ $biz['cs_email'] }}">{{ $biz['cs_email'] }}</a>
      @endif
      @if($biz['wa_href'])
      <a class="footer-contact-item" href="{{ $biz['wa_href'] }}" target="_blank" rel="noopener">{{ $biz['phone'] }}</a>
      @endif
      <p class="footer-contact-item">{{ $biz['cs_hours'] }}</p>
    </div>
  </div>
  <div class="footer-bottom">&copy; {{ date('Y') }} {{ $biz['name'] }}. All Rights Reserved.</div>
</footer>

<!-- ===== MODALS ===== -->
<!-- Topup Modal -->
<div class="modal-overlay" id="topupModal">
  <div class="modal-box modal-box-wide">
    <button class="modal-close" data-close-modal>&times;</button>
    <div class="topup-header">
      <div class="topup-icon" id="topupIcon">ðŸŽ®</div>
      <div>
        <h3 id="topupGameName">Nama Game</h3>
        <p class="modal-sub">Isi data akun dan pilih nominal top up.</p>
      </div>
    </div>
    <form class="modal-form" id="topupForm">
      @csrf
      <label>User ID
        <input type="text" name="customer_number" required placeholder="Masukkan User ID">
      </label>
      <label id="zoneIdLabel" style="display:none">Zone ID / Server
        <input type="text" name="zone_id" placeholder="Contoh: 2001">
      </label>
      <p class="field-label">Pilih Nominal</p>
      <div class="nominal-grid" id="nominalGrid"></div>
      <p class="field-label">Metode Pembayaran</p>
      <div class="pay-select-grid" id="paySelectGrid"></div>
      <div class="topup-total">
        <span>Total Pembayaran</span>
        <strong id="topupTotal">Rp 0</strong>
      </div>
      <button type="submit" class="btn btn-solid btn-full">Beli Sekarang</button>
    </form>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- ===== POPUP BANNER MODAL ===== -->
@if($popupBanners->isNotEmpty())
<div class="popup-overlay" id="popupOverlay">
  <div class="popup-modal" id="popupModal">
    <button class="popup-close" id="popupClose" aria-label="Tutup">&times;</button>
    <div class="popup-scroll">
      @foreach($popupBanners as $popup)
        <div class="popup-slide {{ $loop->first ? 'active' : '' }}" data-orientation="{{ $popup->orientation }}">
          <div class="popup-media">
            @if($popup->link)
              <a href="{{ $popup->link }}" class="popup-link" target="_blank" rel="noopener">
                <img src="{{ $popup->image_url }}" alt="{{ $popup->title ?? 'Promo' }}" class="popup-image"
                     style="object-fit:contain;object-position:{{ $popup->image_position ?? 'center' }}">
              </a>
            @else
              <img src="{{ $popup->image_url }}" alt="{{ $popup->title ?? 'Promo' }}" class="popup-image"
                   style="object-fit:contain;object-position:{{ $popup->image_position ?? 'center' }}">
            @endif
          </div>
          @if($popup->title || $popup->description)
            <div class="popup-caption">
              @if($popup->title)<h3 class="popup-title">{{ $popup->title }}</h3>@endif
              @if($popup->description)<p class="popup-desc">{{ $popup->description }}</p>@endif
            </div>
          @endif
        </div>
      @endforeach
    </div>
    @if($popupBanners->count() > 1)
      <div class="popup-dots" id="popupDots">
        @foreach($popupBanners as $i => $popup)
          <button type="button" class="popup-dot {{ $loop->first ? 'active' : '' }}" data-index="{{ $i }}"></button>
        @endforeach
      </div>
    @endif
    <div class="popup-install-area" hidden>
      <button type="button" class="pwa-install-btn popup-install-btn" data-pwa-install-trigger aria-label="Install App" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
        <span>Install App</span>
      </button>
    </div>
    <label class="popup-dont-show">
      <input type="checkbox" id="popupDontShow">
      <span>Jangan tampilkan lagi</span>
    </label>
  </div>
</div>
@endif

<div class="pwa-install-guide" id="pwaInstallGuide" hidden>
  <div class="pwa-install-guide-backdrop" data-pwa-install-close></div>
  <div class="pwa-install-guide-dialog" role="dialog" aria-modal="true" aria-labelledby="pwaInstallGuideTitle">
    <button type="button" class="pwa-install-guide-close" data-pwa-install-close aria-label="Tutup">&times;</button>
    <div class="pwa-install-guide-mark" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
    </div>
    <h2 id="pwaInstallGuideTitle">Install App</h2>
    <div data-pwa-install-ios>
      <p>Tambahkan Johen Gaming Marketplace ke layar utama iPhone melalui Safari:</p>
      <ol>
        <li>Buka halaman ini di Safari.</li>
        <li>Ketuk tombol Bagikan di bilah Safari.</li>
        <li>Pilih Tambahkan ke Layar Utama.</li>
        <li>Ketuk Tambahkan.</li>
      </ol>
    </div>
    <div data-pwa-install-fallback hidden>
      <p>Jika prompt install browser tidak muncul, gunakan menu browser:</p>
      <ol>
        <li>Buka menu browser.</li>
        <li>Pilih Instal aplikasi atau Tambahkan ke layar utama.</li>
        <li>Konfirmasi untuk memasang Johen Gaming Marketplace.</li>
      </ol>
    </div>
    <button type="button" class="pwa-install-guide-done" data-pwa-install-close>Siap</button>
  </div>
</div>

@include('partials.gacha')
@include('partials.livechat')

<style>
[data-theme="light"] {
  --bg: color-mix(in srgb, var(--theme-primary, #7c3aed) 9%, #ffffff);
  --bg-soft: color-mix(in srgb, var(--theme-primary, #7c3aed) 6%, #f4f1fa);
  --surface: #ffffff;
  --surface-2: color-mix(in srgb, var(--theme-primary, #7c3aed) 5%, #ffffff);
  --surface-3: color-mix(in srgb, var(--theme-primary, #7c3aed) 9%, #ffffff);
  --border: rgba(0,0,0,.08);
  --border-strong: rgba(0,0,0,.14);
  --text: color-mix(in srgb, var(--theme-primary, #7c3aed) 42%, #000000);
  --text-dim: color-mix(in srgb, var(--text) 58%, var(--surface));
  --text-mute: color-mix(in srgb, var(--text) 38%, var(--surface));
  --purple-glow: color-mix(in srgb, var(--theme-primary, #7c3aed) 25%, transparent);
  --shadow-purple: 0 8px 30px -8px color-mix(in srgb, var(--theme-primary, #7c3aed) 35%, transparent);
  --header-bg: color-mix(in srgb, var(--theme-primary, #7c3aed) 4%, rgba(255,255,255,.9));
  --header-bg-scrolled: color-mix(in srgb, var(--theme-primary, #7c3aed) 6%, rgba(255,255,255,.97));
  --nav-active-text: color-mix(in srgb, var(--theme-primary-dark, #4c1d95) 85%, #000000);
  --bg-card:#ffffff;
}
.nav-theme-btn {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--surface);
  color: var(--text-dim);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border);
  cursor: pointer;
  transition: background .15s, color .15s, border-color .15s;
  position: relative;
}
.nav-theme-btn:hover {
  background: var(--surface-2);
  color: var(--text);
  border-color: var(--border-strong);
}
.nav-theme-btn .icon-sun,
.nav-theme-btn .icon-moon {
  position: absolute;
  transition: opacity .25s, transform .25s;
}
.mobile-theme-btn .icon-sun,
.mobile-theme-btn .icon-moon {
  transition: opacity .25s, transform .25s;
}
[data-theme="dark"] .nav-theme-btn .icon-sun,
html:not([data-theme="light"]) .nav-theme-btn .icon-sun,
[data-theme="dark"] .mobile-theme-btn .icon-sun,
html:not([data-theme="light"]) .mobile-theme-btn .icon-sun {
  opacity: 1;
  transform: rotate(0deg);
}
[data-theme="dark"] .nav-theme-btn .icon-moon,
html:not([data-theme="light"]) .nav-theme-btn .icon-moon,
[data-theme="dark"] .mobile-theme-btn .icon-moon,
html:not([data-theme="light"]) .mobile-theme-btn .icon-moon {
  opacity: 0;
  transform: rotate(90deg);
}
[data-theme="light"] .nav-theme-btn .icon-sun,
[data-theme="light"] .mobile-theme-btn .icon-sun {
  opacity: 0;
  transform: rotate(-90deg);
}
[data-theme="light"] .nav-theme-btn .icon-moon,
[data-theme="light"] .mobile-theme-btn .icon-moon {
  opacity: 1;
  transform: rotate(0deg);
}
.mobile-theme-btn {
  display: flex;
  align-items: center;
  gap: .7rem;
  padding: .7rem 0;
  font-size: .92rem;
  color: var(--text-dim);
  border: none;
  background: none;
  cursor: pointer;
  width: 100%;
  text-align: left;
  border-bottom: 1px solid var(--border);
  transition: color .2s;
  font-family: var(--font-body);
}
.mobile-theme-btn:hover {
  color: var(--text);
}
.mobile-theme-btn .icon-wrap {
  position: relative;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.mobile-theme-btn .icon-wrap .icon-sun,
.mobile-theme-btn .icon-wrap .icon-moon {
  position: absolute;
}

</style>

<script>
(function() {
  const theme = localStorage.getItem('theme') || 'dark';
  document.documentElement.setAttribute('data-theme', theme);
  function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: next } }));
  }
  document.getElementById('themeToggle').addEventListener('click', toggleTheme);
  var mobileBtn = document.getElementById('mobileThemeToggle');
  if (mobileBtn) mobileBtn.addEventListener('click', toggleTheme);
})();
</script>

<script>
  window.ZONE_BRANDS = @json(\App\Models\Brand::where('requires_zone_id', true)->where('is_active', true)->pluck('name'));
  // Dipakai topup.js untuk menyegarkan angka keranjang tanpa reload halaman.
  window.CART_STATE_URL = @json(\Illuminate\Support\Facades\Route::has('cart.state') ? route('cart.state') : null);
</script>
  <script src="{{ pwa_asset('js/topup.js') }}"></script>

@if($popupBanners->isNotEmpty())
<script>
(function() {
  var overlay = document.getElementById('popupOverlay');
  var modal = document.getElementById('popupModal');
  if (!overlay) return;

  var dontShow = document.getElementById('popupDontShow');
  var dontShowKey = 'popup_banner_dont_show_v1';

  function openPopup() {
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closePopup() {
    overlay.classList.remove('show');
    document.body.style.overflow = '';
  }

  var slides = Array.prototype.slice.call(modal.querySelectorAll('.popup-slide'));
  var dots = Array.prototype.slice.call(modal.querySelectorAll('.popup-dot'));
  var current = 0;

  function activate(index) {
    if (index < 0) index = slides.length - 1;
    if (index > slides.length - 1) index = 0;
    current = index;
    slides.forEach(function(s, i) { s.classList.toggle('active', i === index); });
    dots.forEach(function(d, i) {
      d.classList.toggle('active', i === index);
      var slide = slides[index];
      d.classList.toggle('for-portrait', slide && slide.dataset.orientation === 'portrait');
    });
    handleOrientation();
  }

  function handleOrientation() {
    var slide = slides[current];
    if (slide) {
      var isPortrait = slide.dataset.orientation === 'portrait';
      modal.classList.toggle('popup-portrait', isPortrait);
    }
  }

  if (dots.length) {
    dots.forEach(function(d) {
      d.addEventListener('click', function() { activate(parseInt(d.dataset.index, 10)); });
    });
  }

  // Auto-rotate each 4s (stop when hovering)
  var timer = null;
  function startTimer() {
    if (slides.length < 2) return;
    stopTimer();
    timer = setInterval(function() { activate(current + 1); }, 4000);
  }
  function stopTimer() { if (timer) { clearInterval(timer); timer = null; } }
  modal.addEventListener('mouseenter', stopTimer);
  modal.addEventListener('mouseleave', startTimer);

  // Closing behavior
  document.getElementById('popupClose').addEventListener('click', closePopup);
  overlay.addEventListener('click', function(e) { if (e.target === overlay) closePopup(); });
  document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePopup(); });
  dontShow.addEventListener('change', function() {
    if (dontShow.checked) localStorage.setItem(dontShowKey, '1');
    else localStorage.removeItem(dontShowKey);
  });

  // Selalu tampil setiap masuk website, kecuali user memilih "Jangan tampilkan lagi"
  if (!localStorage.getItem(dontShowKey)) {
    setTimeout(openPopup, 1200);
    startTimer();
    handleOrientation();
  }
})();
</script>
@endif

@stack('scripts')
</body>
</html>


