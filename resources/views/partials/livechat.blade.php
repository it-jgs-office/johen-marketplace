<link rel="stylesheet" href="{{ pwa_asset('css/livechat.css') }}">
<style>
/* Critical state: cegah modal sempat tampil sebelum stylesheet eksternal siap. */
#lc-overlay:not(.active),#lc-popup:not(.active){opacity:0!important;visibility:hidden!important;pointer-events:none!important}
</style>

<div id="lc-overlay" class="lc-overlay" aria-hidden="true"></div>

<div id="lc-popup" class="lc-popup" role="dialog" aria-modal="true" aria-label="Live Chat" aria-hidden="true">
  <div class="lc-header">
    <div>
      <h3 style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--gold,#00d4ff);margin:0">LIVE CHAT</h3>
    </div>
    <button class="lc-header-close" onclick="window.LiveChat.close()">✕</button>
  </div>
  <div class="lc-body" id="lc-body">
    <div class="lc-empty">Memuat...</div>
  </div>
</div>

<button id="lc-fab" class="lc-fab" aria-label="Live Chat">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
  </svg>
  <span id="lc-fab-badge" class="lc-fab-badge" style="display:none">0</span>
</button>

<script>
window.LIVECHAT_USER = @json(auth('web')->check() ? ['id' => auth('web')->id(), 'name' => auth('web')->user()->name] : null);
window.LIVECHAT_CS_SLUG = '{{ \App\Models\LiveChatChannel::CS_SLUG }}';
</script>
<script src="{{ pwa_asset('js/livechat.js') }}"></script>

<script>
(function() {
  if (!window.LIVECHAT_USER) return;
  function poll() { window.LiveChat.updateBadge(); }
  poll();
  setInterval(poll, 15000);
})();
</script>

@include('partials.push-manager', [
    'pushGuard' => 'web',
    'subscribeUrl' => url('/api/push/subscribe'),
    'testUrl' => url('/api/push/test'),
    'statusUrl' => url('/api/push/status'),
])
