@extends('layouts.topup')

@section('title', 'Detail Pembayaran — ' . $order->order_id)

@section('content')
@php
  $isJoki = $order->service_type === 'joki';
  $statusBadge = match($order->status) {
    'success' => ['label' => 'PEMBELIAN SUKSES', 'color' => '#10b981'],
    'processing' => ['label' => 'SEDANG DIPROSES', 'color' => '#f59e0b'],
    'failed' => ['label' => 'PEMBAYARAN GAGAL', 'color' => '#ef4444'],
    'expired' => ['label' => 'PEMBAYARAN EXPIRED', 'color' => '#ef4444'],
    'cancelled' => ['label' => 'PESANAN DIBATALKAN', 'color' => '#ef4444'],
    'refund' => ['label' => 'REFUND', 'color' => '#94a3b8'],
    default => ['label' => 'MENUNGGU PEMBAYARAN', 'color' => '#f59e0b'],
  };
  $paidType = $order->transaction?->payment_type;
  $paymentMethod = $order->payment_method ?: 'qris';
  $methodLabels = [
    'qris' => 'QRIS',
    'dana' => 'DANA',
    'bca_va' => 'BCA Virtual Account', 'bca' => 'BCA Virtual Account',
    'bni_va' => 'BNI Virtual Account', 'bni' => 'BNI Virtual Account',
    'cimb_va' => 'CIMB Virtual Account', 'cimb' => 'CIMB Virtual Account',
    'bri_va' => 'BRI Virtual Account', 'bri' => 'BRI Virtual Account',
    'mandiri_va' => 'Mandiri Virtual Account', 'mandiri' => 'Mandiri Virtual Account',
    'alfamart' => 'Alfamart',
    'bss_va' => 'BSS Virtual Account', 'bss' => 'BSS Virtual Account',
  ];
  $methodLabel = $methodLabels[strtolower((string) $paymentMethod)] ?? ($paidType ?: 'QRIS');
  $codeAliases = [
    'bca' => 'bca_va', 'bni' => 'bni_va', 'bri' => 'bri_va', 'bss' => 'bss_va', 'cimb' => 'cimb_va',
    'mandiri' => 'mandiri_va',
  ];
  $methodCode = strtolower((string) $paymentMethod);
  $methodCode = $codeAliases[$methodCode] ?? $methodCode;
  $paymentLogo = payment_logo_asset($methodCode);
@endphp

