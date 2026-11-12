const APP_NAME = 'Johen Gaming Marketplace';

// Disuntik PwaController dari mtime file publik. Begitu ada aset yang berubah
// setelah deploy, nilai ini berubah -> script worker berubah -> browser
// memasang worker baru dan cache lama dibuang.
const BUILD = '__JOHEN_BUILD__';

const STATIC_CACHE = 'johen-gaming-static-v' + BUILD;
const RAW_PRECACHE_URLS = [
  '/css/topup.css',
  '/css/gacha.css',
  '/css/livechat.css',
  '/js/topup.js',
  '/js/livechat.js',
  '/js/gacha.js',
  '/js/pwa-register.js',
  '/site.webmanifest',
  '/assets/Foto/logo.gacha.png',
  '/logo-96.png',
  '/logo.png',
  '/logo-180.png',
  '/logo-192.png',
  '/logo-512.png',
  '/logo-maskable-192.png',
  '/logo-maskable-512.png',
  '/favicon.ico'
];

// Aset di-precache dengan query build, bukan query versi yang dipatok di Blade.
// Jadi begitu worker baru aktif, semua entry-nya berisi file terbaru.
const PRECACHE_URLS = RAW_PRECACHE_URLS.map(function (url) {
  return url + '?v=' + BUILD;
});

function isCacheableAsset(url) {
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/storage/') || url.pathname.startsWith('/media/')) {
    return false;
  }

  return /^\/(?:css|js|build|assets|img)\//.test(url.pathname)
    || /\.(?:css|js|mjs|png|jpg|jpeg|gif|webp|svg|ico|woff2?)$/i.test(url.pathname);
}

/* Preseed: aset hasil build ini, dicocokkan lewat pathname saja. Halaman
   masih memakai query versi lamanya (?v=34), jadi tanpa ignoreSearch request
   itu akan lolos ke jaringan dan bisa dilayani cache HTTP yang masih
   menyimpan file lama. */
async function precached(request) {
  const cache = await caches.open(STATIC_CACHE);
  return cache.match(request, { ignoreSearch: true, ignoreVary: true });
}

async function networkFirst(request) {
  const cache = await caches.open(STATIC_CACHE);
  try {
    // cache: 'reload' melewati cache HTTP, jadi aset lama tidak pernah
    // terbawa meski URL-nya sama persis.
    const response = await fetch(new Request(request.url, { cache: 'reload', credentials: 'same-origin' }));
    if (response && response.ok && response.type === 'basic') {
      await cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const cached = await cache.match(request, { ignoreVary: true });
    if (cached) return cached;
    throw error;
  }
}

function normalizePayload(raw) {
  const nested = raw && raw.data && typeof raw.data === 'object' ? raw.data : {};
  const data = { ...raw, ...nested };
  const conversationId = data.conversation_id || data.conversationId || null;
  const channelSlug = data.channel_slug || data.channelSlug || null;
  let url = data.url || data.target_url || '';

  if (!url && conversationId) {
    if (data.target_guard === 'lcadmin') url = `/lcadmin/conversations?open=${conversationId}`;
    else if (data.target_guard === 'admin') url = `/johen-admin-secret/live-chat/conversations/${conversationId}`;
    else if (channelSlug) url = `/?chat=1&channel=${encodeURIComponent(channelSlug)}`;
  }

  return {
    ...data,
    title: String(data.title || data.sender_name || data.senderName || '').trim(),
    body: String(data.body || data.message || data.preview || '').trim(),
    url,
    conversation_id: conversationId,
    channel_slug: channelSlug,
  };
}

function safeIcon(value) {
  if (value && typeof value === 'string' && /^https?:\/\//i.test(value)) return value;
  return '/logo-96.png';
}

function buildNotification(data) {
  const conversationId = data.conversation_id || data.conversationId || null;
  const opts = {
    body: data.body,
    icon: safeIcon(data.icon),
    badge: safeIcon(data.badge || '/logo-96.png'),
    lang: 'id-ID',
    dir: 'auto',
    silent: false,
    requireInteraction: false,
    timestamp: Date.now(),
    data: {
      appName: data.app_name || APP_NAME,
      url: data.url,
      msgId: data.msg_id || data.msgId || null,
      conversationId,
      channelSlug: data.channel_slug || data.channelSlug || null,
      targetGuard: data.target_guard || data.targetGuard || null,
    },
  };
  if (data.tag) opts.tag = String(data.tag);
  else if (conversationId) opts.tag = 'conv-' + conversationId;
  return opts;
}

async function fetchLatestContent() {
  try {
    const res = await fetch('/api/push/latest-message', {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
      cache: 'no-store',
    });
    if (!res.ok) return null;
    const json = await res.json();
    return json && typeof json === 'object' ? json : null;
  } catch (e) {
    return null;
  }
}

async function maybeShow(data) {
  let normalized = normalizePayload(data);
  if (!normalized.title || !normalized.body) {
    const latest = await fetchLatestContent();
    if (latest) normalized = normalizePayload(latest);
  }
  const title = normalized.title || 'Pesan Baru';
  const body = normalized.body || 'Kamu menerima pesan baru.';
  const url = normalized.url || '/';
  await self.registration.showNotification(title, buildNotification({ ...normalized, body, url }));
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => Promise.all(
      PRECACHE_URLS.map((url) => cache.add(new Request(url, { cache: 'reload' })).catch(() => null))
    )).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys
        .filter((key) => key.startsWith('johen-gaming-') && key !== STATIC_CACHE)
        .map((key) => caches.delete(key))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  const msg = event.data || {};
  if (msg.type === 'SKIP_WAITING') {
    event.waitUntil(self.skipWaiting());
    return;
  }
  if (msg.type !== 'chat-notify') return;
  const data = normalizePayload(msg.data || {});
  if (!data.title || !data.body || !data.url) return;
  event.waitUntil(maybeShow(data).catch((error) => {
    console.error('Johen Gaming notifikasi chat gagal:', error);
  }));
});

