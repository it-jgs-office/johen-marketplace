// ============ DATA ============

const TESTIMONIALS = [
  { name: 'User Free Fire', game: 'Top Up - Free Fire', avatar: '🙂', quote: 'Top up Diamond Free Fire di sini cepat banget. Setelah pembayaran berhasil, diamond langsung masuk ke akun tanpa perlu menunggu lama.' },
  { name: 'User Mobile Legends', game: 'Top Up - Mobile Legends', avatar: '😄', quote: 'Top up Diamond MLBB cuma beberapa menit langsung masuk. Harganya juga lebih murah dibanding tempat lain. Sudah langganan dari lama dan selalu aman.' },
  { name: 'User PUBG Mobile', game: 'Top Up - PUBG Mobile', avatar: '🎮', quote: 'Top up UC PUBG Mobile menit langsung masuk ke akun. Harganya bersaing, prosesnya cepat, dan sejauh ini tanpa kendala. Sudah beberapa kali top up di sini dan hasilnya selalu memuaskan.' },
  { name: 'User Valorant', game: 'Top Up - Valorant', avatar: '🎯', quote: 'Poin Valorant masuk instan setelah bayar QRIS. Prosesnya jelas dan ada notifikasi tiap tahap. Recommended buat yang males ribet.' },
  { name: 'User Genshin Impact', game: 'Top Up - Genshin Impact', avatar: '💎', quote: 'Genesis Crystal masuk kurang dari 5 menit. CS-nya responsif kalau ada kendala. Harga juga bersahabat buat dompet pelajar seperti saya.' },
];

const GRADIENTS = [
  'linear-gradient(160deg,#1e3a5f,#0f1c2e)',
  'linear-gradient(160deg,#3b2465,#1a1030)',
  'linear-gradient(160deg,#5c1f2e,#240d13)',
  'linear-gradient(160deg,#1f4d2e,#0d1f13)',
  'linear-gradient(160deg,#4a1f5c,#1a0d24)',
];

let selectedBrand = null;
let selectedNominal = null;
let selectedPay = null;
let paymentMethods = [];

// ============ HEADER SCROLL ============
const header = document.getElementById('siteHeader');
if (header) {
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 12);
  });
}

// ============ MOBILE MENU ============
const hamburgerBtn = document.getElementById('hamburgerBtn');
const mobileMenu = document.getElementById('mobileMenu');
if (hamburgerBtn && mobileMenu) {
  hamburgerBtn.addEventListener('click', () => {
    hamburgerBtn.classList.toggle('open');
    mobileMenu.classList.toggle('open');
  });
  mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    hamburgerBtn.classList.remove('open');
    mobileMenu.classList.remove('open');
  }));
}

// ============ AUTH DROPDOWN ============
document.querySelectorAll('.auth-dropdown-toggle').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const menu = btn.nextElementSibling;
    if (menu) menu.classList.toggle('show');
  });
});
document.addEventListener('click', () => {
  document.querySelectorAll('.auth-dropdown-menu').forEach(m => m.classList.remove('show'));
});

// ============ TABS ============
const featuredGrid = document.querySelector('.featured-grid-section');
const sectionHeading = document.querySelector('.section-heading');

document.querySelectorAll('.tab-pill').forEach(tab => {
  tab.addEventListener('click', () => {
    const filter = tab.dataset.filter;

    if (filter === 'check') {
      window.location.href = '/cek-transaksi';
      return;
    }

    document.querySelectorAll('.tab-pill').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    filterGames(filter);
  });
});

