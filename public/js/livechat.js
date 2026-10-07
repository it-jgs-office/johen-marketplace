(function() {
  'use strict';

  const AUTH_USER = window.LIVECHAT_USER || null;
  const CHANNELS_API = '/api/live-chat/channels';
  const GUEST_CHANNELS_API = '/api/live-chat/guest/channels';
  const IS_GUEST = !AUTH_USER;
  const CS_SLUG = (window.LIVECHAT_CS_SLUG || 'johen-cs');
  const POLL_INTERVAL = 3000;
  const SESSION_TIMEOUT_MS = 5 * 60 * 1000;
  const GUEST_STORAGE_KEY = 'johen_lc_guest';
  const GUEST_NAME_STORAGE_KEY = 'johen_lc_guest_name';

  function getGuestId() {
    let id = null;
    try { id = sessionStorage.getItem(GUEST_STORAGE_KEY); } catch (e) {}
    if (!id) {
      id = (window.crypto && window.crypto.randomUUID)
        ? window.crypto.randomUUID()
        : ('g-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12));
      try { sessionStorage.setItem(GUEST_STORAGE_KEY, id); } catch (e) {}
    }
    return id;
  }

  function getGuestName() {
    let name = '';
    try { name = localStorage.getItem(GUEST_NAME_STORAGE_KEY) || ''; } catch (e) {}
    return name.trim();
  }

  function saveGuestName(name) {
    try { localStorage.setItem(GUEST_NAME_STORAGE_KEY, String(name).trim()); } catch (e) {}
  }

  function resetGuestSession() {
    try { sessionStorage.removeItem(GUEST_STORAGE_KEY); } catch (e) {}
    try { localStorage.removeItem(GUEST_STORAGE_KEY); } catch (e) {}
    try { localStorage.removeItem(GUEST_NAME_STORAGE_KEY); } catch (e) {}
  }

  function guestParam() {
    return encodeURIComponent(getGuestId());
  }

  function messagePostUrl() {
    return IS_GUEST ? '/api/live-chat/guest/messages' : '/api/live-chat/messages';
  }

  function touchActivity() {
    state.lastActivity = Date.now();
  }

  function endSession(message) {
    if (state.sessionEnded) return;
    state.sessionEnded = true;
    state.view = 'ended';
    stopPolling();
    cancelVoiceRec();
    closeAllMenus();
    renderEnded(message || 'Tidak ada interaksi selama 5 menit sehingga sesi chat berakhir. Mulai chat baru untuk terhubung kembali.');
  }

  async function readError(res, fallback) {
    let err = {};
    try { err = await res.json(); } catch (e) {}
    if (err && err.session_expired) {
      endSession(err.error || err.message);
      return (err.error || err.message || fallback);
    }
    return (err && err.message) || (err && err.error) || fallback;
  }

  function renderEnded(message) {
    const container = document.getElementById('lc-body');
    if (!container) return;
    container.innerHTML = `
      <div class="lc-session-ended">
        <div class="lc-ended-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </div>
        <div class="lc-ended-title">Sesi chat berakhir</div>
        <div class="lc-ended-sub">${escapeHtml(message)}</div>
        <button type="button" class="lc-new-session-btn" onclick="window.LiveChat.startNewSession()">Mulai Chat Baru</button>
      </div>`;
  }

  function startNewSession() {
    resetGuestSession();
    state.conversation = null;
    state.messages = [];
    state.sessionEnded = false;
    state.lastActivity = null;
    state.view = 'panel';
    renderGuestNameForm();
  }

  let state = {
    isOpen: false,
    view: 'panel',
    channels: [],
    activeChannel: null,
    conversation: null,
    messages: [],
    pollingTimer: null,
    mediaList: [],
    activeIndex: 0,
    replyToMsg: null,
    activeMenuMsgId: null,
    recorder: null,
    recChunks: [],
    recStream: null,
    recTimer: null,
    recMime: '',
    recStartTs: 0,
    recSeconds: 0,
    lastActivity: null,
    sessionEnded: false,
  };

  function getToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  function headers(json = true) {
    const h = { 'X-CSRF-TOKEN': getToken() };
    if (json) h['Accept'] = 'application/json';
    return h;
  }

  function timeAgo(date) {
    if (!date) return '';
    const now = new Date();
    const d = new Date(date);
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
    if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
    if (diff < 604800) return Math.floor(diff / 86400) + ' hari lalu';
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function attachmentsOf(msg) {
    if (msg.attachments && msg.attachments.length) return msg.attachments;
    if (msg.media_path) return [{ media_path: msg.media_path, poster_path: msg.poster_path || null, media_mime: msg.media_mime || null }];
    return [];
  }

  function galleryHtml(msg, messageId) {
    const atts = attachmentsOf(msg);
    if (!atts.length) return '';

    if (msg.message_type === 'audio') {
      const a = atts[0];
      const dur = a.media_duration ? formatVoiceTime(a.media_duration) : '';
      return `<div class="lc-msg-media"><div class="lc-voice" data-dur="${a.media_duration || ''}" onclick="window.LiveChat.toggleVoicePlay(this)"><span class="lc-voice-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span><span class="lc-voice-progress"><span class="lc-voice-fill"></span></span><span class="lc-voice-time">${dur}</span><audio src="/media/${a.media_path}" preload="metadata"></audio></div></div>`;
    }

    if (msg.message_type === 'video') {
      const a = atts[0];
      const posterAttr = a.poster_path ? ` poster="/media/${a.poster_path}"` : '';
      return `<div class="lc-msg-media lc-video-wrap"><button type="button" class="lc-video-play" onclick="window.LiveChat.playVideoMessage(this)" aria-label="Putar video"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></button><video src="/media/${a.media_path}" controls preload="none"${posterAttr}></video></div>`;
    }

    const gridCls = atts.length === 1 ? 'lc-msg-gallery lc-grid-1'
      : atts.length === 2 ? 'lc-msg-gallery lc-grid-2'
      : atts.length === 3 ? 'lc-msg-gallery lc-grid-3'
      : 'lc-msg-gallery lc-grid-4';
    const urlsJson = JSON.stringify(atts.map(a => `/media/${a.media_path}`)).replace(/"/g, '&quot;');
    const visible = atts.slice(0, 4);
    const hiddenCount = atts.length - 4;
    const tiles = visible.map((a, i) => {
      const url = `/media/${a.media_path}`;
      const more = (i === 3 && hiddenCount > 0) ? `<span class="lc-gallery-more">+${hiddenCount}</span>` : '';
      return `<button type="button" class="lc-gallery-item" data-urls="${urlsJson}" onclick="window.LiveChat.openGallery(event, this, ${messageId}, ${i})" aria-label="Buka gambar ukuran penuh"><img src="${url}" alt="Gambar" loading="lazy">${more}</button>`;
    }).join('');
    return `<div class="lc-msg-media ${gridCls}">${tiles}</div>`;
  }

  function showToast(message, type) {
    const existing = document.querySelector('.lc-toast');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'lc-toast';
    toast.style.cssText = 'position:fixed;bottom:90px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:500;color:#fff;z-index:100000;animation:lcToastIn .3s ease;font-family:Poppins,sans-serif;max-width:350px;text-align:center;box-shadow:0 8px 24px -4px rgba(0,0,0,0.3);';
    toast.style.background = type === 'error' ? '#ef4444' : '#10b981';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3000);
  }

  function extractVideoThumb(file) {
    return new Promise(resolve => {
      const video = document.createElement('video');
      video.preload = 'auto';
      video.muted = true;
      video.playsInline = true;
      video.crossOrigin = 'anonymous';
      const url = URL.createObjectURL(file);
      video.src = url;

      let done = false;
      const finish = (blob) => {
        if (done) return;
        done = true;
        URL.revokeObjectURL(url);
        resolve(blob);
      };

      const timeout = setTimeout(() => finish(null), 8000);

      video.addEventListener('loadedmetadata', () => {
        try {
          const dur = video.duration;
          const isLive = !isFinite(dur) || dur <= 0;
          const target = isLive ? 1 : Math.max(0, Math.min(1, dur - 0.05));
          video.currentTime = target;
          if (video.paused) {
            video.play().catch(() => {});
          }
        } catch (e) {
          clearTimeout(timeout);
          finish(null);
        }
      }, { once: true });

      video.addEventListener('seeked', () => {
        clearTimeout(timeout);
        try {
          const canvas = document.createElement('canvas');
          canvas.width = video.videoWidth || 320;
          canvas.height = video.videoHeight || 180;
          canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
          canvas.toBlob(blob => finish(blob), 'image/jpeg', 0.6);
          if (!video.paused) {
            video.pause();
          }
        } catch (e) {
          finish(null);
        }
      }, { once: true });

      video.addEventListener('error', () => {
        clearTimeout(timeout);
        finish(null);
      }, { once: true });
    });
  }

  function buildMsgHtml(msg) {
    if (msg.sender_type === 'system') {
      return `<div class="lc-msg lc-msg-system" data-msg-id="${msg.id}"><div class="lc-msg-bubble">${escapeHtml(msg.message)}</div></div>`;
    }
    const isUser = msg.sender_type === 'user';
    const cls = isUser ? 'lc-msg-user' : 'lc-msg-admin';
    const time = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    let quoteHtml = '';
    if (msg.reply_to) {
      const rSender = msg.reply_to.sender_type === 'admin' ? 'Admin' : (msg.reply_to.sender?.name || (msg.reply_to.sender_type === 'user' ? (AUTH_USER?.name || 'Anda') : 'Admin'));
      let rContent = '';
      if (msg.reply_to.message_type === 'image' && msg.reply_to.media_path) {
        const n = attachmentsOf(msg.reply_to).length;
        rContent = `<span class="lc-quote-media">📷 ${n > 1 ? n + ' ' : ''}Foto</span>`;
      } else if (msg.reply_to.message_type === 'video' && msg.reply_to.media_path) {
        rContent = '<span class="lc-quote-media">🎬 Video</span>';
      } else if (msg.reply_to.message_type === 'audio') {
        rContent = '<span class="lc-quote-media">🎤 Pesan suara</span>';
      } else {
        rContent = escapeHtml((msg.reply_to.message || 'Pesan telah dihapus').substring(0, 80));
      }
      quoteHtml = `<div class="lc-msg-quote" onclick="event.stopPropagation();window.LiveChat.scrollToMsg(${msg.reply_to.id})"><div class="lc-quote-bar"></div><div class="lc-quote-content"><div class="lc-quote-sender">${escapeHtml(rSender)}</div><div class="lc-quote-text">${rContent}</div></div></div>`;
    }

    let mediaHtml = galleryHtml(msg, msg.id);

    const bubbleContent = msg.message ? `<div class="lc-msg-bubble">${escapeHtml(msg.message)}</div>` : '';

    return `<div class="lc-msg ${cls}" data-msg-id="${msg.id}"><div class="lc-msg-body">${quoteHtml}${mediaHtml}${bubbleContent}<div class="lc-msg-time">${time}</div></div><div class="lc-msg-anchor" onclick="window.LiveChat.showMenu(event, ${msg.id})"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></div></div>`;
  }

  function appendUserMsg(msg) {
    const container = document.getElementById('lc-messages-body');
    if (!container) return;
    const empty = container.querySelector('.lc-empty');
    if (empty) empty.remove();
    container.insertAdjacentHTML('beforeend', buildMsgHtml(msg));
  }

  function addLoadingMsg(tempId, file) {
    const container = document.getElementById('lc-messages-body');
    if (!container) return;
    const empty = container.querySelector('.lc-empty');
    if (empty) empty.remove();
    const isVideo = file && file.type.startsWith('video/');
    const isAudio = file && file.type.startsWith('audio/');
    const icon = isVideo ? '🎬' : isAudio ? '🎤' : '🖼️';
    container.insertAdjacentHTML('beforeend', `<div class="lc-msg lc-msg-user" data-msg-id="temp-${tempId}" data-temp="1"><div class="lc-msg-body"><div class="lc-msg-media lc-msg-loading"><div class="lc-loading-thumb">${icon}</div><div class="lc-loading-spinner"><div></div><div></div><div></div></div><span>Mengirim...</span></div></div></div>`);
    scrollToBottom();
  }

  function removeLoadingMsg(tempId) {
    const el = document.querySelector(`[data-msg-id="temp-${tempId}"]`);
    if (el) el.remove();
  }

  function getInitials(name) {
    return name.replace('Johen ', '').replace('Monkey ', 'M').substring(0, 2).toUpperCase();
  }

  function closeAllMenus() {
    state.activeMenuMsgId = null;
    document.querySelectorAll('.lc-context-menu').forEach(el => el.remove());
    document.querySelectorAll('.lc-msg-has-menu').forEach(el => el.classList.remove('lc-msg-has-menu'));
  }

  function toggleContextMenu(msgId, anchorEl) {
    const existing = document.querySelector(`.lc-context-menu[data-msg-id="${msgId}"]`);
    closeAllMenus();
    if (existing) return;

    state.activeMenuMsgId = msgId;
    const msg = state.messages.find(m => m.id === msgId);
    if (!msg) return;

    const canDelete = !IS_GUEST && msg.sender_id === AUTH_USER?.id;
    const menu = document.createElement('div');
    menu.className = 'lc-context-menu';
    menu.dataset.msgId = msgId;

    const replyItem = `
      <button class="lc-menu-item" onclick="window.LiveChat.replyTo(${msg.id})">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 00-4-4H4"/></svg>
        Balas
      </button>`;

    let items = replyItem;
    if (!IS_GUEST) {
      items += `
      <button class="lc-menu-item" onclick="window.LiveChat.showReactions(${msg.id}, this)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="M8 14s1.4 2 4 2 4-2 4-2"/></svg>Reaksi</button>
      <button class="lc-menu-item" onclick="window.LiveChat.toggleStar(${msg.id})"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/></svg>Beri bintang</button>
      <button class="lc-menu-item lc-menu-danger" onclick="window.LiveChat.showDeleteOptions(${msg.id}, ${canDelete})">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
          Hapus
        </button>`;
    }

    menu.innerHTML = items;
    document.body.appendChild(menu);

    const rect = anchorEl.getBoundingClientRect();
    let top = rect.top;
    let left = Math.max(8, Math.min(rect.right - 172, window.innerWidth - 180));
    if (top + 176 > window.innerHeight) top = Math.max(8, rect.bottom - 176);

    menu.style.top = top + 'px';
    menu.style.left = left + 'px';

    anchorEl.closest('.lc-msg')?.classList.add('lc-msg-has-menu');
  }

  async function toggleStar(id) { const r = await fetch(`/api/live-chat/messages/${id}/star`, { method:'POST', headers:headers() }); if (r.ok) { const d=await r.json(), m=state.messages.find(x=>x.id===id); if(m)m.is_starred=d.is_starred; closeAllMenus(); renderMessages(); } }
  function showReactions(id, button) { const p=document.createElement('div'); p.className='lc-reaction-picker'; p.innerHTML='👍 ❤️ 😂 😮'; p.onclick=async e=>{const emoji=e.target.textContent.trim(); if(!emoji)return; const r=await fetch(`/api/live-chat/messages/${id}/reaction`,{method:'POST',headers:{...headers(),'Content-Type':'application/json'},body:JSON.stringify({emoji})}); if(r.ok){const d=await r.json(),m=state.messages.find(x=>x.id===id);if(m)m.reaction_summary=d.reaction_summary;closeAllMenus();renderMessages();}}; button.parentElement.appendChild(p); }
  function showDeleteOptions(id, canDelete) {
    closeAllMenus();
    const anchorEl = document.querySelector(`[data-msg-id="${id}"] .lc-msg-anchor`);
    if (!anchorEl) return;
    const menu = document.createElement('div');
    menu.className = 'lc-context-menu';
    menu.dataset.msgId = id;
    menu.innerHTML = `
      <button class="lc-menu-item lc-menu-danger" onclick="window.LiveChat.hideForMe(${id})">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
        Hapus untuk saya
      </button>
      <button class="lc-menu-item lc-menu-danger" onclick="window.LiveChat.deleteMsg(${id})" ${canDelete?'':'disabled'}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
        Hapus untuk semua orang
      </button>
      <button class="lc-menu-item" onclick="window.LiveChat.closeMenus()">Batal</button>`;
    document.body.appendChild(menu);
    const rect = anchorEl.getBoundingClientRect();
    let top = rect.top;
    let left = Math.max(8, Math.min(rect.right - 172, window.innerWidth - 180));
    if (top + 176 > window.innerHeight) top = Math.max(8, rect.bottom - 176);
    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
  }
  async function hideForMe(id) { if(!confirm('Hapus pesan ini hanya dari chat Anda?'))return; const r=await fetch(`/api/live-chat/messages/${id}/hide`,{method:'POST',headers:headers()});if(r.ok){state.messages=state.messages.filter(m=>m.id!==id);closeAllMenus();renderMessages();} }

  function getSenderName(msg) {
    if (msg.sender_type === 'user') return AUTH_USER?.name || 'Anda';
    if (msg.sender_type === 'admin') return 'Admin';
    return 'System';
  }

  async function fetchChannels() {
    try {
      const url = IS_GUEST ? GUEST_CHANNELS_API + '?guest_id=' + guestParam() : CHANNELS_API;
      const res = await fetch(url, { headers: headers() });
      if (!res.ok) throw new Error('Failed');
      state.channels = await res.json();
      renderPanel();
    } catch (e) {
      console.error('LiveChat: fetch channels error', e);
    }
  }

  function renderPanel() {
    const container = document.getElementById('lc-body');
    if (!container) return;

    const guestBanner = IS_GUEST
      ? '<div class="lc-login-prompt" style="padding:10px 14px;margin-bottom:8px"><p style="margin:0;font-size:12px">Chat sebagai tamu — admin membalas tanpa perlu login.</p></div>'
      : '';

    const gameCards = state.channels.map(ch => {
      const isOnline = ch.is_online;
      const initial = getInitials(ch.name);
      const avatarHtml = ch.admin_photo
        ? `<div class="lc-game-avatar has-photo"><img src="${ch.admin_photo}" alt="" style="width:100%;height:100%;object-fit:cover"></div>`
        : `<div class="lc-game-avatar">${initial}</div>`;
      const statusHtml = isOnline
        ? `<div class="lc-game-admin active">Admin online</div>`
        : `<div class="lc-game-admin inactive">Admin belum tersedia</div>`;
      const clickAttr = isOnline
        ? `onclick="window.LiveChat.openChannel('${ch.slug}')" style="cursor:pointer"`
        : `style="cursor:default;opacity:0.55"`;
      return `
        <div class="lc-game-card" ${clickAttr}>
          ${avatarHtml}
          <div class="lc-game-info">
            <div class="lc-game-name">${escapeHtml(ch.name)}</div>
            ${statusHtml}
          </div>
        </div>`;
    }).join('');

    const activeConvs = state.channels
      .filter(ch => ch.has_conversation)
      .sort((a, b) => {
        if (a.last_message_at && b.last_message_at) return new Date(b.last_message_at) - new Date(a.last_message_at);
        if (a.last_message_at) return -1;
        if (b.last_message_at) return 1;
        return 0;
      });

    const convHtml = activeConvs.length > 0
      ? activeConvs.map(ch => {
          const initial = getInitials(ch.name);
          const preview = ch.last_message
            ? (ch.last_message.message_type === 'image' ? '📷 Foto'
              : ch.last_message.message_type === 'video' ? '🎬 Video'
              : ch.last_message.message_type === 'audio' ? '🎤 Pesan suara'
              : escapeHtml(ch.last_message.message?.substring(0, 50) || ''))
            : 'Mulai percakapan';
          const time = ch.last_message_at ? timeAgo(ch.last_message_at) : '';
          const badge = ch.unread_count > 0 ? `<span class="lc-conv-badge">${ch.unread_count}</span>` : '';
          const avatarSrc = ch.admin_photo;
          return `
            <div class="lc-conv-item" onclick="window.LiveChat.openChannel('${ch.slug}')">
              <div class="lc-conv-avatar">
                ${avatarSrc ? `<img src="${avatarSrc}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px">` : initial}
              </div>
              <div class="lc-conv-info">
                <div class="lc-conv-name">${escapeHtml(ch.name)}</div>
                <div class="lc-conv-preview">${preview}</div>
              </div>
              <div class="lc-conv-meta">
                <div class="lc-conv-time">${time}</div>
                ${badge}
              </div>
            </div>`;
        }).join('')
      : '<div class="lc-conv-empty">Belum ada percakapan aktif</div>';

    container.innerHTML = `
      <div class="lc-panel">
        <div class="lc-panel-header">
          <div class="lc-panel-title">LIVE CHAT ADMIN</div>
        </div>
        ${guestBanner}
        <div class="lc-panel-grid">${gameCards}</div>
        <div class="lc-panel-divider"></div>
        <div class="lc-panel-section-title">PERCAKAPAN AKTIF</div>
        <div class="lc-conv-list">${convHtml}</div>
        ${notifRowHtml()}
      </div>`;
    refreshNotifRow();
  }

  function notifRowHtml() {
    if (!AUTH_USER) return '';
    const hasBridge = !!(window.PushBridge && window.PushBridge.canPush);
    const statusText = hasBridge ? 'Dapatkan notifikasi saat admin membalas' : 'Notifikasi tidak tersedia';
    const btn = hasBridge
      ? '<button type="button" class="lc-notif-btn" id="lc-notif-btn" onclick="window.LiveChat.toggleNotif()">Aktifkan</button>'
      : '';
    return `
      <div class="lc-notif-row" id="lc-notif-row">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <div class="lc-notif-info">
          <div class="lc-notif-title">Notifikasi chat</div>
          <div class="lc-notif-sub" id="lc-notif-sub">${statusText}</div>
        </div>
        ${btn}
      </div>`;
  }

  function refreshNotifRow() {
    const row = document.getElementById('lc-notif-row');
    if (!row) return;
    const sub = document.getElementById('lc-notif-sub');
    const btn = document.getElementById('lc-notif-btn');
    if (!window.PushBridge || !window.PushBridge.canPush) return;
    window.PushBridge.status().then(function (s) {
      if (!sub || !btn) return;
      if (s.permission === 'granted' && s.subscribed) {
        sub.textContent = 'Notifikasi aktif di browser ini';
        btn.textContent = 'Aktif ✓';
        btn.disabled = true;
        btn.classList.add('is-on');
      } else if (s.permission === 'denied') {
        sub.textContent = 'Notifikasi diblokir di browser — izinkan di pengaturan situs';
        btn.textContent = 'Diblokir';
        btn.disabled = true;
      } else {
        sub.textContent = 'Dapatkan notifikasi saat admin membalas';
        btn.textContent = 'Aktifkan';
        btn.disabled = false;
        btn.classList.remove('is-on');
      }
    }).catch(function () {});
  }

  async function toggleNotif() {
    const btn = document.getElementById('lc-notif-btn');
    const sub = document.getElementById('lc-notif-sub');
    if (!btn || !window.PushBridge || !window.PushBridge.canPush) return;
    btn.disabled = true;
    btn.textContent = 'Memproses…';
    try {
      const res = await window.PushBridge.enable();
      if (res.granted && res.subscribed) {
        if (sub) sub.textContent = 'Notifikasi aktif di browser ini';
        btn.textContent = 'Aktif ✓';
        btn.classList.add('is-on');
      } else if (res.permission === 'denied') {
        if (sub) sub.textContent = 'Notifikasi diblokir di browser — izinkan di pengaturan situs';
        btn.textContent = 'Diblokir';
      } else {
        if (sub) sub.textContent = 'Izin ditunda — klik lagi untuk aktifkan';
        btn.textContent = 'Aktifkan';
        btn.disabled = false;
      }
    } catch (e) {
      console.error('LiveChat: enable notif error', e);
      if (sub) sub.textContent = 'Gagal, coba lagi';
      btn.textContent = 'Aktifkan';
      btn.disabled = false;
    }
  }

  function renderGuestNameForm() {
    const container = document.getElementById('lc-body');
    if (!container) return;
    container.innerHTML = `
      <div class="lc-name-form">
        <div class="lc-name-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="lc-name-title">Halo! Siapa nama kamu?</div>
        <div class="lc-name-sub">Masukkan nama untuk memulai chat dengan admin.</div>
        <input type="text" id="lc-name-input" class="lc-name-input" maxlength="255" placeholder="Nama kamu" autocomplete="off" autocapitalize="words">
        <div class="lc-name-error" id="lc-name-error" style="display:none">Nama tidak boleh kosong</div>
        <button type="button" class="lc-name-btn" onclick="window.LiveChat.submitGuestName()">Mulai Chat</button>
      </div>`;
    const input = document.getElementById('lc-name-input');
    if (input) {
      input.focus();
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          window.LiveChat.submitGuestName();
        }
      });
    }
  }

  function submitGuestName() {
    const input = document.getElementById('lc-name-input');
    if (!input) return;
    const name = input.value.trim();
    const errEl = document.getElementById('lc-name-error');
    if (!name) {
      if (errEl) errEl.style.display = 'block';
      input.focus();
      return;
    }
    if (errEl) errEl.style.display = 'none';
    saveGuestName(name);
    openChannel(CS_SLUG).catch(function () {});
  }

  async function openChannel(slug) {
    closeAllMenus();
    try {
      const res = IS_GUEST
        ? await fetch('/api/live-chat/guest/conversation', {
            method: 'POST',
            headers: { ...headers(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ channel_slug: slug, guest_id: getGuestId(), guest_name: getGuestName() || null })
          })
        : await fetch(`/api/live-chat/conversation/${slug}`, { headers: headers() });
      if (!res.ok) throw new Error('Failed');
      const data = await res.json();
      state.activeChannel = state.channels.find(c => c.slug === slug);
      if (!state.activeChannel && IS_GUEST) state.activeChannel = { slug, name: data.conversation?.channel?.name || 'Live Chat' };
      if (IS_GUEST) {
        if (data.session_expired || data.conversation?.status === 'ended') {
          state.conversation = data.conversation;
          touchActivity();
          endSession('Sesi chat Anda telah berakhir. Mulai chat baru untuk terhubung kembali.');
          return;
        }
        const anchor = data.conversation?.last_message_at || data.conversation?.created_at;
        state.lastActivity = anchor ? new Date(anchor).getTime() : Date.now();
      }
      state.conversation = data.conversation;
      if (data.conversation?.channel) state.activeChannel = data.conversation.channel;
      state.view = 'chat';
      state.replyToMsg = null;
      renderChat(data.active_operator);
      await fetchMessages();
      startPolling();
      markConversationRead();
      updateBadge();
    } catch (e) {
      console.error('LiveChat: open channel error', e);
    }
  }

  function markConversationRead() {
    if (!state.conversation) return;
    if (IS_GUEST) {
      fetch(`/api/live-chat/guest/conversation/${state.conversation.id}/read`, {
        method: 'PATCH',
        headers: { ...headers(), 'Content-Type': 'application/json' },
        body: JSON.stringify({ guest_id: getGuestId() })
      });
    } else {
      fetch(`/api/live-chat/conversation/${state.conversation.id}/read`, { method: 'PATCH', headers: headers() });
    }
  }

  async function fetchMessages() {
    if (!state.conversation || state.sessionEnded) return;
    try {
      const after = state.messages.length > 0 ? `after=${state.messages[state.messages.length - 1].id}` : '';
      const url = IS_GUEST
        ? `/api/live-chat/guest/messages/${state.conversation.id}?guest_id=${guestParam()}${after ? '&' + after : ''}`
        : `/api/live-chat/messages/${state.conversation.id}${after ? '?' + after : ''}`;
      const res = await fetch(url, { headers: headers() });
      if (!res.ok) throw new Error('Failed');
      const payload = await res.json();
      const newMessages = IS_GUEST && payload && !Array.isArray(payload) ? payload.messages || [] : payload;
      if (IS_GUEST && payload && !Array.isArray(payload) && payload.channel) {
        applyGuestChannelHeader(payload.channel);
        if (state.activeChannel?.id && payload.channel.id && state.activeChannel.id !== payload.channel.id) {
          state.activeChannel = payload.channel;
        }
      }
      if (newMessages.length > 0) {
        touchActivity();
        state.messages.push(...newMessages);
        newMessages.forEach(m => appendUserMsg(m));
        scrollToBottom();
        setTimeout(scrollToBottom, 100);
        dismissActiveToast();
      }
      if (!IS_GUEST && state.conversation.user_unread_count > 0) {
        fetch(`/api/live-chat/conversation/${state.conversation.channel?.slug || state.activeChannel?.slug}`, { headers: headers() });
      }
    } catch (e) {
      console.error('LiveChat: fetch messages error', e);
    }
  }

  function renderMessages() {
    const container = document.getElementById('lc-messages-body');
    if (!container) return;

    if (state.messages.length === 0) {
      container.innerHTML = '<div class="lc-empty">Mulai percakapan dengan mengirim pesan</div>';
      return;
    }

    container.innerHTML = state.messages.map(msg => {
      if (msg.sender_type === 'system') {
        return `<div class="lc-msg lc-msg-system" data-msg-id="${msg.id}"><div class="lc-msg-bubble">${escapeHtml(msg.message)}</div></div>`;
      }

      const isUser = msg.sender_type === 'user';
      const cls = isUser ? 'lc-msg-user' : 'lc-msg-admin';
      const time = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

      let quoteHtml = '';
      if (msg.reply_to) {
        const rSender = msg.reply_to.sender_type === 'admin' ? 'Admin' : (msg.reply_to.sender?.name || (msg.reply_to.sender_type === 'user' ? (AUTH_USER?.name || 'Anda') : 'Admin'));
        let rContent = '';
        if (msg.reply_to.message_type === 'image' && msg.reply_to.media_path) {
          const n = attachmentsOf(msg.reply_to).length;
          rContent = `<span class="lc-quote-media">📷 ${n > 1 ? n + ' ' : ''}Foto</span>`;
        } else if (msg.reply_to.message_type === 'video' && msg.reply_to.media_path) {
          rContent = '<span class="lc-quote-media">🎬 Video</span>';
        } else {
          rContent = escapeHtml((msg.reply_to.message || 'Pesan telah dihapus').substring(0, 80));
        }
        quoteHtml = `
          <div class="lc-msg-quote" onclick="event.stopPropagation();window.LiveChat.scrollToMsg(${msg.reply_to.id})">
            <div class="lc-quote-bar"></div>
            <div class="lc-quote-content">
              <div class="lc-quote-sender">${escapeHtml(rSender)}</div>
              <div class="lc-quote-text">${rContent}</div>
            </div>
          </div>`;
      }

      let mediaHtml = galleryHtml(msg, msg.id);

      const bubbleContent = msg.message
        ? `<div class="lc-msg-bubble">${escapeHtml(msg.message)}</div>`
        : '';

      return `
        <div class="lc-msg ${cls}" data-msg-id="${msg.id}">
          <div class="lc-msg-body">
            ${quoteHtml}
            ${mediaHtml}
            ${bubbleContent}
            <div class="lc-msg-time">${time}</div>
          </div>
          <div class="lc-msg-anchor" onclick="window.LiveChat.showMenu(event, ${msg.id})">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
        </div>`;
    }).join('');
  }

  function scrollToBottom() {
    const container = document.getElementById('lc-messages-body');
    if (!container) return;
    const doScroll = () => { container.scrollTop = container.scrollHeight; };
    requestAnimationFrame(() => { requestAnimationFrame(doScroll); });
    setTimeout(doScroll, 150);
  }

  function scrollToMsg(id) {
    const el = document.querySelector(`[data-msg-id="${id}"]`);
    if (el) {
      const container = document.getElementById('lc-messages-body');
      const isVisible = el.offsetTop >= container.scrollTop && el.offsetTop + el.offsetHeight <= container.scrollTop + container.offsetHeight;
      if (!isVisible) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      el.classList.add('lc-msg-highlight');
      setTimeout(() => { el.classList.remove('lc-msg-highlight'); }, 2000);
    }
  }

  function applyGuestChannelHeader(channel) {
    const titleEl = document.querySelector('.lc-chat-title');
    if (titleEl && channel?.name) titleEl.textContent = channel.name;
    if (channel?.name) {
      const fallback = document.querySelector('.lc-chat-avatar .lc-chat-avatar-fallback');
      if (fallback) fallback.textContent = getInitials(channel.name);
    }
    const servedEl = document.querySelector('.lc-chat-served');
    if (servedEl && channel?.name) {
      const shortName = String(channel.name).replace(/^Johen\s+/i, '');
      servedEl.innerHTML = 'Terhubung dengan <strong>Admin ' + escapeHtml(shortName) + '</strong>';
    }
  }

  function renderChat(operator) {
    const container = document.getElementById('lc-body');
    if (!container) return;

    const channelName = escapeHtml(state.activeChannel?.name || 'Live Chat');
    const isOnline = !!operator;

    const avatarImg = operator?.photo
      ? `<img src="${operator.photo}" alt="" class="lc-chat-avatar">`
      : `<div class="lc-chat-avatar lc-chat-avatar-fallback">${getInitials(state.activeChannel?.name || 'Live Chat')}</div>`;
    const avatarHtml = `<div class="lc-chat-avatar-wrap">${avatarImg}<span class="lc-avatar-status ${isOnline ? '' : 'offline'}"></span></div>`;

    const statusPill = IS_GUEST
      ? `<span class="lc-status-pill is-online"><span class="lc-pulse-dot"></span>Admin CS</span>`
      : (isOnline
          ? `<span class="lc-status-pill is-online"><span class="lc-pulse-dot"></span>Online</span>`
          : `<span class="lc-status-pill is-offline"><span class="lc-pulse-dot"></span>Offline</span>`);

    const servedHtml = IS_GUEST
      ? `<div class="lc-chat-served">Terhubung dengan <strong>Admin CS Johen</strong></div><div class="lc-chat-presence">Anda chat sebagai <strong>${escapeHtml(getGuestName() || 'Tamu')}</strong> — admin akan membalas pesanmu di sini</div>`
      : (operator
          ? `<div class="lc-chat-served">Sedang dilayani oleh <strong>${escapeHtml(operator.name)}</strong></div><div class="lc-chat-presence">${operator.schedule ? `Shift ${escapeHtml(operator.schedule)}` : 'Berespons cepat pada jam operasional'}</div>`
          : `<div class="lc-chat-served">Admin sedang offline</div><div class="lc-chat-presence">Pesan akan dibalas pada jam operasional</div>`);

    container.innerHTML = `
      <div class="lc-chat" style="position:relative">
        <div class="lc-chat-header">
          <button class="lc-chat-back" onclick="window.LiveChat.backToList()" title="Kembali">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div class="lc-chat-title-row">
            ${avatarHtml}
            <div class="lc-chat-info">
              <div class="lc-chat-title">${channelName}${isOnline ? '<span class="lc-live-pulse"></span>' : ''}</div>
              ${servedHtml}
            </div>
          </div>
          <div class="lc-chat-actions">
            ${statusPill}
          </div>
        </div>
        <div class="lc-messages" id="lc-messages-body">
          <div class="lc-chat-ambience" aria-hidden="true"></div>
          <div class="lc-empty">Memuat pesan...</div>
        </div>
        <div id="lc-reply-preview" class="lc-reply-preview" style="display:none"></div>
        <div class="lc-media-editor" id="lc-media-editor" style="display:none">
          <div class="lc-media-main" id="lc-media-main">
            <button class="lc-media-close" onclick="window.LiveChat.cancelMediaEditor()">✕</button>
            <button class="lc-media-nav prev" id="lc-media-prev" onclick="window.LiveChat.navMedia(-1)" style="display:none">‹</button>
            <button class="lc-media-nav next" id="lc-media-next" onclick="window.LiveChat.navMedia(1)" style="display:none">›</button>
            <div class="lc-media-counter" id="lc-media-counter" style="display:none"></div>
          </div>
          <div class="lc-media-caption-bar">
            <textarea class="lc-media-caption-input" id="lc-media-caption" rows="1" placeholder="Tambahkan caption..." oninput="window.LiveChat.updateActiveCaption(this.value);window.LiveChat.autoResize(this)"></textarea>
          </div>
          <div class="lc-media-thumbs" id="lc-media-thumbs"></div>
          <div class="lc-media-footer">
            <button class="lc-media-send-btn" id="lc-media-send-btn" onclick="window.LiveChat.sendText()" disabled>
              <span class="lc-media-send-count" id="lc-media-send-count">0</span>
              Kirim
            </button>
          </div>
        </div>
        <div class="lc-composer">
          <div class="lc-rec-bar" id="lc-rec-bar" style="display:none">
            <span class="lc-rec-dot"></span>
            <span class="lc-rec-time" id="lc-rec-time">0:00</span>
            <span class="lc-rec-hint">Merekam...</span>
            <div class="lc-rec-spacer"></div>
            <button type="button" class="lc-rec-btn lc-rec-cancel" onclick="window.LiveChat.cancelVoiceRec()" title="Batal" aria-label="Batal rekaman"><svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            <button type="button" class="lc-rec-btn lc-rec-send" onclick="window.LiveChat.finishVoiceRec()" title="Kirim" aria-label="Kirim pesan suara"><svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
          </div>
          <input type="file" id="lc-file-input" accept="image/*,video/*" multiple style="display:none" onchange="window.LiveChat.onFileSelect(event)">
          <input type="file" id="lc-doc-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z" multiple style="display:none" onchange="window.LiveChat.onFileSelect(event)">
          <input type="file" id="lc-camera-input" accept="image/*" capture="environment" style="display:none" onchange="window.LiveChat.onFileSelect(event)">
          <input type="file" id="lc-audio-input" accept="audio/*" style="display:none" onchange="window.LiveChat.onFileSelect(event)">
          <div class="lc-attach-wrap">
            <button class="lc-composer-attach" id="lc-attach-btn" onclick="window.LiveChat.toggleAttachMenu(event)" title="Lampiran">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
            </button>
            <div class="lc-attach-menu" id="lc-attach-menu" role="menu">
              <button class="lc-attach-item lc-attach-doc" onclick="window.LiveChat.attachAction('doc')">
                <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                <span class="lc-attach-label">Dokumen</span>
              </button>
              <button class="lc-attach-item lc-attach-photo" onclick="window.LiveChat.attachAction('media')">
                <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
                <span class="lc-attach-label">Foto &amp; Video</span>
              </button>
              <button class="lc-attach-item lc-attach-camera" onclick="window.LiveChat.attachAction('camera')">
                <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
                <span class="lc-attach-label">Kamera</span>
              </button>
              <button class="lc-attach-item lc-attach-audio" onclick="window.LiveChat.attachAction('audio')">
                <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></span>
                <span class="lc-attach-label">Audio</span>
              </button>
            </div>
          </div>
          <textarea class="lc-composer-input" id="lc-input" rows="1" placeholder="Ketik pesan..." onkeydown="window.LiveChat.onKeydown(event)" oninput="window.LiveChat.autoResize(this)"></textarea>
          <button class="lc-composer-mic" id="lc-mic-btn" onclick="window.LiveChat.startVoiceRec()" title="Rekam pesan suara" aria-label="Rekam pesan suara">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v1a7 7 0 0 0 14 0v-1M12 18v4"/></svg>
          </button>
          <button class="lc-composer-btn" id="lc-send-btn" onclick="window.LiveChat.sendText()" disabled>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>
      </div>`;

    const input = document.getElementById('lc-input');
    if (input) input.addEventListener('input', updateSendBtn);
  }

  function updateSendBtn() {
    const btn = document.getElementById('lc-send-btn');
    if (btn) {
      const input = document.getElementById('lc-input');
      btn.disabled = !input?.value?.trim() && state.mediaList.length === 0;
    }
    const mediaBtn = document.getElementById('lc-media-send-btn');
    if (mediaBtn) {
      mediaBtn.disabled = state.mediaList.length === 0;
      const countEl = document.getElementById('lc-media-send-count');
      if (countEl) countEl.textContent = state.mediaList.length;
    }
  }

  function backToList() {
    cancelVoiceRec();
    stopPolling();
    closeAllMenus();
    if (IS_GUEST) {
      routeBackToReception();
      return;
    }
    state.view = 'panel';
    state.activeChannel = null;
    state.conversation = null;
    state.messages = [];
    state.replyToMsg = null;
    fetchChannels();
  }

  async function routeBackToReception() {
    if (!state.conversation) {
      close();
      return;
    }
    const inReception = (state.activeChannel?.slug || state.conversation.channel?.slug) === CS_SLUG;
    if (inReception) {
      close();
      return;
    }
    try {
      const res = await fetch(`/api/live-chat/guest/conversation/${state.conversation.id}/route-back`, {
        method: 'POST',
        headers: { ...headers(), 'Content-Type': 'application/json' },
        body: JSON.stringify({ guest_id: getGuestId() })
      });
      if (!res.ok) throw new Error('Failed');
      const data = await res.json();
      if (data.session_expired || data.conversation?.status === 'ended') {
        endSession('Sesi chat Anda telah berakhir. Mulai chat baru untuk terhubung kembali.');
        return;
      }
      await openChannel(CS_SLUG);
    } catch (e) {
      console.error('LiveChat: route-back error', e);
      close();
    }
  }

  function onKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendText();
    }
  }

  function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 80) + 'px';
  }

  /* ---- MEDIA EDITOR ---- */
  function onFileSelect(e) {
    const newFiles = Array.from(e.target.files);
    if (!newFiles.length) return;
    e.target.value = '';
    let supported = 0;
    newFiles.forEach(f => {
      const isImg = f.type.startsWith('image/');
      const isVideo = f.type.startsWith('video/');
      const isAudio = f.type.startsWith('audio/');
      if (isAudio) {
        supported++;
        if (state.conversation) {
          sendVoiceFile(f, 0);
        } else {
          showToast('Mulai percakapan terlebih dahulu untuk mengirim pesan suara.', 'error');
        }
        return;
      }
      if (!isImg && !isVideo) return;
      supported++;
      const url = URL.createObjectURL(f);
      state.mediaList.push({ file: f, url, type: isImg ? 'image' : 'video', caption: '' });
    });
    if (supported < newFiles.length) {
      showToast('Format file tidak didukung. Hanya gambar, video & audio yang bisa dikirim.', 'error');
    }
    if (supported === 0) return;
    if (state.mediaList.length > 0 && document.getElementById('lc-media-editor')?.style.display !== 'flex') {
      state.activeIndex = 0;
    }
    openMediaEditor();
  }

  function toggleAttachMenu(e) {
    e.stopPropagation();
    e.preventDefault();
    const menu = document.getElementById('lc-attach-menu');
    if (menu) menu.classList.toggle('open');
  }
  function attachAction(action) {
    closeAttachMenu();
    if (action === 'doc') document.getElementById('lc-doc-input').click();
    else if (action === 'media') document.getElementById('lc-file-input').click();
    else if (action === 'camera') document.getElementById('lc-camera-input').click();
    else if (action === 'audio') startVoiceRec();
  }
  function closeAttachMenu() {
    const menu = document.getElementById('lc-attach-menu');
    if (menu) menu.classList.remove('open');
  }

  /* ---- VOICE NOTE ---- */
  function formatVoiceTime(sec) {
    sec = Math.round(Number(sec) || 0);
    if (sec < 0) sec = 0;
    const m = Math.floor(sec / 60);
    const s = String(sec % 60).padStart(2, '0');
    return m + ':' + s;
  }
  function voiceRecMime() {
    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    for (let i = 0; i < candidates.length; i++) {
      if (window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(candidates[i])) return candidates[i];
    }
    return 'audio/webm';
  }
  function voiceExt(mime) {
    if (/mp4|m4a/i.test(mime)) return 'm4a';
    if (/ogg|opus/i.test(mime)) return 'ogg';
    return 'webm';
  }
  function stopVoiceTracks() {
    if (state.recStream) {
      try { state.recStream.getTracks().forEach(t => t.stop()); } catch (e) {}
      state.recStream = null;
    }
  }
  function resetRecorder() {
    clearInterval(state.recTimer);
    state.recTimer = null;
    state.recorder = null;
    state.recChunks = [];
    state.recMime = '';
    state.recStartTs = 0;
    state.recSeconds = 0;
    stopVoiceTracks();
  }
  function setRecBar(visible) {
    const bar = document.getElementById('lc-rec-bar');
    if (bar) bar.style.display = visible ? 'flex' : 'none';
    const mic = document.getElementById('lc-mic-btn');
    if (mic) mic.style.display = visible ? 'none' : '';
    const input = document.getElementById('lc-input');
    if (input) input.style.display = visible ? 'none' : '';
    const send = document.getElementById('lc-send-btn');
    if (send) send.style.display = visible ? 'none' : '';
    const attach = document.querySelector('.lc-composer .lc-attach-wrap');
    if (attach) attach.style.display = visible ? 'none' : '';
    if (visible) closeAttachMenu();
  }
  function voiceErrMsg(err) {
    const name = (err && err.name) || '';
    if (!window.isSecureContext) {
      return 'Mikrofon hanya tersedia via HTTPS atau http://localhost. Origin saat ini tidak aman (' + location.origin + ').';
    }
    if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
      return 'Akses mikrofon diblokir. Cek ikon kunci di address bar, izin situs, dan pengaturan mikrofon Windows.';
    }
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
      return 'Tidak ada mikrofon yang terdeteksi.';
    }
    if (name === 'NotReadableError' || name === 'TrackStartError') {
      return 'Mikrofon sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
    }
    if (name === 'OverconstrainedError') {
      return 'Mikrofon tidak mendukung pengaturan yang diminta.';
    }
    return 'Gagal mengakses mikrofon (' + (name || 'Error') + '): ' + ((err && err.message) || 'tidak diketahui');
  }
  async function startVoiceRec() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      showToast(window.isSecureContext ? 'Browser tidak mendukung perekaman suara.' : 'Mikrofon hanya tersedia via HTTPS atau http://localhost.', 'error');
      return;
    }
    if (!state.conversation) {
      showToast('Mulai percakapan terlebih dahulu.', 'error');
      return;
    }
    if (state.recorder) {
      try { state.recorder.resume(); } catch (e) {}
      return;
    }
    closeAttachMenu();
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      state.recStream = stream;
      state.recMime = voiceRecMime();
      let recorder;
      try {
        recorder = state.recMime ? new MediaRecorder(stream, { mimeType: state.recMime }) : new MediaRecorder(stream);
      } catch (e) {
        recorder = new MediaRecorder(stream);
      }
      state.recMime = recorder.mimeType || state.recMime || 'audio/webm';
      state.recChunks = [];
      recorder.ondataavailable = (e) => { if (e.data && e.data.size) state.recChunks.push(e.data); };
      recorder.start();
      state.recorder = recorder;
      state.recStartTs = Date.now();
      state.recSeconds = 0;
      setRecBar(true);
      const timeEl = document.getElementById('lc-rec-time');
      if (timeEl) {
        timeEl.textContent = '0:00';
        state.recTimer = setInterval(() => {
          state.recSeconds = Math.floor((Date.now() - state.recStartTs) / 1000);
          timeEl.textContent = formatVoiceTime(state.recSeconds);
          if (state.recSeconds >= 1800) finishVoiceRec();
        }, 500);
      }
    } catch (err) {
      console.error('LiveChat: mic error', err);
      showToast(voiceErrMsg(err), 'error');
    }
  }
  function cancelVoiceRec() {
    const rec = state.recorder;
    clearInterval(state.recTimer);
    state.recTimer = null;
    if (rec && rec.state !== 'inactive') {
      rec.onstop = () => resetRecorder();
      try { rec.stop(); } catch (e) { resetRecorder(); }
    } else {
      resetRecorder();
    }
    setRecBar(false);
  }
  function finishVoiceRec() {
    const rec = state.recorder;
    if (!rec || rec.state === 'inactive') return;
    setRecBar(false);
    const duration = Math.max(1, state.recSeconds || Math.floor((Date.now() - state.recStartTs) / 1000));
    const mime = state.recMime || 'audio/webm';
    rec.onstop = () => {
      const blob = new Blob(state.recChunks, { type: mime });
      if (blob.size === 0) {
        resetRecorder();
        showToast('Rekaman kosong.', 'error');
        return;
      }
      resetRecorder();
      const file = new File([blob], 'voice-note-' + Date.now() + '.' + voiceExt(mime), { type: mime });
      sendVoiceFile(file, duration);
    };
    try { rec.stop(); } catch (e) { resetRecorder(); setRecBar(false); }
    setTimeout(() => setRecBar(false), 4000);
  }
  async function sendVoiceFile(file, duration) {
    if (!state.conversation) return;
    const tempId = Date.now();
    const replyId = state.replyToMsg?.id || null;
    addLoadingMsg(tempId, file);
    clearReplyPreview();
    try {
      const formData = new FormData();
      formData.append('conversation_id', state.conversation.id);
      if (IS_GUEST) formData.append('guest_id', getGuestId());
      formData.append('message_type', 'audio');
      formData.append('media[]', file);
      formData.append('media_duration', String(Math.round(duration)));
      if (replyId) formData.append('reply_to_message_id', replyId);
      const res = await fetch(messagePostUrl(), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getToken(), 'Accept': 'application/json' },
        body: formData,
      });
      removeLoadingMsg(tempId);
      if (!res.ok) {
        showToast(await readError(res, 'Gagal mengirim pesan suara'), 'error');
        return;
      }
      const msg = await res.json();
      touchActivity();
      state.messages.push(msg);
      appendUserMsg(msg);
      scrollToBottom();
      updateBadge();
    } catch (e) {
      removeLoadingMsg(tempId);
      showToast('Gagal mengirim pesan suara', 'error');
    }
  }
  function toggleVoicePlay(el) {
    const audio = el.querySelector('audio');
    if (!audio || !audio.src) return;
    const btn = el.querySelector('.lc-voice-btn');
    const fill = el.querySelector('.lc-voice-fill');
    const time = el.querySelector('.lc-voice-time');
    const setPlaying = (p) => btn && btn.classList.toggle('is-playing', p);
    if (window.__lcActiveVoice && window.__lcActiveVoice !== audio) {
      try { window.__lcActiveVoice.pause(); } catch (e) {}
    }
    window.__lcActiveVoice = audio.paused ? audio : null;
    if (!audio.dataset.lcBound) {
      audio.dataset.lcBound = '1';
      audio.addEventListener('play', () => setPlaying(true));
      audio.addEventListener('pause', () => setPlaying(false));
      audio.addEventListener('ended', () => {
        setPlaying(false);
        if (fill) fill.style.width = '0%';
        const d = el.dataset.dur;
        if (time) time.textContent = d ? formatVoiceTime(d) : '0:00';
        window.__lcActiveVoice = null;
      });
      audio.addEventListener('timeupdate', () => {
        if (!audio.duration || !isFinite(audio.duration)) return;
        if (fill) fill.style.width = ((audio.currentTime / audio.duration) * 100).toFixed(1) + '%';
        if (time) time.textContent = formatVoiceTime(audio.currentTime);
      });
    }
    if (audio.paused) {
      try {
        if (fill) fill.style.width = '0%';
        if (time) time.textContent = '0:00';
        const p = audio.play();
        if (p && p.catch) p.catch(() => setPlaying(false));
      } catch (e) { setPlaying(false); }
    } else {
      audio.pause();
    }
  }
  function openMediaEditor() {
    if (state.mediaList.length === 0) { cancelMediaEditor(); return; }
    const editor = document.getElementById('lc-media-editor');
    if (editor) editor.style.display = 'flex';
    renderMediaEditor();
  }
  function renderMediaEditor() {
    if (state.mediaList.length === 0) { cancelMediaEditor(); return; }
    const item = state.mediaList[state.activeIndex];
    const main = document.getElementById('lc-media-main');
    if (!main) return;
    const existing = main.querySelector('img, video');
    if (existing) existing.remove();
    let el;
    if (item.type === 'image') { el = document.createElement('img'); el.src = item.url; }
    else { el = document.createElement('video'); el.src = item.url; el.controls = true; }
    main.appendChild(el);
    const prev = document.getElementById('lc-media-prev');
    const next = document.getElementById('lc-media-next');
    if (prev) prev.style.display = state.activeIndex > 0 ? '' : 'none';
    if (next) next.style.display = state.activeIndex < state.mediaList.length - 1 ? '' : 'none';
    const counter = document.getElementById('lc-media-counter');
    if (counter) {
      if (state.mediaList.length > 1) { counter.textContent = `${state.activeIndex + 1} / ${state.mediaList.length}`; counter.style.display = ''; }
      else { counter.style.display = 'none'; }
    }
    const captionInput = document.getElementById('lc-media-caption');
    if (captionInput) captionInput.value = item.caption;
    renderThumbs();
    updateSendBtn();
  }
  function renderThumbs() {
    const bar = document.getElementById('lc-media-thumbs');
    if (!bar) return;
    bar.innerHTML = state.mediaList.map((m, i) => {
      const activeCls = i === state.activeIndex ? ' active' : '';
      const thumb = m.type === 'image' ? `<img src="${m.url}" alt="">` : `<video src="${m.url}" muted preload="metadata"></video>`;
      return `<div class="lc-media-thumb${activeCls}" onclick="window.LiveChat.setActiveIndex(${i})">${thumb}<button class="lc-media-thumb-remove" onclick="event.stopPropagation();window.LiveChat.removeMediaItem(${i})">✕</button></div>`;
    }).join('') + `<button class="lc-media-add" onclick="document.getElementById('lc-file-input').click()">+</button>`;
  }
  function setActiveIndex(i) { state.activeIndex = i; renderMediaEditor(); }
  function navMedia(dir) { const next = state.activeIndex + dir; if (next >= 0 && next < state.mediaList.length) { state.activeIndex = next; renderMediaEditor(); } }
  function updateActiveCaption(val) { if (state.mediaList[state.activeIndex]) state.mediaList[state.activeIndex].caption = val; }
  function removeMediaItem(i) {
    URL.revokeObjectURL(state.mediaList[i].url);
    state.mediaList.splice(i, 1);
    if (state.mediaList.length === 0) { cancelMediaEditor(); return; }
    if (state.activeIndex >= state.mediaList.length) state.activeIndex = state.mediaList.length - 1;
    renderMediaEditor();
  }
  function cancelMediaEditor() {
    state.mediaList.forEach(m => URL.revokeObjectURL(m.url));
    state.mediaList = [];
    state.activeIndex = 0;
    const editor = document.getElementById('lc-media-editor');
    if (editor) editor.style.display = 'none';
    const main = document.getElementById('lc-media-main');
    if (main) { const old = main.querySelector('img, video'); if (old) old.remove(); }
    const cap = document.getElementById('lc-media-caption');
    if (cap) cap.value = '';
    const thumbs = document.getElementById('lc-media-thumbs');
    if (thumbs) thumbs.innerHTML = '';
    updateSendBtn();
  }

  /* ---- SEND ---- */
  async function sendText() {
    const input = document.getElementById('lc-input');
    const text = input?.value?.trim();
    const hasMedia = state.mediaList.length > 0;
    const hasText = !!text;
    if (!hasMedia && !hasText) return;
    if (!state.conversation) return;

    const items = [...state.mediaList];
    const replyId = state.replyToMsg?.id || null;
    input.value = '';
    input.style.height = 'auto';
    document.getElementById('lc-send-btn').disabled = true;
    clearReplyPreview();
    cancelMediaEditor();

    if (hasMedia) {
      const imageItems = items.filter(it => it.type === 'image');
      const videoItems = items.filter(it => it.type === 'video');

      if (imageItems.length > 0) {
        const tempId = Date.now();
        addLoadingMsg(tempId, imageItems[0].file);
        try {
          const formData = new FormData();
          formData.append('conversation_id', state.conversation.id);
          if (IS_GUEST) formData.append('guest_id', getGuestId());
          formData.append('message_type', 'image');
          const caption = imageItems.map(i => i.caption).find(c => c) || '';
          if (caption) formData.append('message', caption);
          if (replyId) formData.append('reply_to_message_id', replyId);
          imageItems.forEach(fi => formData.append('media[]', fi.file));
          const res = await fetch(messagePostUrl(), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getToken(), 'Accept': 'application/json' },
            body: formData,
          });
          removeLoadingMsg(tempId);
          if (!res.ok) {
            showToast(await readError(res, 'Gagal mengirim gambar'), 'error');
          } else {
            const msg = await res.json();
            touchActivity();
            state.messages.push(msg);
            appendUserMsg(msg);
          }
        } catch (e) { removeLoadingMsg(tempId); showToast('Gagal mengirim gambar', 'error'); }
      }

      for (let i = 0; i < videoItems.length; i++) {
        const item = videoItems[i];
        const tempId = Date.now() + i;
        addLoadingMsg(tempId, item.file);
        try {
          const formData = new FormData();
          formData.append('conversation_id', state.conversation.id);
          if (IS_GUEST) formData.append('guest_id', getGuestId());
          formData.append('message_type', 'video');
          formData.append('media[]', item.file);
          if (item.caption) formData.append('message', item.caption);
          if (i === 0 && replyId) formData.append('reply_to_message_id', replyId);
          const thumbBlob = await extractVideoThumb(item.file);
          if (thumbBlob) {
            formData.append('thumbnail[]', thumbBlob, 'thumb.jpg');
            formData.append('thumbnail_indexes[]', '0');
          }
          const res = await fetch(messagePostUrl(), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': getToken(), 'Accept': 'application/json' },
            body: formData,
          });
          removeLoadingMsg(tempId);
          if (!res.ok) { showToast(await readError(res, 'Gagal mengirim file'), 'error'); continue; }
          const msg = await res.json();
          touchActivity();
          state.messages.push(msg);
          appendUserMsg(msg);
        } catch (e) { removeLoadingMsg(tempId); showToast('Gagal mengirim file', 'error'); }
      }
      scrollToBottom();
      return;
    }

    const tempId = Date.now();
    addLoadingMsg(tempId, null);

    try {
      const formData = new FormData();
      formData.append('conversation_id', state.conversation.id);
      if (IS_GUEST) formData.append('guest_id', getGuestId());
      formData.append('message_type', 'text');
      formData.append('message', text);
      if (replyId) formData.append('reply_to_message_id', replyId);

      const res = await fetch(messagePostUrl(), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getToken(), 'Accept': 'application/json' },
        body: formData,
      });
      if (!res.ok) {
        throw new Error(await readError(res, 'Gagal mengirim pesan'));
      }
      removeLoadingMsg(tempId);
      const msg = await res.json();
      touchActivity();
      state.messages.push(msg);
      appendUserMsg(msg);
      scrollToBottom();
    } catch (e) {
      console.error('LiveChat: send error', e);
      removeLoadingMsg(tempId);
      showToast(e.message || 'Gagal mengirim pesan', 'error');
      input.value = text;
    }
  }

  function showMenu(e, msgId) {
    e.stopPropagation();
    toggleContextMenu(msgId, e.currentTarget);
  }

  function replyTo(id) {
    closeAllMenus();
    const msg = state.messages.find(m => m.id === id);
    if (!msg) return;
    state.replyToMsg = msg;
    const senderName = getSenderName(msg);
    let preview = '';
    if (msg.message_type === 'image') {
      const n = attachmentsOf(msg).length;
      preview = n > 1 ? `📷 ${n} Foto` : '📷 Foto';
    } else if (msg.message_type === 'video') preview = '🎬 Video';
    else if (msg.message_type === 'audio') preview = '🎤 Pesan suara';
    else preview = escapeHtml((msg.message || '').substring(0, 80));
    const replyPreview = document.getElementById('lc-reply-preview');
    if (replyPreview) {
      replyPreview.innerHTML = `<div class="lc-reply-preview-inner"><div class="lc-reply-preview-bar"></div><div class="lc-reply-preview-content"><div class="lc-reply-preview-sender">↩ ${escapeHtml(senderName)}</div><div class="lc-reply-preview-text">${preview}</div></div><button class="lc-reply-preview-close" onclick="window.LiveChat.cancelReply()">✕</button></div>`;
      replyPreview.style.display = 'block';
    }
    const input = document.getElementById('lc-input');
    if (input) {
      input.placeholder = `Balas ${escapeHtml(senderName)}...`;
      input.focus();
    }
  }

  function cancelReply() {
    state.replyToMsg = null;
    const replyPreview = document.getElementById('lc-reply-preview');
    if (replyPreview) { replyPreview.innerHTML = ''; replyPreview.style.display = 'none'; }
    const input = document.getElementById('lc-input');
    if (input) input.placeholder = 'Ketik pesan...';
  }

  function clearReplyPreview() {
    state.replyToMsg = null;
    const replyPreview = document.getElementById('lc-reply-preview');
    if (replyPreview) { replyPreview.innerHTML = ''; replyPreview.style.display = 'none'; }
    const input = document.getElementById('lc-input');
    if (input) input.placeholder = 'Ketik pesan...';
  }

  async function deleteMsg(id) {
    closeAllMenus();
    if (!confirm('Hapus pesan ini untuk semua orang?')) return;
    try {
      const res = await fetch(`/api/live-chat/messages/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': getToken(), 'Accept': 'application/json' },
      });
      if (!res.ok) throw new Error('Gagal menghapus pesan');
      state.messages = state.messages.filter(m => m.id !== id);
      const el = document.querySelector(`[data-msg-id="${id}"]`);
      if (el) el.remove();
    } catch (e) {
      console.error('LiveChat: delete error', e);
      showToast('Gagal menghapus pesan', 'error');
    }
  }

  function dismissActiveToast() {
    if (!('serviceWorker' in navigator)) return;
    const convId = state.conversation?.id;
    if (!convId) return;
    navigator.serviceWorker.ready
      .then((reg) => {
        if (!reg.getNotifications) return;
        return reg.getNotifications().then((list) => {
          list.forEach((notification) => {
            if (notification.data?.conversationId === convId
              || notification.data?.conversationId === String(convId)) {
              notification.close();
            }
          });
        });
      })
      .catch(() => {});
  }

  function startPolling() {
    stopPolling();
    state.pollingTimer = setInterval(() => {
      if (state.sessionEnded) return;
      if (state.view === 'chat' && state.conversation) {
        if (IS_GUEST && state.lastActivity && Date.now() - state.lastActivity >= SESSION_TIMEOUT_MS) {
          endSession();
          return;
        }
        fetchMessages();
      }
    }, POLL_INTERVAL);
  }

  function stopPolling() {
    if (state.pollingTimer) { clearInterval(state.pollingTimer); state.pollingTimer = null; }
  }

  function toggle() {
    state.isOpen = !state.isOpen;
    const popup = document.getElementById('lc-popup');
    const overlay = document.getElementById('lc-overlay');
    if (state.isOpen) {
      popup?.classList.add('active');
      overlay?.classList.add('active');
      popup?.setAttribute('aria-hidden', 'false');
      overlay?.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      if (IS_GUEST) {
        if (getGuestName()) {
          openChannel(CS_SLUG).catch(function () {});
        } else {
          renderGuestNameForm();
        }
      } else if (state.view === 'panel') {
        fetchChannels();
      }
    } else {
      popup?.classList.remove('active');
      overlay?.classList.remove('active');
      popup?.setAttribute('aria-hidden', 'true');
      overlay?.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      closeAllMenus();
      stopPolling();
    }
  }

  function close() {
    cancelVoiceRec();
    state.isOpen = false;
    const popup = document.getElementById('lc-popup');
    const overlay = document.getElementById('lc-overlay');
    popup?.classList.remove('active');
    overlay?.classList.remove('active');
    popup?.setAttribute('aria-hidden', 'true');
    overlay?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    closeAllMenus();
    stopPolling();
  }

  function updateBadge() {
    if (IS_GUEST) return;
    const badge = document.getElementById('lc-fab-badge');
    if (!badge) return;
    fetch('/api/live-chat/unread', { headers: headers() })
      .then(r => r.json())
      .then(data => {
        const count = data.unread_count || 0;
        if (count > 0) { badge.textContent = count > 99 ? '99+' : count; badge.style.display = 'flex'; }
        else { badge.style.display = 'none'; }
      })
      .catch(() => {});
  }

  function openImage(event, imageUrl, messageId) {
    openLightbox([imageUrl], 0, messageId);
  }

  function openGallery(event, btn, messageId, startIndex) {
    event?.stopPropagation();
    let urls = [];
    try { urls = JSON.parse(btn.dataset.urls || '[]'); } catch (e) {}
    if (!urls.length) {
      const msg = state.messages.find(m => m.id === messageId);
      if (msg) urls = attachmentsOf(msg).map(a => `/media/${a.media_path}`);
    }
    if (!urls.length) return;
    openLightbox(urls, startIndex || 0, messageId);
  }

  function openLightbox(urls, startIndex, messageId) {
    const existing = document.getElementById('lc-image-lightbox');
    if (existing) existing.remove();

    let index = Math.max(0, Math.min(startIndex || 0, urls.length - 1));

    const lightbox = document.createElement('div');
    lightbox.id = 'lc-image-lightbox';
    lightbox.className = 'lc-image-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Pratinjau gambar');
    const hasMany = urls.length > 1;
    const thumbsHtml = urls.map((u, i) =>
      `<button type="button" class="lc-thumb${i === index ? ' active' : ''}" data-i="${i}" aria-label="Gambar ${i + 1}"><img src="${u}" alt="" loading="lazy"></button>`
    ).join('');
    lightbox.innerHTML = `
      <div class="lc-image-lightbox-tools" aria-label="Aksi gambar">
        <button type="button" data-action="reply" title="Balas" aria-label="Balas"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 9 5 14l5 5"/><path d="M5 14h9a5 5 0 0 1 5 5"/></svg></button>
        <button type="button" data-action="react" title="Beri reaksi" aria-label="Beri reaksi"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M8 14s1.4 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg></button>
        <button type="button" data-action="star" title="Beri bintang" aria-label="Beri bintang" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/></svg></button>
        <a id="lc-lightbox-download" href="${urls[index]}" download title="Unduh" aria-label="Unduh gambar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v11"/><path d="m8 10 4 4 4-4M5 20h14"/></svg></a>
        <button type="button" data-action="close" title="Tutup" aria-label="Tutup gambar">×</button>
        <div class="lc-image-reactions" hidden><button type="button">👍</button><button type="button">❤️</button><button type="button">😂</button><button type="button">😮</button></div>
      </div>
      <div class="lc-lightbox-stage">
        ${hasMany ? '<button type="button" class="lc-lightbox-nav prev" data-nav="-1" aria-label="Sebelumnya">‹</button>' : ''}
        <img id="lc-lightbox-img" src="${urls[index]}" alt="Gambar ukuran penuh">
        ${hasMany ? '<button type="button" class="lc-lightbox-nav next" data-nav="1" aria-label="Berikutnya">›</button>' : ''}
        ${hasMany ? '<div class="lc-lightbox-counter" id="lc-lightbox-counter">' + (index + 1) + ' / ' + urls.length + '</div>' : ''}
        ${hasMany ? `<div class="lc-lightbox-thumbs">${thumbsHtml}</div>` : ''}
      </div>
    `;

    const img = lightbox.querySelector('#lc-lightbox-img');
    const download = lightbox.querySelector('#lc-lightbox-download');
    const thumbs = lightbox.querySelector('.lc-lightbox-thumbs');
    let counter = null;
    const updateThumbs = () => {
      thumbs?.querySelectorAll('.lc-thumb').forEach((t, i) => {
        t.classList.toggle('active', i === index);
        if (i === index) t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      });
    };
    const setIndex = (i) => {
      index = (i + urls.length) % urls.length;
      img.src = urls[index];
      if (download) download.href = urls[index];
      if (counter) counter.textContent = `${index + 1} / ${urls.length}`;
      updateThumbs();
    };

    let onKeydown;
    const close = () => {
      lightbox.remove();
      document.removeEventListener('keydown', onKeydown);
    };
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox || e.target.classList.contains('lc-lightbox-stage')) close();
    });
    const prev = lightbox.querySelector('.lc-lightbox-nav.prev');
    const next = lightbox.querySelector('.lc-lightbox-nav.next');
    prev?.addEventListener('click', () => setIndex(index - 1));
    next?.addEventListener('click', () => setIndex(index + 1));
    thumbs?.addEventListener('click', (e) => {
      const t = e.target.closest('.lc-thumb');
      if (t) setIndex(parseInt(t.dataset.i, 10));
    });
    lightbox.querySelector('[data-action="close"]')?.addEventListener('click', close);
    lightbox.querySelector('[data-action="reply"]')?.addEventListener('click', () => {
      close();
      replyTo(messageId);
    });
    const reactionPicker = lightbox.querySelector('.lc-image-reactions');
    lightbox.querySelector('[data-action="react"]')?.addEventListener('click', () => {
      reactionPicker.hidden = !reactionPicker.hidden;
    });
    reactionPicker?.addEventListener('click', (e) => {
      if (e.target.tagName !== 'BUTTON') return;
      lightbox.querySelector('[data-action="react"]').textContent = e.target.textContent;
      reactionPicker.hidden = true;
    });
    lightbox.querySelector('[data-action="star"]')?.addEventListener('click', (e) => {
      const active = e.currentTarget.classList.toggle('is-active');
      e.currentTarget.setAttribute('aria-pressed', String(active));
    });
    onKeydown = function(e) {
      if (e.key === 'Escape') { close(); return; }
      if (hasMany && e.key === 'ArrowLeft') setIndex(index - 1);
      if (hasMany && e.key === 'ArrowRight') setIndex(index + 1);
    };
    document.addEventListener('keydown', onKeydown);
    document.body.appendChild(lightbox);
    counter = lightbox.querySelector('#lc-lightbox-counter');
    lightbox.querySelector('[data-action="close"]')?.focus();
  }

  function playVideoMessage(btn) {
    const wrap = btn.closest('.lc-video-wrap');
    if (!wrap) return;
    const video = wrap.querySelector('video');
    if (!video) return;
    if (!video.dataset.lcVideoBound) {
      video.dataset.lcVideoBound = '1';
      video.addEventListener('play', () => btn.classList.add('is-hidden'));
      video.addEventListener('pause', () => btn.classList.remove('is-hidden'));
      video.addEventListener('ended', () => btn.classList.remove('is-hidden'));
    }
    btn.classList.add('is-hidden');
    const pr = video.play();
    if (pr && pr.catch) pr.catch(() => { btn.classList.remove('is-hidden'); });
  }

  window.LiveChat = {
    toggle, close, openChannel, backToList, onKeydown, autoResize,
    sendText, onFileSelect, cancelMediaEditor, setActiveIndex, navMedia, removeMediaItem, updateActiveCaption,
    replyTo, cancelReply, deleteMsg, scrollToMsg, showMenu, updateBadge, openImage, openGallery, showReactions, toggleStar, showDeleteOptions, hideForMe, closeMenus: closeAllMenus, playVideoMessage,
    toggleAttachMenu, attachAction, closeAttachMenu,
    startVoiceRec, cancelVoiceRec, finishVoiceRec, toggleVoicePlay,
    toggleNotif, startNewSession, submitGuestName,
  };

  document.addEventListener('DOMContentLoaded', function() {
    const fab = document.getElementById('lc-fab');
    if (fab) fab.addEventListener('click', toggle);
    const overlay = document.getElementById('lc-overlay');
    if (overlay) overlay.addEventListener('click', close);
    document.addEventListener('click', function(e) {
      if (!e.target.isConnected) return;
      if (!e.target.closest('.lc-context-menu') && !e.target.closest('.lc-msg-anchor')) {
        closeAllMenus();
      }
      if (!e.target.closest('.lc-attach-wrap')) closeAttachMenu();
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('chat') === '1') {
      // Parameter ini hanya dipakai sebagai deep link dari notifikasi.
      // Hapus segera setelah dibaca agar refresh/navigasi berikutnya tidak
      // membuka modal chat lagi secara otomatis.
      const channel = params.get('channel');
      const cleanUrl = new URL(window.location.href);
      cleanUrl.searchParams.delete('chat');
      cleanUrl.searchParams.delete('channel');
      window.history.replaceState(window.history.state, document.title, cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);

      state.isOpen = true;
      const popup = document.getElementById('lc-popup');
      const overlayEl = document.getElementById('lc-overlay');
      popup?.classList.add('active');
      overlayEl?.classList.add('active');
      popup?.setAttribute('aria-hidden', 'false');
      overlayEl?.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      if (IS_GUEST) {
        if (getGuestName()) {
          openChannel(CS_SLUG).catch(function () {});
        } else {
          renderGuestNameForm();
        }
      } else {
        if (channel) {
          openChannel(channel).catch(function () {});
        } else if (state.view === 'panel') {
          fetchChannels();
        }
      }
    }
  });

  // Tutup sebelum halaman masuk back-forward cache, lalu pastikan snapshot
  // lama tidak menghidupkan modal lagi ketika pengguna kembali ke halaman.
  window.addEventListener('pagehide', close);
  window.addEventListener('pageshow', function(event) {
    if (event.persisted) close();
  });
})();
