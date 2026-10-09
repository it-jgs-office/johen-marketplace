@extends('admin.layouts.app')
@section('title', 'Chat - ' . ($conversation->channel?->name ?? 'Live Chat'))

@section('content')
<div class="lc-chat-wrap">
    <div class="card-glass lc-chat-card" style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;flex-shrink:0;border-bottom:1px solid var(--border);padding:12px 16px">
            <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
                <a href="{{ route('admin.live-chat.conversations') }}" style="color:var(--purple-light);text-decoration:none;font-size:18px;flex-shrink:0">←</a>
                <div style="min-width:0;flex:1">
                    <h3 class="card-title" style="margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $conversation->channel?->name ?? 'Live Chat' }}</h3>
                    <div style="font-size:12px;color:var(--text-dim);overflow-wrap:anywhere">
                        User: {{ $conversation->isGuest() ? ($conversation->guest_name ?? 'Guest') : ($conversation->user->name ?? 'User') }} ({{ $conversation->isGuest() ? 'Pengunjung' : ($conversation->user->email ?? '') }})
                        @if($activeOperator)
                        • Admin: {{ $activeOperator->display_name }} <span style="color:var(--success)">● Online</span>
                        @else
                        • <span style="color:var(--error)">● Offline</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                @if($conversation->status === 'open')
                <button class="btn btn-sm" onclick="closeConversation({{ $conversation->id }})">Tutup</button>
                @else
                <button class="btn btn-sm btn-primary" onclick="reopenConversation({{ $conversation->id }})">Buka Kembali</button>
                @endif
            </div>
        </div>

        <div id="chat-messages" class="chat-messages" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:8px">
            @forelse($messages as $msg)
            <div class="chat-msg {{ $msg->sender_type === 'user' ? 'chat-msg-user' : ($msg->sender_type === 'admin' ? 'chat-msg-admin' : 'chat-msg-system') }}"
                 data-msg-id="{{ $msg->id }}"
                 data-sender-type="{{ $msg->sender_type }}"
                 data-sender-id="{{ $msg->sender_id ?? '' }}">
                @if($msg->sender_type === 'system')
                    <div class="chat-bubble-system">{{ $msg->message }}</div>
                @else
                    @if($msg->reply_to)
                    <div class="chat-reply" onclick="scrollToMsg({{ $msg->reply_to_id }})">
                        ↳ {{ Str::limit($msg->reply_to->message ?? ($msg->reply_to->message_type === 'audio' ? '🎤 Pesan suara' : ($msg->reply_to->message_type === 'video' ? 'Video' : 'Foto')), 50) }}
                    </div>
                    @endif
                    @if($msg->message_type === 'image' && ($msg->media_path || $msg->attachments->isNotEmpty()))
                    @include('partials.livechat-gallery', ['msg' => $msg])
                    @elseif($msg->message_type === 'video' && $msg->media_path)
                    @include('partials.livechat-gallery', ['msg' => $msg])
                    @elseif($msg->message_type === 'audio' && $msg->media_path)
                    <div class="chat-media">
                        <div class="chat-voice" data-dur="{{ $msg->media_duration ?? '' }}" onclick="toggleChatVoice(this)">
                            <span class="chat-voice-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>
                            <span class="chat-voice-progress"><span class="chat-voice-fill"></span></span>
                            <span class="chat-voice-time">{{ $msg->media_duration ? gmdate('i:s', $msg->media_duration) : '' }}</span>
                            <audio src="/media/{{ $msg->media_path }}" preload="metadata"></audio>
                        </div>
                    </div>
                    @endif
                    @if($msg->message)
                    <div class="chat-bubble">{{ $msg->message }}</div>
                    @endif
                    <div class="chat-time">
                        {{ $msg->created_at->format('H:i') }}
                        @if($msg->sender_type === 'admin')
                            @if($msg->read_at) ✓✓ @else ✓ @endif
                        @endif
                        @if($msg->sender_type !== 'system')
                        <span class="chat-msg-actions">
                            <button type="button" class="chat-del-trigger" onclick="toggleDelMenu(event, {{ $msg->id }})">Hapus</button>
                        </span>
                        @endif
                    </div>
                @endif
            </div>
            @empty
            <div class="empty-state">Belum ada pesan</div>
            @endforelse
        </div>

        @if($conversation->status !== 'closed')
        <div class="chat-composer" style="flex-shrink:0;border-top:1px solid var(--border)">
            <input type="file" id="admin-file-input" accept="image/*,video/*" multiple style="display:none" onchange="adminFileSelect(event)">
            <input type="file" id="admin-audio-input" accept="audio/*" style="display:none" onchange="adminAudioFileSelect(event)">
            <div id="admin-rec-bar" style="display:none;align-items:center;gap:8px;margin-bottom:8px">
                <span style="width:9px;height:9px;border-radius:50%;background:#ef4444;flex-shrink:0;animation:lcRecPulse 1.2s ease-in-out infinite"></span>
                <span id="admin-rec-time" style="font-size:12px;font-weight:600;font-variant-numeric:tabular-nums;color:var(--text);min-width:34px">0:00</span>
                <span style="flex:1;font-size:11px;color:var(--text-mute)">Merekam...</span>
                <button type="button" onclick="cancelAdminVoiceRec()" title="Batal" style="width:28px;height:28px;border-radius:50%;border:none;background:rgba(239,68,68,.85);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0"><svg viewBox="0 0 24 24" width="14" height="14" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
                <button type="button" onclick="finishAdminVoiceRec()" title="Kirim" style="width:28px;height:28px;border-radius:50%;border:none;background:#2563eb;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0"><svg viewBox="0 0 24 24" width="14" height="14" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
            </div>
            <div id="admin-controls" style="display:flex;align-items:flex-end">
                <button class="btn-icon" onclick="document.getElementById('admin-file-input').click()" title="Kirim gambar/video">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                </button>
                <button class="btn-icon" onclick="startAdminVoiceRec()" title="Rekam pesan suara">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v1a7 7 0 0 0 14 0v-1M12 18v4"/></svg>
                </button>
                <textarea id="admin-chat-input" class="input-field" rows="1" placeholder="Ketik pesan..." style="flex:1;resize:none;max-height:80px;border-radius:20px" onkeydown="adminKeydown(event)" oninput="autoResizeInput(this)"></textarea>
                <button class="btn btn-primary" id="admin-send-btn" onclick="adminSendText()" disabled style="border-radius:50%;width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
        </div>
        @else
        <div style="padding:16px;text-align:center;color:var(--text-mute);font-size:13px;border-top:1px solid var(--border)">
            Percakapan telah ditutup
        </div>
        @endif
    </div>
