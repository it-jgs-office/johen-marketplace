@php
  $promoPaymentLogos = [
    'qris' => payment_logo_asset('qris'),
    'dana' => payment_logo_asset('dana'),
    'ovo' => payment_logo_asset('ovo'),
    'linkaja' => payment_logo_asset('linkaja'),
    'bca' => payment_logo_asset('bca'),
    'bca_va' => payment_logo_asset('bca_va'),
    'bni' => payment_logo_asset('bni'),
    'bni_va' => payment_logo_asset('bni_va'),
    'mandiri' => payment_logo_asset('mandiri'),
    'mandiri_va' => payment_logo_asset('mandiri_va'),
    'permata' => payment_logo_asset('permata'),
    'permata_va' => payment_logo_asset('permata_va'),
    'alfamart' => payment_logo_asset('alfamart'),
    'indomaret' => payment_logo_asset('indomaret'),
  ];
@endphp

<div class="frontend-promo">
  <section class="cta-section" aria-labelledby="profileCtaTitle">
    <svg class="cta-wave" viewBox="0 0 1440 78" preserveAspectRatio="none" aria-hidden="true">
      <path d="M0 0H1440V34C1320 69 1200 2 1080 34S840 69 720 34 480 2 360 34 120 69 0 34V0Z" fill="var(--bg, #f3f4f6)"/>
    </svg>
    <div class="cta-card">
      <span class="cta-glow-2" aria-hidden="true"></span>
      <a href="https://www.johengaming.id" target="_blank" rel="noopener noreferrer" class="cta-logo-link">
        <img src="{{ pwa_asset('logo.png') }}" alt="Johen Gaming" class="cta-logo">
      </a>
      <h2 id="profileCtaTitle">Kunjungi Website Profile Kami</h2>
      <p>Dapatkan informasi lengkap tentang layanan, promo terbaru, dan update seputar Johen Gaming.</p>
      <a href="https://www.johengaming.id" target="_blank" rel="noopener noreferrer" class="cta-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Kunjungi johengaming.id
      </a>
    </div>
  </section>

  <section class="payment-marquee-section" aria-label="Metode pembayaran" hidden>
    <div class="payment-track-wrap">
      <div class="payment-track" id="paymentTrack"></div>
    </div>
  </section>
</div>
<script>window.STATIC_PAYMENT_LOGOS = @json($promoPaymentLogos);</script>