<div class="pd-wrap invoice-theme" id="pdInvoiceTheme" data-invoice-state="{{ $order->status === 'success' ? 'success' : (in_array($order->status, ['failed', 'expired', 'cancelled']) ? 'failed' : 'pending') }}">

  {{-- BREADCRUMB --}}
  <nav class="pd-breadcrumb">
    <a href="{{ $isJoki ? route('joki.mobile-legends') : route('home') }}">{{ $isJoki ? 'Joki MLBB' : 'Top Up' }}</a>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
    <span>Detail Pembayaran</span>
  </nav>

  {{-- HEADER --}}
  <div class="pd-header">
    <div class="pd-header-titles">
      <h1 class="pd-title">Detail Pembayaran</h1>
      <p class="pd-sub">Selesaikan pembayaran sebelum waktu habis.</p>
    </div>
  </div>

  @if($isDemo)
  <div class="pd-demo-banner">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><circle cx="12" cy="12" r="4"/></svg>
    <span>@if($isSimulation)Mode Simulasi Aktif — pembayaran tidak nyata. Gunakan tombol "Lanjut Bayar" untuk menyelesaikan pesanan.@else Mode Demo — pembayaran belum dikonfigurasi. Tampilan hanya untuk preview.@endif</span>
  </div>
  @endif

  <div class="pd-grid pd-grid-single">
    <div class="pd-left">
      {{-- PROGRESS STATUS (horizontal, 4 tahap) --}}
      <div class="pd-card">
        <div class="pd-card-head">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          <span>Progress Status</span>
        </div>
        <div class="pd-steps">
          <div class="pd-step" id="pdStepA" data-state="pending">
            <div class="pd-step-dot"><span class="pd-step-num">1</span></div>
            <div class="pd-step-label">Menunggu Pembayaran</div>
          </div>
          <div class="pd-step" id="pdStepB" data-state="pending">
            <div class="pd-step-dot"><span class="pd-step-num">2</span></div>
            <div class="pd-step-label">Pembayaran Diterima</div>
          </div>
          <div class="pd-step" id="pdStepC" data-state="pending">
            <div class="pd-step-dot"><span class="pd-step-num">3</span></div>
            <div class="pd-step-label">{{ $isJoki ? 'Joki Diproses' : 'Top Up Diproses' }}</div>
          </div>
          <div class="pd-step" id="pdStepD" data-state="pending">
            <div class="pd-step-dot"><span class="pd-step-num">4</span></div>
            <div class="pd-step-label">{{ $isJoki ? 'Joki Berhasil' : 'Top Up Berhasil' }}</div>
          </div>
        </div>
        <div class="pd-progress-note" id="pdProgressNote" style="display:none">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
          <span id="pdProgressNoteText"></span>
        </div>
      </div>


      {{-- STATUS PEMBAYARAN (live) --}}
      <div class="pd-card pd-status-card" id="pdStatusCard">
        <div class="pd-status" id="pdStatusBody">
          <div class="pd-status-left">
            <div class="pd-status-icon pd-status-icon-terminal" id="pdStatusIcon">
              <svg class="pd-pos-terminal" viewBox="0 0 96 96" role="img" aria-label="Animasi terminal pembayaran">
                <circle cx="47" cy="49" r="43" fill="#162f68" opacity=".55"/>
                <g class="pd-pos-device">
                  <rect x="23" y="19" width="53" height="68" rx="10" fill="#3158ad" stroke="#a9caff" stroke-width="2.5"/>
                  <rect class="pd-pos-screen" x="31" y="29" width="37" height="22" rx="4" fill="#d5f6ff" stroke="#183d87" stroke-width="2"/>
                  <path d="M38 37h23M38 43h16" fill="none" stroke="#3987bc" stroke-width="2.5" stroke-linecap="round"/>
                  <circle cx="36" cy="61" r="2.5" fill="#bbd7ff"/><circle cx="49" cy="61" r="2.5" fill="#bbd7ff"/><circle cx="62" cy="61" r="2.5" fill="#bbd7ff"/>
                  <circle cx="36" cy="71" r="2.5" fill="#bbd7ff"/><circle cx="49" cy="71" r="2.5" fill="#bbd7ff"/><circle cx="62" cy="71" r="2.5" fill="#f8c64f"/>
                  <path d="M39 80h21" fill="none" stroke="#142f70" stroke-width="3" stroke-linecap="round"/>
                </g>
                <g class="pd-pos-card">
                  <rect x="56" y="7" width="30" height="19" rx="4" fill="#ffca54" stroke="#fff0bc" stroke-width="2"/>
                  <path d="M58 14h26" stroke="#d78831" stroke-width="3"/>
                  <rect x="62" y="18" width="6" height="4" rx="1" fill="#fff1bb"/>
                </g>
                <path class="pd-pos-signal" d="M75 39c3-3 3-7 0-10m5 14c6-6 6-13 0-19" fill="none" stroke="#7ae5ff" stroke-width="2.5" stroke-linecap="round"/>
              </svg>
            </div>
            <div class="pd-status-info">
              <div class="pd-status-label" id="pdStatusLabel">{{ $statusBadge['label'] }}</div>
              <div class="pd-status-sub" id="pdStatusSub">Selesaikan pembayaran sebelum batas waktu habis.</div>
            </div>
          </div>
          <div class="pd-status-right">
            <span class="pd-status-pill" id="pdStatusPill">{{ $statusBadge['label'] }}</span>
          </div>
        </div>
        <div class="pd-countdown" id="pdCountdown">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <span>Sisa waktu:</span>
          <div class="pd-countdown-items">
            <span class="pd-cd-num" id="countHours">00</span>
            <span class="pd-cd-lbl">Jam</span>
            <span class="pd-cd-sep">:</span>
            <span class="pd-cd-num" id="countMinutes">00</span>
            <span class="pd-cd-lbl">Menit</span>
            <span class="pd-cd-sep">:</span>
            <span class="pd-cd-num" id="countSeconds">00</span>
            <span class="pd-cd-lbl">Detik</span>
          </div>
        </div>
      </div>

      <div class="pd-payment-layout">
      <div class="pd-card pd-method-card" id="pdMethodCard">
        <div class="pd-card-head">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          <span>Metode Pembayaran</span>
        </div>
        <div class="pd-detail-grid">
          <div class="pd-detail-col">
            <div class="pd-detail-item">
              <span class="pd-detail-label">Order ID</span>
              <span class="pd-detail-value pd-detail-mono">#{{ $order->order_id }}</span>
            </div>
            <div class="pd-detail-item">
              <span class="pd-detail-label">Merchant</span>
              <span class="pd-detail-value">Johen Gaming</span>
            </div>
            <div class="pd-detail-item">
              <span class="pd-detail-label">Metode</span>
              <span class="pd-detail-value">
                @if($paymentLogo)<img src="{{ $paymentLogo }}" alt="" class="pd-detail-paylogo">@endif
                <span id="pdDetailMethod">{{ $methodLabel }}</span>
              </span>
            </div>
            <div class="pd-detail-item">
              <span class="pd-detail-label">Status</span>
              <span class="pd-detail-value">
                <span class="pd-detail-badge" id="pdDetailStatusBadge" style="background:{{ $statusBadge['color'] }}20;color:{{ $statusBadge['color'] }}">
                  <span class="pd-detail-badge-dot" id="pdDetailStatusDot" style="background:{{ $statusBadge['color'] }}"></span>
                  <span id="pdDetailStatusText">{{ $statusBadge['label'] }}</span>
                </span>
              </span>
            </div>
          </div>
          <div class="pd-detail-col">
            <div class="pd-detail-item">
              <span class="pd-detail-label">Game</span>
              <span class="pd-detail-value">{{ $order->brand }}</span>
            </div>
            <div class="pd-detail-item">
              <span class="pd-detail-label">User ID</span>
              <span class="pd-detail-value">{{ $order->customer_number }}</span>
            </div>
            <div class="pd-detail-item">
              <span class="pd-detail-label">Server</span>
              <span class="pd-detail-value">{{ $order->effective_zone_id ?: '-' }}</span>
            </div>
            <div class="pd-detail-item">
              <span class="pd-detail-label">Item</span>
              <span class="pd-detail-value">{{ $order->product_name }}@if($order->quantity > 1) ({{ $order->quantity }}x)@endif</span>
            </div>
          </div>
        </div>
        <div class="pd-method-detail-divider"></div>

      {{-- METODE PEMBAYARAN --}}
        <div class="pd-method" id="pdMethodBody">
          <div class="pd-method-placeholder" id="pdMethodPlaceholder">
            <div class="pd-pay-cta-head">
              <div class="pd-pay-cta-icon">
                @if($paymentLogo)
                  <img src="{{ $paymentLogo }}" alt="{{ $methodLabel }}">
                @else
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h4"/></svg>
                @endif
              </div>
              <div class="pd-pay-cta-copy">
                <span>Pembayaran belum selesai</span>
                <strong>Selesaikan pembayaran</strong>
              </div>
            </div>
            <div class="pd-pay-cta-meta">
              <div><span>Metode</span><strong>{{ $methodLabel }}</strong></div>
              <div><span>Total</span><strong>Rp {{ number_format((float) $order->cash_amount, 0, ',', '.') }}</strong></div>
            </div>
            <button class="btn btn-solid btn-lg pd-pay-action" id="payNowBtn">
              <span>Lanjut Bayar</span>
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </button>
            <p class="pd-pay-cta-note">Klik tombol untuk melanjutkan proses pembayaran.</p>
          </div>
          <div class="pd-method-result" id="pdMethodResult" style="display:none">
            <div class="pd-method-result-head">
              <div class="pd-method-result-icon" id="pdMethodIcon">
                @if($paymentLogo)<img src="{{ $paymentLogo }}" alt="" style="width:100%;height:100%;object-fit:contain;">
                @else<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--purple-light)" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                @endif
              </div>
              <div>
                <div class="pd-method-result-label">Pembayaran via</div>
                <div class="pd-method-result-name" id="pdMethodName">-</div>
              </div>
            </div>

            <div class="pd-method-qr-wrap" id="pdMethodQrWrap" style="display:none">
              <div class="pd-method-qr" id="pdMethodQr"></div>
              <p class="pd-method-hint">Scan QR code menggunakan aplikasi e-wallet atau mobile banking.</p>
            </div>

            <div class="pd-method-va-wrap" id="pdMethodVa" style="display:none">
              <div class="pd-va-box">
                <div class="pd-va-label">Nomor Virtual Account</div>
                <div class="pd-va-number" id="pdVaNumber">-</div>
                <div class="pd-method-hint" id="pdVaBillerCode" style="display:none;margin:.6rem 0 0"></div>
                <button class="pd-copy-btn" data-copy="" id="pdVaCopyBtn">Salin Nomor VA</button>
              </div>
              <p class="pd-method-hint">Bayar melalui mobile banking / ATM / Internet Banking.</p>
            </div>

            <div class="pd-method-conv" id="pdMethodConv" style="display:none">
              <p class="pd-method-hint" id="pdConvHint">Bayar di gerai terdekat.</p>
            </div>

            <div class="pd-method-trans-id">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              <span class="pd-trans-id-label">No. Transaksi</span>
              <span class="pd-trans-id-value">{{ $order->order_id }}</span>
              <button class="pd-copy-btn" data-copy="{{ $order->order_id }}">Salin</button>
            </div>
          </div>
          <div class="pd-method-success" id="pdMethodSuccess" style="display:none">
            <div class="pd-success-icon">
              <img class="pd-success-lordicon" src="{{ asset('assets/icon/payment-succes.gif') }}" alt="Pembayaran berhasil">
            </div>
            <div class="pd-success-label">Pembayaran Berhasil</div>
            <div class="pd-success-sub">Diamond telah masuk ke akun kamu.</div>
          </div>
          <div class="pd-method-failed" id="pdMethodFailed" style="display:none">
            <div class="pd-failed-icon">
              <img class="pd-failed-lordicon" src="{{ asset('assets/icon/payment-failed.svg') }}" alt="Pembayaran gagal">
            </div>
            <div class="pd-failed-label">Pembayaran Gagal</div>
            <div class="pd-failed-sub">Silakan coba lagi atau hubungi admin.</div>
          </div>
          @if($canCancel)
          <form method="POST" action="{{ route('payment.cancel', $order) }}" class="pd-cancel-form" id="pdCancelForm" onsubmit="return confirm('Batalkan pesanan ini? Pembayaran tidak dapat dilanjutkan setelah pesanan dibatalkan.')">
            @csrf
            <button type="submit" class="pd-cancel-btn">Batalkan Pesanan</button>
          </form>
          @endif
        </div>
      </div>
      </div>

    </div>

  </div>
