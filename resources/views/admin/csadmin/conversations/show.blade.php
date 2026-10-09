@extends('admin.csadmin.layout')
@section('title', 'Guest - ' . ($conversation->channel?->name ?? 'Live Chat'))

<style>
.cs-chat-page { height: 100%; display: flex; flex-direction: column; }
.cs-chat-header {
    flex-shrink: 0; height: 68px; padding: 0 18px;
    background: #102E4D; border-bottom: 1px solid #1A4168;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.cs-chat-header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.cs-back {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    background: #102A47; border: 1px solid #1A4168; color: #F5F7FB;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    text-decoration: none; font-size: 16px;
}
.cs-back:hover { border-color: #3F6DF5; }
.cs-chat-avatar {
    width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #14b8a6, #3F6DF5);
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700; color: #fff;
}
.cs-chat-title { font-weight: 700; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cs-chat-sub { font-size: 11px; color: #6F89A7; margin-top: 2px; }
.cs-chat-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
.cs-status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.cs-status-pill.online { background: rgba(16,185,129,.15); color: #34d399; }
.cs-status-pill.offline { background: rgba(148,163,184,.15); color: #94a3b8; }
.cs-pulse-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: csPulse 1.4s ease-in-out infinite; }
@keyframes csPulse { 0%,100% { opacity: 1; } 50% { opacity: .35; } }
.cs-chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 8px; }
.cs-msg { max-width: 70%; position: relative; }
.cs-msg-user { align-self: flex-end; }
.cs-msg-admin { align-self: flex-start; }
.cs-msg-system { align-self: center; max-width: 100%; }
.cs-bubble { padding: 9px 13px; border-radius: 14px; font-size: 13px; line-height: 1.5; word-break: break-word; background: #103A5C; color: #F5F7FB; }
.cs-msg-user .cs-bubble { background: linear-gradient(135deg, #3F6DF5, #8b5cf6); color: #fff; border-bottom-right-radius: 4px; }
.cs-msg-admin .cs-bubble { background: #103A5C; border-bottom-left-radius: 4px; }
.cs-bubble-system { background: transparent; color: #6F89A7; font-size: 11px; text-align: center; padding: 4px 12px; }
.cs-time { font-size: 10px; color: #6F89A7; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
.cs-msg-user .cs-time { justify-content: flex-end; }
.cs-reply { background: rgba(255,255,255,.05); border-left: 3px solid #3F6DF5; padding: 4px 8px; margin-bottom: 6px; border-radius: 4px; font-size: 11px; color: #8FA8C4; cursor: pointer; }
.cs-media { max-width: 100%; border-radius: 10px; overflow: hidden; margin-bottom: 4px; }
.cs-media img, .cs-media video { width: 100%; max-height: 220px; object-fit: cover; display: block; }
.cs-media video:fullscreen, .cs-media video:-webkit-full-screen { width: 100vw; height: 100vh; max-height: none; object-fit: contain; background: #000; }
.cs-gallery { display: grid; gap: 3px; margin-bottom: 4px; width: 100%; max-width: 260px; }
.cs-grid-1 { grid-template-columns: minmax(0, 1fr); }
.cs-grid-2, .cs-grid-3, .cs-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.cs-gallery-item { position: relative; overflow: hidden; padding: 0; border: 0; background: #0a0a12; cursor: zoom-in; }
.cs-gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cs-grid-1 .cs-gallery-item img { height: auto; max-height: 160px; }
.cs-grid-2 .cs-gallery-item, .cs-grid-3 .cs-gallery-item, .cs-grid-4 .cs-gallery-item { aspect-ratio: 1 / 1; }
.cs-gallery-more { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.55); color: #fff; font-weight: 700; font-size: 20px; pointer-events: none; }
.cs-video-wrap { position: relative; }
.cs-video-play { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2; width: 44px; height: 44px; border: none; border-radius: 50%; background: rgba(0,0,0,.55); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.cs-video-play svg { width: 20px; height: 20px; fill: #fff; margin-left: 2px; }
.cs-video-play.is-hidden { display: none; }
.cs-voice { display: flex; align-items: center; gap: 10px; max-width: 240px; min-width: 170px; padding: 6px 12px; border-radius: 14px; background: #103A5C; cursor: pointer; user-select: none; }
.cs-voice-btn { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cs-voice-btn svg { width: 14px; height: 14px; fill: #fff; }
.cs-voice-btn:not(.is-playing) svg { transform: translateX(1px); }
.cs-voice-btn.is-playing svg { transform: none; }
.cs-voice-progress { flex: 1; height: 4px; border-radius: 2px; background: rgba(255,255,255,.16); overflow: hidden; min-width: 40px; }
.cs-voice-fill { display: block; height: 100%; width: 0%; background: #3F6DF5; border-radius: 2px; transition: width .12s linear; }
.cs-voice-time { font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums; color: #8FA8C4; flex-shrink: 0; }
.cs-image-lightbox { position: fixed; inset: 0; z-index: 10050; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(5,10,22,.94); cursor: zoom-out; }
.cs-image-lightbox img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 10px; cursor: default; }
.cs-chat-composer { flex-shrink: 0; padding: 12px 18px; background: #0A1F38; border-top: 1px solid #1A4168; }
.cs-composer-row { display: flex; align-items: flex-end; gap: 8px; }
.cs-icon-btn {
    width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0;
    background: #102A47; border: 1px solid #1A4168; color: #8FA8C4;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.cs-icon-btn:hover { border-color: #3F6DF5; color: #F5F7FB; }
.cs-composer-input {
    flex: 1; resize: none; max-height: 80px;
    background: #102A47; border: 1px solid #1A4168; color: #F5F7FB;
    padding: 10px 14px; border-radius: 14px; font-size: 13px;
    font-family: 'Poppins', sans-serif; outline: none;
}
.cs-composer-input:focus { border-color: #3F6DF5; }
.cs-send-btn {
    width: 38px; height: 38px; border-radius: 12px; border: none; flex-shrink: 0;
    background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.cs-send-btn:disabled { opacity: .4; cursor: default; }
.cs-rec-bar { display: none; align-items: center; gap: 8px; margin-bottom: 8px; }
.cs-rec-dot { width: 9px; height: 9px; border-radius: 50%; background: #ef4444; animation: csPulse 1.2s ease-in-out infinite; }
.cs-rec-time { font-size: 12px; font-weight: 600; min-width: 34px; font-variant-numeric: tabular-nums; color: #F5F7FB; }
.cs-rec-hint { flex: 1; font-size: 11px; color: #6F89A7; }
.cs-rec-btn { width: 28px; height: 28px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cs-rec-btn.cancel { background: rgba(239,68,68,.85); color: #fff; }
.cs-rec-btn.send { background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; }
.cs-msg-actions { position: relative; display: inline-flex; }
.cs-del-trigger { background: none; border: none; cursor: pointer; font-size: 10px; opacity: .5; color: inherit; padding: 0 3px; }
.cs-del-trigger:hover { opacity: 1; }
.cs-del-menu { position: absolute; bottom: 100%; right: 0; z-index: 50; min-width: 168px; background: #102E4D; border: 1px solid #1A4168; border-radius: 10px; padding: 4px; display: flex; flex-direction: column; margin-bottom: 4px; box-shadow: 0 12px 34px rgba(0,0,0,.38); }
.cs-del-menu button { background: transparent; border: 0; text-align: left; padding: 7px 10px; border-radius: 8px; font-size: 12px; color: #F5F7FB; cursor: pointer; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.cs-del-menu button:hover { background: #102A47; }
.cs-del-menu button.danger { color: #f87171; }
.cs-empty { text-align: center; color: #6F89A7; font-size: 13px; padding: 20px; }
.cs-toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 500; color: #fff; z-index: 100010; box-shadow: 0 8px 24px -4px rgba(0,0,0,.3); }
</style>

@section('content')
<div class="cs-chat-page">
    <div class="cs-chat-header">
        <div class="cs-chat-header-left">
            <a href="{{ route('csadmin.conversations') }}" class="cs-back">←</a>
            <div class="cs-chat-avatar">G</div>
            <div style="min-width:0">
                <div class="cs-chat-title">{{ $conversation->channel?->name ?? 'Live Chat' }}</div>
                <div class="cs-chat-sub">Guest{{ $activeOperator ? ' • ' . $activeOperator->display_name . ' sedang online' : '' }}</div>
            </div>
        </div>
        <div class="cs-chat-actions">
            <span class="cs-status-pill {{ $activeOperator ? 'online' : 'offline' }}"><span class="cs-pulse-dot"></span>{{ $activeOperator ? 'Online' : 'Offline' }}</span>
            @if($conversation->status === 'open')
            <button class="cs-back" onclick="csClose()" title="Tutup percakapan">⏸</button>
            @else
            <button class="cs-back" onclick="csReopen()" title="Buka kembali" style="color:#34d399">▶</button>
            @endif
        </div>
    </div>

    <div class="cs-chat-messages" id="cs-messages">
        @forelse($messages as $msg)
        <div class="cs-msg {{ $msg->sender_type === 'user' ? 'cs-msg-user' : ($msg->sender_type === 'admin' ? 'cs-msg-admin' : 'cs-msg-system') }}"
             data-msg-id="{{ $msg->id }}"
             data-sender-type="{{ $msg->sender_type }}"
             data-sender-id="{{ $msg->sender_id ?? '' }}">
            @if($msg->sender_type === 'system')
                <div class="cs-bubble-system">{{ $msg->message }}</div>
            @else
                @if($msg->reply_to)
                <div class="cs-reply" onclick="csScrollToMsg({{ $msg->reply_to->id }})">
                    ↳ {{ Str::limit($msg->reply_to->message ?? ($msg->reply_to->message_type === 'audio' ? '🎤 Pesan suara' : ($msg->reply_to->message_type === 'video' ? '🎬 Video' : '📷 Foto')), 50) }}
                </div>
                @endif
                @if(($msg->message_type === 'image' && $msg->media_path) || ($msg->message_type === 'video' && $msg->media_path))
                @include('partials.livechat-gallery', ['msg' => $msg])
                @elseif($msg->message_type === 'audio' && $msg->media_path)
                <div class="cs-media">
                    <div class="cs-voice" data-dur="{{ $msg->media_duration ?? '' }}" onclick="csToggleVoice(this)">
                        <span class="cs-voice-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>
                        <span class="cs-voice-progress"><span class="cs-voice-fill"></span></span>
                        <span class="cs-voice-time">{{ $msg->media_duration ? gmdate('i:s', $msg->media_duration) : '' }}</span>
                        <audio src="/media/{{ $msg->media_path }}" preload="metadata"></audio>
                    </div>
                </div>
                @endif
                @if($msg->message)
                <div class="cs-bubble">{{ $msg->message }}</div>
                @endif
                <div class="cs-time">{{ $msg->created_at->format('H:i') }}
                    @if($msg->sender_type !== 'system')
                    <span class="cs-msg-actions">
                        <button type="button" class="cs-del-trigger" onclick="csDelMenu(event, {{ $msg->id }})">Hapus</button>
                    </span>
                    @endif
                </div>
            @endif
        </div>
        @empty
        <div class="cs-empty">Belum ada pesan dari guest</div>
        @endforelse
    </div>

    @if($conversation->status !== 'closed')
    <div class="cs-chat-composer">
        <div class="cs-rec-bar" id="cs-rec-bar">
            <span class="cs-rec-dot"></span>
            <span class="cs-rec-time" id="cs-rec-time">0:00</span>
            <span class="cs-rec-hint">Merekam...</span>
            <button type="button" class="cs-rec-btn cancel" onclick="csCancelVoice()" title="Batal"><svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            <button type="button" class="cs-rec-btn send" onclick="csFinishVoice()" title="Kirim"><svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
        </div>
        <input type="file" id="cs-file-input" accept="image/*,video/*" multiple style="display:none" onchange="csFileSelect(event)">
        <input type="file" id="cs-audio-input" accept="audio/*" style="display:none" onchange="csAudioFileSelect(event)">
        <div class="cs-composer-row">
            <button class="cs-icon-btn" onclick="document.getElementById('cs-file-input').click()" title="Kirim gambar/video">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
            </button>
            <button class="cs-icon-btn" onclick="csStartVoice()" title="Rekam pesan suara">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v1a7 7 0 0 0 14 0v-1M12 18v4"/></svg>
            </button>
            <textarea id="cs-input" class="cs-composer-input" rows="1" placeholder="Ketik pesan..."></textarea>
            <button class="cs-send-btn" id="cs-send-btn" onclick="csSendText()" disabled>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>
    @else
    <div style="padding:14px;text-align:center;color:#6F89A7;font-size:13px;background:#0A1F38;border-top:1px solid #1A4168">Percakapan telah ditutup</div>
    @endif
</div>

<style>
.chat-media { max-width: 100%; border-radius: 8px; overflow: hidden; margin-bottom: 4px; }
.chat-video-wrap { position: relative; }
.chat-video-play { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2; width: 44px; height: 44px; border: none; border-radius: 50%; background: rgba(0,0,0,.55); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.chat-video-play svg { width: 20px; height: 20px; fill: #fff; margin-left: 2px; }
.chat-video-play.is-hidden { display: none; }
.chat-msg-gallery { display: grid; gap: 3px; margin-bottom: 4px; width: 100%; max-width: 260px; }
.chat-grid-1 { grid-template-columns: minmax(0, 1fr); }
.chat-grid-2, .chat-grid-3, .chat-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.chat-msg-gallery .chat-gallery-item { position: relative; overflow: hidden; padding: 0; border: 0; background: #0a0a12; cursor: zoom-in; }
.chat-msg-gallery .chat-gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.chat-grid-1 .chat-gallery-item { display: block; }
.chat-grid-1 .chat-gallery-item img { height: auto; max-height: 160px; }
.chat-grid-2 .chat-gallery-item, .chat-grid-3 .chat-gallery-item, .chat-grid-4 .chat-gallery-item { aspect-ratio: 1 / 1; }
.chat-gallery-more { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.55); color: #fff; font-weight: 700; font-size: 20px; pointer-events: none; }
.empty-state { text-align: center; color: #6F89A7; font-size: 13px; padding: 20px; }
.cs-image-lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 3; width: 42px; height: 42px; border: 0; border-radius: 50%; background: rgba(255,255,255,.14); color: #fff; font-size: 22px; line-height: 1; cursor: pointer; display: grid; place-items: center; }
.cs-image-lightbox-nav.prev { left: 10px; }
.cs-image-lightbox-nav.next { right: 10px; }
.cs-image-lightbox-nav:hover { background: rgba(255,255,255,.25); }
.cs-lightbox-counter { position: fixed; bottom: 92px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,.5); color: #fff; padding: 4px 14px; border-radius: 999px; font-size: 12px; z-index: 10051; }
.cs-lightbox-thumbs { position: fixed; bottom: 18px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; padding: 6px; border-radius: 12px; background: rgba(0,0,0,.55); max-width: 88%; overflow-x: auto; z-index: 10051; }
.cs-lightbox-thumbs .cs-thumb { flex: 0 0 auto; width: 46px; height: 46px; padding: 0; border: 2px solid transparent; border-radius: 8px; overflow: hidden; background: #000; cursor: pointer; }
.cs-lightbox-thumbs .cs-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cs-lightbox-thumbs .cs-thumb.active { border-color: #a78bfa; }
.cs-image-lightbox-tools { position: fixed; top: 16px; right: 18px; z-index: 10051; }
.cs-image-lightbox-tools button, .cs-image-lightbox-tools a { width: 40px; height: 40px; border: 0; border-radius: 50%; background: rgba(255,255,255,.14); color: #fff; display: grid; place-items: center; text-decoration: none; cursor: pointer; }
.cs-image-lightbox-tools [data-action="close"] { font-size: 30px; line-height: 1; }
.cs-image-lightbox-tools svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
</style>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const CONVERSATION_ID = {{ $conversation->id }};
const CS_ADMIN_ID = {{ auth('admin')->id() }};

let csPollTimer = null;
let csReplyToId = null;
let csPreviewFiles = [];

function csEscapeHtml(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }

function csFmtVoiceTime(sec) {
    sec = Math.round(Number(sec) || 0);
    if (sec < 0) sec = 0;
    return Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0');
}

function csScrollBottom() {
    requestAnimationFrame(() => { csMessages.scrollTop = csMessages.scrollHeight; });
    setTimeout(() => { csMessages.scrollTop = csMessages.scrollHeight; }, 150);
}

function csScrollToMsg(id) {
    const el = document.querySelector(`[data-msg-id="${id}"]`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function csAttachmentsOfMsg(msg) {
    if (msg.attachments && msg.attachments.length) return msg.attachments;
    if (msg.media_path) return [{ media_path: msg.media_path, poster_path: msg.poster_path || null, media_mime: msg.media_mime || null, media_duration: msg.media_duration || null }];
    return [];
}

function csBuildGalleryHtml(msg) {
    const atts = csAttachmentsOfMsg(msg);
    if (!atts.length) return '';
    if (msg.message_type === 'video') {
        const a = atts[0];
        const posterAttr = a.poster_path ? ` poster="/media/${a.poster_path}"` : '';
        return `<div class="cs-media cs-video-wrap"><button type="button" class="cs-video-play" onclick="csPlayVideo(this)" aria-label="Putar video"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></button><video src="/media/${a.media_path}" controls preload="none"${posterAttr}></video></div>`;
    }
    const gridCls = atts.length === 1 ? 'cs-gallery cs-grid-1' : atts.length === 2 ? 'cs-gallery cs-grid-2' : atts.length === 3 ? 'cs-gallery cs-grid-3' : 'cs-gallery cs-grid-4';
    const urlsJson = JSON.stringify(atts.map(a => '/media/' + a.media_path)).replace(/"/g, '&quot;');
    const visible = atts.slice(0, 4);
    const hiddenCount = atts.length - 4;
    const tiles = visible.map((a, i) => {
        const url = '/media/' + a.media_path;
        const more = (i === 3 && hiddenCount > 0) ? `<span class="cs-gallery-more">+${hiddenCount}</span>` : '';
        return `<button type="button" class="cs-gallery-item" data-urls="${urlsJson}" onclick="csOpenLightbox(this, ${msg.id}, ${i})" aria-label="Buka gambar ukuran penuh"><img src="${url}" alt="Gambar" loading="lazy">${more}</button>`;
    }).join('');
    return `<div class="cs-media ${gridCls}">${tiles}</div>`;
}

function csBuildVoiceHtml(msg) {
    const a = csAttachmentsOfMsg(msg)[0];
    if (!a) return '';
    const dur = a.media_duration ? csFmtVoiceTime(a.media_duration) : '';
    return `<div class="cs-media"><div class="cs-voice" data-dur="${a.media_duration || ''}" onclick="csToggleVoice(this)"><span class="cs-voice-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span><span class="cs-voice-progress"><span class="cs-voice-fill"></span></span><span class="cs-voice-time">${dur}</span><audio src="/media/${a.media_path}" preload="metadata"></audio></div></div>`;
}

function csAppendMessage(msg) {
    const emptyState = csMessages.querySelector('.cs-empty, .empty-state');
    if (emptyState) emptyState.remove();

    const div = document.createElement('div');
    div.className = 'cs-msg ' + (msg.sender_type === 'user' ? 'cs-msg-user' : (msg.sender_type === 'admin' ? 'cs-msg-admin' : 'cs-msg-system'));
    div.dataset.msgId = msg.id;
    div.dataset.senderType = msg.sender_type;
    div.dataset.senderId = msg.sender_id == null ? '' : msg.sender_id;

    if (msg.sender_type === 'system') {
        div.innerHTML = `<div class="cs-bubble-system">${csEscapeHtml(msg.message)}</div>`;
        csMessages.appendChild(div);
        return;
    }

    let html = '';
    if (msg.reply_to) {
        const rp = msg.reply_to.message_type === 'audio' ? '🎤 Pesan suara'
            : msg.reply_to.message_type === 'video' ? '🎬 Video'
            : msg.reply_to.message_type === 'image' ? '📷 Foto'
            : ((msg.reply_to.message || '').substring(0, 50));
        html += `<div class="cs-reply" onclick="csScrollToMsg(${msg.reply_to.id})">↳ ${csEscapeHtml(rp)}</div>`;
    }
    if (msg.message_type === 'image' && (msg.media_path || (msg.attachments && msg.attachments.length))) {
        html += csBuildGalleryHtml(msg);
    } else if (msg.message_type === 'video' && msg.media_path) {
        html += csBuildGalleryHtml(msg);
    } else if (msg.message_type === 'audio' && msg.media_path) {
        html += csBuildVoiceHtml(msg);
    }
    if (msg.message) {
        html += `<div class="cs-bubble">${csEscapeHtml(msg.message)}</div>`;
    }
    const time = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    let actionBtns = '<span class="cs-msg-actions"><button type="button" class="cs-del-trigger" onclick="csDelMenu(event, ' + msg.id + ')">Hapus</button></span>';
    html += `<div class="cs-time">${time}${actionBtns}</div>`;

    div.innerHTML = html;
    csMessages.appendChild(div);
}

function csStartPoll() {
    csStopPoll();
    csPollTimer = setInterval(csPoll, 3000);
}

function csStopPoll() {
    if (csPollTimer) { clearInterval(csPollTimer); csPollTimer = null; }
}

async function csPoll() {
    const lastMsg = csMessages.querySelector('[data-msg-id]:last-child');
    const lastId = lastMsg ? lastMsg.dataset.msgId : 0;
    try {
        const res = await fetch(`/csadmin/conversations/${CONVERSATION_ID}/poll?after=${lastId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) return;
        const msgs = await res.json();
        if (msgs.length > 0) {
            msgs.forEach(msg => {
                if (!csMessages.querySelector(`[data-msg-id="${msg.id}"]`)) csAppendMessage(msg);
            });
            csMessages.scrollTop = csMessages.scrollHeight;
        }
    } catch (e) {}
}

function csPlayVideo(btn) {
    const wrap = btn.closest('.cs-video-wrap');
    if (!wrap) return;
    const video = wrap.querySelector('video');
    if (!video) return;
    if (!video.dataset.csVideoBound) {
        video.dataset.csVideoBound = '1';
        video.addEventListener('play', () => btn.classList.add('is-hidden'));
        video.addEventListener('pause', () => btn.classList.remove('is-hidden'));
        video.addEventListener('ended', () => btn.classList.remove('is-hidden'));
    }
    btn.classList.add('is-hidden');
    const pr = video.play();
    if (pr && pr.catch) pr.catch(() => btn.classList.remove('is-hidden'));
}

function csToggleVoice(el) {
    const audio = el.querySelector('audio');
    if (!audio || !audio.src) return;
    const btn = el.querySelector('.cs-voice-btn');
    const fill = el.querySelector('.cs-voice-fill');
    const time = el.querySelector('.cs-voice-time');
    const setPlaying = (p) => btn && btn.classList.toggle('is-playing', p);
    if (window.__CsActiveVoice && window.__CsActiveVoice !== audio) {
        try { window.__CsActiveVoice.pause(); } catch (e) {}
    }
    window.__CsActiveVoice = audio.paused ? audio : null;
    if (!audio.dataset.csBound) {
        audio.dataset.csBound = '1';
        audio.addEventListener('play', () => setPlaying(true));
        audio.addEventListener('pause', () => setPlaying(false));
        audio.addEventListener('ended', () => {
            setPlaying(false);
            if (fill) fill.style.width = '0%';
            const d = el.dataset.dur;
            if (time) time.textContent = d ? csFmtVoiceTime(d) : '0:00';
            window.__CsActiveVoice = null;
        });
        audio.addEventListener('timeupdate', () => {
            if (!audio.duration || !isFinite(audio.duration)) return;
            if (fill) fill.style.width = ((audio.currentTime / audio.duration) * 100).toFixed(1) + '%';
            if (time) time.textContent = csFmtVoiceTime(audio.currentTime);
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

function csOpenLightbox(btn, messageId, startIndex) {
    let urls = [];
    try { urls = JSON.parse(btn.dataset.urls || '[]'); } catch (e) {}
    if (!urls.length) return;
    document.getElementById('cs-image-lightbox')?.remove();

    let index = Math.max(0, Math.min(startIndex || 0, urls.length - 1));
    const hasMany = urls.length > 1;
    const thumbsHtml = urls.map((u, i) =>
        `<button type="button" class="cs-thumb${i === index ? ' active' : ''}" data-i="${i}" aria-label="Gambar ${i + 1}"><img src="${u}" alt="" loading="lazy"></button>`
    ).join('');

    const lightbox = document.createElement('div');
    lightbox.id = 'cs-image-lightbox';
    lightbox.className = 'cs-image-lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.innerHTML = `
        <div class="cs-image-lightbox-tools">
            <a href="${urls[index]}" download title="Unduh" aria-label="Unduh gambar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v11"/><path d="m8 10 4 4 4-4M5 20h14"/></svg></a>
            <button type="button" data-action="close" title="Tutup" aria-label="Tutup gambar">×</button>
        </div>
        ${hasMany ? `<button type="button" class="cs-image-lightbox-nav prev" data-nav="-1" aria-label="Sebelumnya">‹</button>` : ''}
        <img src="${urls[index]}" alt="Gambar ukuran penuh" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:10px;cursor:default">
        ${hasMany ? `<button type="button" class="cs-image-lightbox-nav next" data-nav="1" aria-label="Berikutnya">›</button>` : ''}
        ${hasMany ? `<div class="cs-lightbox-counter">${index + 1} / ${urls.length}</div>` : ''}
        ${hasMany ? `<div class="cs-lightbox-thumbs">${thumbsHtml}</div>` : ''}`;

    const img = lightbox.querySelector('img');
    const download = lightbox.querySelector('a[download]');
    const thumbs = lightbox.querySelector('.cs-lightbox-thumbs');
    const counter = lightbox.querySelector('.cs-lightbox-counter');
    const updateThumbs = () => {
        thumbs?.querySelectorAll('.cs-thumb').forEach((t, i) => {
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
    lightbox.addEventListener('click', (event) => { if (event.target === lightbox) close(); });
    lightbox.querySelector('.cs-image-lightbox-nav.prev')?.addEventListener('click', () => setIndex(index - 1));
    lightbox.querySelector('.cs-image-lightbox-nav.next')?.addEventListener('click', () => setIndex(index + 1));
    thumbs?.addEventListener('click', (e) => {
        const t = e.target.closest('.cs-thumb');
        if (t) setIndex(parseInt(t.dataset.i, 10));
    });
    lightbox.querySelector('[data-action="close"]')?.addEventListener('click', close);
    onKeydown = function(event) {
        if (event.key === 'Escape') { close(); return; }
        if (hasMany && event.key === 'ArrowLeft') setIndex(index - 1);
        if (hasMany && event.key === 'ArrowRight') setIndex(index + 1);
    };
    document.addEventListener('keydown', onKeydown);
    document.body.appendChild(lightbox);
    lightbox.querySelector('[data-action="close"]')?.focus();
}

function playChatVideo(btn) { csPlayVideo(btn); }

function openAdminGallery(btn, messageId, startIndex) {
    let urls = [];
    try { urls = JSON.parse(btn.dataset.urls || '[]'); } catch (e) {}
    if (!urls.length) return;
    csOpenLightbox(btn, messageId, startIndex);
}

const csInput = document.getElementById('cs-input');
const csSendBtn = document.getElementById('cs-send-btn');

if (csInput) {
    csInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        if (csSendBtn) csSendBtn.disabled = !this.value.trim();
    });
    csInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); csSendText(); }
    });
}

async function csSendText() {
    const text = csInput?.value?.trim();
    if (!text) return;
    const replyToId = csReplyToId;
    csInput.value = '';
    csInput.style.height = 'auto';
    if (csSendBtn) csSendBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('message_type', 'text');
        formData.append('message', text);
        if (replyToId) formData.append('reply_to_message_id', replyToId);
        const res = await fetch(`/csadmin/conversations/${CONVERSATION_ID}/reply`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        if (!res.ok) throw new Error();
        const msg = await res.json();
        csAppendMessage(msg);
        csReplyToId = null;
        csInput.placeholder = 'Ketik pesan...';
        csScrollBottom();
    } catch (e) {
        csInput.value = text;
        if (csSendBtn) csSendBtn.disabled = false;
    }
}

function csFileSelect(e) {
    const newFiles = Array.from(e.target.files);
    if (!newFiles.length) return;
    e.target.value = '';
    csPreviewFiles.push(...newFiles);
    csShowPreview();
}

function csShowPreview() {
    const o = document.getElementById('cs-preview-overlay');
    if (o) o.remove();
    if (csPreviewFiles.length === 0) return;
    const filesHtml = csPreviewFiles.map((f, i) => {
        const url = URL.createObjectURL(f);
        const isImage = f.type.startsWith('image/');
        return `<div style="position:relative;display:inline-block;margin:4px">
            ${isImage ? `<img src="${url}" style="width:80px;height:80px;object-fit:cover;border-radius:8px">` : `<video src="${url}" style="width:80px;height:80px;object-fit:cover;border-radius:8px" muted preload="metadata"></video>`}
            <button onclick="csRemovePreviewFile(${i})" style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;border-radius:50%;border:none;background:rgba(0,0,0,.7);color:#fff;font-size:10px;cursor:pointer;display:flex;align-items:center;justify-content:center">&times;</button>
        </div>`;
    }).join('');
    const overlay = document.createElement('div');
    overlay.id = 'cs-preview-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:10002;display:flex;align-items:center;justify-content:center';
    overlay.innerHTML = `
        <div style="background:#102E4D;border:1px solid #1A4168;border-radius:16px;padding:20px;max-width:400px;width:90%;text-align:center">
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:4px;margin-bottom:12px">${filesHtml}</div>
            <input type="text" id="cs-preview-caption" placeholder="Caption (opsional)" style="width:100%;margin-bottom:12px;border-radius:12px;padding:10px 14px;background:#102A47;border:1px solid #1A4168;color:#F5F7FB;outline:none;font-family:'Poppins',sans-serif">
            <div style="display:flex;gap:8px;justify-content:center">
                <button onclick="csCancelPreview()" style="padding:8px 20px;border-radius:8px;border:1px solid #1A4168;background:transparent;color:#8FA8C4;cursor:pointer;font-family:'Poppins',sans-serif">Batal</button>
                <button onclick="csConfirmSend()" style="padding:8px 20px;border-radius:8px;border:none;background:linear-gradient(135deg,#2563eb,#0ea5e9);color:#fff;cursor:pointer;font-weight:600;font-family:'Poppins',sans-serif">Kirim</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
}

function csRemovePreviewFile(idx) {
    csPreviewFiles.splice(idx, 1);
    if (csPreviewFiles.length === 0) csCancelPreview();
    else csShowPreview();
}

function csCancelPreview() {
    csPreviewFiles = [];
    document.getElementById('cs-preview-overlay')?.remove();
}

async function csExtractVideoThumb(file) {
    return new Promise(resolve => {
        const video = document.createElement('video');
        video.preload = 'auto';
        video.muted = true;
        video.playsInline = true;
        video.crossOrigin = 'anonymous';
        const url = URL.createObjectURL(file);
        video.src = url;
        let done = false;
        const finish = (blob) => { if (done) return; done = true; URL.revokeObjectURL(url); resolve(blob); };
        const timeout = setTimeout(() => finish(null), 8000);
        video.addEventListener('loadedmetadata', () => {
            try {
                const dur = video.duration;
                const isLive = !isFinite(dur) || dur <= 0;
                video.currentTime = isLive ? 1 : Math.max(0, Math.min(1, dur - 0.05));
                if (video.paused) video.play().catch(() => {});
            } catch (e) { clearTimeout(timeout); finish(null); }
        }, { once: true });
        video.addEventListener('seeked', () => {
            clearTimeout(timeout);
            try {
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth || 320;
                canvas.height = video.videoHeight || 180;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(blob => finish(blob), 'image/jpeg', 0.6);
                if (!video.paused) video.pause();
            } catch (e) { finish(null); }
        }, { once: true });
        video.addEventListener('error', () => { clearTimeout(timeout); finish(null); }, { once: true });
    });
}

async function csConfirmSend() {
    if (!csPreviewFiles.length) return;
    const caption = document.getElementById('cs-preview-caption')?.value || '';
    const files = [...csPreviewFiles];
    const replyToId = csReplyToId;
    csCancelPreview();

    const imageFiles = files.filter(f => f.type.startsWith('image/'));
    const videoFiles = files.filter(f => !f.type.startsWith('image/'));

    if (imageFiles.length > 0) {
        try {
            const formData = new FormData();
            formData.append('message_type', 'image');
            if (caption) formData.append('message', caption);
            if (replyToId) formData.append('reply_to_message_id', replyToId);
            imageFiles.forEach(f => formData.append('media[]', f));
            const res = await fetch(`/csadmin/conversations/${CONVERSATION_ID}/reply`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            if (!res.ok) throw new Error();
            const msg = await res.json();
            csAppendMessage(msg);
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
            const thumbBlob = await csExtractVideoThumb(file);
            if (thumbBlob) {
                formData.append('thumbnail[]', thumbBlob, 'thumb.jpg');
                formData.append('thumbnail_indexes[]', '0');
            }
            const res = await fetch(`/csadmin/conversations/${CONVERSATION_ID}/reply`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            if (!res.ok) throw new Error();
            const msg = await res.json();
            csAppendMessage(msg);
        } catch (e) {}
    }
    csReplyToId = null;
    csInput.placeholder = 'Ketik pesan...';
    csScrollBottom();
}

/* Voice note */
let csRec = { recorder: null, chunks: [], stream: null, timer: null, mime: '', startTs: 0, seconds: 0 };

function csRecMime() {
    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    for (let i = 0; i < candidates.length; i++) {
        if (window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(candidates[i])) return candidates[i];
    }
    return 'audio/webm';
}
function csRecExt(mime) {
    if (/mp4|m4a/i.test(mime)) return 'm4a';
    if (/ogg|opus/i.test(mime)) return 'ogg';
    return 'webm';
}
function csResetRec() {
    clearInterval(csRec.timer);
    csRec.timer = null;
    csRec.recorder = null;
    csRec.chunks = [];
    csRec.mime = '';
    csRec.startTs = 0;
    csRec.seconds = 0;
    if (csRec.stream) {
        try { csRec.stream.getTracks().forEach(t => t.stop()); } catch (e) {}
        csRec.stream = null;
    }
}
function csSetRecBar(visible) {
    const bar = document.getElementById('cs-rec-bar');
    if (bar) bar.style.display = visible ? 'flex' : 'none';
}
function csRecErrorMsg(err) {
    const name = (err && err.name) || '';
    if (!window.isSecureContext) return 'Mikrofon hanya tersedia via HTTPS atau http://localhost. Origin saat ini tidak aman (' + location.origin + ').';
    if (name === 'NotAllowedError' || name === 'PermissionDeniedError') return 'Akses mikrofon diblokir. Cek ikon kunci di address bar dan izin situs.';
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') return 'Tidak ada mikrofon yang terdeteksi.';
    if (name === 'NotReadableError' || name === 'TrackStartError') return 'Mikrofon sedang dipakai aplikasi lain.';
    if (name === 'OverconstrainedError') return 'Mikrofon tidak mendukung pengaturan yang diminta.';
    return 'Gagal mengakses mikrofon (' + (name || 'Error') + '): ' + ((err && err.message) || 'tidak diketahui');
}
async function csStartVoice() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert(window.isSecureContext ? 'Browser tidak mendukung perekaman suara.' : 'Mikrofon hanya tersedia via HTTPS atau http://localhost.');
        return;
    }
    if (csRec.recorder) {
        try { csRec.recorder.resume(); } catch (e) {}
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        csRec.stream = stream;
        csRec.mime = csRecMime();
        let recorder;
        try {
            recorder = csRec.mime ? new MediaRecorder(stream, { mimeType: csRec.mime }) : new MediaRecorder(stream);
        } catch (e) {
            recorder = new MediaRecorder(stream);
        }
        csRec.mime = recorder.mimeType || csRec.mime || 'audio/webm';
        csRec.chunks = [];
        recorder.ondataavailable = (e) => { if (e.data && e.data.size) csRec.chunks.push(e.data); };
        recorder.start();
        csRec.recorder = recorder;
        csRec.startTs = Date.now();
        csRec.seconds = 0;
        csSetRecBar(true);
        const timeEl = document.getElementById('cs-rec-time');
        if (timeEl) {
            timeEl.textContent = '0:00';
            csRec.timer = setInterval(() => {
                csRec.seconds = Math.floor((Date.now() - csRec.startTs) / 1000);
                timeEl.textContent = csFmtVoiceTime(csRec.seconds);
                if (csRec.seconds >= 1800) csFinishVoice();
            }, 500);
        }
    } catch (err) {
        console.error('Admin CS: mic error', err);
        alert(csRecErrorMsg(err));
        csSetRecBar(false);
    }
}
function csCancelVoice() {
    const r = csRec.recorder;
    clearInterval(csRec.timer);
    csRec.timer = null;
    if (r && r.state !== 'inactive') {
        r.onstop = () => csResetRec();
        try { r.stop(); } catch (e) { csResetRec(); }
    } else {
        csResetRec();
    }
    csSetRecBar(false);
}
function csFinishVoice() {
    const r = csRec.recorder;
    if (!r || r.state === 'inactive') return;
    csSetRecBar(false);
    const duration = Math.max(1, csRec.seconds || Math.floor((Date.now() - csRec.startTs) / 1000));
    const mime = csRec.mime || 'audio/webm';
    r.onstop = () => {
        const blob = new Blob(csRec.chunks, { type: mime });
        if (blob.size === 0) {
            csResetRec();
            csShowToast('Rekaman kosong.', 'error');
            return;
        }
        csResetRec();
        const file = new File([blob], 'voice-note-' + Date.now() + '.' + csRecExt(mime), { type: mime });
        csSendVoiceFile(file, duration);
    };
    try { r.stop(); } catch (e) { csResetRec(); csSetRecBar(false); }
    setTimeout(() => csSetRecBar(false), 4000);
}
function csAudioFileSelect(e) {
    const file = e.target.files[0];
    if (!file) return;
    e.target.value = '';
    csSendVoiceFile(file, 0);
}
async function csSendVoiceFile(file, duration) {
    try {
        const formData = new FormData();
        formData.append('message_type', 'audio');
        formData.append('media[]', file);
        formData.append('media_duration', String(Math.round(duration)));
        const res = await fetch(`/csadmin/conversations/${CONVERSATION_ID}/reply`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        if (!res.ok) throw new Error();
        const msg = await res.json();
        csAppendMessage(msg);
        csScrollBottom();
    } catch (e) {
        csShowToast('Gagal mengirim pesan suara', 'error');
    }
}
function csShowToast(message, type) {
    const t = document.createElement('div');
    t.textContent = message;
    t.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:500;color:#fff;z-index:100010;background:' + (type === 'error' ? '#ef4444' : '#10b981') + ';box-shadow:0 8px 24px -4px rgba(0,0,0,.3)';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function csDelMenu(e, id) {
    e.stopPropagation();
    document.querySelectorAll('.cs-del-menu').forEach(m => m.remove());
    const anchor = document.querySelector(`[data-msg-id="${id}"] .cs-msg-actions`);
    if (!anchor) return;
    const node = document.querySelector(`[data-msg-id="${id}"]`);
    const canDelete = node && node.dataset.senderType === 'admin' && String(node.dataset.senderId) === String(CS_ADMIN_ID);
    const menu = document.createElement('div');
    menu.className = 'cs-del-menu';
    menu.innerHTML = `
        <button type="button" class="danger" onclick="csHideMsg(${id})">Hapus untuk saya</button>
        ${canDelete ? `<button type="button" class="danger" onclick="csDeleteMsg(${id})">Hapus untuk semua orang</button>` : ''}
        <button type="button" onclick="this.closest('.cs-del-menu').remove()">Batal</button>`;
    anchor.appendChild(menu);
}
document.addEventListener('click', function(e) {
    if (!e.target.isConnected) return;
    document.querySelectorAll('.cs-del-menu').forEach(m => m.remove());
});

async function csHideMsg(id) {
    if (!confirm('Hapus pesan ini hanya dari chat Anda?')) return;
    try {
        const res = await fetch(`/csadmin/messages/${id}/hide`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        document.querySelector(`[data-msg-id="${id}"]`)?.remove();
    } catch (e) {}
}

async function csDeleteMsg(id) {
    if (!confirm('Hapus pesan ini untuk semua orang?')) return;
    try {
        const res = await fetch(`/csadmin/messages/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        document.querySelector(`[data-msg-id="${id}"]`)?.remove();
    } catch (e) {}
}

async function csClose() {
    if (!confirm('Tutup percakapan ini?')) return;
    try {
        await fetch(`/csadmin/conversations/${CONVERSATION_ID}/close`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        location.reload();
    } catch (e) {}
}

async function csReopen() {
    try {
        await fetch(`/csadmin/conversations/${CONVERSATION_ID}/reopen`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        location.reload();
    } catch (e) {}
}

const csMessages = document.getElementById('cs-messages');

document.addEventListener('DOMContentLoaded', function() {
    csScrollBottom();
    csStartPoll();
});
</script>
@endsection