</div>

<style>
.chat-msg { max-width: 70%; position: relative; }
.chat-msg-user { align-self: flex-end; }
.chat-msg-admin { align-self: flex-start; }
.chat-msg-system { align-self: center; max-width: 100%; }
.chat-bubble { padding: 8px 12px; border-radius: 12px; font-size: 13px; line-height: 1.5; word-break: break-word; }
.chat-msg-user .chat-bubble { background: var(--purple, #8b5cf6); color: #fff; border-bottom-right-radius: 4px; }
.chat-msg-admin .chat-bubble { background: var(--surface-2, rgba(255,255,255,.06)); color: var(--text, #f5f3fb); border-bottom-left-radius: 4px; }
.chat-bubble-system { background: transparent; color: var(--text-mute, rgba(255,255,255,.4)); font-size: 11px; text-align: center; padding: 4px 12px; }
.chat-time { font-size: 10px; color: var(--text-mute, rgba(255,255,255,.3)); margin-top: 4px; display: flex; align-items: center; gap: 4px; }
.chat-msg-user .chat-time { justify-content: flex-end; }
.chat-media { max-width: 100%; border-radius: 8px; overflow: hidden; margin-bottom: 4px; }
.chat-image-trigger { display:block; padding:0; border:0; background:transparent; cursor:zoom-in; }
.chat-media img, .chat-media video { width: 100%; max-height: 200px; object-fit: cover; display: block; }
.chat-media video:fullscreen, .chat-media video:-webkit-full-screen { width: 100vw; height: 100vh; max-height: none; object-fit: contain; background: #000; }
.chat-video-wrap { position: relative; }
.chat-video-play { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2; width: 44px; height: 44px; border: none; border-radius: 50%; background: rgba(0,0,0,.55); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s ease, transform .2s ease; }
.chat-video-play svg { width: 20px; height: 20px; fill: #fff; margin-left: 2px; }
.chat-video-play:hover { background: rgba(37,99,235,.85); transform: translate(-50%,-50%) scale(1.08); }
.chat-video-play.is-hidden { display: none; }
.chat-voice { display: flex; align-items: center; gap: 10px; max-width: 240px; min-width: 170px; padding: 6px 12px; border-radius: 14px; background: var(--surface-2, rgba(255,255,255,.06)); cursor: pointer; user-select: none; }
.chat-voice-btn { width: 30px; height: 30px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.chat-voice-btn svg { width: 14px; height: 14px; fill: #fff; }
.chat-voice-btn:not(.is-playing) svg { transform: translateX(1px); }
.chat-voice-btn.is-playing svg { transform: none; }
.chat-voice-progress { flex: 1; height: 4px; border-radius: 2px; background: rgba(255,255,255,.16); overflow: hidden; min-width: 40px; }
.chat-voice-fill { display: block; height: 100%; width: 0%; background: var(--purple, #8b5cf6); border-radius: 2px; transition: width .12s linear; }
.chat-voice-time { font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--text-mute, rgba(255,255,255,.4)); flex-shrink: 0; }
@keyframes lcRecPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .4; transform: scale(.8); } }
.chat-msg-gallery { display: grid; gap: 3px; margin-bottom: 4px; width: 100%; max-width: 260px; }
.chat-grid-1 { grid-template-columns: minmax(0, 1fr); }
.chat-grid-2, .chat-grid-3, .chat-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.chat-msg-gallery .chat-gallery-item { position: relative; overflow: hidden; padding: 0; border: 0; background: #0a0a12; cursor: zoom-in; }
.chat-msg-gallery .chat-gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.chat-grid-1 .chat-gallery-item { display: block; }
.chat-grid-1 .chat-gallery-item img { height: auto; max-height: 160px; }
.chat-grid-2 .chat-gallery-item, .chat-grid-3 .chat-gallery-item, .chat-grid-4 .chat-gallery-item { aspect-ratio: 1 / 1; }
.chat-gallery-more { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.55); color: #fff; font-weight: 700; font-size: 20px; pointer-events: none; }
.chat-lightbox-stage { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
.chat-lightbox-stage img { max-width: 72%; max-height: 72%; object-fit: contain; border-radius: 10px; box-shadow: 0 24px 80px rgba(0,0,0,.5); cursor: default; }
.chat-lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 2; width: 42px; height: 42px; border: 0; border-radius: 50%; background: rgba(255,255,255,.14); color: #fff; font-size: 22px; line-height: 1; cursor: pointer; display: grid; place-items: center; }
.chat-lightbox-nav.prev { left: 10px; }
.chat-lightbox-nav.next { right: 10px; }
.chat-lightbox-nav:hover { background: rgba(255,255,255,.25); }
.chat-lightbox-counter { position: absolute; bottom: 92px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,.5); color: #fff; padding: 4px 14px; border-radius: 999px; font-size: 12px; z-index: 2; }
.chat-lightbox-thumbs { position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; padding: 6px; border-radius: 12px; background: rgba(0,0,0,.55); max-width: 88%; overflow-x: auto; z-index: 2; }
.chat-lightbox-thumbs .chat-thumb { flex: 0 0 auto; width: 46px; height: 46px; padding: 0; border: 2px solid transparent; border-radius: 8px; overflow: hidden; background: #000; cursor: pointer; }
.chat-lightbox-thumbs .chat-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.chat-lightbox-thumbs .chat-thumb.active { border-color: #a78bfa; }
.chat-image-lightbox { position:fixed; inset:0; z-index:10050; display:flex; align-items:center; justify-content:center; padding:24px; background:rgba(8,6,16,.92); cursor:zoom-out; }
.chat-image-lightbox img { max-width:100%; max-height:100%; object-fit:contain; border-radius:10px; box-shadow:0 24px 80px rgba(0,0,0,.5); cursor:default; }
.chat-image-lightbox-tools { position:fixed; top:16px; right:18px; display:flex; align-items:center; gap:6px; z-index:1; }
.chat-image-lightbox-tools button, .chat-image-lightbox-tools a { width:40px; height:40px; border:0; border-radius:50%; background:rgba(255,255,255,.14); color:#fff; display:grid; place-items:center; text-decoration:none; cursor:pointer; }
.chat-image-lightbox-tools svg { width:19px; height:19px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.chat-image-lightbox-tools [data-action="close"] { font-size:30px; line-height:1; }
.chat-image-lightbox-tools button:hover, .chat-image-lightbox-tools button:focus-visible, .chat-image-lightbox-tools a:hover, .chat-image-lightbox-tools a:focus-visible { background:rgba(255,255,255,.25); }
.chat-image-lightbox-tools .is-active { color:#facc15; }
.chat-image-reactions { position:absolute; top:48px; right:44px; display:flex; gap:4px; padding:6px; border-radius:14px; background:rgba(33,26,47,.96); box-shadow:0 10px 30px rgba(0,0,0,.3); }
.chat-image-reactions button { font-size:18px; background:transparent; }
.chat-reply { background: var(--surface-3, rgba(255,255,255,.04)); border-left: 3px solid var(--purple, #8b5cf6); padding: 4px 8px; margin-bottom: 6px; border-radius: 4px; font-size: 11px; color: var(--text-dim, rgba(255,255,255,.6)); cursor: pointer; }
.chat-reply:hover { background: var(--surface-2, rgba(255,255,255,.06)); }
.chat-delete-btn { background: none; border: none; cursor: pointer; font-size: 10px; opacity: 0.5; }
.chat-delete-btn:hover { opacity: 1; }
.chat-msg-actions { position: relative; display: inline-flex; }
.chat-del-trigger { background: none; border: none; cursor: pointer; font-size: 10px; opacity: 0.5; color: inherit; padding: 0 3px; display: inline-flex; align-items: center; }
.chat-del-trigger:hover { opacity: 1; }
.chat-del-menu { position: absolute; bottom: 100%; right: 0; z-index: 50; min-width: 168px; background: var(--surface, #1e1136); border: 1px solid var(--border, rgba(255,255,255,.1)); border-radius: 10px; padding: 4px; box-shadow: 0 12px 34px rgba(0,0,0,.38); display: flex; flex-direction: column; margin-bottom: 4px; }
.chat-del-menu button { background: transparent; border: 0; text-align: left; padding: 7px 10px; border-radius: 8px; font-size: 12px; color: var(--text, #f5f3fb); cursor: pointer; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.chat-del-menu button:hover { background: var(--surface-2, rgba(255,255,255,.06)); }
.chat-del-menu button.danger { color: #f87171; }
.btn-icon { width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--border); background: transparent; color: var(--text-dim); cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.btn-icon:hover { background: var(--surface-2); }

/* RESPONSIVE */
.lc-chat-wrap { display: flex; gap: 24px; height: calc(100vh - 120px); }
.lc-chat-wrap .card-glass { min-width: 0; }
.lc-chat-wrap .card-glass:hover { transform: none; box-shadow: none; }
.chat-messages { padding: 16px; }
.chat-composer { padding: 12px; }
#admin-controls { gap: 8px; }
#admin-chat-input { min-width: 0; }
.chat-msg { min-width: 0; }
.chat-time { flex-wrap: wrap; min-width: 0; }

@media (max-width: 479px) {
    .lc-chat-wrap { gap: 12px; height: calc(100vh - 96px); }
    .lc-chat-wrap .card-header { padding: 10px 12px; gap: 8px; }
    .chat-messages { padding: 12px 10px; }
    .chat-msg { max-width: 85%; }
    .chat-composer { padding: 10px; }
    #admin-controls { gap: 6px; }
}
</style>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const CONVERSATION_ID = {{ $conversation->id }};
const ADMIN_ID = {{ auth('admin')->id() }};

let pollTimer = null;
let adminReplyToId = null;

function playChatVideo(btn) {
    const wrap = btn.closest('.chat-video-wrap');
    if (!wrap) return;
    const video = wrap.querySelector('video');
    if (!video) return;
    if (!video.dataset.chatVideoBound) {
        video.dataset.chatVideoBound = '1';
        video.addEventListener('play', () => btn.classList.add('is-hidden'));
        video.addEventListener('pause', () => btn.classList.remove('is-hidden'));
        video.addEventListener('ended', () => btn.classList.remove('is-hidden'));
    }
    btn.classList.add('is-hidden');
    const pr = video.play();
    if (pr && pr.catch) pr.catch(() => { btn.classList.remove('is-hidden'); });
}

function buildVoiceHtml(msg) {
    const a = attachmentsOfMsg(msg)[0];
    if (!a) return '';
    const dur = a.media_duration ? fmtVoiceTime(a.media_duration) : '';
    return `<div class="chat-media"><div class="chat-voice" data-dur="${a.media_duration || ''}" onclick="toggleChatVoice(this)"><span class="chat-voice-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span><span class="chat-voice-progress"><span class="chat-voice-fill"></span></span><span class="chat-voice-time">${dur}</span><audio src="/media/${a.media_path}" preload="metadata"></audio></div></div>`;
}

function fmtVoiceTime(sec) {
    sec = Math.round(Number(sec) || 0);
    if (sec < 0) sec = 0;
    const m = Math.floor(sec / 60);
    const s = String(sec % 60).padStart(2, '0');
    return m + ':' + s;
}

function toggleChatVoice(el) {
    const audio = el.querySelector('audio');
    if (!audio || !audio.src) return;
    const btn = el.querySelector('.chat-voice-btn');
    const fill = el.querySelector('.chat-voice-fill');
    const time = el.querySelector('.chat-voice-time');
    const setPlaying = (p) => btn && btn.classList.toggle('is-playing', p);
    if (window.__AdminActiveVoice && window.__AdminActiveVoice !== audio) {
        try { window.__AdminActiveVoice.pause(); } catch (e) {}
    }
    window.__AdminActiveVoice = audio.paused ? audio : null;
    if (!audio.dataset.lcBound) {
        audio.dataset.lcBound = '1';
        audio.addEventListener('play', () => setPlaying(true));
        audio.addEventListener('pause', () => setPlaying(false));
        audio.addEventListener('ended', () => {
            setPlaying(false);
            if (fill) fill.style.width = '0%';
            const d = el.dataset.dur;
            if (time) time.textContent = d ? fmtVoiceTime(d) : '0:00';
            window.__AdminActiveVoice = null;
        });
        audio.addEventListener('timeupdate', () => {
            if (!audio.duration || !isFinite(audio.duration)) return;
            if (fill) fill.style.width = ((audio.currentTime / audio.duration) * 100).toFixed(1) + '%';
            if (time) time.textContent = fmtVoiceTime(audio.currentTime);
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

function startPoll() {
    stopPoll();
    pollTimer = setInterval(pollMessages, 3000);
}

function stopPoll() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

async function pollMessages() {
    const container = document.getElementById('chat-messages');
    const lastMsg = container.querySelector('[data-msg-id]:last-child');
    const lastId = lastMsg ? lastMsg.dataset.msgId : 0;

    try {
        const res = await fetch(`/admin/live-chat/conversations/${CONVERSATION_ID}/poll?after=${lastId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) return;
        const msgs = await res.json();
        if (msgs.length > 0) {
            msgs.forEach(msg => appendMessage(msg));
            container.scrollTop = container.scrollHeight;
        }
    } catch (e) {}
}

function attachmentsOfMsg(msg) {
    if (msg.attachments && msg.attachments.length) return msg.attachments;
    if (msg.media_path) return [{ media_path: msg.media_path, poster_path: msg.poster_path || null, media_mime: msg.media_mime || null, media_duration: msg.media_duration || null }];
    return [];
}

function buildGalleryHtml(msg) {
    const atts = attachmentsOfMsg(msg);
    if (!atts.length) return '';

    if (msg.message_type === 'video') {
        const a = atts[0];
        const posterAttr = a.poster_path ? ` poster="/media/${a.poster_path}"` : '';
        return `<div class="chat-media chat-video-wrap"><button type="button" class="chat-video-play" onclick="playChatVideo(this)" aria-label="Putar video"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></button><video src="/media/${a.media_path}" controls preload="none"${posterAttr}></video></div>`;
    }

    const gridCls = atts.length === 1 ? 'chat-msg-gallery chat-grid-1'
        : atts.length === 2 ? 'chat-msg-gallery chat-grid-2'
        : atts.length === 3 ? 'chat-msg-gallery chat-grid-3'
        : 'chat-msg-gallery chat-grid-4';
    const urlsJson = JSON.stringify(atts.map(a => `/media/${a.media_path}`)).replace(/"/g, '&quot;');
    const visible = atts.slice(0, 4);
    const hiddenCount = atts.length - 4;
    const tiles = visible.map((a, i) => {
        const url = `/media/${a.media_path}`;
        const more = (i === 3 && hiddenCount > 0) ? `<span class="chat-gallery-more">+${hiddenCount}</span>` : '';
        return `<button type="button" class="chat-gallery-item" data-urls="${urlsJson}" onclick="openAdminGallery(this, ${msg.id}, ${i})" aria-label="Buka gambar ukuran penuh"><img src="${url}" alt="Gambar" loading="lazy">${more}</button>`;
    }).join('');
    return `<div class="chat-media ${gridCls}">${tiles}</div>`;
}

function appendMessage(msg) {
    const container = document.getElementById('chat-messages');
    const emptyState = container.querySelector('.empty-state');
    if (emptyState) emptyState.remove();

    const div = document.createElement('div');
    div.className = `chat-msg ${msg.sender_type === 'user' ? 'chat-msg-user' : 'chat-msg-admin'}`;
    div.dataset.msgId = msg.id;
    div.dataset.senderType = msg.sender_type;
    div.dataset.senderId = msg.sender_id == null ? '' : msg.sender_id;

    let html = '';
    if (msg.reply_to) {
        const rp = msg.reply_to.message_type === 'audio' ? '🎤 Pesan suara'
            : msg.reply_to.message_type === 'video' ? 'Video'
            : msg.reply_to.message_type === 'image' ? 'Foto'
            : ((msg.reply_to.message || '').substring(0, 50));
        html += `<div class="chat-reply">↳ ${escapeHtml(rp)}</div>`;
    }
    if (msg.message_type === 'image' && (msg.media_path || (msg.attachments && msg.attachments.length))) {
        html += buildGalleryHtml(msg);
    } else if (msg.message_type === 'video' && msg.media_path) {
        html += buildGalleryHtml(msg);
    } else if (msg.message_type === 'audio' && msg.media_path) {
        html += buildVoiceHtml(msg);
    }
    if (msg.message) {
        html += `<div class="chat-bubble">${escapeHtml(msg.message)}</div>`;
    }
    const time = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    let actionBtns = '';
    if (msg.sender_type !== 'system') {
        actionBtns += `<span class="chat-msg-actions"><button type="button" class="chat-del-trigger" onclick="toggleDelMenu(event, ${msg.id})">Hapus</button></span>`;
    }
    html += `<div class="chat-time">${time}${actionBtns}</div>`;

    div.innerHTML = html;
    container.appendChild(div);
}

function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

function openAdminGallery(btn, messageId, startIndex) {
    let urls = [];
    try { urls = JSON.parse(btn.dataset.urls || '[]'); } catch (e) {}
    if (!urls.length) return;
    openAdminLightbox(urls, startIndex || 0, messageId);
}

function openAdminLightbox(urls, startIndex, messageId) {
    document.getElementById('chat-image-lightbox')?.remove();

    let index = Math.max(0, Math.min(startIndex || 0, urls.length - 1));
    const hasMany = urls.length > 1;
    const thumbsHtml = urls.map((u, i) =>
        `<button type="button" class="chat-thumb${i === index ? ' active' : ''}" data-i="${i}" aria-label="Gambar ${i + 1}"><img src="${u}" alt="" loading="lazy"></button>`
    ).join('');

    const lightbox = document.createElement('div');
    lightbox.id = 'chat-image-lightbox';
    lightbox.className = 'chat-image-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Pratinjau gambar');
    lightbox.innerHTML = `
        <div class="chat-image-lightbox-tools" aria-label="Aksi gambar">
            <button type="button" data-action="reply" title="Balas" aria-label="Balas"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 9 5 14l5 5"/><path d="M5 14h9a5 5 0 0 1 5 5"/></svg></button>
            <button type="button" data-action="react" title="Beri reaksi" aria-label="Beri reaksi"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M8 14s1.4 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg></button>
            <button type="button" data-action="star" title="Beri bintang" aria-label="Beri bintang" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/></svg></button>
            <a id="chat-lightbox-download" href="${urls[index]}" download title="Unduh" aria-label="Unduh gambar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v11"/><path d="m8 10 4 4 4-4M5 20h14"/></svg></a>
            <button type="button" data-action="close" title="Tutup" aria-label="Tutup gambar">×</button>
            <div class="chat-image-reactions" hidden><button type="button">👍</button><button type="button">❤️</button><button type="button">😂</button><button type="button">😮</button></div>
        </div>
        <div class="chat-lightbox-stage">
            ${hasMany ? '<button type="button" class="chat-lightbox-nav prev" data-nav="-1" aria-label="Sebelumnya">‹</button>' : ''}
            <img id="chat-lightbox-img" src="${urls[index]}" alt="Gambar ukuran penuh">
            ${hasMany ? '<button type="button" class="chat-lightbox-nav next" data-nav="1" aria-label="Berikutnya">›</button>' : ''}
            ${hasMany ? '<div class="chat-lightbox-counter">' + (index + 1) + ' / ' + urls.length + '</div>' : ''}
            ${hasMany ? `<div class="chat-lightbox-thumbs">${thumbsHtml}</div>` : ''}
        </div>`;

    const img = lightbox.querySelector('#chat-lightbox-img');
    const download = lightbox.querySelector('#chat-lightbox-download');
    const thumbs = lightbox.querySelector('.chat-lightbox-thumbs');
    let counter = null;
    const updateThumbs = () => {
        thumbs?.querySelectorAll('.chat-thumb').forEach((t, i) => {
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
    lightbox.addEventListener('click', (event) => {
        if (event.target === lightbox || event.target.classList.contains('chat-lightbox-stage')) close();
    });
    lightbox.querySelector('.chat-lightbox-nav.prev')?.addEventListener('click', () => setIndex(index - 1));
    lightbox.querySelector('.chat-lightbox-nav.next')?.addEventListener('click', () => setIndex(index + 1));
    thumbs?.addEventListener('click', (e) => {
        const t = e.target.closest('.chat-thumb');
        if (t) setIndex(parseInt(t.dataset.i, 10));
    });
    lightbox.querySelector('[data-action="close"]')?.addEventListener('click', close);
    lightbox.querySelector('[data-action="reply"]')?.addEventListener('click', () => {
        close();
        adminReplyToId = messageId;
        const input = document.getElementById('admin-chat-input');
        if (input) {
            input.placeholder = `Balas gambar #${messageId}...`;
            input.focus();
        }
    });
    const reactionPicker = lightbox.querySelector('.chat-image-reactions');
    lightbox.querySelector('[data-action="react"]')?.addEventListener('click', () => { reactionPicker.hidden = !reactionPicker.hidden; });
    reactionPicker?.addEventListener('click', (event) => {
        if (event.target.tagName !== 'BUTTON') return;
        lightbox.querySelector('[data-action="react"]').textContent = event.target.textContent;
        reactionPicker.hidden = true;
    });
    lightbox.querySelector('[data-action="star"]')?.addEventListener('click', (event) => {
        const active = event.currentTarget.classList.toggle('is-active');
        event.currentTarget.setAttribute('aria-pressed', String(active));
    });
    onKeydown = function(event) {
        if (event.key === 'Escape') { close(); return; }
        if (hasMany && event.key === 'ArrowLeft') setIndex(index - 1);
        if (hasMany && event.key === 'ArrowRight') setIndex(index + 1);
    };
    document.addEventListener('keydown', onKeydown);
    document.body.appendChild(lightbox);
    counter = lightbox.querySelector('.chat-lightbox-counter');
    lightbox.querySelector('[data-action="close"]')?.focus();
}

function scrollToMsg(id) {
    const el = document.querySelector(`[data-msg-id="${id}"]`);
    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
}

function adminKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); adminSendText(); }
}

function autoResizeInput(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 80) + 'px';
}

const adminInput = document.getElementById('admin-chat-input');
if (adminInput) {
    adminInput.addEventListener('input', function() {
        document.getElementById('admin-send-btn').disabled = !this.value.trim();
    });
}

async function adminSendText() {
    const input = document.getElementById('admin-chat-input');
    const text = input?.value?.trim();
    if (!text) return;
    const replyToId = adminReplyToId;

    input.value = '';
    input.style.height = 'auto';
    document.getElementById('admin-send-btn').disabled = true;

    try {
        const formData = new FormData();
        formData.append('message_type', 'text');
        formData.append('message', text);
        if (replyToId) formData.append('reply_to_message_id', replyToId);

        const res = await fetch(`/admin/live-chat/conversations/${CONVERSATION_ID}/reply`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        if (!res.ok) throw new Error();
        const msg = await res.json();
        appendMessage(msg);
        adminReplyToId = null;
        input.placeholder = 'Ketik pesan...';
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
    } catch (e) {
        input.value = text;
    }
}

let adminPreviewFiles = [];

function adminFileSelect(e) {
    const newFiles = Array.from(e.target.files);
    if (!newFiles.length) return;
    e.target.value = '';
    adminPreviewFiles.push(...newFiles);
    showAdminPreview();
}

/* ---- VOICE NOTE (super admin) ---- */
let adminRec = { recorder: null, chunks: [], stream: null, timer: null, mime: '', startTs: 0, seconds: 0 };

function adminVoiceRecMime() {
    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    for (let i = 0; i < candidates.length; i++) {
        if (window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(candidates[i])) return candidates[i];
    }
    return 'audio/webm';
}
function adminVoiceExt(mime) {
    if (/mp4|m4a/i.test(mime)) return 'm4a';
    if (/ogg|opus/i.test(mime)) return 'ogg';
    return 'webm';
}
function adminResetRec() {
    clearInterval(adminRec.timer);
    adminRec.timer = null;
    adminRec.recorder = null;
    adminRec.chunks = [];
    adminRec.mime = '';
    adminRec.startTs = 0;
    adminRec.seconds = 0;
    if (adminRec.stream) {
        try { adminRec.stream.getTracks().forEach(t => t.stop()); } catch (e) {}
        adminRec.stream = null;
    }
}
function adminSetRecBar(visible) {
    const bar = document.getElementById('admin-rec-bar');
    if (bar) bar.style.display = visible ? 'flex' : 'none';
    const controls = document.getElementById('admin-controls');
    if (controls) controls.style.display = visible ? 'none' : 'flex';
}
function adminVoiceErrMsg(err) {
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
async function startAdminVoiceRec() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert(window.isSecureContext ? 'Browser tidak mendukung perekaman suara.' : 'Mikrofon hanya tersedia via HTTPS atau http://localhost.');
        return;
    }
    if (adminRec.recorder) {
        try { adminRec.recorder.resume(); } catch (e) {}
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        adminRec.stream = stream;
        adminRec.mime = adminVoiceRecMime();
        let recorder;
        try {
            recorder = adminRec.mime ? new MediaRecorder(stream, { mimeType: adminRec.mime }) : new MediaRecorder(stream);
        } catch (e) {
            recorder = new MediaRecorder(stream);
        }
        adminRec.mime = recorder.mimeType || adminRec.mime || 'audio/webm';
        adminRec.chunks = [];
        recorder.ondataavailable = (e) => { if (e.data && e.data.size) adminRec.chunks.push(e.data); };
        recorder.start();
        adminRec.recorder = recorder;
        adminRec.startTs = Date.now();
        adminRec.seconds = 0;
        adminSetRecBar(true);
        const timeEl = document.getElementById('admin-rec-time');
        if (timeEl) {
            timeEl.textContent = '0:00';
            adminRec.timer = setInterval(() => {
                adminRec.seconds = Math.floor((Date.now() - adminRec.startTs) / 1000);
                timeEl.textContent = fmtVoiceTime(adminRec.seconds);
                if (adminRec.seconds >= 1800) finishAdminVoiceRec();
            }, 500);
        }
    } catch (err) {
        console.error('Admin live chat: mic error', err);
        alert(adminVoiceErrMsg(err));
        adminSetRecBar(false);
    }
}
function cancelAdminVoiceRec() {
    const r = adminRec.recorder;
    clearInterval(adminRec.timer);
    adminRec.timer = null;
    if (r && r.state !== 'inactive') {
        r.onstop = () => adminResetRec();
        try { r.stop(); } catch (e) { adminResetRec(); }
    } else {
        adminResetRec();
    }
    adminSetRecBar(false);
}
function finishAdminVoiceRec() {
    const r = adminRec.recorder;
    if (!r || r.state === 'inactive') return;
    adminSetRecBar(false);
    const duration = Math.max(1, adminRec.seconds || Math.floor((Date.now() - adminRec.startTs) / 1000));
    const mime = adminRec.mime || 'audio/webm';
    r.onstop = () => {
        const blob = new Blob(adminRec.chunks, { type: mime });
        if (blob.size === 0) {
            adminResetRec();
            showAdminToast('Rekaman kosong.', 'error');
            return;
        }
        adminResetRec();
        const file = new File([blob], 'voice-note-' + Date.now() + '.' + adminVoiceExt(mime), { type: mime });
        adminSendVoiceFile(file, duration);
    };
    try { r.stop(); } catch (e) { adminResetRec(); adminSetRecBar(false); }
    setTimeout(() => adminSetRecBar(false), 4000);
}
function adminAudioFileSelect(e) {
    const file = e.target.files[0];
    if (!file) return;
    e.target.value = '';
    const replyToId = adminReplyToId;
    adminSendVoiceFile(file, 0, replyToId);
}
async function adminSendVoiceFile(file, duration, replyToId) {
    try {
        const formData = new FormData();
        formData.append('message_type', 'audio');
        formData.append('media[]', file);
        formData.append('media_duration', String(Math.round(duration)));
        if (replyToId) formData.append('reply_to_message_id', replyToId);
        const res = await fetch(`/admin/live-chat/conversations/${CONVERSATION_ID}/reply`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        if (!res.ok) throw new Error();
        const msg = await res.json();
        appendMessage(msg);
        adminReplyToId = null;
        const input = document.getElementById('admin-chat-input');
        if (input) input.placeholder = 'Ketik pesan...';
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
    } catch (e) {
        showAdminToast('Gagal mengirim pesan suara', 'error');
    }
}
function showAdminToast(message, type) {
    const t = document.createElement('div');
    t.textContent = message;
    t.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:500;color:#fff;z-index:100010;background:' + (type === 'error' ? '#ef4444' : '#10b981') + ';box-shadow:0 8px 24px -4px rgba(0,0,0,.3)';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
function showAdminPreview() {
    if (adminPreviewFiles.length === 0) return;
    const o = document.getElementById('admin-preview-overlay');
    if (o) o.remove();
    const filesHtml = adminPreviewFiles.map((f, i) => {
        const url = URL.createObjectURL(f);
        const isImage = f.type.startsWith('image/');
        const isVideo = f.type.startsWith('video/');
        return `<div class="admin-preview-item" style="position:relative;display:inline-block;margin:4px">
            ${isImage ? `<img src="${url}" style="width:80px;height:80px;object-fit:cover;border-radius:8px">` : `<video src="${url}" style="width:80px;height:80px;object-fit:cover;border-radius:8px" muted preload="metadata"></video>`}
            <button onclick="removeAdminPreviewFile(${i})" style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;border-radius:50%;border:none;background:rgba(0,0,0,.7);color:#fff;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center">&times;</button>
        </div>`;
    }).join('');
    const overlay = document.createElement('div');
    overlay.id = 'admin-preview-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:10002;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = `
        <div style="background:var(--surface);border-radius:16px;padding:20px;max-width:400px;width:90%;text-align:center">
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:4px;margin-bottom:12px">${filesHtml}</div>
            <input type="text" id="admin-preview-caption" class="input-field" placeholder="Caption (opsional)" style="width:100%;margin-bottom:12px;border-radius:12px">
            <div style="display:flex;gap:8px;justify-content:center">
                <button onclick="cancelAdminPreview()" style="padding:8px 20px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-dim);cursor:pointer">Batal</button>
                <button onclick="confirmAdminSend()" style="padding:8px 20px;border-radius:8px;border:none;background:var(--purple);color:#fff;cursor:pointer;font-weight:600">Kirim</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
}

function removeAdminPreviewFile(idx) {
    adminPreviewFiles.splice(idx, 1);
    if (adminPreviewFiles.length === 0) {
        cancelAdminPreview();
    } else {
        showAdminPreview();
    }
}

function cancelAdminPreview() {
    adminPreviewFiles = [];
    const o = document.getElementById('admin-preview-overlay');
    if (o) o.remove();
}

async function extractVideoThumb(file) {
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

async function confirmAdminSend() {
    if (!adminPreviewFiles.length) return;
    const caption = document.getElementById('admin-preview-caption')?.value || '';
    const files = [...adminPreviewFiles];
    const replyToId = adminReplyToId;
    cancelAdminPreview();

    const imageFiles = files.filter(f => f.type.startsWith('image/'));
    const videoFiles = files.filter(f => !f.type.startsWith('image/'));

    if (imageFiles.length > 0) {
        try {
            const formData = new FormData();
            formData.append('message_type', 'image');
            if (caption) formData.append('message', caption);
            if (replyToId) formData.append('reply_to_message_id', replyToId);
            imageFiles.forEach(f => formData.append('media[]', f));

            const res = await fetch(`/admin/live-chat/conversations/${CONVERSATION_ID}/reply`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            if (!res.ok) throw new Error();
            const msg = await res.json();
            appendMessage(msg);
        } catch (e) {}
    }

    for (let i = 0; i < videoFiles.length; i++) {
        const file = videoFiles[i];
        try {
            const formData = new FormData();
            formData.append('message_type', 'video');
            formData.append('media[]', file);
            if (i === 0 && caption) formData.append('message', caption);
            if (i === 0 && replyToId) formData.append('reply_to_message_id', replyToId);
            const thumbBlob = await extractVideoThumb(file);
            if (thumbBlob) {
                formData.append('thumbnail[]', thumbBlob, 'thumb.jpg');
                formData.append('thumbnail_indexes[]', '0');
            }

            const res = await fetch(`/admin/live-chat/conversations/${CONVERSATION_ID}/reply`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            if (!res.ok) throw new Error();
            const msg = await res.json();
            appendMessage(msg);
        } catch (e) {}
    }
    adminReplyToId = null;
    const input = document.getElementById('admin-chat-input');
    if (input) input.placeholder = 'Ketik pesan...';
    document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
}

async function deleteMsg(id) {
    if (!confirm('Hapus pesan ini untuk semua orang?')) return;
    try {
        const res = await fetch(`/admin/live-chat/messages/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const el = document.querySelector(`[data-msg-id="${id}"]`);
        if (el) el.remove();
    } catch (e) {}
}

async function hideMsg(id) {
    if (!confirm('Hapus pesan ini hanya dari chat Anda?')) return;
    try {
        const res = await fetch(`/admin/live-chat/messages/${id}/hide`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const el = document.querySelector(`[data-msg-id="${id}"]`);
        if (el) el.remove();
    } catch (e) {}
}

function toggleDelMenu(e, id) {
    e.stopPropagation();
    const existing = document.querySelector(`.chat-del-menu[data-msg-id="${id}"]`);
    document.querySelectorAll('.chat-del-menu').forEach(m => m.remove());
    if (existing) return;
    const anchor = document.querySelector(`[data-msg-id="${id}"] .chat-msg-actions`);
    if (!anchor) return;
    const node = document.querySelector(`[data-msg-id="${id}"]`);
    const canDelete = node && node.dataset.senderType === 'admin' && String(node.dataset.senderId) === String(ADMIN_ID);
    const menu = document.createElement('div');
    menu.className = 'chat-del-menu';
    menu.dataset.msgId = id;
    menu.innerHTML = `
        <button type="button" class="danger" onclick="hideMsg(${id})">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            Hapus untuk saya
        </button>
        ${canDelete ? `<button type="button" class="danger" onclick="deleteMsg(${id})">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            Hapus untuk semua orang
        </button>` : ''}
        <button type="button" onclick="this.closest('.chat-del-menu').remove()">Batal</button>`;
    anchor.appendChild(menu);
}
document.addEventListener('click', function(e) {
    if (!e.target.isConnected) return;
    document.querySelectorAll('.chat-del-menu').forEach(m => m.remove());
});

async function closeConversation(id) {
    if (!confirm('Tutup percakapan ini?')) return;
    try {
        await fetch(`/admin/live-chat/conversations/${id}/close`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        location.reload();
    } catch (e) {}
}

async function reopenConversation(id) {
    try {
        await fetch(`/admin/live-chat/conversations/${id}/reopen`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        location.reload();
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('chat-messages');
    if (container) container.scrollTop = container.scrollHeight;
    startPoll();
});
</script>
@endsection