</div>

<div class="pd-gateway-modal invoice-theme" id="pdGatewayModal" aria-hidden="true">
  <button type="button" class="pd-gateway-backdrop" data-payment-modal-close aria-label="Tutup pembayaran"></button>
  <section class="pd-gateway-dialog" role="dialog" aria-modal="true" aria-labelledby="pdGatewayTitle">
    <header class="pd-gateway-head">
      <div class="pd-gateway-head-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
      </div>
      <div>
        <strong id="pdGatewayTitle">Selesaikan Pembayaran</strong>
        <span>Pesanan #{{ $order->order_id }}</span>
      </div>
      <button type="button" class="pd-gateway-close" data-payment-modal-close aria-label="Tutup pembayaran">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </header>
    <div class="pd-gateway-frame-wrap">
      <div class="pd-gateway-loading" id="pdGatewayLoading">
        <span class="pd-gateway-spinner"></span>
        <span>Memuat pembayaran...</span>
      </div>
      <iframe id="pdGatewayFrame" title="Halaman pembayaran" src="about:blank" allow="payment *" referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
    <footer class="pd-gateway-foot">
      <span>Halaman status tetap terbuka di belakang.</span>
      <a href="#" id="pdGatewayExternal" target="_blank" rel="noopener">Buka di tab baru</a>
    </footer>
  </section>
</div>