// ============ FILTER GAMES ============
function filterGames(filter) {
  const cards = document.querySelectorAll('.game-card');
  const container = document.getElementById('gamesGrid');
  const loadMoreWrap = document.getElementById('loadMoreWrap');

  if (filter === 'all') {
    if (featuredGrid) featuredGrid.style.display = '';
    if (sectionHeading) sectionHeading.style.display = '';
    if (loadMoreWrap) loadMoreWrap.style.display = '';
    if (window.__loadMoreReset) window.__loadMoreReset();
    if (container) container.style.justifyContent = '';
    return;
  }

  if (filter === 'joki') {
    if (featuredGrid) featuredGrid.style.display = 'none';
    if (sectionHeading) sectionHeading.style.display = 'none';
    if (loadMoreWrap) loadMoreWrap.style.display = 'none';

    let found = false;
    cards.forEach(card => {
      const brand = card.dataset.brand;
      if (brand === 'Mobile Legends') {
        card.style.display = '';
        found = true;
      } else {
        card.style.display = 'none';
      }
    });

    if (container) container.style.justifyContent = 'center';

    if (found && cards.length) {
      const mlCard = [...cards].find(c => c.dataset.brand === 'Mobile Legends');
      if (mlCard) mlCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
}

// ============ FETCH PRODUCTS ============
async function fetchProducts(brand) {
  try {
    const res = await fetch(`/api/products?brand=${encodeURIComponent(brand)}`);
    return await res.json();
  } catch (e) {
    return [];
  }
}

async function fetchPaymentMethods() {
  try {
    const res = await fetch('/api/payment-methods');
    return await res.json();
  } catch (e) {
    return [];
  }
}

// ============ SEARCH SUGGESTIONS ============
const searchInput = document.getElementById('searchInput');
const searchSuggest = document.getElementById('searchSuggest');

function bindSearch(input, suggestBox) {
  if (!input) return;
  let debounceTimer;
  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
      const q = input.value.trim().toLowerCase();
      if (!q) { if (suggestBox) suggestBox.classList.remove('show'); return; }
      try {
        const res = await fetch(`/api/brands/search?q=${encodeURIComponent(q)}`);
        const matches = await res.json();
        if (suggestBox) {
          if (matches.length) {
            suggestBox.innerHTML = matches.map(m =>
              `<div class="search-suggest-item" data-brand="${m.brand}"><img class="search-suggest-thumb" src="${m.thumbnail_url || ''}" alt="" ${!m.thumbnail_url ? 'style=display:none' : ''} onerror="this.style.display='none'"> <span class="search-suggest-icon" ${m.thumbnail_url ? 'style=display:none' : ''}>${m.icon || '🎮'}</span> ${m.brand}</div>`
            ).join('');
          } else {
            suggestBox.innerHTML = `<div class="search-suggest-empty">Tidak ada game ditemukan untuk "${q}"</div>`;
          }
          suggestBox.classList.add('show');
          suggestBox.querySelectorAll('.search-suggest-item').forEach(item => {
            item.addEventListener('click', () => {
              const brand = item.dataset.brand;
              suggestBox.classList.remove('show');
              input.value = '';
              window.location.href = '/games/' + encodeURIComponent(brand);
            });
          });
        }
      } catch (e) {
        if (suggestBox) suggestBox.classList.remove('show');
      }
    }, 200);
  });
}
bindSearch(searchInput, searchSuggest);
bindSearch(document.getElementById('mobileSearchInput'), null);
document.addEventListener('click', (e) => {
  if (searchSuggest && !e.target.closest('.search-wrap') && !e.target.closest('.mobile-search-wrap')) {
    searchSuggest.classList.remove('show');
  }
});

function getBrandIcon(brand) {
  const card = document.querySelector(`.game-card[data-brand="${brand}"]`);
  return card ? (card.dataset.icon || '🎮') : '🎮';
}
function getBrandCategory(brand) {
  const card = document.querySelector(`.game-card[data-brand="${brand}"]`);
  return card ? (card.dataset.category || 'other') : 'other';
}

// ============ MODAL SYSTEM ============
function openModal(id) {
  document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  const el = document.getElementById(id);
  if (el) {
    el.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}
function closeAllModals() {
  document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
  document.body.style.overflow = '';
}
document.querySelectorAll('[data-open-modal]').forEach(btn => {
  btn.addEventListener('click', () => openModal(btn.dataset.openModal));
});
document.querySelectorAll('[data-close-modal]').forEach(btn => {
  btn.addEventListener('click', closeAllModals);
});
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => { if (e.target === overlay) closeAllModals(); });
});
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAllModals(); });

// ============ TOPUP MODAL ============
const nominalGrid = document.getElementById('nominalGrid');
const paySelectGrid = document.getElementById('paySelectGrid');
const topupTotal = document.getElementById('topupTotal');

