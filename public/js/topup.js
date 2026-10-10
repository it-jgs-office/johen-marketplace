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

function staticPaymentLogo(method) {
  const raw = typeof method === 'string' ? method : (method.code || method.name || '');
  const code = String(raw).trim().toLowerCase().replace(/[\s-]+/g, '_');
  return (window.STATIC_PAYMENT_LOGOS || {})[code] || '';
}

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
        const photo = staticPaymentLogo(p);
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

// ============ TESTIMONIALS WALL ============
const testiTrack = document.getElementById('testiTrack');
const testimonialAvatar = '/assets/icon/icon-testimoni.png';

function testimonialGameName(game) {
  return game.replace(/^Top Up\s*-\s*/, '');
}

function createTestiCard(t) {
  const game = testimonialGameName(t.game);
  const card = document.createElement('article');
  card.className = 'home-testi-card';
  card.innerHTML = `
    <div class="home-testi-card-head">
      <div class="home-testi-brand">
        <span>${game}</span>
      </div>
      <div class="home-testi-rating" aria-label="Rating 5 dari 5">
        <span aria-hidden="true">★★★★★</span><span>5.0</span>
      </div>
    </div>
    <p class="home-testi-quote">${t.quote}</p>
    <div class="home-testi-user">
      <img class="home-testi-avatar" src="${testimonialAvatar}" alt="Foto profil ${t.name}" loading="lazy">
      <div>
        <div class="home-testi-name">${t.name}</div>
        <div class="home-testi-game">Pembeli terverifikasi</div>
      </div>
    </div>`;
  return card;
}

function fillTestimonialLane(lane, items) {
  if (!lane) return;
  const group = document.createElement('div');
  group.className = 'home-testi-lane-group';
  items.forEach(item => group.appendChild(createTestiCard(item)));
  lane.replaceChildren(group);
}

if (testiTrack) {
  fillTestimonialLane(testiTrack, TESTIMONIALS);
}

