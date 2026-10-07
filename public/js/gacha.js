/* Gacha Voucher Widget - roda undian + terbitan kode voucher */
(function () {
  'use strict';

  var URLS = window.GACHA_URLS || {};
  var USER = window.GACHA_USER || null;
  var SEEN_KEY = 'gacha_seen_v1';

  var overlay = document.getElementById('gc-overlay');
  var modal = document.getElementById('gc-modal');
  var fab = document.getElementById('gc-fab');
  var wheel = document.getElementById('gcWheel');
  var hub = document.getElementById('gcHub');
  var statusEl = document.getElementById('gcStatus');
  var resultEl = document.getElementById('gcResult');
  var resultLabel = document.getElementById('gcResultLabel');
  var resultCode = document.getElementById('gcResultCode');
  var resultMeta = document.getElementById('gcResultMeta');
  var copyBtn = document.getElementById('gcResultCopy');
  var prizesEl = document.getElementById('gcPrizes');
  var voucherLink = document.getElementById('gcVoucherLink');
  var fabDot = document.getElementById('gcFabDot');

  if (!overlay || !modal || !fab || !wheel || !hub) return;

  var state = { isOpen: false, prizes: [], remaining: 0, spinning: false, rotation: 0, loaded: false, lastPrizeId: null };
  var toast = null;
  var toastTimer = null;

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function showToast(message, isError) {
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'gc-toast';
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.className = 'gc-toast show' + (isError ? ' error' : '');
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toast.className = 'gc-toast' + (isError ? ' error' : '');
    }, 3200);
  }

  function rupiah(value) {
    return 'Rp ' + Math.round(value || 0).toLocaleString('id-ID');
  }

  function csrfFetch(url) {
    return fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      credentials: 'same-origin'
    });
  }

  function postSpin() {
    return fetch(URLS.spin, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      credentials: 'same-origin',
      body: JSON.stringify({})
    }).then(function (res) {
      return res.json().then(function (data) {
        return { ok: res.ok, data: data };
      });
    });
  }

  /* ---------------- Roda ---------------- */

  // Sudut dihitung searah jarum jam mulai dari posisi 12 o'clock.
  function polar(cx, cy, r, deg) {
    var rad = (deg - 90) * Math.PI / 180;
    return { x: (cx + r * Math.cos(rad)).toFixed(2), y: (cy + r * Math.sin(rad)).toFixed(2) };
  }

  function svgEl(name, attrs) {
    var el = document.createElementNS('http://www.w3.org/2000/svg', name);
    for (var key in attrs) {
      if (Object.prototype.hasOwnProperty.call(attrs, key)) el.setAttribute(key, attrs[key]);
    }
    return el;
  }

  function shorten(text, max) {
    var str = String(text || '');
    return str.length > max ? str.slice(0, max - 1) + '\u2026' : str;
  }

  // Terangkan / gelapkan warna hex (#rgb atau #rrggbb).
  function shade(hex, percent) {
    var raw = String(hex || '#7c3aed').replace('#', '').trim();
    if (raw.length === 3) raw = raw.replace(/./g, function (c) { return c + c; });
    if (!/^[0-9a-f]{6}$/i.test(raw)) return '#7c3aed';
    var n = parseInt(raw, 16);
    var amt = Math.min(100, Math.abs(percent));
    var t = percent < 0 ? 0 : 255;
    var r = Math.round(((n >> 16) & 255) + (t - ((n >> 16) & 255)) * amt / 100);
    var g = Math.round(((n >> 8) & 255) + (t - ((n >> 8) & 255)) * amt / 100);
    var b = Math.round((n & 255) + (t - (n & 255)) * amt / 100);
    return '#' + [r, g, b].map(function (v) {
      return (v < 16 ? '0' : '') + v.toString(16);
    }).join('');
  }

  // Pecah label jadi 1-2 baris sesuai lebar sektor supaya teks tetap terbaca.
  function labelLines(label, span) {
    var words = String(label || 'Hadiah').trim().split(/\s+/);
    var max = span >= 46 ? 13 : span >= 32 ? 10 : 7;

    if (words.length === 1) return [shorten(words[0], max + 3)];
    if (span >= 46) return [shorten(words.join(' '), max + 4)];

    if (words.length === 2) {
      return [shorten(words[0], max - 1), shorten(words[1], max - 1)];
    }

    var mid = Math.ceil(words.length / 2);
    return [
      shorten(words.slice(0, mid).join(' '), max),
      shorten(words.slice(mid).join(' '), max),
    ];
  }

  function fontSizeFor(span, lines, rLabel) {
    var arc = 2 * rLabel * Math.sin(span * Math.PI / 360);
    var longest = Math.max.apply(null, lines.map(function (l) { return l.length; }));
    var byArc = arc / (longest * 0.62 + 1);
    var base = span >= 40 ? 14 : span >= 28 ? 12.5 : span >= 18 ? 11 : 9.5;

    return Math.max(8.5, Math.min(base, byArc));
  }

  function renderWheel() {
    var prizes = state.prizes;
    var cx = 150;
    var cy = 150;
    var r = 146;

    while (wheel.firstChild) wheel.removeChild(wheel.firstChild);
    if (!prizes.length) return;

    var total = prizes.reduce(function (sum, p) { return sum + Math.max(1, p.weight); }, 0);
    var defs = svgEl('defs');
    var cursor = 0;

    // Sektor dengan gradien dari pusat ke tepi (lebih terang di tengah roda).
    prizes.forEach(function (prize) {
      var span = (Math.max(1, prize.weight) / total) * 360;
      var start = cursor;
      var end = cursor + span;
      cursor = end;

      var mid = start + span / 2;
      var midPt = polar(cx, cy, r, mid);
      var id = 'gcG' + prize.id;
      var grad = svgEl('linearGradient', {
        id: id,
        x1: cx, y1: cy, x2: midPt.x, y2: midPt.y,
        gradientUnits: 'userSpaceOnUse'
      });
      grad.appendChild(svgEl('stop', { offset: '0%', 'stop-color': shade(prize.color, 42) }));
      grad.appendChild(svgEl('stop', { offset: '100%', 'stop-color': shade(prize.color, -22) }));
      defs.appendChild(grad);

      var p1 = polar(cx, cy, r, start);
      var p2 = polar(cx, cy, r, end);
      var largeArc = span > 180 ? 1 : 0;

      wheel.appendChild(svgEl('path', {
        d: 'M' + cx + ' ' + cy + ' L' + p1.x + ' ' + p1.y +
           ' A' + r + ' ' + r + ' 0 ' + largeArc + ' 1 ' + p2.x + ' ' + p2.y + ' Z',
        fill: 'url(#' + id + ')',
        stroke: 'rgba(0,0,0,.2)',
        'stroke-width': 1
      }));
    });

    // Garis pemisah putih di atas sektor tapi di bawah label.
    cursor = 0;
    prizes.forEach(function (prize) {
      var span = (Math.max(1, prize.weight) / total) * 360;
      var start = cursor;
      cursor += span;
      var p1 = polar(cx, cy, r, start);
      wheel.appendChild(svgEl('line', {
        x1: cx, y1: cy, x2: p1.x, y2: p1.y,
        stroke: 'rgba(255,255,255,.4)',
        'stroke-width': 1.5
      }));
    });

    // Label radial 1-2 baris; ukuran teks menyesuaikan lebar sektor.
    cursor = 0;
    prizes.forEach(function (prize) {
      var span = (Math.max(1, prize.weight) / total) * 360;
      var start = cursor;
      cursor += span;

      if (span < 11) return;

      var mid = start + span / 2;
      var lines = labelLines(prize.label, span);
      var rLabel = r * (lines.length > 1 ? 0.74 : 0.62);
      var pt = polar(cx, cy, rLabel, mid);
      var fontSize = fontSizeFor(span, lines, rLabel);
      var lineHeight = fontSize + 2;

      var text = svgEl('text', {
        x: pt.x,
        y: pt.y,
        class: 'gc-wheel-label',
        'text-anchor': 'middle',
        'dominant-baseline': 'central',
        transform: 'rotate(' + mid.toFixed(2) + ' ' + pt.x + ' ' + pt.y + ')'
      });
      text.style.fontSize = fontSize.toFixed(1) + 'px';

      lines.forEach(function (line, i) {
        var dy = i === 0 ? -(lines.length - 1) * lineHeight / 2 : lineHeight;
        var tspan = svgEl('tspan', { x: pt.x, dy: dy });
        tspan.textContent = line;
        text.appendChild(tspan);
      });

      wheel.appendChild(text);
    });

    wheel.appendChild(defs);

    // Tepi roda: ring + lampu sorot.
    wheel.appendChild(svgEl('circle', {
      cx: cx, cy: cy, r: r,
      fill: 'none', stroke: 'rgba(0,0,0,.25)', 'stroke-width': 2
    }));
    wheel.appendChild(svgEl('circle', {
      cx: cx, cy: cy, r: r - 5,
      fill: 'none', stroke: 'rgba(251,191,36,.85)', 'stroke-width': 2
    }));
    for (var i = 0; i < 12; i++) {
      var bulb = polar(cx, cy, r - 2.5, i * 30);
      wheel.appendChild(svgEl('circle', {
        cx: bulb.x, cy: bulb.y, r: 2.4,
        fill: '#fbbf24', stroke: 'rgba(0,0,0,.25)', 'stroke-width': .6
      }));
    }
  }

  function renderPrizes() {
    prizesEl.innerHTML = '';
    if (!state.prizes.length) {
      var empty = document.createElement('div');
      empty.className = 'gc-prize';
      empty.textContent = 'Belum ada hadiah tersedia.';
      prizesEl.appendChild(empty);
      return;
    }

    state.prizes.forEach(function (prize) {
      var row = document.createElement('div');
      row.className = 'gc-prize';

      var swatch = document.createElement('span');
      swatch.className = 'gc-prize-swatch';
      swatch.style.background = prize.color || '#7c3aed';

      var name = document.createElement('span');
      name.className = 'gc-prize-name';
      name.textContent = prize.label;
      name.title = prize.remaining === null
        ? prize.label
        : prize.label + ' (sisa ' + prize.remaining + ')';

      row.appendChild(swatch);
      row.appendChild(name);
      prizesEl.appendChild(row);
    });
  }

  function sectorOf(prizeId) {
    var prizes = state.prizes;
    var total = prizes.reduce(function (sum, p) { return sum + Math.max(1, p.weight); }, 0);
    var cursor = 0;

    for (var i = 0; i < prizes.length; i++) {
      var span = (Math.max(1, prizes[i].weight) / total) * 360;
      if (prizes[i].id === prizeId) {
        return { start: cursor, span: span, center: cursor + span / 2 };
      }
      cursor += span;
    }

    return null;
  }

  /* ---------------- Tampilan ---------------- */

  function setStatus(message, warn) {
    statusEl.textContent = message;
    statusEl.className = 'gc-status' + (warn ? ' warn' : '');
  }

  function setHubState() {
    if (state.spinning) {
      hub.disabled = true;
      hub.textContent = 'MEMUTAR';
      return;
    }
    hub.disabled = state.remaining < 1 || !state.prizes.length;
    hub.textContent = state.remaining > 0 ? 'PUTAR' : 'JADI LAGI';
  }

  /* Countdown sampai giliran baru (lewat tengah malam) */
  var countdownTimer = null;

  function pad2(n) { return (n < 10 ? '0' : '') + n; }

  function updateCountdown() {
    var now = new Date();
    var midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
    var ms = Math.max(0, midnight.getTime() - now.getTime());
    var h = pad2(Math.floor(ms / 3600000));
    var m = pad2(Math.floor((ms % 3600000) / 60000));
    var s = pad2(Math.floor((ms % 60000) / 1000));
    statusEl.textContent = 'Kamu sudah memutar hari ini. Chance baru tersedia dalam ' + h + ':' + m + ':' + s + '.';
    statusEl.className = 'gc-status warn';
  }

  function startCountdown() {
    stopCountdown();
    updateCountdown();
    countdownTimer = setInterval(updateCountdown, 1000);
  }

  function stopCountdown() {
    if (countdownTimer) {
      clearInterval(countdownTimer);
      countdownTimer = null;
    }
  }

  function open() {
    // Tombol FAB memiliki handler inline dan listener JavaScript. Guard ini
    // membuat satu klik tetap menjalankan pembukaan sekali saja.
    if (state.isOpen) return;
    state.isOpen = true;
    overlay.classList.add('active');
    modal.classList.add('active');
    overlay.setAttribute('aria-hidden', 'false');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    setHubState();
    if (!state.loaded) load();
    else if (state.remaining < 1) startCountdown();
  }

  function close() {
    state.isOpen = false;
    overlay.classList.remove('active');
    modal.classList.remove('active');
    overlay.setAttribute('aria-hidden', 'true');
    modal.setAttribute('aria-hidden', 'true');
    stopCountdown();
    if (!state.spinning) document.body.style.overflow = '';
  }

  function resetWheelRotation() {
    state.rotation = 0;
    wheel.style.transition = 'none';
    wheel.style.transform = 'rotate(0deg)';
    // Paksa reflow supaya transisi berikutnya benar-benar berjalan.
    void wheel.getBoundingClientRect();
    wheel.style.transition = '';
  }

  function showResult(data) {
    resultLabel.textContent = data.label || 'Voucher';
    resultCode.textContent = data.code || '-';
    resultCode.setAttribute('data-code', data.code || '');

    var meta = [];
    meta.push('Potongan ' + (data.value_label || '') + ' langsung terpotong di checkout.');
    if (data.min_spend > 0) meta.push('Minimal belanja ' + rupiah(data.min_spend) + '.');
    if (data.expires_at) {
      var until = new Date(data.expires_at);
      meta.push('Berlaku sampai ' + until.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + '.');
    }
    resultMeta.textContent = meta.join(' ');

    resultEl.hidden = false;
    try {
      localStorage.setItem(SEEN_KEY, '1');
    } catch (e) { /* storage penuh / dinonaktifkan */ }
    if (fabDot) fabDot.hidden = true;
  }

  function markSeenDot() {
    if (!fabDot) return;
    try {
      fabDot.hidden = !!localStorage.getItem(SEEN_KEY);
    } catch (e) {
      fabDot.hidden = true;
    }
  }

  function applyConfig(data) {
    state.prizes = data.prizes || [];
    state.remaining = typeof data.remaining === 'number' ? data.remaining : 0;
    state.loaded = true;

    resetWheelRotation();
    renderWheel();
    renderPrizes();
    setHubState();

    if (!data.enabled) {
      stopCountdown();
      setStatus('Gacha sedang tidak tersedia. Cek kembali nanti ya!', true);
    } else if (state.remaining < 1) {
      startCountdown();
    } else if (data.daily_limit > 1) {
      stopCountdown();
      setStatus('Sisa chances hari ini: ' + state.remaining + ' dari ' + data.daily_limit + '.', false);
    } else {
      stopCountdown();
      setStatus('Putar sekarang dan dapatkan voucher diskon!', false);
    }
  }

  function load() {
    setStatus('Memuat hadiah...', false);
    csrfFetch(URLS.config)
      .then(function (res) { return res.json(); })
      .then(applyConfig)
      .catch(function () {
        state.loaded = false;
        setStatus('Gagal memuat hadiah. Coba buka ulang widget ini.', true);
      });
  }

  function spin() {
    if (state.spinning || state.remaining < 1) return;

    if (!state.prizes.length) {
      setStatus('Hadiah belum siap. Memuat ulang...', true);
      state.loaded = false;
      load();
      return;
    }

    state.spinning = true;
    stopCountdown();
    setHubState();
    setStatus('Memutar...', false);
    resultEl.hidden = true;

    postSpin().then(function (res) {
      if (!res.ok || !res.data.success) {
        state.spinning = false;
        setStatus(res.data.message || 'Gagal memutar roda. Coba lagi.', true);
        setHubState();
        return;
      }

      var target = sectorOf(res.data.prize_id);
      if (!target) {
        // Hadiah yang keluar tidak ada di roda yang tampil: muat ulang agar
        // tampilan dan server kembali sinkron, lalu tampilkan kode tetap.
        state.loaded = false;
        state.spinning = false;
        load();
        showResult(res.data);
        return;
      }

      // Putar 5 putaran penuh lalu berhenti di pusat sektor pemenang, dengan
      // simpangan kecil supaya hasil tidak selalu tepat di tengah.
      var turns = 360 * 5;
      var jitter = (Math.random() - 0.5) * target.span * 0.5;
      var offset = (((-(target.center + jitter) - state.rotation) % 360) + 360) % 360;

      state.rotation += turns + offset;
      wheel.classList.add('spinning');
      wheel.style.transform = 'rotate(' + state.rotation + 'deg)';

      setTimeout(function () {
        wheel.classList.remove('spinning');
        state.lastPrizeId = res.data.prize_id;
        state.remaining = typeof res.data.remaining === 'number' ? res.data.remaining : 0;
        state.spinning = false;
        setHubState();
        showResult(res.data);
        setStatus('Voucher kamu siap dipakai. Jangan lupa pakai sebelum kedaluwarsa!', false);
      }, 4500);
    }).catch(function () {
      state.spinning = false;
      setHubState();
      setStatus('Terjadi kesalahan jaringan. Coba lagi.', true);
    });
  }

  function copyCode() {
    var code = resultCode.getAttribute('data-code');
    if (!code) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(code).then(function () {
        showToast('Kode voucher disalin: ' + code, false);
      }).catch(function () {
        showToast('Gagal menyalin otomatis. Catat kodenya manual ya.', true);
      });
      return;
    }

    showToast('Kode voucher: ' + code, false);
  }

  /* ---------------- Event ---------------- */

  // Browser/PWA dapat mengembalikan halaman dari back-forward cache dengan
  // class modal lama masih menempel. Pastikan navigasi reguler selalu mulai
  // dari keadaan tertutup, bukan mewarisi modal Spin Voucher sebelumnya.
  function closeForNavigation() {
    state.isOpen = false;
    state.spinning = false;
    state.loaded = false;
    overlay.classList.remove('active');
    modal.classList.remove('active');
    overlay.setAttribute('aria-hidden', 'true');
    modal.setAttribute('aria-hidden', 'true');
    stopCountdown();
    document.body.style.overflow = '';
  }

  fab.addEventListener('click', open);
  hub.addEventListener('click', spin);
  copyBtn.addEventListener('click', copyCode);
  overlay.addEventListener('click', close);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('active')) close();
  });
  window.addEventListener('pagehide', closeForNavigation);
  window.addEventListener('pageshow', closeForNavigation);

  if (voucherLink) {
    voucherLink.href = USER ? URLS.voucherPage : URLS.login;
    voucherLink.hidden = false;
  }

  markSeenDot();

  window.Gacha = { open: open, close: close, spin: spin };
})();
