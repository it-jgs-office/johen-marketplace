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
    <div class="gc-header-text">
      <h3 id="gcTitle">GACHA VOUCHER</h3>
      <p>Putarkan roda, Chances voucher tiap hari</p>
    </div>
    <button class="gc-header-close" type="button" onclick="window.Gacha.close()" aria-label="Tutup">&times;</button>
  </div>

  <div class="gc-body">
    <div class="gc-stage">
      <div class="gc-pointer" aria-hidden="true">
        <svg width="26" height="32" viewBox="0 0 26 32" fill="none">
          <path d="M13 31L2.6 8.2A2 2 0 014.4 5h17.2a2 2 0 011.8 3.2L13 31z" fill="#fbbf24" stroke="#78350f" stroke-width="1.6" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="gc-wheel-wrap">
        <svg class="gc-wheel" id="gcWheel" viewBox="0 0 300 300" role="img" aria-label="Roda undian"></svg>
        <button class="gc-hub" id="gcHub" type="button">PUTAR</button>
      </div>
    </div>

    <div class="gc-result" id="gcResult" hidden>
      <div class="gc-result-badge">SELAMAT, KAMU MENANG!</div>
      <div class="gc-result-label" id="gcResultLabel">-</div>
      <div class="gc-result-code-row">
        <code class="gc-result-code" id="gcResultCode">-</code>
        <button class="gc-result-copy" id="gcResultCopy" type="button">Salin</button>
      </div>
      <p class="gc-result-meta" id="gcResultMeta"></p>
    </div>

    <p class="gc-status" id="gcStatus">Putar sekarang dan dapatkan voucher diskon!</p>

    <div class="gc-prizes" id="gcPrizes"></div>

    <div class="gc-actions">
      <a class="gc-link" id="gcVoucherLink" href="#" hidden>Voucher Saya</a>
    </div>
  </div>
</div>

<button id="gc-fab" class="gc-fab" type="button" onclick="window.Gacha.open()" aria-label="Gacha Voucher">
  <img class="gc-fab-logo" src="{{ pwa_asset('assets/Foto/logo.gacha.png') }}" width="52" height="52" alt="Gacha Voucher" decoding="async">
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