// Each half of the track must cover the visible area before it is duplicated.
// Otherwise short lists expose an empty tail on wide screens before looping.
document.querySelectorAll('.home-testi-lane').forEach(lane => {
  const group = lane.querySelector('.home-testi-lane-group');
  const wrap = lane.closest('.home-testi-lane-wrap');
  if (!group || !wrap || !group.children.length) return;

  const cards = Array.from(group.children).map(card => card.cloneNode(true));
  let lastWidth = 0;

  function rebuildLane() {
    const width = wrap.clientWidth;
    if (!width || width === lastWidth) return;
    lastWidth = width;

    group.replaceChildren(...cards.map(card => card.cloneNode(true)));
    while (group.getBoundingClientRect().width < width) {
      cards.forEach(card => group.appendChild(card.cloneNode(true)));
    }

    const copy = group.cloneNode(true);
    copy.setAttribute('aria-hidden', 'true');
    copy.querySelectorAll('article').forEach(card => card.setAttribute('tabindex', '-1'));
    lane.replaceChildren(group, copy);
  }

  rebuildLane();
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(rebuildLane).observe(wrap);
  } else {
    window.addEventListener('resize', rebuildLane);
  }
});

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

  // Halaman Top Up memakai tampilan banner lama: satu banner memenuhi frame,
  // lalu berpindah ke slide berikutnya tanpa kartu preview kiri/kanan.
  const classicImgA = section.querySelector('.hero-banner-img-a');
  const classicImgB = section.querySelector('.hero-banner-img-b');
  if (classicImgA && classicImgB) {
    let classicBanners = [];
    try {
      classicBanners = JSON.parse(classicImgA.dataset.banners || '[]');
    } catch (e) {}

    if (classicBanners.length > 1) {
      let currentIndex = 0;
      let activeImage = 'a';
      let moving = false;
      let timer = null;

      function clearTimer() {
        if (timer) window.clearInterval(timer);
        timer = null;
      }
      function schedule() {
        clearTimer();
        if (!document.hidden) timer = window.setInterval(() => move(1), 3000);
      }
      function move(direction) {
        if (moving) return;
        moving = true;
        currentIndex = (currentIndex + direction + classicBanners.length) % classicBanners.length;
        const current = activeImage === 'a' ? classicImgA : classicImgB;
        const next = activeImage === 'a' ? classicImgB : classicImgA;

        next.src = classicBanners[currentIndex];
        next.classList.add('no-transition');
        next.style.transform = direction > 0 ? 'translateX(100%)' : 'translateX(-100%)';
        void next.offsetHeight;
        next.classList.remove('no-transition');
        current.style.transform = direction > 0 ? 'translateX(-100%)' : 'translateX(100%)';
        next.style.transform = 'translateX(0)';
        activeImage = activeImage === 'a' ? 'b' : 'a';

        window.setTimeout(() => {
          current.classList.add('no-transition');
          current.style.transform = '';
          current.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
          moving = false;
        }, 950);
      }

      section.querySelector('[data-banner-prev]')?.addEventListener('click', () => { clearTimer(); move(-1); schedule(); });
      section.querySelector('[data-banner-next]')?.addEventListener('click', () => { clearTimer(); move(1); schedule(); });
      section.addEventListener('mouseenter', clearTimer);
      section.addEventListener('mouseleave', schedule);
      document.addEventListener('visibilitychange', schedule);
      schedule();
    }
    return;
  }

  const track = section.querySelector('.hero-banner-track[data-banners]');
  if (!track) return;

  const prevCard = track.querySelector('[data-banner-slot="prev"]');
  const activeCard = track.querySelector('[data-banner-slot="active"]');
  const nextCard = track.querySelector('[data-banner-slot="next"]');
  const dots = section.querySelector('[data-banner-dots]');
  let cards = { prev: prevCard, active: activeCard, next: nextCard };
  let bannerIndex = 0;
  let banners = [];
  let bannerTimer = null;
  let isPaused = false;
  let isMoving = false;

  try {
    const raw = track.dataset.banners;
    if (raw) banners = JSON.parse(raw);
  } catch (e) {}

  if (banners.length < 2 || !activeCard) return;

  const wrapIndex = (index) => (index + banners.length) % banners.length;
  const imageFor = (slot) => cards[slot]?.querySelector('img');
  const preloadedBanners = new Map();

  function preloadBanner(url) {
    if (preloadedBanners.has(url)) return preloadedBanners.get(url);

    const preload = new Promise(resolve => {
      const image = new Image();
      let settled = false;
      const finish = () => {
        if (settled) return;
        settled = true;
        if (typeof image.decode === 'function') {
          image.decode().catch(() => {}).finally(resolve);
        } else {
          resolve();
        }
      };
      image.addEventListener('load', finish, { once: true });
      image.addEventListener('error', resolve, { once: true });
      image.src = url;
      if (image.complete) finish();
    });

    preloadedBanners.set(url, preload);
    return preload;
  }

  function updateDots(index) {
    if (!dots) return;
    Array.from(dots.children).forEach((dot, dotIndex) => {
      dot.classList.toggle('is-active', dotIndex === index);
      dot.setAttribute('aria-current', dotIndex === index ? 'true' : 'false');
    });
  }

  function waitForSlideTransition(card) {
    return new Promise(resolve => {
      let fallbackTimer = null;
      const finish = () => {
        card.removeEventListener('transitionend', onTransitionEnd);
        if (fallbackTimer) window.clearTimeout(fallbackTimer);
        resolve();
      };
      const onTransitionEnd = event => {
        if (event.target === card && event.propertyName === 'transform') finish();
      };
      card.addEventListener('transitionend', onTransitionEnd);
      fallbackTimer = window.setTimeout(finish, 1500);
    });
  }

  function nextPaint() {
    return new Promise(resolve => {
      requestAnimationFrame(() => requestAnimationFrame(resolve));
    });
  }

  function updateCard(card, slot, slideIndex, updateImage = true) {
    if (!card) return;
    card.classList.remove('hero-banner-card-prev', 'hero-banner-card-active', 'hero-banner-card-next');
    card.classList.add(`hero-banner-card-${slot}`);
    card.dataset.bannerSlot = slot;
    card.tabIndex = slot === 'active' ? -1 : 0;
    if (slot === 'active') {
      card.setAttribute('aria-live', 'polite');
      card.setAttribute('aria-current', 'true');
    } else {
      card.removeAttribute('aria-live');
      card.removeAttribute('aria-current');
    }
    card.setAttribute('aria-label', slot === 'active'
      ? `Banner promo ${slideIndex + 1}`
      : `Lihat banner promo ${slideIndex + 1}`);

    if (!updateImage) return;
    const image = card.querySelector('img');
    if (!image) return;
    image.src = banners[slideIndex];
    image.alt = `Banner promo ${slideIndex + 1}`;
  }

  function render(index) {
    bannerIndex = wrapIndex(index);
    const indices = {
      prev: wrapIndex(bannerIndex - 1),
      active: bannerIndex,
      next: wrapIndex(bannerIndex + 1),
    };

    Object.entries(indices).forEach(([slot, slideIndex]) => {
      updateCard(cards[slot], slot, slideIndex);
    });

    updateDots(bannerIndex);
  }

  async function switchBanner(index) {
    const targetIndex = wrapIndex(index);
    if (targetIndex === bannerIndex || isMoving) return;
    const startingIndex = bannerIndex;
    const direction = wrapIndex(targetIndex - bannerIndex) <= banners.length / 2 ? 'next' : 'prev';
    const destination = imageFor(direction);
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    clearBannerTimer();

    if (reduceMotion || !destination) {
      render(targetIndex);
      scheduleBannerTimer();
      return;
    }

    isMoving = true;
    await preloadBanner(banners[targetIndex]);
    destination.src = banners[targetIndex];
    destination.alt = `Banner promo ${targetIndex + 1}`;
    await nextPaint();
    updateDots(targetIndex);
    track.classList.add(`is-moving-${direction}`);

    await waitForSlideTransition(cards.active);
    const recycledCard = direction === 'next' ? cards.prev : cards.next;
    const rotatedCards = direction === 'next'
      ? { prev: cards.active, active: cards.next, next: cards.prev }
      : { prev: cards.next, active: cards.prev, next: cards.active };
    const isAdjacent = targetIndex === wrapIndex(startingIndex + (direction === 'next' ? 1 : -1));
    const cardsToRecycle = isAdjacent ? [recycledCard] : [recycledCard, cards.active];

    cardsToRecycle.forEach(card => card.classList.add('is-recycling'));
    await Promise.all([
      preloadBanner(banners[wrapIndex(targetIndex - 1)]),
      preloadBanner(banners[wrapIndex(targetIndex + 1)]),
    ]);
    track.classList.add('is-resetting');
    track.classList.remove(`is-moving-${direction}`);
    cards = rotatedCards;
    bannerIndex = targetIndex;
    updateCard(cards.prev, 'prev', wrapIndex(bannerIndex - 1));
    updateCard(cards.active, 'active', bannerIndex, false);
    updateCard(cards.next, 'next', wrapIndex(bannerIndex + 1));
    void track.offsetWidth;
    track.classList.remove('is-resetting');
    await nextPaint();
    cardsToRecycle.forEach(card => card.classList.remove('is-recycling'));
    isMoving = false;
    scheduleBannerTimer();
  }

  function prevBanner() { switchBanner(bannerIndex - 1); }
  function nextBanner() { switchBanner(bannerIndex + 1); }

  function clearBannerTimer() {
    if (!bannerTimer) return;
    window.clearTimeout(bannerTimer);
    bannerTimer = null;
  }

  function scheduleBannerTimer() {
    clearBannerTimer();
    if (isPaused || isMoving || document.hidden) return;
    bannerTimer = window.setTimeout(nextBanner, 3000);
  }
  if (dots) {
    banners.forEach((_, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'hero-banner-dot';
      dot.setAttribute('aria-label', `Tampilkan banner ${index + 1}`);
      dot.addEventListener('click', () => switchBanner(index));
      dots.appendChild(dot);
    });
  }
  banners.forEach(preloadBanner);
  render(0);
  scheduleBannerTimer();

  section.querySelector('[data-banner-prev]')?.addEventListener('click', prevBanner);
  section.querySelector('[data-banner-next]')?.addEventListener('click', nextBanner);
  track.addEventListener('click', event => {
    const card = event.target.closest('[data-banner-slot]');
    if (!card || !track.contains(card)) return;
    if (card.dataset.bannerSlot === 'prev') prevBanner();
    if (card.dataset.bannerSlot === 'next') nextBanner();
  });
  section.addEventListener('mouseenter', () => { isPaused = true; clearBannerTimer(); });
  section.addEventListener('mouseleave', () => { isPaused = false; scheduleBannerTimer(); });
  section.addEventListener('focusin', () => { isPaused = true; clearBannerTimer(); });
  section.addEventListener('focusout', (event) => {
    if (section.contains(event.relatedTarget)) return;
    isPaused = false;
    scheduleBannerTimer();
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) clearBannerTimer();
    else scheduleBannerTimer();
  });

  let touchStartX = null;
  track.addEventListener('touchstart', (event) => {
    touchStartX = event.touches[0]?.clientX ?? null;
  }, { passive: true });
  track.addEventListener('touchend', (event) => {
    if (touchStartX === null) return;
    const distance = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
    touchStartX = null;
    if (Math.abs(distance) < 40) return;
    if (distance < 0) nextBanner();
    else prevBanner();
  }, { passive: true });
}

