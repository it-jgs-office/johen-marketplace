{{-- Splash: gate + critical CSS. Wajib inline (tidak lewat topup.css) supaya
     overlay tetap tampil walau stylesheet eksternal masih diunduh / cached. --}}
<script>
(function () {
    'use strict';

    var root = document.documentElement;

    // Tema ditulis lebih awal supaya splash tidak berkedip gelap lalu melompat
    // ke terang. Nilai defaultnya sama dengan script toggle di footer layout.
    try {
        var saved = localStorage.getItem('theme');
        root.setAttribute('data-theme', (saved === 'light' || saved === 'dark') ? saved : 'dark');
    } catch (e) {
        root.setAttribute('data-theme', 'dark');
    }

    var KEY = 'jhm_splash_seen';
    var param = new URLSearchParams(window.location.search).get('splash');

    // Ditampilkan sekali per sesi browser - termasuk saat aplikasi PWA dibuka
    // dalam mode standalone, karena di sana tetap tidak ada splash lain yang
    // menutupi layar putih sebelum stylesheet selesai dimuat.
    var show;
    if (param === '0') {
        show = false;
    } else if (param === '1') {
        show = true;
    } else {
        try { show = sessionStorage.getItem(KEY) !== '1'; } catch (e) { show = true; }
    }

    root.setAttribute('data-splash', show ? 'on' : 'off');
})();
</script>
<style>
#jhm-splash {
    --jhm-bg: #061a31;
    --jhm-text: #f4f8fc;
    --jhm-muted: #8ea4bb;
    --jhm-track: rgba(142, 164, 187, .22);
    --jhm-accent: #bfdbfe;
    position: fixed;
    inset: 0;
    z-index: 2147483000;
    display: grid;
    place-items: center;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .28s ease, visibility .28s ease;
    font-family: 'Sora', 'Inter', system-ui, sans-serif;
    background: var(--jhm-bg);
}

#jhm-splash *,
#jhm-splash *::before,
#jhm-splash *::after { box-sizing: border-box; }

html[data-splash="on"] #jhm-splash { opacity: 1; visibility: visible; pointer-events: auto; }

/* Halaman tidak boleh ter-scroll selama splash tampil. */
html[data-splash="on"] body { overflow: hidden; }

.jhm-stack {
    width: min(320px, calc(100vw - 48px));
    display: grid;
    justify-items: center;
    gap: 0;
    text-align: center;
}

.jhm-stage {
    width: 88px;
    height: 88px;
    display: grid;
    place-items: center;
    animation: jhm-enter .42s cubic-bezier(.22, 1, .36, 1) both;
}

.jhm-logo {
    width: 76px;
    height: 76px;
    object-fit: contain;
    filter: drop-shadow(0 10px 22px rgba(0, 0, 0, .22));
}

.jhm-copy {
    display: grid;
    justify-items: center;
    gap: 6px;
    margin-top: 18px;
    animation: jhm-enter .42s cubic-bezier(.22, 1, .36, 1) .08s both;
}

.jhm-title {
    max-width: 280px;
    color: var(--jhm-text);
    font-size: 1.02rem;
    font-weight: 700;
    letter-spacing: .025em;
    line-height: 1.35;
}

.jhm-sub {
    color: var(--jhm-muted);
    font-family: 'Inter', system-ui, sans-serif;
    font-size: .73rem;
    font-weight: 500;
    letter-spacing: .01em;
    line-height: 1.4;
}

.jhm-bar {
    width: 148px;
    height: 2px;
    margin-top: 24px;
    border-radius: 999px;
    overflow: hidden;
    background: var(--jhm-track);
    animation: jhm-enter .42s cubic-bezier(.22, 1, .36, 1) .14s both;
}

.jhm-bar::after {
    content: '';
    display: block;
    width: 46%;
    height: 100%;
    border-radius: 999px;
    background: var(--jhm-accent);
    animation: jhm-progress 1.05s cubic-bezier(.65, 0, .35, 1) infinite;
}

@keyframes jhm-progress {
    0% { transform: translateX(-115%); }
    100% { transform: translateX(335%); }
}

@keyframes jhm-enter {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}

[data-theme="light"] #jhm-splash {
    --jhm-bg: #f4f7fb;
    --jhm-text: #10243a;
    --jhm-muted: #657a90;
    --jhm-track: rgba(101, 122, 144, .20);
    --jhm-accent: #60a5fa;
}

[data-theme="light"] .jhm-logo { filter: drop-shadow(0 10px 20px rgba(16, 36, 58, .12)); }

@media (max-width: 480px) {
    .jhm-stage { width: 80px; height: 80px; }
    .jhm-logo { width: 70px; height: 70px; }
    .jhm-copy { margin-top: 16px; }
}

@media (prefers-reduced-motion: reduce) {
    .jhm-stage, .jhm-copy, .jhm-bar, .jhm-bar::after { animation: none; }
    .jhm-bar::after { width: 46%; transform: none; }
}
</style>
