@php
    $gachaService = app(\App\Services\GachaService::class);
    $gachaVisible = $gachaService->isEnabled()
        && $gachaService->dailyLimit() > 0
        && $gachaService->activePrizes()->isNotEmpty();
@endphp
@if($gachaVisible)
<link rel="stylesheet" href="{{ pwa_asset('css/gacha.css') }}">
<style>
/* Critical state: modal tetap tersembunyi selama CSS widget sedang dimuat. */
#gc-overlay:not(.active),#gc-modal:not(.active){opacity:0!important;visibility:hidden!important;pointer-events:none!important}
</style>

<div id="gc-overlay" class="gc-overlay" aria-hidden="true"></div>

<div id="gc-modal" class="gc-modal" role="dialog" aria-modal="true" aria-labelledby="gcTitle" aria-hidden="true">
  <div class="gc-header">
    <div class="gc-brand">
      <span class="gc-brand-mark" aria-hidden="true">
        <img src="{{ pwa_asset('assets/icon/gacha.png') }}" width="42" height="42" alt="" decoding="async">
      </span>
      <div class="gc-header-text">
        <span class="gc-eyebrow">DAILY REWARD</span>
        <h3 id="gcTitle">Gacha Voucher</h3>
        <p>Putar roda dan menangkan voucher setiap hari.</p>
      </div>
    </div>
    <div class="gc-header-actions">
      <span class="gc-daily-badge">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        1 kesempatan / hari
      </span>
      <button class="gc-header-close" type="button" onclick="window.Gacha.close()" aria-label="Tutup">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
  </div>

  <div class="gc-body">
    <div class="gc-layout">
      <section class="gc-wheel-panel" aria-label="Roda hadiah">
        <div class="gc-section-heading">
          <span>PUTAR &amp; MENANGKAN</span>
          <p>Hadiah ditentukan otomatis dan langsung tersimpan.</p>
        </div>

        <div class="gc-stage">
          <div class="gc-pointer" aria-hidden="true">
            <svg width="28" height="34" viewBox="0 0 28 34" fill="none">
              <path d="M14 32L2.8 8.4A2.2 2.2 0 014.8 5.2h18.4a2.2 2.2 0 012 3.2L14 32z" fill="#f4c95d" stroke="#7c4a03" stroke-width="1.8" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="gc-wheel-wrap">
            <span class="gc-wheel-ring" aria-hidden="true"></span>
            <svg class="gc-wheel" id="gcWheel" viewBox="0 0 300 300" role="img" aria-label="Roda undian"></svg>
            <button class="gc-hub" id="gcHub" type="button"><span>PUTAR</span></button>
          </div>
        </div>

        <div class="gc-wheel-note">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.6-2.8 7.8-7 10-4.2-2.2-7-5.4-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
          Voucher tersimpan otomatis dan aman digunakan saat checkout.
        </div>
      </section>

      <section class="gc-info-panel" aria-label="Informasi gacha">
        <div class="gc-status-card">
          <span class="gc-status-indicator" aria-hidden="true"></span>
          <p class="gc-status" id="gcStatus">Putar sekarang dan dapatkan voucher diskon!</p>
        </div>

        <div class="gc-result" id="gcResult" hidden>
          <div class="gc-result-badge">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8v4h3v2a5 5 0 01-5 5h-4a5 5 0 01-5-5V7h3V3z"/><path d="M9 18h6M12 14v4M8 21h8"/></svg>
            Selamat, kamu menang
          </div>
          <div class="gc-result-label" id="gcResultLabel">-</div>
          <div class="gc-result-code-row">
            <code class="gc-result-code" id="gcResultCode">-</code>
            <button class="gc-result-copy" id="gcResultCopy" type="button">
              <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2"/></svg>
              Salin
            </button>
          </div>
          <p class="gc-result-meta" id="gcResultMeta"></p>
        </div>

        <div class="gc-prizes-block">
          <div class="gc-prizes-heading">
            <div>
              <span>PRIZE POOL</span>
              <h4>Hadiah tersedia</h4>
            </div>
            <span class="gc-live-badge"><i></i> Live</span>
          </div>
          <div class="gc-prizes" id="gcPrizes"></div>
        </div>

        <div class="gc-actions">
          <a class="gc-link" id="gcVoucherLink" href="#" hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4z"/><path d="M8 7V5a4 4 0 018 0v2M8 12h8"/></svg>
            Lihat voucher saya
            <svg class="gc-link-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
          </a>
        </div>
      </section>
    </div>
  </div>
</div>

<button id="gc-fab" class="gc-fab" type="button" onclick="window.Gacha.open()" aria-label="Gacha Voucher">
  <img class="gc-fab-logo" src="{{ pwa_asset('assets/icon/gacha.png') }}" width="52" height="52" alt="Gacha Voucher" decoding="async">
  <span class="gc-fab-label" aria-hidden="true">GACHA</span>
  <span class="gc-fab-dot" id="gcFabDot" hidden title="Voucher baru tersedia"></span>
</button>

<script>
  window.GACHA_USER = @json(auth('web')->check() ? ['id' => auth('web')->id(), 'name' => auth('web')->user()->name] : null);
  window.GACHA_URLS = {
    config: @json(route('api.gacha.index')),
    spin: @json(route('api.gacha.spin')),
    voucherPage: @json(route('vouchers.index')),
    login: @json(route('login'))
  };
</script>
<script src="{{ pwa_asset('js/gacha.js') }}" defer></script>
@endif