async function openTopupModal(brand) {
  selectedBrand = brand;
  selectedNominal = null;
  selectedPay = null;
  if (topupTotal) topupTotal.textContent = 'Rp 0';

  const nameEl = document.getElementById('topupGameName');
  const iconEl = document.getElementById('topupIcon');
  if (nameEl) nameEl.textContent = brand;
  if (iconEl) iconEl.textContent = getBrandIcon(brand);

  // Tampilkan field Zone ID hanya untuk game yang membutuhkannya
  const zoneLabel = document.getElementById('zoneIdLabel');
  if (zoneLabel) {
    const needsZone = (window.ZONE_BRANDS || []).some(b => b.toLowerCase() === String(brand).toLowerCase());
    zoneLabel.style.display = needsZone ? '' : 'none';
    const zoneInput = zoneLabel.querySelector('input[name="zone_id"]');
    if (zoneInput) { zoneInput.value = ''; zoneInput.required = needsZone; }
  }

  const cat = getBrandCategory(brand);
  const label = cat === 'moba' ? 'Diamond' : cat === 'fps' ? 'Poin' : cat === 'br' ? 'UC' : 'Item';

  if (nominalGrid) {
    nominalGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-mute);padding:1rem;font-size:.85rem;">Memuat...</div>';
  }
  if (paySelectGrid) {
    paySelectGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-mute);padding:1rem;font-size:.85rem;">Memuat...</div>';
  }

  const [products, payments] = await Promise.all([
    fetchProducts(brand),
    fetchPaymentMethods()
  ]);

  paymentMethods = payments;

  if (nominalGrid) {
    if (products.length) {
      nominalGrid.innerHTML = products.map((p, i) => `
        <div class="nominal-opt" data-idx="${i}" data-product='${JSON.stringify(p).replace(/'/g, "&#39;")}'>
          ${p.product_name}
          <span class="nominal-price">Rp ${Number(p.selling_price).toLocaleString('id-ID')}</span>
        </div>`).join('');
      nominalGrid.querySelectorAll('.nominal-opt').forEach(opt => {
        opt.addEventListener('click', () => {
          nominalGrid.querySelectorAll('.nominal-opt').forEach(o => o.classList.remove('selected'));
          opt.classList.add('selected');
          const raw = opt.dataset.product;
          try {
            selectedNominal = JSON.parse(raw.replace(/&#39;/g, "'"));
          } catch (e) {
            selectedNominal = { selling_price: 0 };
          }
          updateTotal();
        });
      });
    } else {
      nominalGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-mute);padding:1rem;font-size:.85rem;">Tidak ada produk tersedia</div>';
    }
  }

  if (paySelectGrid) {
    if (payments.length) {
      paySelectGrid.innerHTML = payments.map(p => {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        const photo = (isLight && p.photo_light_url) ? p.photo_light_url : (p.photo_url || '');
        const icon = p.icon || '';
        const content = photo ? `<img src="${photo}" alt="${p.name}" class="pay-opt-img">` : (icon ? `<span class="pay-opt-icon">${icon}</span><span class="pay-opt-name">${p.name}</span>` : p.name);
        return `<div class="pay-opt" data-pay="${p.name}">${content}</div>`;
      }).join('');
      paySelectGrid.querySelectorAll('.pay-opt').forEach(opt => {
        opt.addEventListener('click', () => {
          paySelectGrid.querySelectorAll('.pay-opt').forEach(o => o.classList.remove('selected'));
          opt.classList.add('selected');
          selectedPay = opt.dataset.pay;
        });
      });
    } else {
      paySelectGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-mute);padding:1rem;font-size:.85rem;">Metode pembayaran tidak tersedia</div>';
    }
  }

  openModal('topupModal');
}
function updateTotal() {
  if (topupTotal) {
    topupTotal.textContent = selectedNominal ? 'Rp ' + Number(selectedNominal.selling_price).toLocaleString('id-ID') : 'Rp 0';
  }
}

document.getElementById('topupForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!selectedNominal) { showToast('Pilih nominal top up terlebih dahulu', true); return; }
  if (!selectedPay) { showToast('Pilih metode pembayaran terlebih dahulu', true); return; }

  const zoneInput = e.target.querySelector('input[name="zone_id"]');
  if (zoneInput && zoneInput.required && !zoneInput.value.trim()) {
    showToast('Zone ID wajib diisi untuk game ini', true);
    zoneInput.focus();
    return;
  }
  if (zoneInput && zoneInput.value.trim() && !/^[A-Za-z0-9]+$/.test(zoneInput.value.trim())) {
    showToast('Zone ID hanya boleh huruf dan angka', true);
    zoneInput.focus();
    return;
  }

  const form = e.target;
  const formData = new FormData(form);
  formData.append('product_id', selectedNominal.id || '');
  formData.append('payment_method', selectedPay);

  try {
    const res = await fetch('/orders', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
      body: formData
    });
    const data = await res.json();
    if (data.redirect) {
      window.location.href = data.redirect;
    } else if (data.success) {
      closeAllModals();
      showToast('Pesanan berhasil dibuat!');
    } else {
      showToast(data.message || 'Gagal membuat pesanan', true);
    }
  } catch (err) {
    showToast('Terjadi kesalahan. Silakan coba lagi.', true);
  }
});

