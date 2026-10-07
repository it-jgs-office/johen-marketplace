<meta name="theme-color" content="#01203c">
<meta name="application-name" content="Johen Gaming Marketplace">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Johen Marketplace">
{{-- Stamp build PWA. Dipakai pwa-register.js sebagai query service worker
     supaya setiap deploy menghasilkan URL worker yang baru. --}}
<meta name="pwa-build" content="{{ pwa_build() }}">
<link rel="manifest" href="{{ pwa_asset('site.webmanifest') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ pwa_asset('logo-180.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ pwa_asset('logo-192.png') }}">
{{-- Layar pembuka (logo beranimasi + progress bar) dibuat oleh
     partials/splash: CSS-nya inline di head supaya tampil sebelum
     stylesheet eksternal selesai dimuat, termasuk di mode standalone. --}}
<script src="{{ pwa_asset('js/pwa-register.js') }}" defer></script>