<style>
/* ===== Replate status detail dengan design system yang sama dengan halaman transaksi existing (dt-*) ===== */
.pd-wrap{max-width:var(--layout-max);}
.pd-grid.pd-grid-single{grid-template-columns:minmax(0,1fr);}
.pd-payment-layout{display:grid;grid-template-columns:minmax(0,1fr);gap:1rem;align-items:start;}
.pd-payment-layout>.pd-card{width:100%;}
.pd-payment-layout-checkout{grid-template-columns:minmax(0,1fr);}
.pd-method-card{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,390px);}
.pd-method-card>.pd-card-head{grid-column:1/-1;}
.pd-method-card>.pd-detail-grid{grid-column:1;grid-row:2;align-self:stretch;padding:1rem 1.1rem;}
.pd-method-card>.pd-method,.pd-method-card>.pd-checkout-actions{grid-column:2;grid-row:2;align-self:stretch;border-left:1px solid var(--border);}
.pd-method-card>.pd-method{display:flex;flex-direction:column;justify-content:center;background:var(--invoice-accent-soft);}
.pd-method-detail-divider{display:none;}
.pd-method-card .pd-method-placeholder{display:flex;flex-direction:column;align-items:stretch;text-align:left;padding:.35rem 0 0;}
.pd-pay-cta-head{display:flex;align-items:center;gap:.8rem;margin-bottom:1rem;}
.pd-pay-cta-icon{display:grid;place-items:center;width:48px;height:48px;flex-shrink:0;border-radius:12px;background:#fff;border:1px solid var(--invoice-accent-border);color:var(--invoice-accent);box-shadow:0 8px 20px -14px var(--invoice-accent);}
.pd-pay-cta-icon img{display:block;width:30px;height:30px;object-fit:contain;}
.pd-pay-cta-copy{display:flex;flex-direction:column;gap:.16rem;min-width:0;}
.pd-pay-cta-copy span{font-size:.68rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--invoice-accent);}
.pd-pay-cta-copy strong{font-family:var(--font-display);font-size:1.05rem;line-height:1.25;color:var(--text);}
.pd-pay-cta-meta{display:grid;grid-template-columns:1fr 1fr;gap:.55rem;margin-bottom:.8rem;}
.pd-pay-cta-meta>div{display:flex;flex-direction:column;gap:.2rem;min-width:0;padding:.65rem .7rem;border:1px solid var(--border);border-radius:9px;background:var(--bg-card);}
.pd-pay-cta-meta span{font-size:.66rem;color:var(--text-mute);}
.pd-pay-cta-meta strong{font-size:.78rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pd-pay-action{width:100%;min-height:52px;justify-content:space-between;padding-inline:1.05rem;background:var(--invoice-accent)!important;color:var(--invoice-heading-fg)!important;box-shadow:0 12px 26px -14px var(--invoice-accent);}
.pd-pay-action:hover{transform:translateY(-2px);box-shadow:0 16px 30px -14px var(--invoice-accent);}
.pd-pay-cta-note{margin:.65rem 0 0;text-align:center;font-size:.7rem;color:var(--text-dim);}
.pd-checkout-actions{display:flex;flex-direction:column;align-items:center;gap:.35rem;padding:1.2rem 1.1rem;}
.pd-continue-payment{width:100%;justify-content:center;}
.pd-payment-nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem;width:100%;margin-top:.3rem;}
.pd-payment-nav .btn{width:100%;justify-content:center;text-align:center;}
.pd-cancel-form{display:block;width:100%;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);text-align:center;visibility:visible;opacity:1;}
.pd-cancel-btn{display:inline-flex;align-items:center;justify-content:center;width:100%;min-height:38px;border:1px solid transparent;background:transparent;color:var(--text-mute);font:inherit;font-size:.76rem;font-weight:700;cursor:pointer;padding:.5rem .8rem;border-radius:9px;transition:background .2s,color .2s,border-color .2s,box-shadow .2s;}
.pd-cancel-btn:hover{background:rgba(239,68,68,.2);border-color:#f87171;color:#fff;box-shadow:0 8px 20px -14px rgba(248,113,113,.9);}
.pd-gateway-modal{position:fixed;inset:0;z-index:12000;display:grid;place-items:center;padding:20px;visibility:hidden;opacity:0;transition:opacity .2s ease,visibility .2s ease;}
.pd-gateway-modal.open{visibility:visible;opacity:1;}
.pd-gateway-backdrop{position:absolute;inset:0;border:0;background:rgba(2,10,24,.82);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px);cursor:default;}
.pd-gateway-dialog{position:relative;display:grid;grid-template-rows:auto minmax(0,1fr) auto;width:min(580px,calc(100vw - 32px));height:min(760px,calc(100dvh - 48px));overflow:hidden;border:1px solid var(--border-strong);border-radius:18px;background:var(--bg-card);box-shadow:0 30px 90px rgba(0,0,0,.64);transform:translateY(12px) scale(.985);transition:transform .22s ease;}
.pd-gateway-modal.open .pd-gateway-dialog{transform:translateY(0) scale(1);}
.pd-gateway-head{display:flex;align-items:center;gap:.7rem;min-height:64px;padding:.7rem .9rem;border-bottom:1px solid var(--border);background:var(--surface-2);}
.pd-gateway-head-icon{display:grid;place-items:center;width:38px;height:38px;flex-shrink:0;border-radius:10px;background:var(--invoice-accent-soft);color:var(--invoice-accent);}
.pd-gateway-head>div:nth-child(2){display:flex;flex-direction:column;gap:.1rem;min-width:0;}
.pd-gateway-head strong{font-family:var(--font-display);font-size:.9rem;color:var(--text);}
.pd-gateway-head span{font-size:.68rem;color:var(--text-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pd-gateway-close{display:grid;place-items:center;width:38px;height:38px;margin-left:auto;flex-shrink:0;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text-dim);cursor:pointer;transition:background .2s,color .2s,border-color .2s;}
.pd-gateway-close:hover{background:rgba(239,68,68,.12);border-color:rgba(248,113,113,.45);color:#fca5a5;}
.pd-gateway-frame-wrap{position:relative;min-height:0;background:#f7f7f8;}
.pd-gateway-frame-wrap iframe{display:block;width:100%;height:100%;border:0;background:#fff;}
.pd-gateway-loading{position:absolute;inset:0;z-index:1;display:flex;align-items:center;justify-content:center;gap:.65rem;background:var(--bg-card);color:var(--text-dim);font-size:.78rem;transition:opacity .2s ease,visibility .2s ease;}
.pd-gateway-loading.hidden{visibility:hidden;opacity:0;}
.pd-gateway-spinner{width:22px;height:22px;border:2px solid var(--border-strong);border-top-color:var(--invoice-accent);border-radius:50%;animation:pdGatewaySpin .75s linear infinite;}
@keyframes pdGatewaySpin{to{transform:rotate(360deg)}}
.pd-gateway-foot{display:flex;align-items:center;justify-content:space-between;gap:1rem;min-height:44px;padding:.55rem .9rem;border-top:1px solid var(--border);background:var(--surface-2);font-size:.7rem;color:var(--text-mute);}
.pd-gateway-foot a{color:var(--invoice-accent);font-weight:800;text-decoration:none;white-space:nowrap;}
html.pd-payment-modal-open,body.pd-payment-modal-open{overflow:hidden!important;overscroll-behavior:none;}
body.pd-payment-modal-open{position:fixed;left:0;right:0;width:100%;}
.pd-breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--text-dim);margin:-.2rem 0 1.2rem;}
.pd-breadcrumb a{color:var(--text-dim);text-decoration:none;transition:color .2s;}
.pd-breadcrumb a:hover{color:var(--text);}
.pd-breadcrumb span{color:var(--text-mute);}
.pd-header{display:flex;align-items:center;gap:1rem;margin-bottom:1.6rem;flex-wrap:wrap;}
.pd-header-titles{flex:1;min-width:0;}
.pd-header-actions{display:flex;gap:.6rem;flex-shrink:0;flex-wrap:wrap;}
.pd-header-actions .btn{white-space:nowrap;}

/* status card */
.pd-status-card{border-bottom:1px solid var(--border);}
.pd-status-icon-terminal{display:grid;place-items:center;width:94px;height:94px;border-radius:22px;background:radial-gradient(circle at 50% 40%,rgba(81,139,242,.22),rgba(34,66,137,.09) 70%,transparent);}
.pd-status-icon-terminal .pd-pos-terminal{display:block;width:88px;height:88px;filter:none;overflow:visible;}
.pd-status-icon-success{display:grid;place-items:center;width:94px;height:94px;border-radius:22px;background:radial-gradient(circle at 50% 40%,rgba(52,211,153,.2),rgba(16,185,129,.06) 70%,transparent);}
.pd-status-icon-success .pd-success-lordicon{display:block;width:88px;height:88px;object-fit:contain;}
.pd-status-icon-failed{display:grid;place-items:center;width:94px;height:94px;border-radius:22px;background:radial-gradient(circle at 50% 40%,rgba(248,113,113,.2),rgba(239,68,68,.06) 70%,transparent);}
.pd-status-icon-failed .pd-failed-lordicon{display:block;width:88px;height:88px;object-fit:contain;}
.pd-failed-lordicon{display:block;width:72px;height:72px;object-fit:contain;}
.pd-success-lordicon{display:block;width:72px;height:72px;object-fit:contain;}
.pd-pos-device{animation:pd-pos-float 3.4s ease-in-out infinite;transform-origin:center;}
.pd-pos-card{animation:pd-pos-tap 3.4s ease-in-out infinite;transform-box:fill-box;transform-origin:center;}
.pd-pos-screen{animation:pd-pos-screen-glow 3.4s ease-in-out infinite;}
.pd-pos-signal{animation:pd-pos-signal-pulse 3.4s ease-in-out infinite;}
@keyframes pd-pos-float{0%,100%{transform:translateY(0)}50%{transform:translateY(1.5px)}}
@keyframes pd-pos-tap{0%,10%,100%{transform:translate(10px,-10px) rotate(9deg);opacity:0}22%{opacity:1}40%,56%{transform:translate(-9px,10px) rotate(-7deg);opacity:1}76%{transform:translate(10px,-10px) rotate(9deg);opacity:1}88%{opacity:0}}
@keyframes pd-pos-screen-glow{0%,34%,75%,100%{fill:#d5f6ff}45%,62%{fill:#8ce8f8}}
@keyframes pd-pos-signal-pulse{0%,32%,72%,100%{opacity:0}42%,60%{opacity:1}}
@media(prefers-reduced-motion:reduce){.pd-pos-device,.pd-pos-card,.pd-pos-screen,.pd-pos-signal{animation:none}.pd-pos-card{transform:translate(-9px,10px) rotate(-7deg)}.pd-pos-signal{opacity:1}}
.pd-card-head{text-transform:none;font-size:.84rem;color:var(--text);gap:.55rem;}
.pd-card-head svg{color:var(--invoice-accent);}

/* detail transaksi 2 kolom */
.pd-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 1.5rem;padding:.4rem 1.1rem .8rem;}
.pd-detail-col{display:flex;flex-direction:column;}
.pd-detail-item{display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.55rem 0;border-bottom:1px dashed var(--border);}
.pd-detail-item:last-child{border-bottom:none;}
.pd-detail-label{color:var(--text-dim);font-size:.82rem;white-space:nowrap;flex-shrink:0;}
.pd-detail-value{color:var(--text);font-size:.84rem;font-weight:500;text-align:right;display:inline-flex;align-items:center;gap:.45rem;min-width:0;justify-content:flex-end;}
.pd-detail-mono{font-family:var(--font-display);font-weight:600;}
.pd-detail-paylogo{width:22px;height:22px;object-fit:contain;border-radius:5px;flex-shrink:0;background:#fff;padding:2px;border:1px solid rgba(15,23,42,.12);}
.pd-detail-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .65rem;border-radius:6px;font-size:.72rem;font-weight:700;white-space:nowrap;}
.pd-detail-badge-dot{width:6px;height:6px;border-radius:50%;display:inline-block;}

/* progress horizontal 4 tahap */
.pd-steps{display:flex;align-items:flex-start;padding:1.4rem 1.1rem 1rem;position:relative;}
.pd-step{flex:1;display:flex;flex-direction:column;align-items:center;gap:.6rem;text-align:center;position:relative;min-width:0;}
.pd-step::before{content:"";position:absolute;top:12px;left:-50%;width:100%;height:2px;background:var(--border);z-index:0;}
.pd-step:first-child::before{display:none;}
.pd-step-dot{width:26px;height:26px;border-radius:50%;flex-shrink:0;position:relative;z-index:1;background:var(--surface);border:2px solid var(--border);transition:all .3s;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:.78rem;}
.pd-step-num{color:var(--text-mute);line-height:1;}
.pd-step[data-state="pending"] .pd-step-dot{border-color:var(--border);}
.pd-step[data-state="active"] .pd-step-dot{border-color:var(--invoice-accent);background:var(--invoice-accent);box-shadow:0 0 0 5px var(--invoice-accent-glow);animation:pdStepPulse 1.8s infinite;}
.pd-step[data-state="done"] .pd-step-dot{border-color:var(--invoice-accent);background:var(--invoice-accent);box-shadow:0 0 0 4px var(--invoice-accent-glow);}
.pd-step[data-state="done"] .pd-step-num,.pd-step[data-state="active"] .pd-step-num{color:#fff;}
.pd-step[data-state="done"]::before,.pd-step[data-state="active"]::before{background:var(--invoice-accent);}
.pd-step-label{font-size:.72rem;font-weight:600;color:var(--text-dim);line-height:1.25;max-width:110px;}
.pd-step[data-state="active"] .pd-step-label{color:var(--invoice-accent);font-weight:700;}
.pd-step[data-state="done"] .pd-step-label{color:var(--text);}
@keyframes pdStepPulse{0%,100%{box-shadow:0 0 0 0 rgba(133,77,234,.35)}50%{box-shadow:0 0 0 7px rgba(133,77,234,0)}}
.pd-progress-note{display:flex;align-items:center;gap:.5rem;margin:.2rem 1.1rem 1.1rem;padding:.65rem .85rem;border-radius:8px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.28);font-size:.74rem;font-weight:600;color:#10b981;}
.pd-progress-note svg{flex-shrink:0;color:#10b981;}

/* bantuan */
.pd-help{padding:1.1rem;}
.pd-help-text{font-size:.82rem;color:var(--text-dim);line-height:1.5;margin-bottom:1rem;}

/* beli lagi produk */
.pd-reorder-body{padding:1.1rem;}
.pd-reorder-product{display:flex;gap:.9rem;align-items:center;margin-bottom:1rem;}
.pd-reorder-icon{width:52px;height:52px;border-radius:10px;background:var(--surface-2);flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.pd-reorder-game{font-size:.72rem;color:var(--text-dim);font-weight:600;}
.pd-reorder-pkg{font-size:.9rem;font-weight:700;color:var(--text);line-height:1.3;}
.pd-reorder-price{font-size:.82rem;color:var(--purple-light);font-weight:700;margin-top:.1rem;}

@media(max-width:1000px){
  .pd-grid{grid-template-columns:1fr;}
  .pd-payment-layout{grid-template-columns:1fr;}
  .pd-card-sticky{position:static;}
}
@media(max-width:768px){
  .pd-method-card{grid-template-columns:1fr;}
  .pd-method-card>.pd-card-head{grid-column:1;grid-row:1;}
  .pd-method-card>.pd-method,.pd-method-card>.pd-checkout-actions{grid-column:1;grid-row:2;border-left:0;border-top:0;}
  .pd-method-card>.pd-detail-grid{grid-column:1;grid-row:3;padding:.4rem 1.1rem .8rem;border-top:1px solid var(--border);}
  .pd-payment-nav{grid-template-columns:repeat(2,minmax(0,1fr));}
  .pd-payment-nav .btn{padding-inline:.6rem;font-size:.72rem;white-space:normal;}
  .pd-detail-grid{grid-template-columns:1fr;gap:0;}
  .pd-detail-col .pd-detail-item{border-bottom:1px dashed var(--border);}
}
@media(max-width:480px){
  .pd-gateway-modal{padding:0;}
  .pd-gateway-dialog{width:100vw;height:100dvh;border:0;border-radius:0;}
  .pd-gateway-foot span{display:none;}
  .pd-gateway-foot{justify-content:flex-end;}
  .pd-steps{gap:.2rem;}
  .pd-step-label{font-size:.62rem;max-width:80px;}
}

/* review popup */
.pd-stars{display:flex;gap:.3rem;justify-content:center;margin-bottom:.4rem;}
.pd-stars button{background:none;border:none;cursor:pointer;font-size:1.9rem;line-height:1;color:#3a2b5c;transition:transform .15s ease,color .15s ease;padding:0;}
.pd-stars button:hover{transform:scale(1.12);}
.pd-stars button.on{color:#fbbf24;}
.pd-stars button .star{filter:grayscale(1);opacity:.5;}
.pd-stars button.on .star{filter:none;opacity:1;}
.review-note{text-align:center;font-size:.78rem;color:var(--text-dim);margin-bottom:1rem;}
.pd-review-textarea{background:var(--surface-2);border:1px solid var(--border);border-radius:9px;padding:.7rem .9rem;color:var(--text);font-size:.88rem;width:100%;resize:vertical;min-height:84px;font-family:inherit;}
.pd-review-textarea:focus{outline:none;border-color:var(--purple-light);}
.review-msg{font-size:.82rem;margin-top:.4rem;text-align:center;}
</style>

{{-- REVIEW POPUP (muncul otomatis saat pembayaran sukses) --}}
<div class="modal-overlay" id="reviewModal" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="reviewTitle">
    <button type="button" class="modal-close" id="reviewClose" aria-label="Tutup">&times;</button>
    <div class="topup-header">
      <div class="topup-icon" style="overflow:hidden;">
        @if($brand && $brand->thumbnail_url)
          <img src="{{ $brand->thumbnail_url }}" alt="{{ $order->brand }}" style="width:100%;height:100%;object-fit:cover;">
        @else
          ⭐
        @endif
      </div>
      <div>
        <h3 id="reviewTitle">Top Up Berhasil!</h3>
        <p class="modal-sub" style="margin-bottom:0;">Beri rating untuk pengalaman kamu ya.</p>
      </div>
    </div>
    <form id="reviewForm" class="modal-form" style="margin-top:1rem;">
      <input type="hidden" name="rating" value="">
      <div class="pd-stars" id="reviewStars">
        @for($i = 1; $i <= 5; $i++)
          <button type="button" data-value="{{ $i }}" aria-label="{{ $i }} bintang">
            <svg class="star" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          </button>
        @endfor
      </div>
      <p class="review-note">Bagaimana menurutmu tentang {{ $order->product_name }}?</p>
      <label>
        <span>Ulasan (opsional)</span>
        <textarea class="pd-review-textarea" name="comment" maxlength="1000" placeholder="Ceritakan pengalaman top up kamu..."></textarea>
      </label>
      <button type="submit" class="btn btn-solid btn-full" style="justify-content:center;">Kirim Ulasan</button>
      <p class="review-msg" id="reviewMsg"></p>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
'use strict';

const isDemo = @json($isDemo);
const isSimulation = @json($isSimulation);
const orderId = @json($order->order_id);
const invoiceUrl = @json($invoiceUrl);
const checkoutUrl = @json($checkoutUrl);
const statusUrl = @json(route('payment.status', $order));
const simulateUrl = @json(route('payment.simulate', $order));
const reviewUrl = @json(route('reviews.store', $order));
const hasReviewed = @json($hasReviewed);
const canReview = @json($canReview);
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ===== review popup (muncul otomatis saat pembayaran sukses) ===== */
const reviewShownKey = 'review_shown_' + orderId;
let reviewPrompted = sessionStorage.getItem(reviewShownKey) === '1';

function openReviewModal() {
    const overlay = document.getElementById('reviewModal');
    if (overlay) overlay.classList.add('open');
}

function closeReviewModal() {
    const overlay = document.getElementById('reviewModal');
    if (overlay) overlay.classList.remove('open');
}

function maybeShowReviewPopup(type) {
    if (type !== 'success' || !canReview || hasReviewed || reviewPrompted) return;
    reviewPrompted = true;
    sessionStorage.setItem(reviewShownKey, '1');
    setTimeout(() => openReviewModal(), 700);
}

/* ===== Payment Link ===== */
const payBtn = document.getElementById('payNowBtn');
const gatewayModal = document.getElementById('pdGatewayModal');
const gatewayFrame = document.getElementById('pdGatewayFrame');
const gatewayLoading = document.getElementById('pdGatewayLoading');
const gatewayExternal = document.getElementById('pdGatewayExternal');
let gatewayPageScrollY = 0;

if (gatewayModal && gatewayModal.parentElement !== document.body) {
    document.body.appendChild(gatewayModal);
}

function lockGatewayPageScroll() {
    if (document.body.classList.contains('pd-payment-modal-open')) return;
    gatewayPageScrollY = window.scrollY || document.documentElement.scrollTop || 0;
    document.documentElement.classList.add('pd-payment-modal-open');
    document.body.classList.add('pd-payment-modal-open');
    document.body.style.top = '-' + gatewayPageScrollY + 'px';
}

function unlockGatewayPageScroll() {
    document.documentElement.classList.remove('pd-payment-modal-open');
    document.body.classList.remove('pd-payment-modal-open');
    document.body.style.top = '';
    window.scrollTo(0, gatewayPageScrollY);
}

function openGatewayModal(url) {
    if (!url || !gatewayModal || !gatewayFrame) return false;
    if (gatewayLoading) gatewayLoading.classList.remove('hidden');
    if (gatewayExternal) gatewayExternal.href = url;
    gatewayFrame.src = url;
    gatewayModal.classList.add('open');
    gatewayModal.setAttribute('aria-hidden', 'false');
    lockGatewayPageScroll();
    gatewayModal.querySelector('.pd-gateway-close')?.focus();
    return true;
}

function closeGatewayModal() {
    if (!gatewayModal || !gatewayFrame) return;
    gatewayModal.classList.remove('open');
    gatewayModal.setAttribute('aria-hidden', 'true');
    unlockGatewayPageScroll();
    gatewayFrame.src = 'about:blank';
    payBtn?.focus();
}

gatewayFrame?.addEventListener('load', function() {
    if (gatewayFrame.src !== 'about:blank') gatewayLoading?.classList.add('hidden');
});

document.querySelectorAll('[data-payment-modal-close]').forEach(function(button) {
    button.addEventListener('click', closeGatewayModal);
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && gatewayModal?.classList.contains('open')) closeGatewayModal();
});

function startPayment() {
    if (isSimulation) {
        runSimulatedPayment();
        return;
    }
    if (checkoutUrl) {
        if (!openGatewayModal(checkoutUrl)) window.location.href = checkoutUrl;
        return;
    }
    if (!invoiceUrl || isDemo) {
        simulatePayment();
        return;
    }
    if (!openGatewayModal(invoiceUrl)) window.location.href = invoiceUrl;
}

/* Tampilkan QR dummy lalu panggil endpoint simulasi bayar di server. */
function runSimulatedPayment() {
    if (!payBtn || payBtn.disabled) return;
    payBtn.disabled = true;
    payBtn.innerHTML = '<svg class="spin-slow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Memproses...';

    document.getElementById('pdMethodPlaceholder').style.display = 'none';
    document.getElementById('pdMethodResult').style.display = 'block';
    document.getElementById('pdMethodQrWrap').style.display = 'block';
    document.getElementById('pdMethodQr').innerHTML =
        '<div class="pd-qr-dummy"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(orderId) + '" alt="QR Code" style="width:180px;height:180px;border-radius:8px;display:block;background:#fff;"></div>';
    document.getElementById('pdMethodName').textContent = 'QRIS — Mode Simulasi';
    document.querySelector('#pdVaCopyBtn').dataset.copy = orderId;

    fetch(simulateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' || data.status === 'processing') {
                if (data.note && data.status === 'success') resultNote = data.note;
                updateStatus(data.status);
                return;
            }
            if (data.status === 'failed') {
                updateStatus('failed');
                return;
            }
            startPolling();
        })
        .catch(() => { startPolling(); });
}

function simulatePayment() {
    if (!isDemo || !payBtn) return;
    payBtn.disabled = true;
    payBtn.innerHTML = '<svg class="spin-slow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Memproses...';

    setTimeout(() => {
        document.getElementById('pdMethodPlaceholder').style.display = 'none';
        document.getElementById('pdMethodResult').style.display = 'block';
        document.getElementById('pdMethodQrWrap').style.display = 'block';
        document.getElementById('pdMethodQr').innerHTML =
            '<div class="pd-qr-dummy"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ $order->order_id }}" alt="QR Code" style="width:180px;height:180px;border-radius:8px;display:block;background:#fff;"></div>';
        document.getElementById('pdMethodName').textContent = 'QRIS — Demo Mode';
        document.querySelector('#pdVaCopyBtn').dataset.copy = orderId;
        document.getElementById('pdStatusIcon').classList.add('pd-status-icon-terminal');
        document.getElementById('pdStatusIcon').classList.remove('pd-status-icon-success');
        document.getElementById('pdStatusIcon').innerHTML = pendingTerminalMarkup;
        document.getElementById('pdStatusLabel').textContent = 'Menunggu Pembayaran';
        document.getElementById('pdStatusSub').textContent = 'Scan QR untuk menyelesaikan pembayaran (Demo)';
        document.getElementById('pdStatusPill').textContent = 'MENUNGGU PEMBAYARAN';
        payBtn.disabled = false;
        payBtn.innerHTML = '<span>Lanjut Bayar</span><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>';
    }, 1500);
}

/* mapping status existing -> informasi UI (label, warna, tahap progres) */
const STATUS_MAP = {
    pending:    { label: 'MENUNGGU PEMBAYARAN', color: '#f59e0b', step: 1 },
    processing: { label: 'SEDANG DIPROSES',     color: '#f59e0b', step: 3 },
    success:    { label: 'PEMBELIAN SUKSES',    color: '#10b981', step: 4 },
    failed:     { label: 'PEMBAYARAN GAGAL',    color: '#ef4444', step: 0 },
    expired:    { label: 'PEMBAYARAN EXPIRED',  color: '#ef4444', step: 0 },
    cancelled:  { label: 'PESANAN DIBATALKAN',  color: '#ef4444', step: 0 },
    refund:     { label: 'REFUND',              color: '#94a3b8', step: 0 },
};

function cssVar(name){ return getComputedStyle(document.documentElement).getPropertyValue(name).trim(); }
const pendingTerminalMarkup = document.getElementById('pdStatusIcon').innerHTML;
const failedAnimationUrl = @json(asset('assets/icon/payment-failed.svg'));
const expiredAnimationUrl = @json(asset('assets/icon/payment-expired.svg'));

function updateStatus(type, result) {
    const map = STATUS_MAP[type] || STATUS_MAP.pending;
    if (gatewayModal?.classList.contains('open') && ['processing', 'success', 'failed', 'expired', 'cancelled'].includes(type)) {
        closeGatewayModal();
    }
    const invoiceTheme = document.getElementById('pdInvoiceTheme');
    if (invoiceTheme) {
        invoiceTheme.dataset.invoiceState = type === 'success'
            ? 'success'
            : ['failed', 'expired', 'cancelled'].includes(type) ? 'failed' : 'pending';
    }
    const icon = document.getElementById('pdStatusIcon');
    const label = document.getElementById('pdStatusLabel');
    const sub = document.getElementById('pdStatusSub');
    const pill = document.getElementById('pdStatusPill');
    const countdown = document.getElementById('pdCountdown');
    const placeholder = document.getElementById('pdMethodPlaceholder');
    const methodResult = document.getElementById('pdMethodResult');
    const methodSuccess = document.getElementById('pdMethodSuccess');
    const methodFailed = document.getElementById('pdMethodFailed');
    const cancelForm = document.getElementById('pdCancelForm');

    icon.classList.toggle('pd-status-icon-terminal', type === 'pending' || type === 'processing');
    icon.classList.toggle('pd-status-icon-success', type === 'success');
    icon.classList.toggle('pd-status-icon-failed', type === 'failed' || type === 'expired' || type === 'cancelled');
    if (cancelForm) cancelForm.style.display = type === 'pending' ? 'block' : 'none';

    // status card
    if (type === 'processing') {
        icon.innerHTML = pendingTerminalMarkup;
        label.textContent = 'Pembayaran Diterima';
        sub.textContent = 'Diamond sedang dikirim ke akun kamu.';
        pill.textContent = 'SEDANG DIPROSES';
        pill.style.background = 'rgba(245,158,11,.15)';
        pill.style.color = '#f59e0b';
        countdown.style.display = 'none';
        if (placeholder) placeholder.style.display = 'none';
        if (methodResult) methodResult.style.display = 'none';
        if (methodSuccess) methodSuccess.style.display = 'flex';
        if (methodFailed) methodFailed.style.display = 'none';
    } else if (type === 'success') {
        icon.innerHTML = '<img class="pd-success-lordicon" src="{{ asset('assets/icon/payment-succes.gif') }}" alt="Pembayaran berhasil">';
        label.textContent = 'Pembayaran Berhasil';
        sub.textContent = 'Diamond telah masuk ke akun kamu.';
        pill.textContent = 'PEMBELIAN SUKSES';
        pill.style.background = 'rgba(16,185,129,.15)';
        pill.style.color = '#10b981';
        countdown.style.display = 'none';
        if (placeholder) placeholder.style.display = 'none';
        if (methodResult) methodResult.style.display = 'none';
        if (methodSuccess) methodSuccess.style.display = 'flex';
        if (methodFailed) methodFailed.style.display = 'none';
        maybeShowReviewPopup('success');
    } else if (type === 'failed' || type === 'expired' || type === 'cancelled') {
        const cancelled = type === 'cancelled';
        const expired = type === 'expired';
        const terminalAnimationUrl = expired ? expiredAnimationUrl : failedAnimationUrl;
        const terminalAnimationAlt = expired ? 'Waktu pembayaran habis' : (cancelled ? 'Pesanan dibatalkan' : 'Pembayaran gagal');
        icon.innerHTML = '<img class="pd-failed-lordicon" src="' + terminalAnimationUrl + '" alt="' + terminalAnimationAlt + '">';
        label.textContent = cancelled ? 'Pesanan Dibatalkan' : expired ? 'Pembayaran Expired' : 'Pembayaran Gagal';
        sub.textContent = cancelled ? 'Pesanan dibatalkan tanpa proses refund.' : expired ? 'Batas waktu pembayaran telah habis.' : 'Silakan coba lagi atau hubungi admin.';
        pill.textContent = cancelled ? 'PESANAN DIBATALKAN' : expired ? 'PEMBAYARAN EXPIRED' : 'PEMBAYARAN GAGAL';
        pill.style.background = 'rgba(239,68,68,.15)';
        pill.style.color = '#ef4444';
        countdown.style.display = 'none';
        if (placeholder) placeholder.style.display = 'none';
        if (methodResult) methodResult.style.display = 'none';
        if (methodSuccess) methodSuccess.style.display = 'none';
        if (methodFailed) {
            methodFailed.style.display = 'flex';
            const methodFailedIcon = methodFailed.querySelector('.pd-failed-lordicon');
            if (methodFailedIcon) {
                methodFailedIcon.src = terminalAnimationUrl;
                methodFailedIcon.alt = terminalAnimationAlt;
            }
            methodFailed.querySelector('.pd-failed-label').textContent = cancelled ? 'Pesanan Dibatalkan' : expired ? 'Pembayaran Expired' : 'Pembayaran Gagal';
            methodFailed.querySelector('.pd-failed-sub').textContent = cancelled ? 'Pesanan ini tidak dilanjutkan.' : expired ? 'Batas waktu pembayaran telah habis.' : 'Silakan coba lagi atau hubungi admin.';
        }
    } else {
        // pending
        if (type === 'pending') {
            if (!icon.querySelector('.pd-pos-terminal')) icon.innerHTML = pendingTerminalMarkup;
        } else {
            icon.innerHTML = '<svg class="spin-slow" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" width="44" height="44"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>';
        }
        label.textContent = 'Menunggu Pembayaran';
        sub.textContent = 'Selesaikan pembayaran sebelum batas waktu habis.';
        pill.textContent = 'MENUNGGU PEMBAYARAN';
        pill.style.background = 'rgba(245,158,11,.15)';
        pill.style.color = '#f59e0b';
    }

    updateDetailBadge(type, map);
    setProgress(map.step, type === 'success' && resultNote);
}

function updateDetailBadge(type, map) {
    const badge = document.getElementById('pdDetailStatusBadge');
    const dot = document.getElementById('pdDetailStatusDot');
    const text = document.getElementById('pdDetailStatusText');
    if (!badge || !text) return;
    badge.style.background = map.color + '20';
    badge.style.color = map.color;
    if (dot) dot.style.background = map.color;
    text.textContent = map.label;

    // ringkasan status (sisi kanan)
    const ringkasanText = document.getElementById('pdRingkasanStatusText');
    const ringkasanDot = document.querySelector('#pdRingkasanStatus .pd-dot');
    if (ringkasanText) {
        const nice = { pending: 'Pending', processing: 'Diproses', success: 'Success', failed: 'Gagal', expired: 'Expired', cancelled: 'Dibatalkan' };
        ringkasanText.textContent = nice[type] || type;
    }
    if (ringkasanDot) {
        const dotClass = { pending: 'pending', processing: 'processing', success: 'success', failed: 'failed', expired: 'failed', cancelled: 'failed' };
        ringkasanDot.className = 'pd-dot ' + (dotClass[type] || 'pending');
    }
}

/* progres horizontal 4 tahap: step 1..4 */
function setProgress(activeStep, withSn) {
    const steps = ['pdStepA', 'pdStepB', 'pdStepC', 'pdStepD'];
    const note = document.getElementById('pdProgressNote');

    if (note) {
        note.style.display = (withSn && activeStep >= 4) ? 'flex' : 'none';
        if (withSn) {
            const t = document.getElementById('pdProgressNoteText');
            if (t) t.textContent = 'Serial Number: ' + withSn;
        }
    }

    steps.forEach(function(id, i) {
        const el = document.getElementById(id);
        if (!el) return;
        const stepNum = i + 1;
        if (activeStep >= 4 && stepNum <= 4) {
            el.setAttribute('data-state', 'done');
        } else if (stepNum < activeStep) {
            el.setAttribute('data-state', 'done');
        } else if (stepNum === activeStep) {
            el.setAttribute('data-state', 'active');
        } else {
            el.setAttribute('data-state', 'pending');
        }
    });
}

let pollingStarted = false;
let resultNote = @json($order->note);

if (payBtn) {
    payBtn.addEventListener('click', startPayment);
}

/* render status awal */
const initialStatus = @json($order->status);
updateStatus(initialStatus, resultNote);

/* ===== countdown ===== */
(function() {
    const gatewayExpiry = @json(data_get($order->gateway_extra, 'expired_date'));
    const defaultExpiresAt = @json($order->created_at->copy()->addHour()->getTimestamp() * 1000);
    const parsedGatewayExpiry = gatewayExpiry ? Date.parse(gatewayExpiry) : NaN;
    const expiresAt = Number.isFinite(parsedGatewayExpiry) ? parsedGatewayExpiry : defaultExpiresAt;

    function tick() {
        const diff = Math.max(0, expiresAt - Date.now());
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        document.getElementById('countHours').textContent = String(h).padStart(2, '0');
        document.getElementById('countMinutes').textContent = String(m).padStart(2, '0');
        document.getElementById('countSeconds').textContent = String(s).padStart(2, '0');

        if (diff <= 0 && document.getElementById('pdCountdown').style.display !== 'none') {
            document.getElementById('pdStatusSub').textContent = 'Batas waktu tercapai. Menunggu konfirmasi status pembayaran.';
        }
    }
    tick();
    setInterval(tick, 1000);
})();

/* ===== polling status (aktif saat bukan demo murni, termasuk mode simulasi) ===== */
function startPolling() {
    if (pollingStarted) return;
    pollingStarted = true;
    (function check() {
        fetch(statusUrl, {cache: 'no-store', headers: {'Accept': 'application/json'}})
            .then(r => { if (!r.ok) throw new Error('Status unavailable'); return r.json(); })
            .then(data => {
                const s = data.status;
                document.dispatchEvent(new CustomEvent('payment:updated', {detail: data}));
                if (s === 'success' || s === 'processing' || s === 'failed' || s === 'expired' || s === 'cancelled') {
                    if (data.note && s === 'success') resultNote = data.note;
                    updateStatus(s);
                    if (s !== 'processing') return;
                }
                setTimeout(check, document.hidden ? 15000 : 3000);
            })
            .catch(() => {
                document.dispatchEvent(new Event('payment:offline'));
                setTimeout(check, 5000);
            });
    })();
}

if (!isDemo || isSimulation) {
    startPolling();
}

/* ===== copy button ===== */
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.pd-copy-btn');
    if (!btn) return;
    const text = btn.dataset.copy;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Tersalin!';
        btn.style.background = 'rgba(16,185,129,.2)';
        btn.style.color = '#10b981';
        setTimeout(() => {
            btn.textContent = orig;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    });
});

/* ===== review submit ===== */
const reviewForm = document.getElementById('reviewForm');
const reviewCloseBtn = document.getElementById('reviewClose');
const reviewOverlay = document.getElementById('reviewModal');

if (reviewOverlay) {
    reviewOverlay.addEventListener('click', function(e) {
        if (e.target === reviewOverlay) closeReviewModal();
    });
}
if (reviewCloseBtn) reviewCloseBtn.addEventListener('click', closeReviewModal);

if (reviewForm) {
    // pilih bintang
    reviewForm.querySelectorAll('.pd-stars button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const val = parseInt(btn.dataset.value, 10);
            reviewForm.querySelectorAll('.pd-stars button').forEach(function(b) {
                const on = parseInt(b.dataset.value, 10) <= val;
                b.classList.toggle('on', on);
            });
            reviewForm.querySelector('input[name="rating"]').value = val;
        });
    });

    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const rating = reviewForm.querySelector('input[name="rating"]').value;
        const msg = document.getElementById('reviewMsg');
        const btn = reviewForm.querySelector('button[type="submit"]');
        if (!rating) {
            if (msg) { msg.textContent = 'Pilih rating bintang dulu ya.'; msg.style.color = '#ef4444'; }
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Mengirim...';
        fetch(reviewUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ rating: rating, comment: reviewForm.querySelector('textarea[name="comment"]').value }),
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (msg) { msg.textContent = data.message; msg.style.color = '#10b981'; }
                    setTimeout(closeReviewModal, 1400);
                } else {
                    if (msg) {
                        msg.textContent = (data.errors && (data.errors.rating || data.errors.order || data.errors.comment || []).join(' ')) || 'Gagal mengirim ulasan.';
                        msg.style.color = '#ef4444';
                    }
                    btn.disabled = false;
                    btn.textContent = 'Kirim Ulasan';
                }
            })
            .catch(function() {
                if (msg) { msg.textContent = 'Terjadi kesalahan. Coba lagi.'; msg.style.color = '#ef4444'; }
                btn.disabled = false;
                btn.textContent = 'Kirim Ulasan';
            });
    });
}

/* theme-aware payment logo swap */
function swapPaymentLogos() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    document.querySelectorAll('.pd-detail-paylogo[data-light], .pd-method-result-icon img[data-light]').forEach(img => {
        if (isLight) {
            img.dataset.dark = img.src;
            img.src = img.dataset.light;
        } else if (img.dataset.dark) {
            img.src = img.dataset.dark;
        }
    });
}
swapPaymentLogos();
document.addEventListener('themeChanged', swapPaymentLogos);

})();
</script>
@endpush