// ============ TESTIMONIALS CAROUSEL ============
const testiTrack = document.getElementById('testiTrack');
const testiDots = document.getElementById('testiDots');
let testiCurrent = 0;
let testiTimer = null;
const TC = TESTIMONIALS.length;

function createTestiCard(t) {
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

function getTestiOffset(i) {
  const cw = testiTrack.parentElement.getBoundingClientRect().width;
  const card = testiTrack.children[i + 1];
  return card.offsetLeft - (cw - card.offsetWidth) / 2;
}

function applyTestiCenter(i) {
  testiTrack.querySelectorAll('.testi-card').forEach((c, idx) => c.classList.toggle('center', idx === i + 1));
  if (testiDots) Array.from(testiDots.children).forEach((d, idx) => d.classList.toggle('active', idx === i));
}

if (testiTrack) {
  TESTIMONIALS.forEach(t => testiTrack.appendChild(createTestiCard(t)));
  const clones = Array.from(testiTrack.children);
  testiTrack.appendChild(clones[0].cloneNode(true));
  testiTrack.insertBefore(clones[TC - 1].cloneNode(true), testiTrack.firstChild);

  testiTrack.style.transition = 'none';
  testiTrack.style.transform = `translateX(${-getTestiOffset(0)}px)`;
  void testiTrack.offsetHeight;
  testiTrack.style.transition = '';
  applyTestiCenter(0);

  if (testiDots) {
    TESTIMONIALS.forEach((_, i) => {
      const dot = document.createElement('span');
      dot.className = 'dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', () => goTesti(i));
      testiDots.appendChild(dot);
    });
  }

  function goTesti(i, instant = false) {
    if (i === testiCurrent) return;
    testiCurrent = i;
    const x = -getTestiOffset(i);
    if (instant) testiTrack.style.transition = 'none';
    testiTrack.style.transform = `translateX(${x}px)`;
    if (instant) { void testiTrack.offsetHeight; testiTrack.style.transition = ''; }
    applyTestiCenter(i);
    resetTestiTimer();
  }

  testiTrack.addEventListener('transitionend', () => {
    if (testiCurrent === -1) {
      testiTrack.style.transition = 'none';
      testiTrack.style.transform = `translateX(${-getTestiOffset(TC - 1)}px)`;
      testiCurrent = TC - 1;
      void testiTrack.offsetHeight;
      testiTrack.style.transition = '';
      applyTestiCenter(TC - 1);
    } else if (testiCurrent === TC) {
      testiTrack.style.transition = 'none';
      testiTrack.style.transform = `translateX(${-getTestiOffset(0)}px)`;
      testiCurrent = 0;
      void testiTrack.offsetHeight;
      testiTrack.style.transition = '';
      applyTestiCenter(0);
    }
  });

  window.prevTesti = function() { goTesti((testiCurrent - 1 + TC) % TC); };
  window.nextTesti = function() { goTesti((testiCurrent + 1) % TC); };

  function resetTestiTimer() {
    if (testiTimer) clearInterval(testiTimer);
    testiTimer = setInterval(() => nextTesti(), 5000);
  }
  resetTestiTimer();
  const testiCarousel = document.querySelector('.testi-carousel');
  if (testiCarousel) {
    testiCarousel.addEventListener('mouseenter', () => clearInterval(testiTimer));
    testiCarousel.addEventListener('mouseleave', resetTestiTimer);
  }
  window.addEventListener('resize', () => goTesti(testiCurrent, true));
}

// ============ PAYMENT MARQUEE ============
const paymentTrack = document.getElementById('paymentTrack');

function isLightTheme() {
  return document.documentElement.getAttribute('data-theme') === 'light';
}

