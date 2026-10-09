@extends('admin.csadmin.layout')
@section('title', 'Conversations')

@php
    $adminUser = Auth::guard('admin')->user();
@endphp

@push('styles')
<style>
.lc-container {
    display: flex;
    height: 100%;
    overflow: hidden;
    background: #0B2340;
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

.lc-list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #214D78 transparent;
}
.lc-list::-webkit-scrollbar { width: 5px; }
.lc-list::-webkit-scrollbar-track { background: transparent; }
.lc-list::-webkit-scrollbar-thumb { background: #214D78; border-radius: 10px; }

.lc-list-empty {
    padding: 3rem 1.5rem;
    text-align: center;
    color: #6F89A7;
    font-size: 13px;
}

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
.lc-item.has-unread { background: rgba(63,109,245,0.06); }
.lc-item.pinned { background: rgba(63,109,245,0.05); }
.lc-item.pinned .lc-item-name { font-weight: 700; }
.lc-item-pin { display: none; font-size: 10px; color: #5B9CF5; margin-left: auto; flex-shrink: 0; }
.lc-item.pinned .lc-item-pin { display: inline-block; }
.lc-online-dot { width: 7px; height: 7px; border-radius: 50%; background: #36C98F; flex-shrink: 0; }
.lc-online-dot.off { background: #6F89A7; }
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
.lc-item-pin { font-size: 10px; color: #5B9CF5; flex-shrink: 0; }
.lc-item-name {
    font-weight: 600; font-size: 13px; color: #F5F7FB;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.lc-item.is-closed .lc-item-name { color: #8FA8C4; }
.lc-item.is-ended .lc-item-name { color: #8FA8C4; }
.lc-item-ended {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .4px; color: #E2B93B;
    background: rgba(226,185,59,.12);
    border: 1px solid rgba(226,185,59,.35);
    border-radius: 3px; padding: 1px 5px; flex-shrink: 0;
}
.lc-item-channel { font-weight: 600; font-size: 11px; color: #5B9CF5; margin-bottom: 2px; }
.lc-item-preview {
    font-size: 12px; color: #8FA8C4;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4;
}
.lc-item.is-closed .lc-item-preview { color: #6F89A7; }
.lc-item.is-ended .lc-item-preview { color: #6F89A7; }
.lc-item-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; }
.lc-item-time { font-size: 10px; color: #6F89A7; }
.lc-item-unread {
    min-width: 18px; height: 18px; border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff; font-size: 9px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; padding: 0 5px;
}

/* LIST ITEM DROPDOWN */
.lc-item-actions { position: relative; flex-shrink: 0; }
.lc-item-arrow {
    display: flex;
    background: none; border: none; color: #6F89A7; font-size: 11px;
    padding: 4px 6px; cursor: pointer; border-radius: 6px; transition: all .15s;
    opacity: .45;
}
.lc-item:hover .lc-item-arrow { opacity: 1; }
.lc-item-arrow:hover { background: rgba(255,255,255,0.08); color: #F5F7FB; opacity: 1; }
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

/* ARCHIVED */
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
.lc-archived-badge[hidden] { display: none !important; }
.lc-archived-panel {
    display: none;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    background: #102E4D;
}
.lc-archived-panel.open { display: flex; }
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

/* MAIN */
.lc-main-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: #0B2340;
}
.lc-empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #6F89A7;
    font-size: 14px;
    text-align: center;
    padding: 20px;
}
.lc-empty-chat i { font-size: 44px; opacity: .5; }

.lc-chat-panel {
    display: none;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}
.lc-chat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #1A4168;
    background: #102E4D;
    flex-shrink: 0;
}
.lc-mobile-back {
    display: none;
    background: none; border: none; color: #8FA8C4; font-size: 16px;
    cursor: pointer; padding: 4px 6px; border-radius: 6px;
}
.lc-mobile-back:hover { background: rgba(255,255,255,0.06); color: #F5F7FB; }
.lc-chat-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, #14b8a6, #3F6DF5);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px; color: #fff;
    flex-shrink: 0;
}
.lc-chat-head-info { flex: 1; min-width: 0; }
.lc-chat-head-name {
    font-weight: 700; font-size: 15px; color: #F5F7FB;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.lc-chat-head-sub { font-size: 12px; color: #6F89A7; }
.lc-chat-head-actions { display: flex; gap: 8px; flex-shrink: 0; }
.lc-head-btn {
    padding: 7px 12px;
    border-radius: 8px;
    border: 1px solid #1A4168;
    background: transparent;
    color: #8FA8C4;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all .15s;
}
.lc-head-btn:hover { border-color: #3F6DF5; color: #F5F7FB; }
.lc-head-btn.warn { border-color: rgba(245,158,11,.5); color: #f59e0b; }
.lc-head-btn.warn:hover { background: rgba(245,158,11,.08); }

.lc-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px 18px;
    scrollbar-width: thin;
    scrollbar-color: #214D78 transparent;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.lc-messages::-webkit-scrollbar { width: 5px; }
.lc-messages::-webkit-scrollbar-thumb { background: #214D78; border-radius: 10px; }
.lc-no-msg { text-align: center; color: #6F89A7; font-size: 13px; margin: auto; }

.lc-msg { display: flex; align-items: flex-end; gap: 8px; max-width: 76%; }
.lc-msg-user { align-self: flex-start; }
.lc-msg-admin { align-self: flex-end; flex-direction: row-reverse; }
.lc-msg-system { align-self: center; max-width: 90%; }
.lc-msg-body { min-width: 0; }
.lc-bubble {
    background: #102E4D;
    border: 1px solid #1A4168;
    color: #F5F7FB;
    padding: 9px 13px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
    white-space: pre-wrap;
}
.lc-msg-user .lc-bubble {
    background: #102A47;
    border-top-left-radius: 4px;
}
.lc-msg-admin .lc-bubble {
    background: linear-gradient(135deg, #3F6DF5, #8b5cf6);
    border: none;
    border-top-right-radius: 4px;
    color: #fff;
}
.lc-bubble-system {
    background: rgba(63,109,245,0.12);
    border-color: rgba(63,109,245,0.35);
    font-size: 12px;
    padding: 6px 13px;
}
.lc-msg-time { font-size: 10px; color: #6F89A7; margin-top: 4px; padding: 0 4px; }
.lc-msg-admin .lc-msg-time { text-align: right; }
.lc-msg-sender { font-size: 10px; font-weight: 700; color: #5B9CF5; margin-bottom: 3px; padding: 0 4px; }

/* QUOTE */
.lc-msg-quote {
    display: flex; gap: 8px; align-items: stretch;
    background: rgba(26,65,104,0.5);
    border-radius: 10px; padding: 6px 10px;
    margin-bottom: 6px; cursor: pointer;
    max-width: 100%; overflow: hidden;
}
.lc-quote-bar { width: 3px; border-radius: 3px; background: #3F6DF5; flex-shrink: 0; }
.lc-quote-content { min-width: 0; }
.lc-quote-sender { font-size: 11px; font-weight: 700; color: #5B9CF5; }
.lc-quote-text { font-size: 12px; color: #8FA8C4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lc-quote-media { font-size: 12px; color: #8FA8C4; }
.lc-msg-admin .lc-quote-bar { background: #fff; }

/* MEDIA */
.lc-msg-gallery {
    display: grid;
    gap: 4px;
    max-width: 320px;
    margin-bottom: 6px;
}
.lc-grid-1 { grid-template-columns: 1fr; }
.lc-grid-2 { grid-template-columns: 1fr 1fr; }
.lc-grid-3 { grid-template-columns: repeat(3, 1fr); }
.lc-grid-4 { grid-template-columns: repeat(2, 1fr); }
.lc-gallery-item {
    position: relative;
    padding: 0; border: none; background: none; cursor: pointer;
    border-radius: 10px; overflow: hidden;
    width: 100%; aspect-ratio: 1 / 1;
}
.lc-gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.lc-gallery-more {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(7,29,53,0.65);
    color: #fff; font-weight: 700; font-size: 18px;
}
.lc-msg-media { margin-bottom: 6px; }
.lc-video-wrap {
    position: relative; width: 320px; max-width: 100%;
    border-radius: 12px; overflow: hidden; background: #000;
}
.lc-video-wrap video { width: 100%; height: auto; max-height: 320px; display: block; }
.lc-video-play {
    position: absolute; inset: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.3); border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: opacity .2s; z-index: 2;
}
.lc-video-play svg { width: 46px; height: 46px; fill: #fff; filter: drop-shadow(0 2px 6px rgba(0,0,0,.4)); }
.lc-video-play.is-hidden { opacity: 0; pointer-events: none; }

.lc-voice {
    display: flex; align-items: center; gap: 10px;
    background: #102A47; border: 1px solid #1A4168;
    border-radius: 24px; padding: 7px 12px; cursor: pointer;
    width: 240px; max-width: 100%;
}
.lc-voice-btn {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.lc-voice-btn svg { width: 14px; height: 14px; fill: #fff; margin-left: 2px; }
.lc-voice-progress { flex: 1; height: 4px; border-radius: 2px; background: #214D78; overflow: hidden; }
.lc-voice-fill { display: block; height: 100%; width: 0; background: linear-gradient(135deg, #3F6DF5, #8b5cf6); }
.lc-voice-time { font-size: 11px; color: #8FA8C4; flex-shrink: 0; min-width: 34px; text-align: right; }

/* LIGHTBOX */
.lc-lightbox {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,0.92);
    display: flex; align-items: center; justify-content: center;
    cursor: zoom-out; padding: 24px;
}
.lc-lightbox img { max-width: 92vw; max-height: 92vh; border-radius: 8px; box-shadow: 0 12px 40px rgba(0,0,0,.6); }

/* CONTEXT MENU */
.lc-context-menu {
    position: fixed; z-index: 1200;
    background: #102E4D; border: 1px solid #1A4168;
    border-radius: 12px; padding: 5px; min-width: 180px;
    box-shadow: 0 12px 40px -8px rgba(0,0,0,0.5);
}
.lc-menu-item {
    display: flex; align-items: center; gap: 8px; width: 100%;
    padding: 9px 12px; border: none; background: none;
    color: #F5F7FB; font-size: 12px; font-family: 'Poppins', sans-serif;
    border-radius: 8px; cursor: pointer; transition: background .12s;
}
.lc-menu-item svg { width: 14px; height: 14px; flex-shrink: 0; }
.lc-menu-item:hover { background: rgba(63,109,245,0.14); color: #3F6DF5; }
.lc-menu-danger { color: #ef4444 !important; }
.lc-menu-danger:hover { background: rgba(239,68,68,0.12) !important; color: #ef4444 !important; }

/* REPLY + MEDIA PREVIEW BARS */
.lc-composer-bar {
    display: flex; align-items: center; gap: 10px;
    background: #102E4D;
    border-top: 1px solid #1A4168;
    padding: 8px 14px;
    flex-shrink: 0;
}
.lc-bar-inner { flex: 1; min-width: 0; display: flex; gap: 8px; align-items: center; }
.lc-bar-border { width: 3px; align-self: stretch; border-radius: 3px; background: #3F6DF5; flex-shrink: 0; }
.lc-bar-sender { font-size: 11px; font-weight: 700; color: #5B9CF5; }
.lc-bar-text { font-size: 12px; color: #8FA8C4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lc-bar-close {
    background: none; border: none; color: #6F89A7;
    font-size: 14px; cursor: pointer; flex-shrink: 0; padding: 4px;
}
.lc-bar-close:hover { color: #F5F7FB; }
.lc-preview-thumbs { display: flex; gap: 8px; overflow-x: auto; }
.lc-preview-thumb { position: relative; flex-shrink: 0; }
.lc-preview-thumb img, .lc-preview-thumb video {
    width: 64px; height: 64px; border-radius: 8px; object-fit: cover; display: block;
    border: 1px solid #1A4168; background: #000;
}
.lc-preview-remove {
    position: absolute; top: -6px; right: -6px;
    width: 20px; height: 20px; border-radius: 50%;
    background: #ef4444; border: 1px solid #fff; color: #fff;
    font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center;
}

/* COMPOSER */
.lc-composer {
    display: flex; align-items: flex-end; gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid #1A4168;
    background: #102E4D;
    flex-shrink: 0;
}
.lc-attach-btn, .lc-composer-btn, .lc-voice-btn-big {
    width: 38px; height: 38px; border-radius: 10px;
    border: 1px solid #1A4168; background: #102A47;
    color: #8FA8C4; font-size: 15px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s; flex-shrink: 0;
}
.lc-attach-btn:hover, .lc-composer-btn:hover, .lc-voice-btn-big:hover {
    border-color: #3F6DF5; color: #F5F7FB;
}
.lc-voice-btn-big.recording {
    background: linear-gradient(135deg, #ef4444, #f97316);
    border-color: transparent; color: #fff;
    animation: lc-pulse 1.2s infinite;
}
@keyframes lc-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
    50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
}
.lc-rec-note { display: none; align-items: center; gap: 6px; color: #f97316; font-size: 12px; font-weight: 600; }
.lc-rec-note .lc-rec-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: lc-blink 1s infinite; }
@keyframes lc-blink { 0%,100% { opacity: 1; } 50% { opacity: .3; } }
.lc-composer-input-wrap { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
.lc-input {
    width: 100%;
    min-height: 38px; max-height: 110px;
    padding: 9px 12px;
    border-radius: 10px;
    border: 1px solid #1A4168;
    background: #102A47;
    color: #F5F7FB;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    resize: none;
    outline: none;
    transition: border-color .2s;
    box-sizing: border-box;
}
.lc-input:focus { border-color: #3F6DF5; }
.lc-input::placeholder { color: #6F89A7; }
.lc-input:disabled { opacity: .5; }
.lc-send-btn {
    width: 38px; height: 38px; border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: #fff; font-size: 15px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: filter .15s; flex-shrink: 0;
}
.lc-send-btn:hover { filter: brightness(1.1); }
.lc-send-btn:disabled { opacity: .5; cursor: default; }

/* TOAST */
.lc-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    z-index: 1300; padding: 11px 18px; border-radius: 10px;
    font-size: 13px; font-family: 'Poppins', sans-serif; font-weight: 500;
    color: #fff; box-shadow: 0 10px 30px -6px rgba(0,0,0,.5);
    opacity: 0; pointer-events: none; transition: opacity .25s, transform .25s;
}
.lc-toast.show { opacity: 1; transform: translateX(-50%) translateY(-4px); }
.lc-toast.success { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.lc-toast.error { background: linear-gradient(135deg, #ef4444, #b91c1c); }

@media (max-width: 900px) {
    .lc-sidebar { width: 100%; max-width: 100%; }
    .lc-main-panel.mobile-open .lc-sidebar-hidden { display: none; }
    .lc-sidebar.hidden-mobile { display: none; }
    .lc-mobile-back { display: flex; }
    .lc-chat-panel { position: absolute; inset: 0; background: #0B2340; z-index: 50; }
    .lc-main-panel { position: relative; }
}
</style>
@endpush

@section('content')
<div class="lc-container">
    <aside class="lc-sidebar" id="lcSidebar">
        <div id="lcMainPanel" style="display:flex;flex-direction:column;flex:1;min-height:0">
            <div class="lc-sidebar-header">
                <div class="lc-search">
                    <i class="fas fa-search lc-search-icon"></i>
                    <input type="text" id="lcSearch" placeholder="Cari user...">
                </div>
                <div class="lc-filters">
                    <button type="button" class="lc-filter-btn active" id="lcFilterAll">Semua</button>
                    <button type="button" class="lc-filter-btn" id="lcFilterUnread">Belum dibaca</button>
                    <button type="button" class="lc-filter-btn" id="lcFilterOpen">Terbuka</button>
                </div>
            </div>
            <div class="lc-archived-row" onclick="openArchived()">
                <div class="lc-archived-row-icon"><i class="fas fa-archive"></i></div>
                <span class="lc-archived-row-name">Diarsipkan</span>
                <span class="lc-archived-badge" id="lcArchivedBadge" {{ $archivedCount > 0 ? '' : 'hidden' }}>{{ $archivedCount }}</span>
            </div>
            <div class="lc-list" id="lcList">
                @forelse ($conversations as $conv)
                    @include('admin.csadmin.conversations._list-item', ['conv' => $conv])
                @empty
                    <div class="lc-list-empty">Belum ada percakapan dari tamu.</div>
                @endforelse
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
    </aside>

    <main class="lc-main-panel" id="lcMain">
        <div class="lc-empty-chat" id="lcEmpty">
            <i class="fas fa-comments"></i>
            <div>Pilih percakapan di sebelah kiri untuk mulai membalas tamu.</div>
        </div>

        <div class="lc-chat-panel" id="lcRoom">
            <div class="lc-chat-header">
                <button class="lc-mobile-back" onclick="showSidebar()" title="Kembali"><i class="fas fa-arrow-left"></i></button>
                <div class="lc-chat-avatar" id="lcHeadAvatar">G</div>
                <div class="lc-chat-head-info">
                    <div class="lc-chat-head-name" id="lcHeadName">Loading...</div>
                    <div class="lc-chat-head-sub" id="lcHeadSub">Memuat percakapan...</div>
                </div>
                <div class="lc-chat-head-actions">
                    <button class="lc-head-btn" id="lcReopenBtn" style="display:none" onclick="toggleStatus('open')">Buka</button>
                    <button class="lc-head-btn warn" id="lcCloseBtn" onclick="toggleStatus('closed')">Tutup</button>
                </div>
            </div>

            <div class="lc-messages" id="lcMessages"></div>

            <div class="lc-composer-bar" id="lcReplyBar" style="display:none">
                <div class="lc-bar-border"></div>
                <div class="lc-bar-inner">
                    <div style="min-width:0;flex:1">
                        <div class="lc-bar-sender" id="lcReplySender"></div>
                        <div class="lc-bar-text" id="lcReplyText"></div>
                    </div>
                    <button class="lc-bar-close" onclick="cancelReply()">✕</button>
                </div>
            </div>

            <div class="lc-composer-bar" id="lcMediaBar" style="display:none">
                <div style="min-width:0;flex:1">
                    <div class="lc-bar-sender" style="margin-bottom:4px">Media siap dikirim</div>
                    <div class="lc-preview-thumbs" id="lcMediaThumbs"></div>
                </div>
                <button class="lc-bar-close" onclick="clearMedia()" title="Hapus semua">✕</button>
            </div>

            <div class="lc-composer">
                <button class="lc-attach-btn" onclick="document.getElementById('lcFileInput').click()" title="Kirim gambar/video">
                    <i class="fas fa-paperclip"></i>
                </button>
                <input type="file" id="lcFileInput" accept="image/*,video/*" multiple style="display:none">
                <div class="lc-composer-input-wrap">
                    <div class="lc-rec-note" id="lcRecNote"><span class="lc-rec-dot"></span> Sedang merekam...</div>
                    <textarea class="lc-input" id="lcInput" rows="1" placeholder="Ketik pesan..."></textarea>
                </div>
                <button class="lc-voice-btn-big" id="lcRecordBtn" title="Rekam pesan suara" onclick="toggleRecording()">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="lc-send-btn" id="lcSendBtn" title="Kirim" onclick="sendText()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF = @json(csrf_token());
    const ADMIN_ID = @json($adminUser->id);

    let convId = null;
    let messages = [];
    let lastId = 0;
    let pollTimer = null;
    let replyToId = null;
    let mediaList = [];   // {file, url, type}
    let activeMenuMsgId = null;

    // recorder
    let rec = null;
    let recStream = null;
    let recChunks = [];
    let recTimer = null;
    let recSeconds = 0;
    let recMime = '';

    const listEl = document.getElementById('lcList');
    const mainEl = document.getElementById('lcMain');
    const roomEl = document.getElementById('lcRoom');
    const emptyEl = document.getElementById('lcEmpty');
    const messagesEl = document.getElementById('lcMessages');
    const inputEl = document.getElementById('lcInput');

    /* helpers */
    function esc(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }
    function hdr(auth = true) {
        return { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    }
    function toast(msg, type) {
        const el = document.createElement('div');
        el.className = 'lc-toast ' + (type || 'success');
        el.textContent = msg;
        document.body.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }, 2600);
    }
    function fmtVoiceTime(s) {
        s = Number(s) || 0;
        const m = Math.floor(s / 60), sec = Math.floor(s % 60);
        return m + ':' + String(sec).padStart(2, '0');
    }
    function attachmentsOf(msg) {
        if (msg.attachments && msg.attachments.length) return msg.attachments;
        if (msg.media_path) return [{ media_path: msg.media_path, poster_path: msg.poster_path || null }];
        return [];
    }
    function scrollBottom() {
        const el = messagesEl;
        requestAnimationFrame(() => { requestAnimationFrame(() => { el.scrollTop = el.scrollHeight; }); });
        setTimeout(() => { el.scrollTop = el.scrollHeight; }, 120);
    }
    function closeAllMenus() {
        activeMenuMsgId = null;
        document.querySelectorAll('.lc-context-menu').forEach(el => el.remove());
        document.querySelectorAll('.lc-item-dropdown.open').forEach(el => el.classList.remove('open'));
    }

    /* conversation list filters */
    function applyFilter() {
        const q = (document.getElementById('lcSearch').value || '').toLowerCase().trim();
        const fAll = document.getElementById('lcFilterAll');
        const fUnread = document.getElementById('lcFilterUnread');
        const fOpen = document.getElementById('lcFilterOpen');
        const mode = fUnread.classList.contains('active') ? 'unread' : (fOpen.classList.contains('active') ? 'open' : 'all');
        listEl.querySelectorAll('.lc-item').forEach(item => {
            const name = (item.dataset.name || '').toLowerCase();
            let show = true;
            if (q && !name.includes(q)) show = false;
            if (show && mode === 'unread') show = Number(item.dataset.unread || 0) > 0;
            if (show && mode === 'open') show = item.dataset.status === 'open';
            item.style.display = show ? '' : 'none';
        });
    }
    function setFilter(btn, mode) {
        ['all', 'unread', 'open'].forEach(m => {
            const id = m === 'all' ? 'lcFilterAll' : m === 'unread' ? 'lcFilterUnread' : 'lcFilterOpen';
            document.getElementById(id).classList.remove('active');
        });
        btn.classList.add('active');
        applyFilter();
    }
    document.getElementById('lcSearch').addEventListener('input', applyFilter);
    document.getElementById('lcFilterAll').addEventListener('click', function () { setFilter(this, 'all'); });
    document.getElementById('lcFilterUnread').addEventListener('click', function () { setFilter(this, 'unread'); });
    document.getElementById('lcFilterOpen').addEventListener('click', function () { setFilter(this, 'open'); });

    /* item helpers */
    function findItem(id) {
        return document.getElementById('conv-' + id);
    }
    function highlightItem(id) {
        document.querySelectorAll('.lc-item').forEach(el => el.classList.remove('active'));
        const item = findItem(id);
        if (item) item.classList.add('active');
    }
    function resetUnread(id) {
        const item = findItem(id);
        if (item) { item.dataset.unread = '0'; item.classList.remove('has-unread'); const b = item.querySelector('.lc-item-unread'); if (b) b.remove(); }
    }
    function markClosedItem(id, closed) {
        const item = findItem(id);
        if (item) {
            item.dataset.status = closed ? 'closed' : 'open';
            item.classList.toggle('is-closed', !!closed);
        }
    }
    function markRoomEnded() {
        const sub = document.getElementById('lcHeadSub');
        if (sub) sub.textContent = (sub.textContent.split('·')[0] || '').trim() + ' · Sesi berakhir';
        document.getElementById('lcCloseBtn').style.display = 'none';
        document.getElementById('lcReopenBtn').style.display = 'none';
        const item = findItem(convId);
        if (item) {
            item.dataset.status = 'ended';
            item.classList.remove('is-closed');
            item.classList.add('is-ended');
            const badge = item.querySelector('.lc-item-unread');
            if (badge) badge.remove();
        }
        stopPoll();
    }

    /* item dropdown / archive / delete */
    window.toggleItemDropdown = function (btn) {
        const dd = btn.nextElementSibling;
        const wasOpen = dd.classList.contains('open');
        document.querySelectorAll('.lc-item-dropdown.open').forEach(el => el.classList.remove('open'));
        if (!wasOpen) dd.classList.add('open');
    };
    function closeActiveRoom(id) {
        if (convId === id) {
            convId = null;
            try { history.replaceState(null, '', window.location.pathname); } catch (e) {}
            roomEl.style.display = 'none';
            emptyEl.style.display = 'flex';
            stopPoll();
        }
    }
    window.archiveChat = async function (id) {
        try {
            const res = await fetch('/csadmin/conversations/' + id + '/archive', {
                method: 'PATCH',
                headers: hdr(),
            });
            if (!res.ok) throw new Error();
            const item = findItem(id);
            if (item) item.remove();
            closeActiveRoom(id);
            toast('Chat diarsipkan', 'success');
            updateArchivedBadge();
        } catch (e) {
            toast('Gagal mengarsipkan chat', 'error');
        }
    };
    window.unarchiveChat = async function (id) {
        try {
            const res = await fetch('/csadmin/conversations/' + id + '/restore', {
                method: 'PATCH',
                headers: hdr(),
            });
            if (!res.ok) throw new Error();
            const item = findItem(id);
            if (item) item.remove();
            const list = document.getElementById('lcArchivedList');
            if (list && !list.querySelector('.lc-item')) {
                const empty = document.getElementById('lcArchivedEmpty');
                if (empty) empty.style.display = 'flex';
            }
            toast('Chat dipindahkan ke chat utama', 'success');
            updateArchivedBadge();
        } catch (e) {
            toast('Gagal membatalkan arsip', 'error');
        }
    };
    window.deleteChat = async function (id) {
        if (!confirm('Hapus percakapan ini?')) return;
        try {
            const res = await fetch('/csadmin/conversations/' + id, {
                method: 'DELETE',
                headers: hdr(),
            });
            if (!res.ok) throw new Error();
            const item = findItem(id);
            if (item) item.remove();
            closeActiveRoom(id);
        } catch (e) {
            toast('Gagal menghapus percakapan', 'error');
        }
    };
    window.openArchived = function () {
        closeAllMenus();
        stopPoll();
        document.getElementById('lcMainPanel').style.display = 'none';
        document.getElementById('lcArchivedPanel').classList.add('open');
        loadArchived();
    };
    window.closeArchived = function () {
        document.getElementById('lcArchivedPanel').classList.remove('open');
        document.getElementById('lcMainPanel').style.display = 'flex';
        if (convId !== null) startPoll();
    };
    async function loadArchived() {
        const list = document.getElementById('lcArchivedList');
        const empty = document.getElementById('lcArchivedEmpty');
        empty.style.display = 'none';
        list.innerHTML = '<div style="padding:3rem 1.5rem;text-align:center;color:#6F89A7"><i class="fas fa-spinner fa-spin" style="font-size:18px"></i></div>';
        try {
            const res = await fetch('/csadmin/conversations/archived', { headers: hdr() });
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
    function updateArchivedBadge() {
        const badge = document.getElementById('lcArchivedBadge');
        if (!badge) return;
        fetch('/csadmin/conversations/archived-count', { headers: hdr() })
            .then(r => r.json())
            .then(data => {
                const count = data.count || 0;
                badge.textContent = count;
                badge.hidden = count === 0;
            })
            .catch(() => {});
    }
    setInterval(() => { if (document.getElementById('lcArchivedPanel') && !document.getElementById('lcArchivedPanel').classList.contains('open')) updateArchivedBadge(); }, 15000);

    /* open / load */
    window.openConversation = function (id) {
        if (id === convId) return;
        stopPoll();
        closeAllMenus();
        cancelRecording();
        convId = id;
        messages = [];
        lastId = 0;
        replyToId = null;
        hideReplyBar();
        clearMedia();
        emptyEl.style.display = 'none';
        roomEl.style.display = 'flex';
        highlightItem(id);
        try { history.replaceState(null, '', '?open=' + id); } catch (e) {}
        loadMessages(id);
        if (window.innerWidth <= 900) {
            document.getElementById('lcSidebar').classList.add('hidden-mobile');
        }
    };

    window.showSidebar = function () {
        cancelRecording();
        stopPoll();
        convId = null;
        try { history.replaceState(null, '', window.location.pathname); } catch (e) {}
        highlightItem(null);
        roomEl.style.display = 'none';
        emptyEl.style.display = 'flex';
        if (window.innerWidth <= 900) {
            document.getElementById('lcSidebar').classList.remove('hidden-mobile');
        }
    };

    async function loadMessages(id) {
        messagesEl.innerHTML = '<div class="lc-no-msg"><i class="fas fa-spinner fa-spin"></i></div>';
        try {
            const res = await fetch('/csadmin/conversations/' + id + '/messages', { headers: hdr() });
            if (!res.ok) throw new Error();
            const data = await res.json();
            const conv = data.conversation || {};
            document.getElementById('lcHeadAvatar').textContent = (conv.guest_name || 'G').charAt(0).toUpperCase();
            document.getElementById('lcHeadName').textContent = conv.guest_name || 'Guest';
            document.getElementById('lcHeadSub').textContent = (conv.channel ? conv.channel.name : '') + ' · ' + (conv.status === 'closed' ? 'Ditutup' : conv.status === 'ended' ? 'Sesi berakhir' : 'Terbuka');
            document.getElementById('lcCloseBtn').style.display = (conv.status === 'closed' || conv.status === 'ended') ? 'none' : '';
            document.getElementById('lcReopenBtn').style.display = conv.status === 'closed' ? '' : 'none';
            messages = data.messages || [];
            lastId = messages.length ? messages[messages.length - 1].id : 0;
            window.__csGuestName = conv.guest_name || 'Guest';
            renderMessages();
            resetUnread(id);
            startPoll();
        } catch (e) {
            messagesEl.innerHTML = '<div class="lc-no-msg">Gagal memuat pesan.</div>';
            toast('Gagal memuat pesan', 'error');
        }
    }

    function renderMessages() {
        messagesEl.innerHTML = messages.length
            ? messages.map(function (m) { const d = document.createElement('div'); d.innerHTML = msgHtml(m); return d.firstElementChild ? d.firstElementChild.outerHTML : ''; }).join('')
            : '<div class="lc-no-msg">Belum ada pesan. Kirim sapaan pertama untuk tamu ini.</div>';
        scrollBottom();
    }

    function msgHtml(msg) {
        if (msg.sender_type === 'system') {
            return '<div class="lc-msg lc-msg-system"><div class="lc-bubble lc-bubble-system">' + esc(msg.message) + '</div></div>';
        }
        const isUser = msg.sender_type === 'user';
        const cls = isUser ? 'lc-msg-user' : 'lc-msg-admin';
        const time = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        const senderLabel = isUser ? (msg.sender_name || window.__csGuestName || 'Guest') : (msg.sender && msg.sender.name) || 'Admin';

        let quoteHtml = '';
        if (msg.reply_to) {
            const rSender = msg.reply_to.sender?.name || (msg.reply_to.sender_type === 'user' ? 'Guest' : 'Admin');
            let rContent = '';
            if (msg.reply_to.message_type === 'image') rContent = '<span class="lc-quote-media">Foto</span>';
            else if (msg.reply_to.message_type === 'video') rContent = '<span class="lc-quote-media">Video</span>';
            else if (msg.reply_to.message_type === 'audio') rContent = '<span class="lc-quote-media">🎤 Pesan suara</span>';
            else rContent = esc((msg.reply_to.message || 'Pesan telah dihapus').substring(0, 80));
            quoteHtml = '<div class="lc-msg-quote" onclick="scrollToMsg(' + msg.reply_to.id + ')"><div class="lc-quote-bar"></div><div class="lc-quote-content"><div class="lc-quote-sender">' + esc(rSender) + '</div><div class="lc-quote-text">' + rContent + '</div></div></div>';
        }

        let mediaHtml = '';
        if (msg.message_type === 'image') {
            const atts = attachmentsOf(msg);
            if (atts.length) {
                const urlsJson = JSON.stringify(atts.map(a => '/media/' + a.media_path)).replace(/"/g, '&quot;');
                const gridCls = atts.length === 1 ? 'lc-grid-1' : atts.length === 2 ? 'lc-grid-2' : atts.length === 3 ? 'lc-grid-3' : 'lc-grid-4';
                const visible = atts.slice(0, 4);
                const hiddenCount = atts.length - 4;
                const tiles = visible.map((a, i) => {
                    const more = (i === 3 && hiddenCount > 0) ? '<span class="lc-gallery-more">+' + hiddenCount + '</span>' : '';
                    return '<button type="button" class="lc-gallery-item" data-urls="' + urlsJson + '" onclick="openGallery(event, this, ' + i + ')"><img src="/media/' + a.media_path + '" alt="Gambar" loading="lazy">' + more + '</button>';
                }).join('');
                mediaHtml = '<div class="lc-msg-gallery ' + gridCls + '">' + tiles + '</div>';
            }
        } else if (msg.message_type === 'video') {
            const v = attachmentsOf(msg)[0] || {};
            if (v.media_path) {
                const posterAttr = v.poster_path ? ' poster="/media/' + v.poster_path + '"' : '';
                mediaHtml = '<div class="lc-msg-media lc-video-wrap"><button type="button" class="lc-video-play" onclick="playVideoMessage(this)"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></button><video src="/media/' + v.media_path + '" controls preload="none"' + posterAttr + '></video></div>';
            }
        } else if (msg.message_type === 'audio') {
            const a = attachmentsOf(msg)[0] || {};
            if (a.media_path) {
                const dur = msg.media_duration ? fmtVoiceTime(msg.media_duration) : '';
                mediaHtml = '<div class="lc-msg-media"><div class="lc-voice" data-dur="' + (msg.media_duration || '') + '" onclick="toggleVoicePlay(this)"><span class="lc-voice-btn"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span><span class="lc-voice-progress"><span class="lc-voice-fill"></span></span><span class="lc-voice-time">' + dur + '</span><audio src="/media/' + a.media_path + '" preload="metadata"></audio></div></div>';
            }
        }

        const bubble = msg.message ? '<div class="lc-bubble">' + esc(msg.message) + '</div>' : '';

        return '<div class="lc-msg ' + cls + '" data-msg-id="' + msg.id + '" data-sender-type="' + msg.sender_type + '" data-sender-id="' + (msg.sender_id == null ? '' : msg.sender_id) + '">' +
            '<div class="lc-msg-body">' +
                (isUser ? '<div class="lc-msg-sender">' + esc(senderLabel) + '</div>' : '') +
                quoteHtml + mediaHtml + bubble +
                '<div class="lc-msg-time">' + time + '</div>' +
            '</div>' +
            '<div class="lc-msg-anchor" style="flex-shrink:0;padding:2px;cursor:pointer;color:#6F89A7;opacity:0.6" onclick="showMenu(event,' + msg.id + ')"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></div>' +
        '</div>';
    }

    function appendMessage(msg) {
        messages.push(msg);
        if (msg.id > lastId) lastId = msg.id;
        const empty = messagesEl.querySelector('.lc-no-msg');
        if (empty && messages.length === 1) renderMessages();
        else {
            const div = document.createElement('div');
            div.innerHTML = msgHtml(msg);
            const node = div.firstElementChild;
            if (node) messagesEl.appendChild(node);
        }
        scrollBottom();
    }

    /* poll */
    function startPoll() { stopPoll(); pollTimer = setInterval(pollNew, 3000); }
    function stopPoll() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }
    async function pollNew() {
        if (!convId) return;
        try {
            const res = await fetch('/csadmin/conversations/' + convId + '/poll?after=' + lastId, { headers: hdr() });
            if (!res.ok) return;
            const data = await res.json();
            (data.messages || []).forEach(appendMessage);
            if (data.conversation && data.conversation.status === 'ended' && !document.getElementById('lcCloseBtn').dataset.ended) {
                document.getElementById('lcCloseBtn').dataset.ended = '1';
                markRoomEnded();
            }
        } catch (e) {}
    }

    /* context menu */
    window.showMenu = function (e, msgId) {
        e.stopPropagation();
        const existing = document.querySelector('.lc-context-menu[data-msg-id="' + msgId + '"]');
        closeAllMenus();
        if (existing) return;
        activeMenuMsgId = msgId;
        const menu = document.createElement('div');
        menu.className = 'lc-context-menu';
        menu.dataset.msgId = msgId;
        menu.innerHTML =
            '<button class="lc-menu-item" onclick="replyTo(' + msgId + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 00-4-4H4"/></svg> Balas</button>' +
            '<button class="lc-menu-item lc-menu-danger" onclick="showDeleteOptions(' + msgId + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg> Hapus</button>';
        document.body.appendChild(menu);
        const rect = e.currentTarget.getBoundingClientRect();
        let top = rect.top;
        let left = Math.max(8, Math.min(rect.right - 172, window.innerWidth - 180));
        if (top + 200 > window.innerHeight) top = Math.max(8, rect.bottom - 200);
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
    };

    window.showDeleteOptions = function (msgId) {
        closeAllMenus();
        const el = document.querySelector('[data-msg-id="' + msgId + '"]');
        const isOwn = el && el.dataset.senderType === 'admin' && String(el.dataset.senderId) === String(ADMIN_ID);
        const menu = document.createElement('div');
        menu.className = 'lc-context-menu';
        menu.dataset.msgId = msgId;
        menu.innerHTML =
            '<button class="lc-menu-item lc-menu-danger" onclick="hideMsg(' + msgId + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg> Hapus untuk saya</button>' +
            (isOwn ? '<button class="lc-menu-item lc-menu-danger" onclick="deleteMsg(' + msgId + ')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg> Hapus untuk semua orang</button>' : '') +
            '<button class="lc-menu-item" onclick="closeAllMenus()">Batal</button>';
        document.body.appendChild(menu);
        const rect = el ? el.getBoundingClientRect() : { top: window.innerHeight / 2, right: window.innerWidth / 2, bottom: window.innerHeight / 2 };
        let top = rect.top;
        let left = Math.max(8, Math.min((rect.right || rect.left + 180) - 172, window.innerWidth - 180));
        if (top + 200 > window.innerHeight) top = Math.max(8, (rect.bottom || rect.top) - 200);
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
    };

    window.deleteMsg = async function (id) {
        closeAllMenus();
        if (!confirm('Hapus pesan ini untuk semua orang?')) return;
        try {
            const res = await fetch('/csadmin/messages/' + id, { method: 'DELETE', headers: hdr() });
            if (!res.ok) throw new Error();
            const el = document.querySelector('[data-msg-id="' + id + '"]');
            if (el) el.remove();
            messages = messages.filter(m => m.id !== id);
            toast('Pesan dihapus', 'success');
        } catch (e) { toast('Gagal menghapus pesan', 'error'); }
    };

    window.hideMsg = async function (id) {
        closeAllMenus();
        if (!confirm('Hapus pesan ini hanya dari chat Anda?')) return;
        try {
            const res = await fetch('/csadmin/messages/' + id + '/hide', { method: 'POST', headers: hdr() });
            if (!res.ok) throw new Error();
            const el = document.querySelector('[data-msg-id="' + id + '"]');
            if (el) el.remove();
            messages = messages.filter(m => m.id !== id);
            toast('Pesan disembunyikan', 'success');
        } catch (e) { toast('Gagal menyembunyikan pesan', 'error'); }
    };

    /* reply */
    window.replyTo = function (id) {
        closeAllMenus();
        const el = document.querySelector('[data-msg-id="' + id + '"]');
        if (!el) return;
        const bubble = el.querySelector('.lc-bubble');
        const voice = el.querySelector('.lc-voice');
        const video = el.querySelector('.lc-video-wrap');
        const image = el.querySelector('.lc-gallery-item img');
        const isUser = el.classList.contains('lc-msg-user');
        const senderName = isUser ? window.__csGuestName || 'Guest' : 'Admin';
        let preview = '';
        if (voice) preview = '🎤 Pesan suara';
        else if (video) preview = 'Video';
        else if (image) preview = 'Foto';
        else if (bubble) preview = bubble.textContent.substring(0, 80);
        else preview = 'Pesan';
        replyToId = id;
        document.getElementById('lcReplySender').textContent = '↩ Balas ' + senderName;
        document.getElementById('lcReplyText').textContent = preview;
        document.getElementById('lcReplyBar').style.display = 'flex';
        inputEl.placeholder = 'Balas ' + senderName + '...';
        inputEl.focus();
    };

    window.cancelReply = function () {
        replyToId = null;
        document.getElementById('lcReplyBar').style.display = 'none';
        inputEl.placeholder = 'Ketik pesan...';
    };
    function hideReplyBar() { cancelReply(); }

    /* media picker */
    document.getElementById('lcFileInput').addEventListener('change', function (e) {
        const files = Array.from(e.target.files || []);
        e.target.value = '';
        files.forEach(function (f) {
            const isImg = f.type.startsWith('image/');
            const isVideo = f.type.startsWith('video/');
            if (!isImg && !isVideo) { toast('Hanya gambar & video yang bisa dikirim', 'error'); return; }
            mediaList.push({ file: f, url: URL.createObjectURL(f), type: isImg ? 'image' : 'video' });
        });
        renderMediaBar();
    });

    function renderMediaBar() {
        const bar = document.getElementById('lcMediaBar');
        const thumbs = document.getElementById('lcMediaThumbs');
        if (!mediaList.length) { bar.style.display = 'none'; return; }
        bar.style.display = 'flex';
        thumbs.innerHTML = mediaList.map(function (m, i) {
            const tag = m.type === 'image'
                ? '<img src="' + m.url + '" alt="">'
                : '<video src="' + m.url + '" muted preload="metadata"></video>';
            return '<div class="lc-preview-thumb">' + tag + '<button class="lc-preview-remove" onclick="removeMedia(' + i + ')">✕</button></div>';
        }).join('');
    }

    window.removeMedia = function (i) {
        if (mediaList[i]) URL.revokeObjectURL(mediaList[i].url);
        mediaList.splice(i, 1);
        renderMediaBar();
    };

    window.clearMedia = function () {
        mediaList.forEach(m => URL.revokeObjectURL(m.url));
        mediaList = [];
        renderMediaBar();
    };

    /* send */
    async function sendRequest(formData) {
        const res = await fetch('/csadmin/conversations/' + convId + '/reply', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        });
        if (!res.ok) {
            let errMsg = '';
            let sessionEnded = false;
            try {
                const err = await res.clone().json();
                errMsg = err.error || err.message || '';
                sessionEnded = !!err.session_expired;
            } catch (e2) {}
            if (sessionEnded) {
                toast(errMsg || 'Sesi chat telah berakhir, balasan tidak dapat dikirim.', 'error');
                markRoomEnded();
            } else {
                toast(errMsg || 'Gagal mengirim pesan', 'error');
            }
            const err = new Error(errMsg || 'fail');
            err.handled = true;
            throw err;
        }
        return res.json();
    }

    window.sendText = async function () {
        if (!convId) return;
        const text = inputEl.value.trim();
        const hasMedia = mediaList.length > 0;
        const isSending = document.getElementById('lcSendBtn').disabled;
        const hasVoice = !!rec;
        if ((!text && !hasMedia) || isSending || hasVoice || recSeconds > 0) return;

        const sendBtn = document.getElementById('lcSendBtn');
        sendBtn.disabled = true;
        const replyId = replyToId;

        if (hasMedia) {
            const images = mediaList.filter(m => m.type === 'image');
            const videos = mediaList.filter(m => m.type === 'video');

            for (const item of images) {
                const fd = new FormData();
                fd.append('message_type', 'image');
                fd.append('media[]', item.file, item.file.name);
                if (text) fd.append('message', text);
                if (replyId) fd.append('reply_to_message_id', replyId);
                try { appendMessage(await sendRequest(fd)); } catch (e) { if (!e.handled) toast('Gagal mengirim gambar', 'error'); }
            }

            for (const item of videos) {
                const fd = new FormData();
                fd.append('message_type', 'video');
                fd.append('media[]', item.file, item.file.name);
                if (text) fd.append('message', text);
                if (replyId) fd.append('reply_to_message_id', replyId);
                try {
                    const thumb = await extractVideoThumb(item.file);
                    if (thumb) { fd.append('thumbnail[]', thumb, 'thumb.jpg'); fd.append('thumbnail_indexes[]', '0'); }
                    appendMessage(await sendRequest(fd));
                } catch (e) { if (!e.handled) toast('Gagal mengirim video', 'error'); }
            }
            inputEl.value = '';
            clearMedia();
        } else {
            const fd = new FormData();
            fd.append('message_type', 'text');
            fd.append('message', text);
            if (replyId) fd.append('reply_to_message_id', replyId);
            inputEl.value = '';
            autoResize(inputEl);
            try { appendMessage(await sendRequest(fd)); } catch (e) { if (!e.handled) toast('Gagal mengirim pesan', 'error'); }
        }

        sendBtn.disabled = false;
        if (replyId) { cancelReply(); }
        inputEl.focus();
    };

    /* voice rec */
    window.toggleRecording = async function () {
        if (rec) { await finishRecording(); return; }
        if (!navigator.mediaDevices || !window.MediaRecorder) { toast('Perekaman tidak didukung di browser ini', 'error'); return; }
        if (!convId) { toast('Buka percakapan terlebih dahulu', 'error'); return; }
        if (!window.MediaRecorder) return;
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            recMime = (MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4');
            rec = new MediaRecorder(stream, { mimeType: recMime });
            recChunks = [];
            rec.ondataavailable = function (ev) { if (ev.data && ev.data.size) recChunks.push(ev.data); };
            rec.onstop = sendVoiceFile;
            rec.start();
            recStream = stream;
            recSeconds = 0;
            document.getElementById('lcRecNote').style.display = 'flex';
            document.getElementById('lcRecordBtn').classList.add('recording');
            document.getElementById('lcRecordBtn').innerHTML = '<i class="fas fa-stop"></i>';
            recTimer = setInterval(function () { recSeconds++; }, 1000);
        } catch (e) { toast('Akses mikrofon ditolak', 'error'); }
    };

    function cancelRecording() {
        if (rec) {
            try { rec.onstop = null; rec.stop(); } catch (e) {}
            rec = null;
        }
        if (recStream) { recStream.getTracks().forEach(t => t.stop()); recStream = null; }
        if (recTimer) { clearInterval(recTimer); recTimer = null; }
        recChunks = [];
        recSeconds = 0;
        document.getElementById('lcRecNote').style.display = 'none';
        const b = document.getElementById('lcRecordBtn');
        b.classList.remove('recording');
        b.innerHTML = '<i class="fas fa-microphone"></i>';
    }

    async function finishRecording() {
        if (!rec) return;
        const secs = recSeconds;
        rec.stop();
        recSeconds = secs;
        rec = null;
        if (recStream) { recStream.getTracks().forEach(t => t.stop()); recStream = null; }
        if (recTimer) { clearInterval(recTimer); recTimer = null; }
        document.getElementById('lcRecNote').style.display = 'none';
        const b = document.getElementById('lcRecordBtn');
        b.classList.remove('recording');
        b.innerHTML = '<i class="fas fa-microphone"></i>';
    }

    async function sendVoiceFile() {
        const blob = new Blob(recChunks, { type: recMime });
        recChunks = [];
        if (!blob.size) return;
        const ext = recMime.includes('webm') ? 'webm' : 'm4a';
        const fd = new FormData();
        fd.append('message_type', 'audio');
        fd.append('media[]', blob, 'voice.' + ext);
        if (recSeconds > 0) fd.append('media_duration', String(recSeconds));
        if (replyToId) fd.append('reply_to_message_id', replyToId);
        try {
            const msg = await sendRequest(fd);
            appendMessage(msg);
        } catch (e) { toast('Gagal mengirim pesan suara', 'error'); }
    }

    function extractVideoThumb(videoFile) {
        return new Promise(function (resolve) {
            try {
                const video = document.createElement('video');
                video.preload = 'metadata';
                video.muted = true;
                video.playsInline = true;
                video.src = URL.createObjectURL(videoFile);
                video.onloadeddata = function () {
                    try {
                        video.currentTime = 0.5;
                    } catch (e) {}
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = 240;
                        canvas.height = 135;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        canvas.toBlob(function (blob) {
                            URL.revokeObjectURL(video.src);
                            resolve(blob);
                        }, 'image/jpeg', 0.7);
                    } catch (e) {
                        URL.revokeObjectURL(video.src);
                        resolve(null);
                    }
                };
                video.onerror = function () { URL.revokeObjectURL(video.src); resolve(null); };
                setTimeout(function () { URL.revokeObjectURL(video.src); resolve(null); }, 6000);
            } catch (e) { resolve(null); }
        });
    }

    /* status (close/reopen) */
    window.toggleStatus = async function (status) {
        if (!convId) return;
        const action = status === 'closed' ? 'close' : 'reopen';
        const label = status === 'closed' ? 'Tutup' : 'Buka';
        if (status === 'closed' && !confirm('Tutup percakapan ini?')) return;
        try {
            const res = await fetch('/csadmin/conversations/' + convId + '/' + action, { method: 'PATCH', headers: hdr() });
            if (!res.ok) throw new Error();
            markClosedItem(convId, status === 'closed');
            document.getElementById('lcCloseBtn').style.display = status === 'closed' ? 'none' : '';
            document.getElementById('lcReopenBtn').style.display = status === 'closed' ? '' : 'none';
            document.getElementById('lcHeadSub').textContent = document.getElementById('lcHeadSub').textContent.split('·')[0] + '· ' + (status === 'closed' ? 'Ditutup' : 'Terbuka');
            toast(label + ' percakapan', 'success');
        } catch (e) { toast('Gagal ' + label.toLowerCase() + ' percakapan', 'error'); }
    };

    /* media playback */
    window.playVideoMessage = function (btn) {
        const wrap = btn.closest('.lc-video-wrap');
        if (!wrap) return;
        const video = wrap.querySelector('video');
        if (!video) return;
        if (!video.dataset.lcBound) {
            video.dataset.lcBound = '1';
            video.addEventListener('play', () => btn.classList.add('is-hidden'));
            video.addEventListener('pause', () => btn.classList.remove('is-hidden'));
            video.addEventListener('ended', () => btn.classList.remove('is-hidden'));
        }
        btn.classList.add('is-hidden');
        const pr = video.play();
        if (pr && pr.catch) pr.catch(() => btn.classList.remove('is-hidden'));
    };

    window.toggleVoicePlay = function (el) {
        const audio = el.querySelector('audio');
        const fill = el.querySelector('.lc-voice-fill');
        const timeEl = el.querySelector('.lc-voice-time');
        const dur = Number(el.dataset.dur || 0);
        document.querySelectorAll('.lc-voice audio').forEach(a => { if (a !== audio) { a.pause(); a.currentTime = 0; } });
        if (!audio.paused) { audio.pause(); return; }
        fill.style.width = '0%';
        audio.currentTime = 0;
        audio.play();
        audio.ontimeupdate = function () {
            const d = dur || audio.duration || 0;
            const pct = d ? (audio.currentTime / d) * 100 : 0;
            fill.style.width = Math.min(100, pct) + '%';
            timeEl.textContent = fmtVoiceTime(audio.currentTime);
        };
        audio.onended = function () { fill.style.width = '0%'; timeEl.textContent = fmtVoiceTime(dur); };
    };

    window.scrollToMsg = function (id) {
        const el = document.querySelector('[data-msg-id="' + id + '"]');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    /* lightbox */
    window.openGallery = function (event, btn, startIndex) {
        event.stopPropagation();
        let urls = [];
        try { urls = JSON.parse(btn.dataset.urls || '[]'); } catch (e) {}
        if (!urls.length) return;
        const lb = document.createElement('div');
        lb.className = 'lc-lightbox';
        lb.innerHTML = '<img src="' + urls[startIndex || 0] + '" alt="">';
        lb.addEventListener('click', () => lb.remove());
        document.body.appendChild(lb);
    };

    /* input */
    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 110) + 'px';
    }
    inputEl.addEventListener('input', function () { autoResize(inputEl); });
    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendText(); }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.lc-context-menu') && !e.target.closest('.lc-msg-anchor')) closeAllMenus();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        const lb = document.querySelector('.lc-lightbox');
        if (lb) { lb.remove(); return; }
        if (document.querySelector('.lc-context-menu')) { closeAllMenus(); return; }
        const tag = (e.target && e.target.tagName) || '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        if (convId) showSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            document.getElementById('lcSidebar').classList.remove('hidden-mobile');
        }
    });

    /* auto open from ?open= */
    const params = new URLSearchParams(window.location.search);
    const openId = params.get('open');
    if (openId) {
        openConversation(parseInt(openId, 10));
    }
})();
</script>
@endpush
