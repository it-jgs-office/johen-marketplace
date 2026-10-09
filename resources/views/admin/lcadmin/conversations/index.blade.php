@extends('admin.layouts.lcadmin')
@section('title', 'Live Chat')

@section('topbar')
@endsection

@php
    $adminUser = Auth::guard('admin')->user();
    $primaryChannel = $adminUser->assignedOperators()->with('channel')->first()?->channel;
    $channelName = $primaryChannel?->name ?? '';
    $liveChatAdmin = null;
    if ($primaryChannel) {
        $liveChatAdmin = \App\Models\LiveChatAdmin::where('channel_id', $primaryChannel->id)->first();
    }
    $adminPhoto = $liveChatAdmin && $liveChatAdmin->photo_path ? asset('storage/' . $liveChatAdmin->photo_path) : null;
@endphp

@push('styles')
<style>
.admin-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;margin-top:12px;font-family:'Poppins',sans-serif}
.admin-pagination__summary{margin:0;color:#8fa8c4;font-size:12px;white-space:nowrap}.admin-pagination__summary strong{color:#f5f7fb}
.admin-pagination__links{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:4px}.admin-pagination__control{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border:1px solid #1a4168;border-radius:8px;background:#102a47;color:#8fa8c4;font-size:12px;font-weight:600;line-height:1;text-decoration:none}.admin-pagination__control:hover{border-color:#3f6df5;background:#102e4d;color:#f5f7fb}.admin-pagination__control.is-current{border-color:transparent;background:linear-gradient(135deg,#3f6df5,#7653ec);color:#fff}.admin-pagination__control.is-disabled{opacity:.4;cursor:not-allowed}.admin-pagination__control.is-dots{min-width:22px;border-color:transparent;background:transparent;cursor:default}
@media(max-width:560px){.admin-pagination{flex-direction:column;align-items:stretch}.admin-pagination__summary{text-align:center;white-space:normal}.admin-pagination__links{justify-content:center}}
.lc-container {
    display: flex;
    height: 100%;
    border-radius: 0;
    overflow: hidden;
    border: none;
    background: #102E4D;
}

/* SIDEBAR */
.lc-sidebar {
    width: 600px;
    min-width: 600px;
    border-right: 1px solid #1A4168;
    display: flex;
    flex-direction: column;
    background: #102E4D;
}
.lc-sidebar-header {
    padding: 14px 16px 14px;
    border-bottom: 1px solid #1A4168;
}
.lc-sidebar-header h2 {
    font-weight: 700;
    font-size: 17px;
    color: #F5F7FB;
    margin: 0 0 14px;
}
.lc-search { position: relative; }
.lc-search input {
    width: 100%;
    height: 36px;
    padding: 0 12px 0 36px;
    border-radius: 8px;
    border: 1px solid #1A4168;
    background: #102A47;
    color: #F5F7FB;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    outline: none;
    transition: border-color 0.2s;
}
.lc-search input:focus { border-color: #3F6DF5; }
.lc-search input::placeholder { color: #6F89A7; }
.lc-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6F89A7;
    font-size: 13px;
    pointer-events: none;
}
.lc-filters {
    display: flex;
    gap: 6px;
    margin-top: 12px;
}
.lc-filter-btn {
    flex: 1;
    padding: 6px 0;
    border-radius: 8px;
    border: 1px solid #1A4168;
    background: transparent;
    color: #6F89A7;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all .2s;
}
.lc-filter-btn:hover { border-color: #3F6DF5; color: #8FA8C4; }
.lc-filter-btn.active {
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 8px -2px rgba(63,109,245,0.4);
}
.lc-fav-btn {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font-size: 11px;
    color: #214D78;
    transition: color .2s;
    line-height: 1;
}
.lc-fav-btn:hover { color: #f59e0b; }
.lc-fav-btn.active { color: #f59e0b; }

/* ---- LIST ITEM DROPDOWN ---- */
.lc-item-actions { position: relative; flex-shrink: 0; }
.lc-item-arrow {
    display: none;
    background: none; border: none; color: #6F89A7; font-size: 11px;
    padding: 4px 6px; cursor: pointer; border-radius: 6px; transition: all .15s;
}
.lc-item:hover .lc-item-arrow { display: flex; }
.lc-item-arrow:hover { background: rgba(255,255,255,0.08); color: #F5F7FB; }
.lc-item-dropdown {
    position: absolute; top: 100%; right: 0; z-index: 80;
    background: #102E4D; border: 1px solid #1A4168; border-radius: 10px;
    padding: 4px; min-width: 176px;
    box-shadow: 0 8px 24px -4px rgba(0,0,0,0.4);
    opacity: 0; pointer-events: none;
    transform: translateY(-4px); transition: all .15s;
}
.lc-item-dropdown.open { opacity: 1; pointer-events: auto; transform: translateY(2px); }
.lc-item-dropdown button {
    display: flex; align-items: center; gap: 8px; width: 100%;
    padding: 8px 12px; border: none; background: none;
    color: #F5F7FB; font-size: 12px; font-family: 'Poppins', sans-serif;
    border-radius: 6px; cursor: pointer; transition: background .12s;
    white-space: nowrap;
}
.lc-item-dropdown button i { flex-shrink: 0; }
.lc-item-dropdown button:hover { background: rgba(63,109,245,0.14); color: #3F6DF5; }
.lc-item-dropdown button.danger { color: #ef4444; }
.lc-item-dropdown button.danger:hover { background: rgba(239,68,68,0.12); color: #ef4444; }

.lc-list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #214D78 transparent;
}
.lc-list::-webkit-scrollbar { width: 5px; }
.lc-list::-webkit-scrollbar-track { background: transparent; }
.lc-list::-webkit-scrollbar-thumb { background: #214D78; border-radius: 10px; }

.lc-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    cursor: pointer;
    transition: background 0.18s ease;
    border-bottom: 1px solid rgba(26,65,104,0.4);
}
.lc-item:hover { background: rgba(63,109,245,0.06); }
.lc-item.active { background: rgba(63,109,245,0.1); }
.lc-item.pinned { background: rgba(63,109,245,0.05); }
.lc-item.pinned .lc-item-name { font-weight: 700; }
.lc-item-pin { display: none; font-size: 10px; color: #5B9CF5; margin-left: auto; flex-shrink: 0; }
.lc-item.pinned .lc-item-pin { display: inline-block; }
.lc-item-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
}
.lc-item-body { flex: 1; min-width: 0; }
.lc-item-row1 { display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
.lc-online-dot { width: 7px; height: 7px; border-radius: 50%; background: #36C98F; flex-shrink: 0; }
.lc-item-name { font-weight: 600; font-size: 13px; color: #F5F7FB; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lc-item-channel { font-weight: 600; font-size: 11px; color: #5B9CF5; margin-bottom: 2px; }
.lc-item-preview { font-size: 12px; color: #8FA8C4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4; }
.lc-item-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; }
.lc-item-time { font-size: 10px; color: #6F89A7; }
.lc-item-unread {
    min-width: 18px; height: 18px; border-radius: 50%; background: linear-gradient(135deg, #3F6DF5, #8b5cf6);
    color: #fff; font-size: 9px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; padding: 0 5px;
}

/* ARCHIVED */
.lc-main-panel {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}
.lc-archived-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid rgba(26,65,104,0.4);
    transition: background 0.18s ease;
    flex-shrink: 0;
}
.lc-archived-row:hover { background: rgba(255,255,255,0.05); }
.lc-archived-row-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #102A47;
    color: #8FA8C4;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 15px;
}
.lc-archived-row-name { font-weight: 600; font-size: 13px; color: #F5F7FB; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lc-archived-badge {
    min-width: 18px; height: 18px; border-radius: 50%;
    background: linear-gradient(135deg, #3F6DF5, #8b5cf6);
    color: #fff; font-size: 9px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    padding: 0 5px; margin-left: auto; flex-shrink: 0;
}
.lc-archived-panel {
    display: none;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    background: #102E4D;
}
.lc-archived-header {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 8px;
    border-bottom: 1px solid #1A4168;
    background: #102E4D;
    flex-shrink: 0;
}
.lc-archived-back {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: none;
    border: none;
    color: #F5F7FB;
    cursor: pointer;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.lc-archived-back:hover { background: rgba(255,255,255,0.08); }
.lc-archived-title { font-weight: 700; font-size: 15px; color: #F5F7FB; }
.lc-archived-items {
    flex: 1;
    overflow-y: auto;
    min-height: 0;
    scrollbar-width: thin;
    scrollbar-color: #214D78 transparent;
}
.lc-archived-items::-webkit-scrollbar { width: 5px; }
.lc-archived-items::-webkit-scrollbar-thumb { background: #214D78; border-radius: 10px; }
.lc-archived-empty {
    flex: 1;
    min-height: 0;
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 8px;
    padding: 24px 16px;
    text-align: center;
}
.lc-archived-empty-icon { font-size: 40px; color: #6F89A7; opacity: 0.8; }
.lc-archived-empty-title { font-weight: 700; font-size: 15px; color: #F5F7FB; }
.lc-archived-empty-desc { font-size: 13px; color: #8FA8C4; max-width: 260px; line-height: 1.5; }

/* CHAT AREA */
.lc-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #071D35;
    min-width: 0;
}
.lc-empty {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 12px;
}
.lc-empty-icon svg { width: 64px; height: 64px; color: #607995; opacity: 0.7; }
.lc-empty-title { font-weight: 700; font-size: 18px; color: #F5F7FB; text-align: center; }
.lc-empty-desc { font-size: 14px; color: #8FA8C4; text-align: center; max-width: 300px; line-height: 1.5; }

/* Chat Header */
.lc-header {
    padding: 14px 18px;
    border-bottom: 1px solid #1A4168;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #102E4D;
}
.lc-header-left { display: flex; align-items: center; gap: 12px; }
.lc-header-avatar {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #3F6DF5, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; color: #fff; flex-shrink: 0;
}
.lc-header-info { display: flex; flex-direction: column; gap: 1px; }
.lc-header-name { font-weight: 700; font-size: 14px; color: #F5F7FB; }
.lc-header-meta { font-size: 12px; color: #8FA8C4; display: flex; align-items: center; gap: 6px; }
.lc-header-meta .online-dot { width: 6px; height: 6px; border-radius: 50%; background: #36C98F; }
.lc-header-actions { display: flex; gap: 8px; }

/* Messages */
.lc-messages {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: linear-gradient(180deg, #0A1F38 0%, #071D35 42%);
    scrollbar-width: thin;
    scrollbar-color: #214D78 transparent;
}
.lc-messages::-webkit-scrollbar { width: 5px; }
.lc-messages::-webkit-scrollbar-thumb { background: #214D78; border-radius: 10px; }

/* Message structure — matches user side */
.lc-msg {
    max-width: 70%;
    position: relative;
    display: flex;
    align-items: flex-end;
    gap: 4px;
    animation: lcMsgIn .22s ease-out both;
    z-index: 1;
}
@keyframes lcMsgIn { from { opacity: 0; transform: translateY(7px) scale(.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
.lc-msg-user { align-self: flex-start; }
.lc-msg-admin { align-self: flex-end; flex-direction: row-reverse; }
.lc-msg-system { align-self: center; max-width: 100%; }
.lc-msg-body { min-width: 0; display: flex; flex-direction: column; }

/* Anchor / chevron */
.lc-msg-anchor {
    width: 22px; height: 22px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: #6F89A7; background: transparent;
    flex-shrink: 0; align-self: center;
    transition: background .15s, color .15s;
    opacity: 0; user-select: none;
}
.lc-msg:hover .lc-msg-anchor, .lc-msg-has-menu .lc-msg-anchor { opacity: 1; }
.lc-msg-anchor:hover { background: rgba(63,109,245,0.12); color: #3F6DF5; }
.lc-msg-anchor svg { pointer-events: none; }

/* Bubbles */
.lc-bubble {
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
    transition: transform .16s ease, box-shadow .16s ease;
}
.lc-msg:hover .lc-bubble { transform: translateY(-1px); box-shadow: 0 7px 18px rgba(0,0,0,.14); }
.lc-msg-user .lc-bubble {
    background: #1A3A5C;
    color: #F5F7FB;
    border-bottom-left-radius: 4px;
}
.lc-msg-admin .lc-bubble {
    background: linear-gradient(135deg, #3F6DF5, #8b5cf6);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.lc-bubble-system {
    background: rgba(63,109,245,0.08);
    color: #8FA8C4;
    font-size: 12px;
    text-align: center;
    border: 1px solid rgba(63,109,245,0.15);
    padding: 10px 16px;
}
.lc-msg-time { font-size: 10px; color: #6F89A7; margin-top: 4px; }
.lc-msg-admin .lc-msg-time { text-align: right; }

/* Media */
.lc-msg-media { max-width: 100%; border-radius: 8px; overflow: hidden; margin-bottom: 4px; }
.lc-image-trigger {
    display: block; padding: 0; border: 0; background: transparent; cursor: zoom-in;
}
.lc-msg-media img {
    width: 100%; max-height: 200px; object-fit: cover; display: block;
    border-radius: 8px;
}
.lc-msg-gallery { display: grid; gap: 3px; width: 100%; max-width: 260px; }
.lc-grid-1 { grid-template-columns: minmax(0, 1fr); }
.lc-grid-2, .lc-grid-3, .lc-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.lc-msg-gallery .lc-gallery-item { position: relative; overflow: hidden; padding: 0; border: 0; background: #0a0a12; cursor: zoom-in; }
.lc-msg-gallery .lc-gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; max-height: none; border-radius: 0; }
.lc-grid-1 .lc-gallery-item { display: block; }
.lc-grid-1 .lc-gallery-item img { height: auto; max-height: 160px; border-radius: 8px; }
.lc-grid-2 .lc-gallery-item, .lc-grid-3 .lc-gallery-item, .lc-grid-4 .lc-gallery-item { aspect-ratio: 1 / 1; }
.lc-gallery-more { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,.55); color: #fff; font-weight: 700; font-size: 20px; pointer-events: none; }
.lc-msg-media video {
    width: 100%; max-height: 200px; object-fit: cover; display: block;
    border-radius: 8px;
}
.lc-msg-media video:fullscreen, .lc-msg-media video:-webkit-full-screen { width: 100vw; height: 100vh; max-height: none; object-fit: contain; background: #000; }
.lc-video-wrap { position: relative; }
.lc-video-play { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2; width: 44px; height: 44px; border: none; border-radius: 50%; background: rgba(0,0,0,.55); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s ease, transform .2s ease; }
.lc-video-play svg { width: 20px; height: 20px; fill: #fff; margin-left: 2px; }
.lc-video-play:hover { background: rgba(124,58,237,.85); transform: translate(-50%,-50%) scale(1.08); }
.lc-video-play.is-hidden { display: none; }

/* Reply quote */
.lc-msg-quote {
    display: flex; flex-direction: row; gap: 6px;
    padding: 6px 8px; margin-bottom: 4px;
    background: rgba(0,0,0,.15);
    border-radius: 8px; cursor: pointer; overflow: hidden; max-width: 280px;
}
.lc-msg-quote:hover { background: rgba(0,0,0,.22); }
.lc-quote-bar { width: 3px; flex-shrink: 0; border-radius: 2px; background: linear-gradient(180deg, #3F6DF5, #8b5cf6); min-height: 100%; align-self: stretch; }
.lc-quote-content { min-width: 0; flex: 1; }
.lc-quote-sender { font-size: 11px; font-weight: 600; color: #5B9CF5; margin-bottom: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lc-quote-text { font-size: 11px; color: #8FA8C4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
.lc-quote-media { font-style: italic; opacity: .7; }

/* Context Menu */
.lc-context-menu {
    position: fixed; z-index: 10002; min-width: 150px;
    background: #102E4D; border: 1px solid #1A4168;
    border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.4);
    padding: 4px 0; animation: lcMenuIn .12s ease-out;
}
@keyframes lcMenuIn { from { opacity: 0; transform: scale(.92) translateY(-4px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.lc-menu-item {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 8px 14px; background: none; border: none;
    color: #F5F7FB; font-size: 12.5px; font-family: 'Poppins', sans-serif;
    cursor: pointer; text-align: left; transition: background .1s;
}
.lc-menu-item:hover { background: rgba(63,109,245,0.08); }
.lc-menu-danger { color: #f87171; }
.lc-menu-danger:hover { background: rgba(248,113,113,.1); }
.lc-menu-item:disabled { opacity: .45; cursor: not-allowed; }
.lc-reaction-picker {
    position: absolute; right: calc(100% + 6px); top: 36px;
    white-space: nowrap; padding: 7px 9px; border-radius: 10px;
    background: #102E4D; border: 1px solid #1A4168;
    box-shadow: 0 8px 24px rgba(0,0,0,.35);
    cursor: pointer; font-size: 18px; letter-spacing: 3px;
}

/* Reply Preview */
.lc-reply-preview {
    border-top: 1px solid #1A4168;
    background: #102E4D; padding: 8px 14px; flex-shrink: 0;
}
.lc-reply-preview-inner {
    display: flex; flex-direction: row; gap: 8px; align-items: stretch;
    background: rgba(63,109,245,0.06);
    border-radius: 8px; padding: 8px 10px; max-height: 72px;
}
.lc-reply-preview-bar { width: 3px; flex-shrink: 0; border-radius: 2px; background: linear-gradient(180deg, #3F6DF5, #8b5cf6); }
.lc-reply-preview-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.lc-reply-preview-sender { font-size: 11px; font-weight: 600; color: #5B9CF5; margin-bottom: 2px; }
.lc-reply-preview-text { font-size: 11.5px; color: #8FA8C4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.lc-reply-preview-close {
    background: none; border: none; color: #6F89A7; cursor: pointer;
    font-size: 14px; padding: 0 2px; align-self: flex-start; line-height: 1;
    transition: color .15s;
}
.lc-reply-preview-close:hover { color: #F5F7FB; }

/* Media Editor (WhatsApp-style pre-send) */
.lc-media-editor {
    position: absolute; inset: 0; z-index: 50;
    background: #071D35;
    display: flex; flex-direction: column;
}
.lc-media-main {
    flex: 1; position: relative; display: flex; align-items: center; justify-content: center;
    background: #0A1828; overflow: hidden; min-height: 0;
}
.lc-media-main img, .lc-media-main video {
    max-width: 100%; max-height: 100%; object-fit: contain; display: block;
}
.lc-media-close {
    position: absolute; top: 12px; left: 12px; z-index: 2;
    width: 34px; height: 34px; border-radius: 50%; border: none;
    background: rgba(0,0,0,.55); color: #fff; font-size: 16px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(6px); transition: background .2s;
}
.lc-media-close:hover { background: rgba(239,68,68,.7); }
.lc-media-nav {
    position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
    width: 36px; height: 36px; border-radius: 50%; border: none;
    background: rgba(0,0,0,.45); color: #fff; font-size: 14px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px); transition: background .2s;
}
.lc-media-nav:hover { background: rgba(0,0,0,.7); }
.lc-media-nav.prev { left: 10px; }
.lc-media-nav.next { right: 10px; }
.lc-media-counter {
    position: absolute; top: 14px; right: 14px; z-index: 2;
    background: rgba(0,0,0,.55); color: #fff; font-size: 11px; font-weight: 600;
    padding: 4px 10px; border-radius: 20px; backdrop-filter: blur(6px);
}
.lc-media-caption-bar {
    padding: 10px 14px; background: #102E4D; border-top: 1px solid #1A4168;
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.lc-media-caption-input {
    flex: 1; background: #102A47; border: 1px solid #1A4168; border-radius: 20px;
    padding: 8px 14px; color: #F5F7FB; font-size: 13px; font-family: 'Poppins', sans-serif;
    outline: none; resize: none; max-height: 60px;
}
.lc-media-caption-input:focus { border-color: #3F6DF5; }
.lc-media-caption-input::placeholder { color: #6F89A7; }
.lc-media-thumbs {
    padding: 10px 14px; background: #102E4D; border-top: 1px solid #1A4168;
    display: flex; gap: 8px; align-items: center; overflow-x: auto; flex-shrink: 0;
    scrollbar-width: thin; scrollbar-color: #214D78 transparent;
}
.lc-media-thumbs::-webkit-scrollbar { height: 4px; }
.lc-media-thumbs::-webkit-scrollbar-thumb { background: #214D78; border-radius: 4px; }
.lc-media-thumb {
    width: 56px; height: 56px; border-radius: 8px; flex-shrink: 0;
    overflow: hidden; position: relative; cursor: pointer;
    border: 2px solid transparent; transition: border-color .2s;
}
.lc-media-thumb.active { border-color: #3F6DF5; }
.lc-media-thumb img, .lc-media-thumb video {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.lc-media-thumb-remove {
    position: absolute; top: 2px; right: 2px;
    width: 18px; height: 18px; border-radius: 50%; border: none;
    background: rgba(0,0,0,.65); color: #fff; font-size: 10px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    line-height: 1; padding: 0; opacity: 0; transition: opacity .15s;
}
.lc-media-thumb:hover .lc-media-thumb-remove { opacity: 1; }
.lc-media-add {
    width: 56px; height: 56px; border-radius: 8px; flex-shrink: 0;
    border: 2px dashed #214D78; background: transparent;
    color: #6F89A7; font-size: 22px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
}
.lc-media-add:hover { border-color: #3F6DF5; color: #3F6DF5; }
.lc-media-footer {
    padding: 10px 14px; background: #102E4D; border-top: 1px solid #1A4168;
    display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-shrink: 0;
}
.lc-media-send-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 20px; border-radius: 20px; border: none;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff; font-size: 13px; font-weight: 600; font-family: 'Poppins', sans-serif;
    cursor: pointer; transition: all .2s; box-shadow: 0 4px 14px -4px rgba(63,109,245,0.4);
}
.lc-media-send-btn:hover { box-shadow: 0 6px 20px -4px rgba(63,109,245,0.5); transform: translateY(-1px); }
.lc-media-send-btn:disabled { opacity: .45; cursor: not-allowed; transform: none; }
.lc-media-send-count {
    width: 20px; height: 20px; border-radius: 50%;
    background: rgba(255,255,255,.25); font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}

/* Composer */
.lc-composer {
    padding: 10px 14px; border-top: 1px solid #1A4168;
    background: #102E4D; display: flex; align-items: flex-end; gap: 8px;
}
.lc-composer-input {
    flex: 1; resize: none; max-height: 80px; border-radius: 20px;
    padding: 8px 14px; font-size: 13px; font-family: 'Poppins', sans-serif;
    background: #102A47; border: 1px solid #1A4168;
    color: #F5F7FB; outline: none; transition: border-color .2s;
}
.lc-composer-input:focus { border-color: #3F6DF5; box-shadow: 0 0 0 3px rgba(63,109,245,0.12); }
.lc-composer-input::placeholder { color: #6F89A7; }
.lc-composer-btn {
    width: 36px; height: 36px; border-radius: 50%; border: none;
    background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: filter .15s;
}
.lc-composer-btn:hover { filter: brightness(1.15); }
.lc-composer-btn:disabled { opacity: .3; cursor: not-allowed; filter: none; }
.lc-composer-attach {
    width: 36px; height: 36px; border-radius: 50%;
    border: 1px solid #1A4168; background: transparent;
    color: #6F89A7; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: background .15s;
}
.lc-composer-attach:hover { background: rgba(63,109,245,0.08); }

/* Attachment Menu (WhatsApp-style, muncul ke atas) */
.lc-attach-wrap { position: relative; flex-shrink: 0; }
.lc-attach-menu {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 0;
    min-width: 210px;
    padding: 6px;
    background: #1b1f24;
    border: 1px solid rgba(245,247,251,0.08);
    border-radius: 12px;
    box-shadow: 0 12px 32px -8px rgba(0,0,0,0.55);
    z-index: 2100;
    opacity: 0;
    pointer-events: none;
    transform: translateY(6px);
    transition: opacity .18s ease, transform .18s ease;
}
.lc-attach-menu.open {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}
.lc-attach-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border: none;
    background: none;
    border-radius: 8px;
    color: rgba(245,247,251,0.92);
    font-size: 13px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    text-align: left;
    cursor: pointer;
    transition: background .15s ease;
}
.lc-attach-item:hover { background: rgba(255,255,255,0.08); }
.lc-attach-icon {
    width: 28px; height: 28px;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.lc-attach-doc .lc-attach-icon { background: rgba(139,92,246,0.16); color: #a78bfa; }
.lc-attach-photo .lc-attach-icon { background: rgba(59,130,246,0.16); color: #60a5fa; }
.lc-attach-camera .lc-attach-icon { background: rgba(236,72,153,0.16); color: #f472b6; }
.lc-attach-audio .lc-attach-icon { background: rgba(249,115,22,0.16); color: #fb923c; }

    /* Voice Note (record + player) */
    .lc-composer-mic {
        width: 36px; height: 36px; border-radius: 50%;
        border: 1px solid rgba(255,255,255,0.10);
        background: transparent; color: #6F89A7; cursor: pointer;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        transition: background .15s;
    }
    .lc-composer-mic:hover { background: rgba(63,109,245,0.08); color: #3F6DF5; }
    .lc-rec-bar {
        flex: 1; align-items: center; gap: 8px; height: 36px; padding: 0 8px;
        border-radius: 20px; background: #161a29; border: 1px solid rgba(255,255,255,0.10);
        color: #F5F7FB; font-size: 12px; font-family: 'Inter', sans-serif; min-width: 0;
    }
    .lc-rec-dot { width: 9px; height: 9px; border-radius: 50%; background: #ef4444; flex-shrink: 0; animation: lcRecPulse 1.2s ease-in-out infinite; }
    @keyframes lcRecPulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .4; transform: scale(.8); } }
    .lc-rec-time { font-variant-numeric: tabular-nums; font-weight: 600; min-width: 34px; flex-shrink: 0; }
    .lc-rec-hint { color: #6F89A7; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; }
    .lc-rec-spacer { flex: 1; }
    .lc-rec-btn {
        width: 28px; height: 28px; border-radius: 50%; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        transition: filter .15s;
    }
    .lc-rec-btn svg { width: 14px; height: 14px; stroke: #fff; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
    .lc-rec-cancel { background: rgba(239,68,68,0.85); }
    .lc-rec-send { background: #3F6DF5; }
    .lc-rec-btn:hover { filter: brightness(1.15); }
    .lc-voice {
        display: flex; align-items: center; gap: 10px;
        max-width: 240px; min-width: 170px; padding: 6px 12px;
        border-radius: 14px; background: rgba(0,0,0,0.22); cursor: pointer; user-select: none;
    }
    .lc-voice-btn {
        width: 30px; height: 30px; border-radius: 50%; background: #3F6DF5; color: #fff;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .lc-voice-btn svg { width: 14px; height: 14px; fill: #fff; }
    .lc-voice-btn:not(.is-playing) svg { transform: translateX(1px); }
    .lc-voice-btn.is-playing svg { transform: none; }
    .lc-voice-progress { flex: 1; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.16); overflow: hidden; min-width: 40px; }
    .lc-voice-fill { display: block; height: 100%; width: 0%; background: #8b5cf6; border-radius: 2px; transition: width .12s linear; }
    .lc-voice-time { font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums; color: rgba(245,247,251,0.55); flex-shrink: 0; }

/* Image Lightbox */
.lc-image-lightbox {
    position: fixed; inset: 0; z-index: 10050;
    display: flex; align-items: center; justify-content: center;
    padding: 24px; background: rgba(7,29,53,.92);
    cursor: zoom-out;
}
.lc-image-lightbox img {
    max-width: 100%; max-height: 100%; object-fit: contain;
    border-radius: 10px; box-shadow: 0 24px 80px rgba(0,0,0,.5);
    cursor: default;
}
.lc-image-lightbox-tools {
    position: fixed; top: 16px; right: 18px;
    display: flex; align-items: center; gap: 6px; z-index: 1;
}
.lc-image-lightbox-tools button,
.lc-image-lightbox-tools a {
    width: 40px; height: 40px; border: 0; border-radius: 50%;
    background: rgba(255,255,255,.14); color: #fff;
    display: grid; place-items: center; text-decoration: none; cursor: pointer;
}
.lc-image-lightbox-tools svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.lc-image-lightbox-tools [data-action="close"] { font-size: 30px; line-height: 1; }
.lc-image-lightbox-tools button:hover { background: rgba(255,255,255,.25); }
.lc-image-lightbox-tools .is-active { color: #facc15; }
.lc-image-reactions { position: absolute; top: 48px; right: 44px; display: flex; gap: 4px; padding: 6px; border-radius: 14px; background: rgba(16,46,77,.96); box-shadow: 0 10px 30px rgba(0,0,0,.3); }
.lc-image-reactions button { font-size: 18px; background: transparent; border: none; cursor: pointer; }
.lc-lightbox-stage { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
.lc-lightbox-stage img { max-width: 72%; max-height: 72%; }
.lc-lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 2; width: 42px; height: 42px; border: 0; border-radius: 50%; background: rgba(255,255,255,.14); color: #fff; font-size: 22px; line-height: 1; cursor: pointer; display: grid; place-items: center; }
.lc-lightbox-nav.prev { left: 10px; }
.lc-lightbox-nav.next { right: 10px; }
.lc-lightbox-nav:hover { background: rgba(255,255,255,.25); }
.lc-lightbox-counter { position: absolute; bottom: 92px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,.5); color: #fff; padding: 4px 14px; border-radius: 999px; font-size: 12px; z-index: 2; }
.lc-lightbox-thumbs { position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; padding: 6px; border-radius: 12px; background: rgba(0,0,0,.55); max-width: 88%; overflow-x: auto; z-index: 2; }
.lc-lightbox-thumbs .lc-thumb { flex: 0 0 auto; width: 46px; height: 46px; padding: 0; border: 2px solid transparent; border-radius: 8px; overflow: hidden; background: #000; cursor: pointer; }
.lc-lightbox-thumbs .lc-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.lc-lightbox-thumbs .lc-thumb.active { border-color: #a78bfa; }

/* Highlight animation */
@keyframes lcHighlight { 0% { background: rgba(63,109,245,.25); } 100% { background: transparent; } }
.lc-msg-highlight { animation: lcHighlight 2s ease-out; border-radius: 8px; }

/* Back button (mobile) */
.lc-back {
    display: none; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px;
    background: #1A3A5C; border: none; color: #F5F7FB;
    cursor: pointer; font-size: 14px; flex-shrink: 0;
}

/* Responsive */
@media (max-width: 900px) { .lc-sidebar { width: 460px; min-width: 460px; } }
@media (max-width: 768px) {
    .lc-container { flex-direction: column; height: 100%; }
    .lc-sidebar { width: 100%; min-width: 0; border-right: none; border-bottom: 1px solid #1A4168; overflow-y: auto; }
    .lc-sidebar.hidden-mobile { display: none !important; }
    .lc-chat.hidden-mobile { display: none !important; }
    .lc-chat { height: 100%; min-height: 0; overflow: hidden; }
    .lc-chat #lcRoom { height: 100%; min-height: 0; }
    .lc-messages { min-height: 0; }
    .lc-back { display: flex; }
    .lc-msg { max-width: 85%; }
    .lc-msg-anchor { opacity: 1; }
    .lc-media-main { min-height: 0; max-height: 50vh; }
    .lc-media-main img, .lc-media-main video { max-height: 50vh; }
    .lc-media-close { width: 28px; height: 28px; font-size: 13px; top: 8px; left: 8px; }
    .lc-media-nav { width: 30px; height: 30px; font-size: 12px; }
    .lc-media-counter { font-size: 10px; padding: 3px 8px; top: 10px; right: 10px; }
    .lc-media-caption-bar { padding: 8px 10px; }
    .lc-media-caption-input { padding: 6px 12px; font-size: 12px; }
    .lc-media-thumbs { padding: 8px 10px; gap: 6px; }
    .lc-media-thumb { width: 44px; height: 44px; border-radius: 6px; }
    .lc-media-add { width: 44px; height: 44px; border-radius: 6px; font-size: 18px; }
    .lc-media-footer { padding: 8px 10px; }
    .lc-media-send-btn { padding: 6px 14px; font-size: 12px; }
    .lc-media-send-count { width: 18px; height: 18px; font-size: 10px; }
}
@keyframes lcToastIn {
    from { opacity: 0; transform: translate(-50%, 10px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}
.lc-msg-loading {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.06);
    border-radius: 12px; padding: 10px 14px;
    min-width: 140px;
}
.lc-loading-thumb {
    font-size: 20px; width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.08); border-radius: 8px;
}
.lc-loading-spinner {
    display: flex; gap: 3px; align-items: center;
}
.lc-loading-spinner div {
    width: 5px; height: 5px; border-radius: 50%;
    background: rgba(255,255,255,0.5);
    animation: lcDotBounce 1.2s infinite ease-in-out;
}
.lc-loading-spinner div:nth-child(2) { animation-delay: 0.15s; }
.lc-loading-spinner div:nth-child(3) { animation-delay: 0.3s; }
@keyframes lcDotBounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}
.lc-msg-loading span {
    font-size: 11px; color: rgba(255,255,255,0.5);
    font-family: 'Poppins', sans-serif;
}
</style>
@endpush

@section('content')
<div class="lc-container">
    <!-- LEFT: SIDEBAR -->
    <div class="lc-sidebar" id="lcSidebar">
        <div class="lc-topbar" style="height:auto;padding:14px 16px;border-bottom:none;flex-shrink:0">
            <div class="lc-topbar-left">
                @if($adminPhoto)
                    <img src="{{ $adminPhoto }}" alt="{{ $channelName }}" class="lc-topbar-logo">
                @else
                    <img src="{{ asset('logo.png') }}" alt="Johen" class="lc-topbar-logo">
                @endif
                <div class="lc-topbar-title">Live Chat {{ $channelName }}</div>
            </div>
            <div class="lc-topbar-right">
                <div class="lc-topbar-profile" id="lcProfile" onclick="this.classList.toggle('open')">
                    @if($adminPhoto)
                        <img src="{{ $adminPhoto }}" alt="{{ $adminUser->name }}" class="lc-topbar-avatar">
                    @else
                        <div class="lc-topbar-avatar">{{ substr($adminUser->name, 0, 1) }}</div>
                    @endif
                    <span class="lc-topbar-name">{{ $adminUser->name }}</span>
                    <i class="fas fa-chevron-down lc-topbar-chevron"></i>
                    <div class="lc-topbar-dropdown">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="lc-main-panel" id="lcMainPanel">
        <div class="lc-sidebar-header">
            <div class="lc-search">
                <i class="fas fa-search lc-search-icon"></i>
                <input type="text" placeholder="Cari user..." id="lcSearch" oninput="filterList()">
            </div>
            <div class="lc-filters">
                <button class="lc-filter-btn active" data-filter="all" onclick="setFilter('all', this)">Semua</button>
                <button class="lc-filter-btn" data-filter="unread" onclick="setFilter('unread', this)">Belum Dibaca</button>
                <button class="lc-filter-btn" data-filter="favorited" onclick="setFilter('favorited', this)">Favorit</button>
            </div>
        </div>
        <div class="lc-archived-row" onclick="openArchived()">
            <div class="lc-archived-row-icon"><i class="fas fa-archive"></i></div>
            <span class="lc-archived-row-name">Diarsipkan</span>
            <span class="lc-archived-badge" id="lcArchivedBadge" {{ $archivedUnreadCount > 0 ? '' : 'style="display:none"' }}>{{ $archivedUnreadCount }}</span>
        </div>
        <div class="lc-list" id="lcList">
            @forelse($conversations as $conv)
            @include('admin.lcadmin.conversations._list-item', ['conv' => $conv])
            @empty
            <div style="padding:3rem 1.5rem;text-align:center">
                <i class="fas fa-comments" style="font-size:2rem;color:#214D78;margin-bottom:10px;display:block"></i>
                <p style="font-size:13px;color:#6F89A7">Belum ada percakapan</p>
            </div>
            @endforelse
            @if($conversations->hasPages())
            <div style="padding:12px;text-align:center">{{ $conversations->withQueryString()->links('vendor.pagination.admin') }}</div>
            @endif
        </div>
        </div>

        <div class="lc-archived-panel" id="lcArchivedPanel">
            <div class="lc-archived-header">
                <button class="lc-archived-back" onclick="closeArchived()" aria-label="Kembali"><i class="fas fa-arrow-left"></i></button>
                <span class="lc-archived-title">Diarsipkan</span>
            </div>
            <div class="lc-archived-items" id="lcArchivedList"></div>
            <div class="lc-archived-empty" id="lcArchivedEmpty">
                <div class="lc-archived-empty-icon"><i class="fas fa-archive"></i></div>
                <div class="lc-archived-empty-title">Belum ada chat yang diarsipkan</div>
                <div class="lc-archived-empty-desc">Chat yang kamu arsipkan akan muncul di sini.</div>
            </div>
        </div>
    </div>

    <!-- RIGHT: CHAT -->
    <div class="lc-chat" id="lcChat">
        <div class="lc-empty" id="lcEmpty">
            <div class="lc-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    <path d="M8 10h.01M12 10h.01M16 10h.01" stroke-width="2"/>
                </svg>
            </div>
            <div class="lc-empty-title">Belum ada chat dipilih</div>
            <div class="lc-empty-desc">Pilih percakapan dari panel kiri untuk mulai membalas.</div>
        </div>
            <div id="lcRoom" style="display:none;flex-direction:column;height:100%;position:relative">
            <div class="lc-header">
                <div class="lc-header-left">
                    <button class="lc-back" onclick="showSidebar()"><i class="fas fa-arrow-left"></i></button>
                    <div class="lc-header-avatar" id="rAvatar"></div>
                    <div class="lc-header-info">
                        <span class="lc-header-name" id="rName"></span>
                        <span class="lc-header-meta" id="rMeta"></span>
                    </div>
                </div>
            </div>
            <div class="lc-messages" id="lcMessages"></div>
            <div class="lc-reply-preview" id="lcReplyPreview" style="display:none"></div>
            <div class="lc-media-editor" id="lcMediaEditor" style="display:none">
                <div class="lc-media-main" id="lcMediaMain">
                    <button class="lc-media-close" onclick="cancelMediaEditor()">\u2715</button>
                    <button class="lc-media-nav prev" id="lcMediaPrev" onclick="navMedia(-1)" style="display:none">\u2039</button>
                    <button class="lc-media-nav next" id="lcMediaNext" onclick="navMedia(1)" style="display:none">\u203A</button>
                    <div class="lc-media-counter" id="lcMediaCounter" style="display:none"></div>
                </div>
                <div class="lc-media-caption-bar">
                    <textarea class="lc-media-caption-input" id="lcMediaCaption" rows="1" placeholder="Tambahkan caption..." oninput="updateActiveCaption(this.value);autoResize(this)"></textarea>
                </div>
                <div class="lc-media-thumbs" id="lcMediaThumbs"></div>
                <div class="lc-media-footer">
                    <button class="lc-media-send-btn" id="lcMediaSendBtn" onclick="sendMediaList()" disabled>
                        <span class="lc-media-send-count" id="lcMediaSendCount">0</span>
                        Kirim
                    </button>
                </div>
            </div>
            <div class="lc-composer" id="lcComposer">
                <div class="lc-rec-bar" id="lcRecBar" style="display:none">
                    <span class="lc-rec-dot"></span>
                    <span class="lc-rec-time" id="lcRecTime">0:00</span>
                    <span class="lc-rec-hint">Merekam...</span>
                    <div class="lc-rec-spacer"></div>
                    <button type="button" class="lc-rec-btn lc-rec-cancel" onclick="cancelVoiceRec()" title="Batal" aria-label="Batal rekaman"><svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
                    <button type="button" class="lc-rec-btn lc-rec-send" onclick="finishVoiceRec()" title="Kirim" aria-label="Kirim pesan suara"><svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
                </div>
                <input type="file" id="lcFileInput" accept="image/*,video/*" multiple style="display:none" onchange="handleFileSelect(event)">
                <input type="file" id="lcDocInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z" multiple style="display:none" onchange="handleFileSelect(event)">
                <input type="file" id="lcPhotoInput" accept="image/*" capture="environment" style="display:none" onchange="handleFileSelect(event)">
                <input type="file" id="lcAudioInput" accept="audio/*" style="display:none" onchange="handleFileSelect(event)">
                <div class="lc-attach-wrap" id="lcAttachWrap">
                    <button class="lc-composer-attach" id="lcAttachBtn" onclick="toggleAttachMenu(event)" title="Lampiran">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                    </button>
                    <div class="lc-attach-menu" id="lcAttachMenu" role="menu">
                        <button class="lc-attach-item lc-attach-doc" onclick="attachAction('doc')">
                            <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                            <span class="lc-attach-label">Dokumen</span>
                        </button>
                        <button class="lc-attach-item lc-attach-photo" onclick="attachAction('media')">
                            <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
                            <span class="lc-attach-label">Foto &amp; Video</span>
                        </button>
                        <button class="lc-attach-item lc-attach-camera" onclick="attachAction('camera')">
                            <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
                            <span class="lc-attach-label">Kamera</span>
                        </button>
                        <button class="lc-attach-item lc-attach-audio" onclick="attachAction('audio')">
                            <span class="lc-attach-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></span>
                            <span class="lc-attach-label">Audio</span>
                        </button>
                    </div>
                </div>
                <textarea id="lcInput" class="lc-composer-input" rows="1" placeholder="Ketik pesan..." onkeydown="handleKeydown(event)" oninput="autoResize(this);toggleSend()"></textarea>
                <button class="lc-composer-mic" id="lcMicBtn" onclick="startVoiceRec()" title="Rekam pesan suara" aria-label="Rekam pesan suara">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v1a7 7 0 0 0 14 0v-1M12 18v4"/></svg>
                </button>
                <button class="lc-composer-btn" id="lcSendBtn" onclick="sendText()" disabled>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
const ADMIN_ID = {{ auth('admin')->id() }};

function showToast(message, type) {
    const existing = document.querySelector('.lc-toast');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'lc-toast';
    toast.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:10px;font-size:13px;font-weight:500;color:#fff;z-index:100000;animation:lcToastIn .3s ease;font-family:Poppins,sans-serif;max-width:350px;text-align:center;box-shadow:0 8px 24px -4px rgba(0,0,0,0.3);';
    toast.style.background = type === 'error' ? '#ef4444' : '#10b981';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3000);
}

function addLoadingMsg(tempId, file) {
    const container = document.getElementById('lcMessages');
    if (!container) return;
    const empty = container.querySelector('div[style*="text-align:center"]');
    if (empty) empty.remove();
    const isVideo = file && file.type.startsWith('video/');
    const isAudio = file && file.type.startsWith('audio/');
    const icon = isVideo ? '🎬' : isAudio ? '🎤' : '🖼️';
    container.insertAdjacentHTML('beforeend', `<div class="lc-msg lc-msg-admin" data-msg-id="temp-${tempId}" data-temp="1"><div class="lc-msg-body"><div class="lc-msg-media lc-msg-loading"><div class="lc-loading-thumb">${icon}</div><div class="lc-loading-spinner"><div></div><div></div><div></div></div><span>Mengirim...</span></div></div></div>`);
    container.scrollTop = container.scrollHeight;
}

function addLoadingThumbMsg(tempId, file, count) {
    const container = document.getElementById('lcMessages');
    if (!container) return;
    const empty = container.querySelector('div[style*="text-align:center"]');
    if (empty) empty.remove();
    const url = URL.createObjectURL(file);
    const tiles = [];
    for (let i = 0; i < Math.min(count, 4); i++) {
        tiles.push(`<span class="lc-gallery-item" style="display:inline-block;aspect-ratio:1/1;overflow:hidden;background:#0a0a12"><img src="${url}" alt="" style="width:100%;height:100%;object-fit:cover"></span>`);
    }
    const counterEl = count > 4 ? `<span class="lc-gallery-more">+${count - 4}</span>` : '';
    container.insertAdjacentHTML('beforeend', `<div class="lc-msg lc-msg-admin" data-msg-id="temp-${tempId}" data-temp="1"><div class="lc-msg-body"><div class="lc-msg-media lc-msg-gallery lc-grid-${Math.min(count, 4)}"><span style="position:relative;display:grid;grid-template-columns:repeat(${Math.min(count, 4) === 1 ? 1 : 2}, minmax(0,1fr));gap:3px">${tiles.slice(0, 4).join('')}${counterEl}</span></div></div></div>`);
    URL.revokeObjectURL(url);
    container.scrollTop = container.scrollHeight;
}

function removeLoadingMsg(tempId) {
    const el = document.querySelector(`[data-msg-id="temp-${tempId}"]`);
    if (el) el.remove();
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

let activeConvId = null;
let pollTimer = null;
let replyToMsg = null;
let mediaList = [];
let activeIndex = 0;
let activeMenuMsgId = null;
let rec = { recorder: null, chunks: [], stream: null, timer: null, mime: '', startTs: 0, seconds: 0 };

function openConversation(id) {
    activeConvId = id;
    history.replaceState(null, '', '?open=' + id);
    document.querySelectorAll('.lc-item').forEach(el => el.classList.remove('active'));
    const item = document.getElementById('conv-' + id);
    if (item) item.classList.add('active');
    document.getElementById('lcEmpty').style.display = 'none';
    document.getElementById('lcRoom').style.display = 'flex';
    loadMessages(id);
    if (window.innerWidth <= 768) {
        document.getElementById('lcSidebar').classList.add('hidden-mobile');
        document.getElementById('lcChat').classList.remove('hidden-mobile');
    }
}
function showSidebar() {
    cancelVoiceRec();
    activeConvId = null;
    history.replaceState(null, '', window.location.pathname);
    stopPoll();
    document.querySelectorAll('.lc-item').forEach(el => el.classList.remove('active'));
    document.getElementById('lcRoom').style.display = 'none';
    document.getElementById('lcEmpty').style.display = 'flex';
    if (window.innerWidth <= 768) {
        document.getElementById('lcSidebar').classList.remove('hidden-mobile');
        document.getElementById('lcChat').classList.add('hidden-mobile');
    }
}

function openArchived() {
    closeAllMenus();
    stopPoll();
    document.getElementById('lcMainPanel').style.display = 'none';
    document.getElementById('lcArchivedPanel').style.display = 'flex';
    loadArchived();
}
function closeArchived() {
    document.getElementById('lcArchivedPanel').style.display = 'none';
    document.getElementById('lcMainPanel').style.display = 'flex';
    if (activeConvId !== null) startPoll();
}
async function loadArchived() {
    const list = document.getElementById('lcArchivedList');
    const empty = document.getElementById('lcArchivedEmpty');
    empty.style.display = 'none';
    list.innerHTML = '<div style="padding:3rem 1.5rem;text-align:center;color:#6F89A7"><i class="fas fa-spinner fa-spin" style="font-size:18px"></i></div>';
    try {
        const res = await fetch('/lcadmin/conversations/archived', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const data = await res.json();
        list.innerHTML = data.html || '';
        if (data.count === 0) {
            list.innerHTML = '';
            empty.style.display = 'flex';
        }
    } catch (e) {
        list.innerHTML = '';
        empty.style.display = 'flex';
    }
    updateArchivedBadge();
}

async function loadMessages(id) {
    stopPoll();
    const container = document.getElementById('lcMessages');
    container.innerHTML = '<div style="text-align:center;padding:3rem;color:#6F89A7"><i class="fas fa-spinner fa-spin" style="font-size:20px"></i></div>';
    try {
        const res = await fetch(`/lcadmin/conversations/${id}/messages`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const data = await res.json();
        const conv = data.conversation;
        const msgs = data.messages;
        const dispName = conv.user?.name || conv.guest_name || conv.guest_email || 'User';
        document.getElementById('rAvatar').textContent = dispName.charAt(0);
        document.getElementById('rName').textContent = dispName;
        const channelName = conv.channel?.name || '';
        const onlineHtml = conv.status === 'open' ? '<span class="online-dot"></span> Online' : '<span style="color:#6F89A7">Offline</span>';
        document.getElementById('rMeta').innerHTML = channelName + ' &bull; ' + onlineHtml;
        document.getElementById('lcComposer').style.display = '';
        container.innerHTML = '';
        if (msgs.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:3rem;color:#6F89A7"><p style="font-size:13px">Belum ada pesan</p></div>';
        } else {
            msgs.forEach(msg => appendMsg(msg));
            const doScroll = () => { container.scrollTop = container.scrollHeight; };
            requestAnimationFrame(() => { requestAnimationFrame(doScroll); });
            setTimeout(doScroll, 150);
        }
        startPoll();
        updateArchivedBadge();
    } catch (e) {
        container.innerHTML = '<div style="text-align:center;padding:3rem;color:#ef4444"><p style="font-size:13px">Gagal memuat pesan</p></div>';
    }
}

function startPoll() { stopPoll(); pollTimer = setInterval(pollNew, 3000); }
function stopPoll() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

async function pollNew() {
    if (!activeConvId) return;
    const container = document.getElementById('lcMessages');
    const last = container.querySelector('[data-msg-id]:last-child');
    const lastId = last ? last.dataset.msgId : 0;
    try {
        const res = await fetch(`/lcadmin/conversations/${activeConvId}/poll?after=${lastId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) return;
        const msgs = await res.json();
        if (msgs.length > 0) {
            msgs.forEach(m => appendMsg(m));
            const doScroll = () => { container.scrollTop = container.scrollHeight; };
            requestAnimationFrame(() => { requestAnimationFrame(doScroll); });
            setTimeout(doScroll, 150);
        }
    } catch (e) {}
}

function attachmentsOfMsg(msg) {
    if (msg.attachments && msg.attachments.length) return msg.attachments;
    if (msg.media_path) return [{ media_path: msg.media_path, poster_path: msg.poster_path || null, media_mime: msg.media_mime || null }];
    return [];
}

function galleryHtml(msg) {
    const atts = attachmentsOfMsg(msg);
    if (!atts.length) return '';
    const gridCls = atts.length === 1 ? 'lc-msg-media lc-msg-gallery lc-grid-1'
        : atts.length === 2 ? 'lc-msg-media lc-msg-gallery lc-grid-2'
        : atts.length === 3 ? 'lc-msg-media lc-msg-gallery lc-grid-3'
        : 'lc-msg-media lc-msg-gallery lc-grid-4';
    const urlsJson = JSON.stringify(atts.map(a => `/media/${a.media_path}`)).replace(/"/g, '&quot;');
    const visible = atts.slice(0, 4);
    const hiddenCount = atts.length - 4;
    const tiles = visible.map((a, i) => {
        const url = `/media/${a.media_path}`;
        const more = (i === 3 && hiddenCount > 0) ? `<span class="lc-gallery-more">+${hiddenCount}</span>` : '';
        return `<button type="button" class="lc-gallery-item" data-urls="${urlsJson}" onclick="openGallery(event, this, ${msg.id}, ${i})" aria-label="Buka gambar ukuran penuh"><img src="${url}" alt="Gambar" loading="lazy">${more}</button>`;
    }).join('');
    return `<div class="${gridCls}">${tiles}</div>`;
}

function appendMsg(msg) {
    const container = document.getElementById('lcMessages');
    const empty = container.querySelector('div[style*="text-align:center"]');
    if (empty) empty.remove();
    if (msg.sender_type === 'system') {
        const div = document.createElement('div');
        div.className = 'lc-msg lc-msg-system';
        div.dataset.msgId = msg.id;
        div.innerHTML = `<div class="lc-bubble lc-bubble-system">${esc(msg.message)}</div>`;
        container.appendChild(div);
        return;
    }
    const isUser = msg.sender_type === 'user';
    const cls = isUser ? 'lc-msg-user' : 'lc-msg-admin';
    const time = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    let quoteHtml = '';
    if (msg.reply_to) {
        const rSender = msg.reply_to.sender?.name || (msg.reply_to.sender_type === 'user' ? 'User' : 'Admin');
        let rContent = '';
        if (msg.reply_to.message_type === 'image' && msg.reply_to.media_path) {
            rContent = '<span class="lc-quote-media">Foto</span>';
        } else if (msg.reply_to.message_type === 'video' && msg.reply_to.media_path) {
            rContent = '<span class="lc-quote-media">Video</span>';
        } else if (msg.reply_to.message_type === 'audio') {
            rContent = '<span class="lc-quote-media">🎤 Pesan suara</span>';
        } else {
            rContent = esc((msg.reply_to.message || 'Pesan telah dihapus').substring(0, 80));
        }
        quoteHtml = `<div class="lc-msg-quote" onclick="event.stopPropagation();scrollToMsg(${msg.reply_to.id})"><div class="lc-quote-bar"></div><div class="lc-quote-content"><div class="lc-quote-sender">${esc(rSender)}</div><div class="lc-quote-text">${rContent}</div></div></div>`;
    }

    let mediaHtml = '';
    if (msg.message_type === 'image' && (msg.media_path || (msg.attachments && msg.attachments.length))) {
        mediaHtml = galleryHtml(msg);
    } else if (msg.message_type === 'video' && msg.media_path) {
        const posterAttr = msg.poster_path ? ` poster="/media/${msg.poster_path}"` : '';
        mediaHtml = `<div class="lc-msg-media lc-video-wrap"><button type="button" class="lc-video-play" onclick="playVideoMessage(this)" aria-label="Putar video"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></button><video src="/media/${msg.media_path}" controls preload="none"${posterAttr}></video></div>`;
    } else if (msg.message_type === 'audio' && msg.media_path) {
        const dur = msg.media_duration ? fmtVoiceTime(msg.media_duration) : '';
        mediaHtml = `<div class="lc-msg-media"><div class="lc-voice" data-dur="${msg.media_duration || ''}" onclick="toggleVoicePlay(this)"><span class="lc-voice-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span><span class="lc-voice-progress"><span class="lc-voice-fill"></span></span><span class="lc-voice-time">${dur}</span><audio src="/media/${msg.media_path}" preload="metadata"></audio></div></div>`;
    }

    const bubbleContent = msg.message ? `<div class="lc-bubble">${esc(msg.message)}</div>` : '';

    const div = document.createElement('div');
    div.className = `lc-msg ${cls}`;
    div.dataset.msgId = msg.id;
    div.dataset.senderType = msg.sender_type;
    div.dataset.senderId = msg.sender_id == null ? '' : msg.sender_id;
    div.innerHTML = `
        <div class="lc-msg-body">
            ${quoteHtml}
            ${mediaHtml}
            ${bubbleContent}
            <div class="lc-msg-time">${time}</div>
        </div>
        <div class="lc-msg-anchor" onclick="showMenu(event, ${msg.id})">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>`;
    container.appendChild(div);
}

function esc(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

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

function scrollToMsg(id) {
    const el = document.querySelector(`[data-msg-id="${id}"]`);
    if (el) {
        const container = document.getElementById('lcMessages');
        const isVisible = el.offsetTop >= container.scrollTop && el.offsetTop + el.offsetHeight <= container.scrollTop + container.offsetHeight;
        if (!isVisible) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('lc-msg-highlight');
        setTimeout(() => el.classList.remove('lc-msg-highlight'), 2000);
    }
}

/* ---- CONTEXT MENU ---- */
function closeAllMenus() {
    activeMenuMsgId = null;
    document.querySelectorAll('.lc-context-menu').forEach(el => el.remove());
    document.querySelectorAll('.lc-msg-has-menu').forEach(el => el.classList.remove('lc-msg-has-menu'));
    document.querySelectorAll('.lc-item-dropdown.open').forEach(el => el.classList.remove('open'));
}
function toggleItemDropdown(btn) {
    const dd = btn.nextElementSibling;
    const wasOpen = dd.classList.contains('open');
    document.querySelectorAll('.lc-item-dropdown.open').forEach(el => el.classList.remove('open'));
    if (!wasOpen) dd.classList.add('open');
}
async function deleteChat(id) {
    if (!confirm('Hapus percakapan ini?')) return;
    try {
        const res = await fetch(`/lcadmin/conversations/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const item = document.getElementById('conv-' + id);
        if (item) item.remove();
        if (activeConvId === id) {
            activeConvId = null;
            history.replaceState(null, '', window.location.pathname);
            document.getElementById('lcRoom').style.display = 'none';
            document.getElementById('lcEmpty').style.display = 'flex';
        }
    } catch (e) {
        showToast('Gagal menghapus percakapan', 'error');
    }
}
async function archiveChat(id) {
    try {
        const res = await fetch(`/lcadmin/conversations/${id}/archive`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const item = document.getElementById('conv-' + id);
        if (item) item.remove();
        if (activeConvId === id) {
            activeConvId = null;
            history.replaceState(null, '', window.location.pathname);
            document.getElementById('lcRoom').style.display = 'none';
            document.getElementById('lcEmpty').style.display = 'flex';
        }
        showToast('Chat diarsipkan', 'success');
        updateArchivedBadge();
    } catch (e) {
        showToast('Gagal mengarsipkan chat', 'error');
    }
}
async function unarchiveChat(id) {
    try {
        const res = await fetch(`/lcadmin/conversations/${id}/restore`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const item = document.getElementById('conv-' + id);
        if (item) item.remove();
        const list = document.getElementById('lcArchivedList');
        if (list && !list.querySelector('.lc-item')) {
            const empty = document.getElementById('lcArchivedEmpty');
            if (empty) empty.style.display = 'flex';
        }
        showToast('Chat dipindahkan ke chat utama', 'success');
        updateArchivedBadge();
    } catch (e) {
        showToast('Gagal membatalkan arsip', 'error');
    }
}
function updateArchivedBadge() {
    const badge = document.getElementById('lcArchivedBadge');
    if (!badge) return;
    fetch('/lcadmin/conversations/archived-unread', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(res => res.ok ? res.json() : Promise.reject())
        .then(data => {
            const n = data.count || 0;
            badge.textContent = n;
            badge.style.display = n > 0 ? '' : 'none';
        })
        .catch(() => {});
}
setInterval(updateArchivedBadge, 5000);
function showMenu(e, msgId) {
    e.stopPropagation();
    const existing = document.querySelector(`.lc-context-menu[data-msg-id="${msgId}"]`);
    closeAllMenus();
    if (existing) return;
    activeMenuMsgId = msgId;
    const menu = document.createElement('div');
    menu.className = 'lc-context-menu';
    menu.dataset.msgId = msgId;
    menu.innerHTML = `
        <button class="lc-menu-item" onclick="replyTo(${msgId})">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 00-4-4H4"/></svg>
            Balas
        </button>
        <button class="lc-menu-item lc-menu-danger" onclick="showDeleteOptions(${msgId})">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            Hapus
        </button>`;
    document.body.appendChild(menu);
    const rect = e.currentTarget.getBoundingClientRect();
    let top = rect.top;
    let left = Math.max(8, Math.min(rect.right - 172, window.innerWidth - 180));
    if (top + 200 > window.innerHeight) top = Math.max(8, rect.bottom - 200);
    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
    e.currentTarget.closest('.lc-msg')?.classList.add('lc-msg-has-menu');
}

function showDeleteOptions(msgId) {
    closeAllMenus();
    const anchorEl = document.querySelector(`[data-msg-id="${msgId}"] .lc-msg-anchor`);
    if (!anchorEl) return;
    const el = document.querySelector(`[data-msg-id="${msgId}"]`);
    const isOwn = el && el.dataset.senderType === 'admin' && String(el.dataset.senderId) === String(ADMIN_ID);
    const menu = document.createElement('div');
    menu.className = 'lc-context-menu';
    menu.dataset.msgId = msgId;
    menu.innerHTML = `
        <button class="lc-menu-item lc-menu-danger" onclick="hideMsg(${msgId})">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            Hapus untuk saya
        </button>
        ${isOwn ? `<button class="lc-menu-item lc-menu-danger" onclick="deleteMsg(${msgId})">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            Hapus untuk semua orang
        </button>` : ''}
        <button class="lc-menu-item" onclick="closeAllMenus()">Batal</button>`;
    document.body.appendChild(menu);
    const rect = anchorEl.getBoundingClientRect();
    let top = rect.top;
    let left = Math.max(8, Math.min(rect.right - 172, window.innerWidth - 180));
    if (top + 200 > window.innerHeight) top = Math.max(8, rect.bottom - 200);
    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
    anchorEl.closest('.lc-msg')?.classList.add('lc-msg-has-menu');
}

/* ---- REPLY ---- */
function replyTo(id) {
    closeAllMenus();
    const el = document.querySelector(`[data-msg-id="${id}"]`);
    if (!el) return;
    const bubble = el.querySelector('.lc-bubble');
    const media = el.querySelector('.lc-msg-media img');
    const voice = el.querySelector('.lc-voice');
    const isUser = el.classList.contains('lc-msg-user');
    const senderName = isUser ? 'User' : 'Admin';
    let preview = '';
    if (voice) preview = '🎤 Pesan suara';
    else if (media) preview = 'Foto';
    else if (bubble) preview = esc(bubble.textContent.substring(0, 80));
    else preview = 'Pesan';
    replyToMsg = { id, senderName, preview };
    const rp = document.getElementById('lcReplyPreview');
    rp.innerHTML = `<div class="lc-reply-preview-inner"><div class="lc-reply-preview-bar"></div><div class="lc-reply-preview-content"><div class="lc-reply-preview-sender">\u21A9 ${esc(senderName)}</div><div class="lc-reply-preview-text">${preview}</div></div><button class="lc-reply-preview-close" onclick="cancelReply()">\u2715</button></div>`;
    rp.style.display = 'block';
    const input = document.getElementById('lcInput');
    if (input) { input.placeholder = `Balas ${senderName}...`; input.focus(); }
}
function cancelReply() {
    replyToMsg = null;
    const rp = document.getElementById('lcReplyPreview');
    rp.innerHTML = ''; rp.style.display = 'none';
    const input = document.getElementById('lcInput');
    if (input) input.placeholder = 'Ketik pesan...';
}

/* ---- DELETE ---- */
async function deleteMsg(id) {
    closeAllMenus();
    if (!confirm('Hapus pesan ini untuk semua orang?')) return;
    try {
        const res = await fetch(`/lcadmin/messages/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Gagal menghapus pesan');
        const el = document.querySelector(`[data-msg-id="${id}"]`);
        if (el) el.remove();
    } catch (e) { showToast('Gagal menghapus pesan', 'error'); }
}

async function hideMsg(id) {
    closeAllMenus();
    if (!confirm('Hapus pesan ini hanya dari chat Anda?')) return;
    try {
        const res = await fetch(`/lcadmin/messages/${id}/hide`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error('Gagal menyembunyikan pesan');
        const el = document.querySelector(`[data-msg-id="${id}"]`);
        if (el) el.remove();
    } catch (e) { showToast('Gagal menyembunyikan pesan', 'error'); }
}

/* ---- IMAGE LIGHTBOX ---- */
function openImage(event, imageUrl, messageId) {
    openLightbox([imageUrl], 0, messageId);
    event?.stopPropagation();
}
function openGallery(event, btn, messageId, startIndex) {
    let urls = [];
    try { urls = JSON.parse(btn.dataset.urls || '[]'); } catch (e) {}
    event?.stopPropagation();
    if (!urls.length) return;
    openLightbox(urls, startIndex || 0, messageId);
}
function openLightbox(urls, startIndex, messageId) {
    const existing = document.getElementById('lc-image-lightbox');
    if (existing) existing.remove();

    let index = Math.max(0, Math.min(startIndex || 0, urls.length - 1));
    const hasMany = urls.length > 1;
    const thumbsHtml = urls.map((u, i) =>
        `<button type="button" class="lc-thumb${i === index ? ' active' : ''}" data-i="${i}" aria-label="Gambar ${i + 1}"><img src="${u}" alt="" loading="lazy"></button>`
    ).join('');
    const lightbox = document.createElement('div');
    lightbox.id = 'lc-image-lightbox';
    lightbox.className = 'lc-image-lightbox';
    lightbox.innerHTML = `
        <div class="lc-image-lightbox-tools">
            <button data-action="reply" title="Balas" onclick="closeLightbox();replyTo(${messageId})"><svg viewBox="0 0 24 24"><path d="M10 9 5 14l5 5"/><path d="M5 14h9a5 5 0 0 1 5 5"/></svg></button>
            <a id="lc-lightbox-download" href="${urls[index]}" download title="Unduh"><svg viewBox="0 0 24 24"><path d="M12 3v11"/><path d="m8 10 4 4 4-4M5 20h14"/></svg></a>
            <button data-action="close" title="Tutup" onclick="closeLightbox()">\u00D7</button>
        </div>
        <div class="lc-lightbox-stage">
            ${hasMany ? '<button type="button" class="lc-lightbox-nav prev" data-nav="-1" aria-label="Sebelumnya">\u2039</button>' : ''}
            <img id="lc-lightbox-img" src="${urls[index]}" alt="Gambar ukuran penuh">
            ${hasMany ? '<button type="button" class="lc-lightbox-nav next" data-nav="1" aria-label="Berikutnya">\u203A</button>' : ''}
            ${hasMany ? '<div class="lc-lightbox-counter">' + (index + 1) + ' / ' + urls.length + '</div>' : ''}
            ${hasMany ? `<div class="lc-lightbox-thumbs">${thumbsHtml}</div>` : ''}
        </div>`;

    const img = lightbox.querySelector('#lc-lightbox-img');
    const download = lightbox.querySelector('#lc-lightbox-download');
    const thumbs = lightbox.querySelector('.lc-lightbox-thumbs');
    let counter = lightbox.querySelector('.lc-lightbox-counter');
    let onKeydown;
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
    lightbox.querySelector('.lc-lightbox-nav.prev')?.addEventListener('click', () => setIndex(index - 1));
    lightbox.querySelector('.lc-lightbox-nav.next')?.addEventListener('click', () => setIndex(index + 1));
    thumbs?.addEventListener('click', (e) => {
        const t = e.target.closest('.lc-thumb');
        if (t) setIndex(parseInt(t.dataset.i, 10));
    });
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox || e.target.classList.contains('lc-lightbox-stage')) closeLightbox(); });
    onKeydown = (e) => {
        if (e.key === 'Escape') { closeLightbox(); document.removeEventListener('keydown', onKeydown); }
        if (hasMany && e.key === 'ArrowLeft') setIndex(index - 1);
        if (hasMany && e.key === 'ArrowRight') setIndex(index + 1);
    };
    document.addEventListener('keydown', onKeydown);
    document.body.appendChild(lightbox);
}
function closeLightbox() {
    const lb = document.getElementById('lc-image-lightbox');
    if (lb) lb.remove();
}

/* ---- MEDIA EDITOR (WhatsApp-style) ---- */
function handleFileSelect(e) {
    const newFiles = Array.from(e.target.files);
    if (!newFiles.length) return;
    e.target.value = '';
    let supportedCount = 0;
    newFiles.forEach(f => {
        const isImg = f.type.startsWith('image/');
        const isVideo = f.type.startsWith('video/');
        const isAudio = f.type.startsWith('audio/');
        if (isAudio) {
            supportedCount++;
            if (activeConvId) {
                sendVoiceFile(f, 0);
            } else {
                showToast('Buka percakapan terlebih dahulu untuk mengirim pesan suara.', 'error');
            }
            return;
        }
        if (!isImg && !isVideo) {
            showToast('Format file tidak didukung. Hanya gambar, video & audio yang bisa dikirim.', 'error');
            return;
        }
        supportedCount++;
        const url = URL.createObjectURL(f);
        const type = isImg ? 'image' : 'video';
        mediaList.push({ file: f, url, type, caption: '' });
    });
    if (supportedCount === 0) return;
    const mediaEditor = document.getElementById('lcMediaEditor');
    if (mediaList.length > 0 && !(mediaEditor && mediaEditor.style.display.includes('flex'))) {
        activeIndex = 0;
    }
    openMediaEditor();
}
function openMediaEditor() {
    if (mediaList.length === 0) { cancelMediaEditor(); return; }
    document.getElementById('lcMediaEditor').style.display = 'flex';
    renderMediaEditor();
}
function renderMediaEditor() {
    if (mediaList.length === 0) { cancelMediaEditor(); return; }
    const item = mediaList[activeIndex];
    const main = document.getElementById('lcMediaMain');
    const existing = main.querySelector('img, video');
    if (existing) existing.remove();
    let el;
    if (item.type === 'image') {
        el = document.createElement('img');
        el.src = item.url;
    } else {
        el = document.createElement('video');
        el.src = item.url;
        el.controls = true;
    }
    main.appendChild(el);
    document.getElementById('lcMediaPrev').style.display = activeIndex > 0 ? '' : 'none';
    document.getElementById('lcMediaNext').style.display = activeIndex < mediaList.length - 1 ? '' : 'none';
    const counter = document.getElementById('lcMediaCounter');
    if (mediaList.length > 1) { counter.textContent = `${activeIndex + 1} / ${mediaList.length}`; counter.style.display = ''; }
    else { counter.style.display = 'none'; }
    const captionInput = document.getElementById('lcMediaCaption');
    captionInput.value = item.caption;
    renderThumbs();
    updateSendBtn();
}
function renderThumbs() {
    const bar = document.getElementById('lcMediaThumbs');
    bar.innerHTML = mediaList.map((m, i) => {
        const activeCls = i === activeIndex ? ' active' : '';
        const thumb = m.type === 'image' ? `<img src="${m.url}" alt="">` : `<video src="${m.url}" muted preload="metadata"></video>`;
        return `<div class="lc-media-thumb${activeCls}" onclick="setActiveIndex(${i})">${thumb}<button class="lc-media-thumb-remove" onclick="event.stopPropagation();removeMediaItem(${i})">\u2715</button></div>`;
    }).join('') + `<button class="lc-media-add" onclick="document.getElementById('lcFileInput').click()">+</button>`;
}
function setActiveIndex(i) {
    activeIndex = i;
    renderMediaEditor();
}
function navMedia(dir) {
    const next = activeIndex + dir;
    if (next >= 0 && next < mediaList.length) { activeIndex = next; renderMediaEditor(); }
}
function updateActiveCaption(val) {
    if (mediaList[activeIndex]) mediaList[activeIndex].caption = val;
}
function removeMediaItem(i) {
    URL.revokeObjectURL(mediaList[i].url);
    mediaList.splice(i, 1);
    if (mediaList.length === 0) { cancelMediaEditor(); return; }
    if (activeIndex >= mediaList.length) activeIndex = mediaList.length - 1;
    renderMediaEditor();
}
function cancelMediaEditor() {
    mediaList.forEach(m => URL.revokeObjectURL(m.url));
    mediaList = [];
    activeIndex = 0;
    const editor = document.getElementById('lcMediaEditor');
    editor.style.display = 'none';
    const main = document.getElementById('lcMediaMain');
    const old = main.querySelector('img, video');
    if (old) old.remove();
    document.getElementById('lcMediaCaption').value = '';
    document.getElementById('lcMediaThumbs').innerHTML = '';
    toggleSend();
}
function toggleSend() { updateSendBtn(); }
function updateSendBtn() {
    const input = document.getElementById('lcInput');
    const hasText = input?.value?.trim();
    const hasMedia = mediaList.length > 0;
    document.getElementById('lcSendBtn').disabled = !hasText && !hasMedia;
    const mediaSendBtn = document.getElementById('lcMediaSendBtn');
    if (mediaSendBtn) {
        mediaSendBtn.disabled = mediaList.length === 0;
        document.getElementById('lcMediaSendCount').textContent = mediaList.length;
    }
}

/* ---- ATTACHMENT MENU ---- */
function toggleAttachMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('lcAttachMenu');
    if (!menu) return;
    menu.classList.toggle('open');
}
function attachAction(action) {
    closeAttachMenu();
    if (action === 'doc') document.getElementById('lcDocInput').click();
    else if (action === 'media') document.getElementById('lcFileInput').click();
    else if (action === 'camera') document.getElementById('lcPhotoInput').click();
else if (action === 'audio') startVoiceRec();
    }
function closeAttachMenu() {
    const menu = document.getElementById('lcAttachMenu');
    if (menu) menu.classList.remove('open');
}

/* ---- VOICE NOTE ---- */
function fmtVoiceTime(sec) {
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
    if (rec.stream) {
        try { rec.stream.getTracks().forEach(t => t.stop()); } catch (e) {}
        rec.stream = null;
    }
}
function resetRecorder() {
    clearInterval(rec.timer);
    rec.timer = null;
    rec.recorder = null;
    rec.chunks = [];
    rec.mime = '';
    rec.startTs = 0;
    rec.seconds = 0;
    stopVoiceTracks();
}
function setRecBar(visible) {
    const bar = document.getElementById('lcRecBar');
    if (bar) bar.style.display = visible ? 'flex' : 'none';
    const mic = document.getElementById('lcMicBtn');
    if (mic) mic.style.display = visible ? 'none' : '';
    const input = document.getElementById('lcInput');
    if (input) input.style.display = visible ? 'none' : '';
    const send = document.getElementById('lcSendBtn');
    if (send) send.style.display = visible ? 'none' : '';
    const attach = document.querySelector('#lcComposer .lc-attach-wrap');
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
    if (!activeConvId) {
        showToast('Buka percakapan terlebih dahulu.', 'error');
        return;
    }
    if (rec.recorder) {
        try { rec.recorder.resume(); } catch (e) {}
        return;
    }
    closeAttachMenu();
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        rec.stream = stream;
        rec.mime = voiceRecMime();
        let recorder;
        try {
            recorder = rec.mime ? new MediaRecorder(stream, { mimeType: rec.mime }) : new MediaRecorder(stream);
        } catch (e) {
            recorder = new MediaRecorder(stream);
        }
        rec.mime = recorder.mimeType || rec.mime || 'audio/webm';
        rec.chunks = [];
        recorder.ondataavailable = (e) => { if (e.data && e.data.size) rec.chunks.push(e.data); };
        recorder.start();
        rec.recorder = recorder;
        rec.startTs = Date.now();
        rec.seconds = 0;
        setRecBar(true);
        const timeEl = document.getElementById('lcRecTime');
        if (timeEl) {
            timeEl.textContent = '0:00';
            rec.timer = setInterval(() => {
                rec.seconds = Math.floor((Date.now() - rec.startTs) / 1000);
                timeEl.textContent = fmtVoiceTime(rec.seconds);
                if (rec.seconds >= 1800) finishVoiceRec();
            }, 500);
        }
    } catch (err) {
        console.error('LCAdmin: mic error', err);
        showToast(voiceErrMsg(err), 'error');
    }
}
function cancelVoiceRec() {
    const r = rec.recorder;
    clearInterval(rec.timer);
    rec.timer = null;
    if (r && r.state !== 'inactive') {
        r.onstop = () => resetRecorder();
        try { r.stop(); } catch (e) { resetRecorder(); }
    } else {
        resetRecorder();
    }
    setRecBar(false);
}
function finishVoiceRec() {
    const r = rec.recorder;
    if (!r || r.state === 'inactive') return;
    setRecBar(false);
    const duration = Math.max(1, rec.seconds || Math.floor((Date.now() - rec.startTs) / 1000));
    const mime = rec.mime || 'audio/webm';
    r.onstop = () => {
        const blob = new Blob(rec.chunks, { type: mime });
        if (blob.size === 0) {
            resetRecorder();
            showToast('Rekaman kosong.', 'error');
            return;
        }
        resetRecorder();
        const file = new File([blob], 'voice-note-' + Date.now() + '.' + voiceExt(mime), { type: mime });
        sendVoiceFile(file, duration);
    };
    try { r.stop(); } catch (e) { resetRecorder(); setRecBar(false); }
    setTimeout(() => setRecBar(false), 4000);
}
async function sendVoiceFile(file, duration) {
    if (!activeConvId) return;
    const tempId = Date.now();
    const replyId = replyToMsg?.id || null;
    addLoadingMsg(tempId, file);
    cancelReply();
    try {
        const fd = new FormData();
        fd.append('message_type', 'audio');
        fd.append('media[]', file);
        fd.append('media_duration', String(Math.round(duration)));
        if (replyId) fd.append('reply_to_message_id', replyId);
        const res = await fetch(`/lcadmin/conversations/${activeConvId}/reply`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        removeLoadingMsg(tempId);
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            showToast(err.message || 'Gagal mengirim pesan suara', 'error');
            return;
        }
        appendMsg(await res.json());
        const c = document.getElementById('lcMessages');
        const doScroll = () => { c.scrollTop = c.scrollHeight; };
        requestAnimationFrame(() => { requestAnimationFrame(doScroll); });
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
            if (time) time.textContent = d ? fmtVoiceTime(d) : '0:00';
            window.__lcActiveVoice = null;
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

/* ---- SEND ---- */
function handleKeydown(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendText(); } }
function autoResize(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 80) + 'px'; }
function sendMediaList() { sendText(); }
async function sendText() {
    const input = document.getElementById('lcInput');
    const text = input?.value?.trim();
    const hasMedia = mediaList.length > 0;
    const hasText = !!text;
    if (!hasMedia && !hasText) return;
    if (!activeConvId) return;

    const items = [...mediaList];
    const replyId = replyToMsg?.id || null;
    input.value = ''; input.style.height = 'auto';
    document.getElementById('lcSendBtn').disabled = true;
    cancelMediaEditor();
    cancelReply();

    if (hasMedia) {
        const imageItems = items.filter(it => it.type === 'image');
        const videoItems = items.filter(it => it.type !== 'image');

        if (imageItems.length > 0) {
            const tempId = Date.now();
            addLoadingThumbMsg(tempId, imageItems[0].file, imageItems.length);
            const caption = imageItems.find(it => it.caption)?.caption || '';
            try {
                const fd = new FormData();
                fd.append('message_type', 'image');
                if (caption) fd.append('message', caption);
                if (replyId) fd.append('reply_to_message_id', replyId);
                imageItems.forEach(it => fd.append('media[]', it.file));
                const res = await fetch(`/lcadmin/conversations/${activeConvId}/reply`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                removeLoadingMsg(tempId);
                if (!res.ok) { showToast('Gagal mengirim gambar', 'error'); }
                else appendMsg(await res.json());
            } catch (e) { removeLoadingMsg(tempId); showToast('Gagal mengirim gambar', 'error'); }
        }

        for (let i = 0; i < videoItems.length; i++) {
            const item = videoItems[i];
            const tempId = Date.now() + i;
            addLoadingMsg(tempId, item.file);
            try {
                const fd = new FormData();
                fd.append('message_type', 'video');
                fd.append('media[]', item.file);
                if (item.caption) fd.append('message', item.caption);
                if (i === 0 && replyId) fd.append('reply_to_message_id', replyId);
                const thumbBlob = await extractVideoThumb(item.file);
                if (thumbBlob) {
                    fd.append('thumbnail[]', thumbBlob, 'thumb.jpg');
                    fd.append('thumbnail_indexes[]', '0');
                }
                const res = await fetch(`/lcadmin/conversations/${activeConvId}/reply`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                removeLoadingMsg(tempId);
                if (!res.ok) { showToast('Gagal mengirim file', 'error'); continue; }
                const msg = await res.json();
                appendMsg(msg);
            } catch (e) { removeLoadingMsg(tempId); showToast('Gagal mengirim file', 'error'); }
        }
        const c = document.getElementById('lcMessages');
        const doScroll = () => { c.scrollTop = c.scrollHeight; };
        requestAnimationFrame(() => { requestAnimationFrame(doScroll); });
        return;
    }

    const tempId = Date.now();
    addLoadingMsg(tempId, null);
    try {
        const fd = new FormData();
        fd.append('message_type', 'text');
        fd.append('message', text);
        if (replyId) fd.append('reply_to_message_id', replyId);
        const res = await fetch(`/lcadmin/conversations/${activeConvId}/reply`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        removeLoadingMsg(tempId);
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || 'Gagal mengirim pesan');
        }
        const msg = await res.json();
        appendMsg(msg);
        const c = document.getElementById('lcMessages');
        const doScroll = () => { c.scrollTop = c.scrollHeight; };
        requestAnimationFrame(() => { requestAnimationFrame(doScroll); });
    } catch (e) { removeLoadingMsg(tempId); input.value = text; showToast(e.message || 'Gagal mengirim pesan', 'error'); }
}

/* ---- SEARCH ---- */
let activeFilter = 'all';
function setFilter(filter, btn) {
    activeFilter = filter;
    document.querySelectorAll('.lc-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterList();
}
function filterList() {
    const q = document.getElementById('lcSearch').value.toLowerCase();
    document.querySelectorAll('#lcList .lc-item').forEach(el => {
        const name = el.dataset.name || '';
        const channel = el.dataset.channel || '';
        const matchSearch = name.includes(q) || channel.includes(q);
        let matchFilter = true;
        if (activeFilter === 'unread') matchFilter = el.dataset.unread === '1';
        else if (activeFilter === 'favorited') matchFilter = el.dataset.favorited === '1';
        el.style.display = (matchSearch && matchFilter) ? '' : 'none';
    });
}
async function toggleFav(id, btn) {
    try {
        const res = await fetch(`/lcadmin/conversations/${id}/favorite`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const data = await res.json();
        const item = document.getElementById('conv-' + id);
        if (item) item.dataset.favorited = data.is_favorited ? '1' : '0';
        btn.classList.toggle('active', data.is_favorited);
        if (activeFilter !== 'all') filterList();
    } catch (e) {}
}

async function togglePin(id, btn) {
    try {
        const res = await fetch(`/lcadmin/conversations/${id}/pin`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error();
        const data = await res.json();
        const item = document.getElementById('conv-' + id);
        if (item) {
            item.dataset.pinned = data.is_pinned ? '1' : '0';
            item.classList.toggle('pinned', data.is_pinned);
        }
        const label = btn?.querySelector('span');
        if (label) label.textContent = data.is_pinned ? 'Lepas Semat' : 'Sematkan Chat';
        reorderPinnedItems();
        showToast(data.is_pinned ? 'Chat disematkan' : 'Semat dilepas', 'success');
    } catch (e) {
        showToast('Gagal menyematkan chat', 'error');
    }
}

function reorderPinnedItems() {
    const list = document.getElementById('lcList');
    if (!list) return;
    const items = Array.from(list.querySelectorAll('.lc-item'));
    if (!items.length) return;
    const byLast = (a, b) => (parseInt(b.dataset.last, 10) || 0) - (parseInt(a.dataset.last, 10) || 0);
    const pinned = items.filter(el => el.dataset.pinned === '1').sort(byLast);
    const rest = items.filter(el => el.dataset.pinned !== '1').sort(byLast);
    const anchor = Array.from(list.children).find(ch => !ch.classList.contains('lc-item')) || null;
    [...pinned, ...rest].forEach(el => list.insertBefore(el, anchor));
}

/* ---- CLOSE MENUS ON CLICK OUTSIDE ---- */
document.addEventListener('click', function(e) {
    if (!e.target.isConnected) return;
    if (!e.target.closest('.lc-context-menu') && !e.target.closest('.lc-msg-anchor')) {
        closeAllMenus();
    }
    if (!e.target.closest('#lcAttachWrap')) {
        closeAttachMenu();
    }
});

/* ---- ESCAPE KEY TO EXIT CHAT ---- */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const menu = document.getElementById('lcAttachMenu');
        if (menu && menu.classList.contains('open')) {
            e.stopPropagation();
            closeAttachMenu();
            return;
        }
    }
    if (e.key === 'Escape' && activeConvId !== null) {
        const mediaEditor = document.getElementById('lcMediaEditor');
        if (mediaEditor && mediaEditor.style.display === 'flex') return;
        showSidebar();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    updateArchivedBadge();
    const params = new URLSearchParams(window.location.search);
    const openId = params.get('open');
    if (openId) openConversation(parseInt(openId));
});
</script>
@endpush