function renderPaymentMarquee(methods) {
  if (!paymentTrack) return;
  const items = methods.map(m => typeof m === 'string' ? { name: m, photo_url: null, photo_light_url: null, icon: null } : m);
  const doubled = [...items, ...items];
  paymentTrack.innerHTML = '';
  doubled.forEach(p => {
    const badge = document.createElement('div');
    badge.className = 'pay-badge';
    const imgUrl = isLightTheme() && p.photo_light_url ? p.photo_light_url : p.photo_url;
    if (imgUrl) {
      badge.innerHTML = `<img src="${imgUrl}" alt="${p.name}" class="pay-badge-img">`;
    } else if (p.icon) {
      badge.innerHTML = `<span class="pay-badge-icon">${p.icon}</span><span class="pay-badge-name">${p.name}</span>`;
    } else {
      badge.textContent = p.name;
    }
    paymentTrack.appendChild(badge);
  });
}

(async function initPaymentMarquee() {
  let methods = await fetchPaymentMethods();
  if (!methods.length) {
    methods = ['QRIS','GoPay','OVO','DANA','BCA','BRI','Mandiri','Alfamart','Indomaret'];
  }
  renderPaymentMarquee(methods);
  document.addEventListener('themeChanged', function() {
    renderPaymentMarquee(methods);
  });
})();



// ============ NEWSLETTER ============
document.getElementById('newsletterForm')?.addEventListener('submit', (e) => {
  e.preventDefault();
  const email = document.getElementById('newsletterEmail')?.value;
  const feedback = document.getElementById('newsletterFeedback');
  if (feedback && email) {
    feedback.textContent = `Terima kasih! Kode diskon telah dikirim ke ${email}`;
    e.target.reset();
    setTimeout(() => { feedback.textContent = ''; }, 5000);
  }
});

// ============ TOAST ============
let toastTimer;
function showToast(msg, isError = false) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = msg;
  toast.className = 'toast' + (isError ? ' error' : ' success');
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
}

// ============ CART BADGE ============
// Dipanggil setelah tambah/hapus keranjang supaya angka di header ikut berubah
// tanpa perlu reload halaman.
function setCartBadge(count) {
  const value = Math.max(0, parseInt(count, 10) || 0);
  document.querySelectorAll('[data-role="cart-badge"]').forEach((badge) => {
    const wasHidden = badge.hasAttribute('hidden');
    badge.textContent = value > 99 ? '99+' : String(value);
    if (value > 0) {
      badge.removeAttribute('hidden');
      if (wasHidden) {
        badge.classList.remove('bump');
        void badge.offsetWidth;
        badge.classList.add('bump');
      }
    } else {
      badge.setAttribute('hidden', 'hidden');
    }
  });
}

// ============ CART STATE (REALTIME) ============
// Semua angka keranjang (badge navbar, "N item di keranjang", subtotal, total,
// status stok per baris) disegarkan dari satu payload yang sama supaya tidak
// perlu reload halaman.
//
// Elemen yang bisa diperbarui ditandai lewat data-role:
//   cart-badge         -> angka badge di header (dipakai setCartBadge)
//   cart-summary-qty   -> teks "N item di keranjang" (semua item)
//   cart-payable-qty   -> jumlah item yang ikut ditagih (stok habis dikecualikan)
//   cart-summary-items -> teks "N item dari M produk" (checkout)
//   cart-subtotal      -> nominal subtotal
//   cart-total         -> nominal total
//   cart-unavailable   -> blok peringatan item stok habis
//   cart-checkout-btn  -> tombol lanjut checkout (dinonaktifkan saat ada item bermasalah)
//   cart-empty         -> tampilan keranjang kosong
//   cart-filled        -> isi halaman keranjang
//   cart-note          -> catatan "Di keranjang: N item" di detail game
//   row-out-note       -> catatan "stok habis" pada satu baris keranjang
//   row-flash-note     -> catatan "harga flash" pada satu baris keranjang
function formatRupiah(value) {
  const number = Math.max(0, parseInt(value, 10) || 0);
  return 'Rp ' + number.toLocaleString('id-ID');
}

