@extends('admin.layouts.app')
@section('title', 'Live Chat Dashboard')

@push('styles')
<style>
    /* ===== Live Chat Dashboard — responsive scoped styles ===== */
    .lc-dash {
        display: flex;
        flex-direction: column;
        gap: 24px;
        min-width: 0;
    }

    /* Page header */
    .lc-dash-head { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .lc-page-title {
        font-size: 1.25rem; font-weight: 800; color: var(--text);
        margin: 0; line-height: 1.3;
    }
    .lc-page-subtitle { font-size: 0.85rem; color: var(--text-muted); margin: 0; }

    /* Statistic cards */
    .lc-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: clamp(12px, 2vw, 20px);
        min-width: 0;
    }
    .lc-stat { position: relative; min-width: 0; }
    .lc-stat-accent {
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        border-radius: 16px 16px 0 0;
    }
    .lc-stat-label {
        font-size: 0.7rem; letter-spacing: 0.06em; text-transform: uppercase;
        font-weight: 600; color: var(--text-muted);
    }
    .lc-stat-value {
        font-size: 1.6rem; font-weight: 800; color: var(--text); line-height: 1.15;
        font-family: 'Poppins', sans-serif;
    }

    /* Bottom panels */
    .lc-cards {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
        min-width: 0;
    }
    .lc-card { min-width: 0; }
    .lc-card-head {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        padding: 18px 20px 0;
    }
    .lc-card-title { font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0; }
    .lc-card-body { padding: 6px 20px 14px; min-width: 0; }

    /* Channels list */
    .lc-list { display: flex; flex-direction: column; min-width: 0; }
    .lc-list-item {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--glass-border);
        min-width: 0;
    }
    .lc-list-item:last-child { border-bottom: none; padding-bottom: 8px; }
    .lc-list-name {
        min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        font-size: 13px; font-weight: 500; color: var(--text);
    }

    /* Recent conversations */
    .lc-recent { display: flex; flex-direction: column; min-width: 0; }
    .lc-recent-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 0; border-bottom: 1px solid var(--glass-border);
        text-decoration: none; color: inherit; min-width: 0;
        transition: background 0.15s ease;
    }
    .lc-recent-item:hover .lc-recent-name { color: var(--accent); }
    .lc-recent-item:last-child { border-bottom: none; padding-bottom: 8px; }
    .lc-recent-avatar {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, var(--accent), #8b5cf6);
        color: #fff; font-weight: 700; font-size: 12px;
        display: flex; align-items: center; justify-content: center;
        text-transform: uppercase;
    }
    .lc-recent-main { flex: 1; min-width: 0; }
    .lc-recent-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lc-recent-name {
        min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        font-size: 13px; font-weight: 600; color: var(--text);
    }
    .lc-recent-time { flex-shrink: 0; font-size: 11px; color: var(--text-muted); white-space: nowrap; }
    .lc-recent-preview {
        margin-top: 2px; font-size: 12px; color: var(--text-dim);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .lc-recent-badge { flex-shrink: 0; }

    @media (min-width: 760px) {
        .lc-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 479px) {
        .lc-dash { gap: 16px; }
        .lc-page-title { font-size: 1.1rem; }
        .lc-stat-value { font-size: 1.35rem; }
        .lc-card-head { padding: 14px 16px 0; }
        .lc-card-body { padding: 4px 16px 10px; }
        .lc-recent-item { align-items: flex-start; }
        .lc-recent-avatar { width: 36px; height: 36px; font-size: 11px; }
        .lc-recent-top { flex-direction: column; align-items: flex-start; gap: 2px; }
        .lc-recent-name { white-space: normal; overflow: visible; }
        .lc-recent-preview {
            white-space: normal;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .lc-recent-time { font-size: 10.5px; margin-top: 2px; }
    }

    .lc-dash a:focus-visible, .lc-dash button:focus-visible {
        outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="lc-dash">
    <div class="lc-dash-head">
        <h1 class="lc-page-title">Live Chat Dashboard</h1>
        <p class="lc-page-subtitle">Overview percakapan live chat</p>
    </div>

    <div class="lc-stats">
        <div class="stat-card lc-stat">
            <div class="lc-stat-accent" style="background:var(--accent)"></div>
            <div class="lc-stat-label">Total Conversations</div>
            <div class="lc-stat-value">{{ $totalConversations }}</div>
        </div>
        <div class="stat-card lc-stat">
            <div class="lc-stat-accent" style="background:var(--success)"></div>
            <div class="lc-stat-label">Open</div>
            <div class="lc-stat-value">{{ $openConversations }}</div>
        </div>
        <div class="stat-card lc-stat">
            <div class="lc-stat-accent" style="background:var(--warning)"></div>
            <div class="lc-stat-label">Pending</div>
            <div class="lc-stat-value">{{ $pendingConversations }}</div>
        </div>
        <div class="stat-card lc-stat">
            <div class="lc-stat-accent" style="background:var(--error)"></div>
            <div class="lc-stat-label">Unread</div>
            <div class="lc-stat-value">{{ $totalUnread }}</div>
        </div>
        <div class="stat-card lc-stat">
            <div class="lc-stat-accent" style="background:var(--info)"></div>
            <div class="lc-stat-label">Admin Online</div>
            <div class="lc-stat-value">{{ $onlineOperators }}</div>
        </div>
        <div class="stat-card lc-stat">
            <div class="lc-stat-accent" style="background:var(--text-mute, #666)"></div>
            <div class="lc-stat-label">Admin Offline</div>
            <div class="lc-stat-value">{{ $offlineOperators }}</div>
        </div>
    </div>

    <div class="lc-cards">
        <section class="card-glass lc-card">
            <div class="lc-card-head">
                <h3 class="lc-card-title">Conversations per Channel</h3>
            </div>
            <div class="lc-card-body">
                <div class="lc-list">
                    @forelse($conversationsPerChannel as $channel)
                    <div class="lc-list-item">
                        <span class="lc-list-name">{{ $channel->name }}</span>
                        <span class="badge badge-info" style="flex-shrink:0">{{ $channel->conversations_count }}</span>
                    </div>
                    @empty
                    <div class="empty-state">Belum ada data</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="card-glass lc-card">
            <div class="lc-card-head">
                <h3 class="lc-card-title">Recent Conversations</h3>
                <a href="{{ route('admin.live-chat.conversations') }}" class="btn btn-sm">Lihat Semua</a>
            </div>
            <div class="lc-card-body">
                <div class="lc-recent">
                    @forelse($recentConversations as $conv)
                    <a href="{{ route('admin.live-chat.conversations.show', $conv) }}" class="lc-recent-item">
                        <x-user-avatar :user="$conv->isGuest() ? null : $conv->user" :name="$conv->isGuest() ? ($conv->guest_name ?? 'Guest') : ($conv->user->name ?? 'User')" :size="38" radius="10px" class="lc-recent-avatar" />
                        <div class="lc-recent-main">
                            <div class="lc-recent-top">
                                <span class="lc-recent-name">{{ $conv->isGuest() ? ($conv->guest_name ?? 'Guest') : ($conv->user->name ?? 'User') }}</span>
                                <span class="lc-recent-time">{{ $conv->last_message_at?->diffForHumans() ?? '' }}</span>
                            </div>
                            <div class="lc-recent-preview">{{ $conv->last_message?->message ?? 'Belum ada pesan' }}</div>
                        </div>
                        @if($conv->admin_unread_count > 0)
                        <span class="badge badge-error lc-recent-badge">{{ $conv->admin_unread_count }}</span>
                        @endif
                    </a>
                    @empty
                    <div class="empty-state">Belum ada percakapan</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