initBanner('joki');
initBanner('jba-hero');

function initStockShowcase() {
  const section = document.querySelector('[data-stock-showcase]');
  if (!section) return;

  const slides = Array.from(section.querySelectorAll('[data-stock-slide]'));
  const frame = section.querySelector('.stock-showcase-frame');
  const categoryLink = section.querySelector('[data-stock-category]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!slides.length || !categoryLink || !frame) return;

  const stockBrandColors = {
    pubg: '#8995a5',
    mlbb: '#55b9ed',
    ff: '#e54450',
    efootball: '#153d78',
    fcm: '#2c9b64',
    roblox: '#72a83c',
    valorant: '#9c304c',
  };

  let activeIndex = 0;
  let timer = null;
  let paused = false;

  function updateBrandColor(slide) {
    const color = stockBrandColors[slide.dataset.stockSlug];
    if (color) frame.style.setProperty('--stock-brand', color);
  }

  function warmSlide(index) {
    const slide = slides[(index + slides.length) % slides.length];
    const images = [slide.querySelector('.stock-showcase-art img'),
      ...Array.from(slide.querySelectorAll('.stock-showcase-group:first-child .stock-showcase-card-image img')).slice(0, 4)];
    images.filter(Boolean).forEach(image => { image.loading = 'eager'; });
  }

  function clearTimer() {
    if (timer !== null) window.clearTimeout(timer);
    timer = null;
  }

  function schedule() {
    clearTimer();
    if (slides.length < 2 || paused || document.hidden || reducedMotion.matches) return;
    timer = window.setTimeout(() => show(activeIndex + 1, 1), 3000);
  }

  function show(index, direction = 1) {
    const nextIndex = (index + slides.length) % slides.length;
    if (nextIndex === activeIndex) return;

    const previous = slides[activeIndex];
    const next = slides[nextIndex];
    [previous, next].forEach(slide => {
      if (slide._stockTransitionTimer) window.clearTimeout(slide._stockTransitionTimer);
      slide._stockTransitionTimer = null;
      slide.classList.remove('is-entering', 'is-exiting');
    });
    previous.style.setProperty('--stock-slide-direction', String(direction));
    next.style.setProperty('--stock-slide-direction', String(direction));
    warmSlide(nextIndex);
    previous.classList.remove('is-active');
    previous.classList.add('is-exiting');
    previous.setAttribute('aria-hidden', 'true');
    previous.inert = true;
    next.inert = false;
    next.removeAttribute('aria-hidden');
    next.classList.add('is-active');
    next.classList.add('is-entering');
    categoryLink.textContent = next.dataset.stockGame;
    categoryLink.href = next.dataset.stockHref;
    updateBrandColor(next);
    if (!reducedMotion.matches) {
      categoryLink.animate?.([
        { opacity: 0, filter: 'blur(5px)', transform: 'translateY(7px) scale(.96)' },
        { opacity: 1, filter: 'blur(0)', transform: 'translateY(0) scale(1)' },
      ], { duration: 620, easing: 'cubic-bezier(.22,1,.36,1)' });
    }
    const transitionTimer = window.setTimeout(() => {
      if (!previous.classList.contains('is-active')) {
        previous.classList.remove('is-exiting');
        previous.style.removeProperty('--stock-slide-direction');
      }
      if (next.classList.contains('is-active')) {
        next.classList.remove('is-entering');
        next.style.removeProperty('--stock-slide-direction');
      }
      if (previous._stockTransitionTimer === transitionTimer) previous._stockTransitionTimer = null;
      if (next._stockTransitionTimer === transitionTimer) next._stockTransitionTimer = null;
    }, 820);
    previous._stockTransitionTimer = transitionTimer;
    next._stockTransitionTimer = transitionTimer;
    activeIndex = nextIndex;
    warmSlide(activeIndex + 1);
    schedule();
  }

  updateBrandColor(slides[activeIndex]);
  warmSlide(0);
  warmSlide(1);
  schedule();
  section.querySelector('[data-stock-prev]')?.addEventListener('click', () => show(activeIndex - 1, -1));
  section.querySelector('[data-stock-next]')?.addEventListener('click', () => show(activeIndex + 1, 1));
  section.addEventListener('mouseenter', () => { paused = true; clearTimer(); });
  section.addEventListener('mouseleave', () => { paused = false; schedule(); });
  section.addEventListener('focusin', () => { paused = true; clearTimer(); });
  section.addEventListener('focusout', event => {
    if (section.contains(event.relatedTarget)) return;
    paused = false;
    schedule();
  });
  document.addEventListener('visibilitychange', schedule);
  reducedMotion.addEventListener?.('change', schedule);
}

initStockShowcase();