function applyCartState(state) {
  if (!state || typeof state !== 'object') return null;

  const count = Math.max(0, parseInt(state.count, 10) || 0);
  const unavailable = Math.max(0, parseInt(state.unavailable, 10) || 0);

  // Badge di header (desktop + isi hamburger menu).
  setCartBadge(count);

  // Jumlah item pada teks halaman keranjang & checkout.
  document.querySelectorAll('[data-role="cart-summary-qty"]').forEach((el) => {
    el.textContent = count;
  });

  document.querySelectorAll('[data-role="cart-summary-items"]').forEach((el) => {
    const products = Array.isArray(state.items) ? state.items.length : 0;
    el.textContent = count + ' item dari ' + products + ' produk';
  });

  // Jumlah item yang benar-benar ikut ditagih (stok habis tidak dihitung).
  const payableQty = Math.max(0, parseInt(state.total_qty, 10) || 0);
  document.querySelectorAll('[data-role="cart-payable-qty"]').forEach((el) => {
    el.textContent = payableQty;
  });

  // Nominal subtotal & total.
  document.querySelectorAll('[data-role="cart-subtotal"]').forEach((el) => {
    el.textContent = formatRupiah(state.subtotal);
  });

  document.querySelectorAll('[data-role="cart-total"]').forEach((el) => {
    el.textContent = formatRupiah(state.subtotal);
  });

  // Peringatan item yang tidak bisa dibayar.
  document.querySelectorAll('[data-role="cart-unavailable"]').forEach((el) => {
    el.hidden = unavailable < 1;
    const text = el.querySelector('[data-role="cart-unavailable-count"]');
    if (text) text.textContent = unavailable;
  });

  // Tombol lanjut ke checkout tidak boleh aktif selama masih ada item bermasalah.
  document.querySelectorAll('[data-role="cart-checkout-btn"]').forEach((btn) => {
    const blocked = unavailable > 0;
    btn.classList.toggle('btn-disabled', blocked);

    if (blocked) {
      btn.setAttribute('href', '#');
    } else if (btn.dataset.checkoutUrl) {
      btn.setAttribute('href', btn.dataset.checkoutUrl);
    }
  });

  // Detail tiap baris keranjang: status stok, qty, line total, dan batas tombol "+".
  (Array.isArray(state.items) ? state.items : []).forEach((item) => {
    const row = document.querySelector('[data-item="' + item.id + '"]');
    if (!row) return;

    const out = item.unavailable === true;
    row.classList.toggle('cart-item-out', out);
    row.dataset.max = parseInt(item.max, 10) || 0;

    const outNote = row.querySelector('[data-role="row-out-note"]');
    if (outNote) outNote.hidden = !out;

    const flashNote = row.querySelector('[data-role="row-flash-note"]');
    if (flashNote) flashNote.hidden = out || item.flash_deal !== true;

    const qtyEl = row.querySelector('[data-role="qty"]');
    if (qtyEl) qtyEl.textContent = item.quantity;

    const lineEl = row.querySelector('[data-role="line-total"]');
    if (lineEl) lineEl.textContent = out ? '—' : formatRupiah(item.line_total);

    // Harga coret dari flash deal: disembunyikan kalau tidak ada diskon.
    const oldEl = row.querySelector('[data-role="line-old"]');
    if (oldEl) {
      const original = parseInt(item.original_price, 10) || 0;
      oldEl.textContent = formatRupiah(original * (parseInt(item.quantity, 10) || 0));
      oldEl.hidden = out || original <= (parseInt(item.unit_price, 10) || 0);
    }

    const max = parseInt(item.max, 10) || 0;
    const limit = max > 0 ? Math.min(max, 99) : 99;

    row.querySelectorAll('.cart-qty button').forEach((btn) => {
      const inc = btn.dataset.act === 'inc';
      // Stok habis: quantity tidak bisa diubah dari halaman ini.
      btn.disabled = out || (inc && item.quantity >= limit);
    });
  });

  // Catatan "Di keranjang: N item" di halaman detail game disembunyikan
  // saat keranjang kosong, dan muncul lagi begitu ada item.
  document.querySelectorAll('[data-role="cart-note"]').forEach((el) => {
    el.hidden = count < 1;
  });

  // Tampilan keranjang kosong & isi keranjang tidak perlu reload setelah item
  // terakhir dihapus atau keranjang dikosongkan.
  const emptyEl = document.querySelector('[data-role="cart-empty"]');
  const filledEl = document.querySelector('[data-role="cart-filled"]');

  if (emptyEl || filledEl) {
    const isEmpty = count < 1;
    if (emptyEl) emptyEl.hidden = !isEmpty;
    if (filledEl) filledEl.hidden = isEmpty;
  }

  window.__cartState = state;

  document.dispatchEvent(new CustomEvent('cart:state', { detail: state }));

  return state;
}