/* Dokumen HTML: selalu divalidasi ulang ke server (cache 'no-cache' = boleh
   304, tapi tidak boleh dilayani dari cache tanpa bertanya). Inilah yang
   membuat deploy baru langsung terlihat di PWA ter-install. Sengaja tidak
   dicache: kalau disimpan, halaman berisi data user bisa tampil di perangkat
   lain yang memakai browser yang sama. */
async function freshDocument(request) {
  return fetch(request, { cache: 'no-cache' });
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(freshDocument(request));
    return;
  }

  if (!isCacheableAsset(url)) {
    return;
  }

  // Aset dilayani dari precache build ini, dicocokkan lewat pathname saja.
  // Halaman masih memakai query versi lamanya (?v=34), jadi tanpa
  // ignoreSearch request itu akan lolos ke jaringan dan bisa dilayani
  // cache HTTP yang masih menyimpan file lama.
  event.respondWith(
    precached(request).then((hit) => hit || networkFirst(request))
  );
});

self.addEventListener('push', (event) => {
  let data = {};
  try {
    const parsed = event.data ? event.data.json() : null;
    if (parsed && typeof parsed === 'object') data = parsed;
  } catch (e) {
    try {
      const text = event.data ? event.data.text() : '';
      data = text ? { body: text } : {};
    } catch (e2) {
      data = {};
    }
  }

  const handle = maybeShow(data).catch((error) => {
    console.error('Johen Gaming push notification gagal:', error);
    return self.registration.showNotification('Pesan Baru', {
      body: 'Kamu menerima pesan baru.',
      icon: '/logo-96.png',
      badge: '/logo-96.png',
    });
  });

  event.waitUntil(handle);
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const data = event.notification.data || {};
  const raw = data.url;
  let isAdminNotif = data.targetGuard === 'admin' || data.targetGuard === 'lcadmin';

  let target;
  try {
    target = new URL(raw, self.location.href);
  } catch (e) {
    target = new URL('/', self.location.href);
  }

  // Fallback deteksi notifikasi admin via pola URL untuk notifikasi lama.
  if (!isAdminNotif) {
    const path = target.pathname || '';
    if (path === '/johen-admin-secret/' || path.startsWith('/johen-admin-secret/') || path === '/lcadmin/' || path.startsWith('/lcadmin/')) {
      isAdminNotif = true;
    }
  }

  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of windows) {
      try {
        if (new URL(client.url).origin === target.origin) {
          await client.navigate(target.href);
          return client.focus();
        }
      } catch (e) { /* lewati client yang gagal dinavigasi */ }
    }
    // Belum ada jendela web terbuka: admin/lcadmin diarahkan ke halaman login,
    // bukan ke beranda.
    const openUrl = isAdminNotif ? '/johen-admin-secret/login' : target.href;
    return self.clients.openWindow(openUrl);
  })());
});