// Tarik state terbaru dari server. Dipakai halaman yang tidak punya tombol
// ubah keranjang sendiri (mis. checkout) supaya angka tidak basi setelah user
// mengubah keranjang lalu kembali ke halaman ini.
function refreshCartState() {
  const url = window.CART_STATE_URL;
  if (!url) return Promise.resolve(null);

  return fetch(url, {
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  })
    .then((r) => (r.ok ? r.json() : null))
    .then((json) => (json ? applyCartState(json.state || json) : null))
    .catch(() => null);
}

window.applyCartState = applyCartState;
window.refreshCartState = refreshCartState;
window.formatCartRupiah = formatRupiah;

// ============ FLASH MESSAGES ============
(function showFlashMessages() {
  const flash = document.getElementById('flash-data');
  if (flash) {
    try {
      const data = JSON.parse(flash.textContent);
      if (data.success) showToast(data.success);
      if (data.error) showToast(data.error, true);
    } catch (e) {}
    flash.remove();
  }
})();

// ============ FEATURED IMAGE ROTATION ============
document.querySelectorAll('.bento-card[data-featured-imgs]').forEach(card => {
  const imgs = JSON.parse(card.dataset.featuredImgs || '[]').filter(Boolean);
  if (imgs.length < 2) return;
  const bg = card.querySelector('.bento-card-bg');
  if (!bg) return;
  const fade = document.createElement('div');
  fade.className = 'bento-card-bg-fade';
  card.insertBefore(fade, bg.nextSibling);
  let idx = 0;
  setInterval(() => {
    const next = (idx + 1) % imgs.length;
    fade.style.backgroundImage = `url('${imgs[next]}')`;
    fade.classList.add('show');
    setTimeout(() => {
      bg.style.backgroundImage = `url('${imgs[next]}')`;
      fade.classList.remove('show');
      idx = next;
    }, 600);
  }, 3000);
});

// ============ HERO BANNER ARROWS ============
function initBanner(sectionId) {
  const section = document.getElementById(sectionId);
  if (!section) return;

  const imgA = section.querySelector('.hero-banner-img-a');
  const imgB = section.querySelector('.hero-banner-img-b');
  let bannerIndex = 0;
  let banners = [];
  let bannerTimer = null;
  let bannerMoving = false;
  let activeImg = 'a';

  try {
    const raw = imgA?.dataset?.banners;
    if (raw) banners = JSON.parse(raw);
  } catch (e) {}

  if (!banners.length) return;

  function switchBanner(index, dir) {
    if (!banners.length || !imgA || !imgB) return;
    if (index === bannerIndex || bannerMoving) return;
    bannerMoving = true;
    bannerIndex = (index + banners.length) % banners.length;

    const currEl = activeImg === 'a' ? imgA : imgB;
    const nextEl = activeImg === 'a' ? imgB : imgA;

    nextEl.src = banners[bannerIndex];

    nextEl.classList.add('no-transition');
    nextEl.style.transform = dir === 'next' ? 'translateX(100%)' : 'translateX(-100%)';

    void nextEl.offsetHeight;

    nextEl.classList.remove('no-transition');
    currEl.style.transform = dir === 'next' ? 'translateX(-100%)' : 'translateX(100%)';
    nextEl.style.transform = 'translateX(0)';

    activeImg = activeImg === 'a' ? 'b' : 'a';

    setTimeout(() => {
      currEl.classList.add('no-transition');
      currEl.style.transform = '';
      currEl.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
      bannerMoving = false;
    }, 500);

    resetBannerTimer();
  }

  function prevBanner() { switchBanner(bannerIndex - 1, 'prev'); }
  function nextBanner() { switchBanner(bannerIndex + 1, 'next'); }

  function resetBannerTimer() {
    if (bannerTimer) clearInterval(bannerTimer);
    if (banners.length > 1) {
      bannerTimer = setInterval(() => nextBanner(), 5000);
    }
  }
  resetBannerTimer();

  section.querySelector('[data-banner-prev]')?.addEventListener('click', prevBanner);
  section.querySelector('[data-banner-next]')?.addEventListener('click', nextBanner);
}

initBanner('joki');
initBanner('jba-hero');
