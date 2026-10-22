@extends('admin.layouts.lcadmin')
@section('title', 'Dashboard')

@push('styles')
<style>
.dash-greeting {
    margin-bottom: 24px;
}
.dash-greeting h1 {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 22px;
    color: #F5F7FB;
    margin: 0 0 4px;
}
.dash-greeting p {
    color: #8FA8C4;
    font-size: 14px;
    margin: 0;
}
.dash-channels {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px;
}
.dash-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 8px;
    background: rgba(54,201,143,0.1);
    border: 1px solid rgba(54,201,143,0.2);
    color: #36C98F;
    font-size: 12px;
    font-weight: 600;
}
.dash-chip i { font-size: 10px; }

/* Stats Grid */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.dash-stat {
    background: #102E4D;
    border: 1px solid #1A4168;
    border-radius: 12px;
    padding: 18px 16px;
    cursor: default;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.dash-stat::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: 12px 12px 0 0;
    opacity: 0;
    transition: opacity 0.2s;
}
.dash-stat:hover {
    transform: translateY(-2px);
    border-color: transparent;
    box-shadow: 0 8px 24px -8px rgba(0,0,0,0.3);
}
.dash-stat:hover::before { opacity: 1; }
.dash-stat.s-total::before { background: linear-gradient(90deg, #3F6DF5, #8b5cf6); }
.dash-stat.s-open::before { background: linear-gradient(90deg, #36C98F, #10b981); }
.dash-stat.s-pending::before { background: linear-gradient(90deg, #f59e0b, #f97316); }
.dash-stat.s-closed::before { background: linear-gradient(90deg, #6F89A7, #8FA8C4); }
.dash-stat.s-unread::before { background: linear-gradient(90deg, #ef4444, #f97316); }

.dash-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-bottom: 14px;
}
.dash-stat-icon.i-total { background: rgba(63,109,245,0.12); color: #3F6DF5; }
.dash-stat-icon.i-open { background: rgba(54,201,143,0.12); color: #36C98F; }
.dash-stat-icon.i-pending { background: rgba(245,158,11,0.12); color: #f59e0b; }
.dash-stat-icon.i-closed { background: rgba(111,137,167,0.12); color: #8FA8C4; }
.dash-stat-icon.i-unread { background: rgba(239,68,68,0.12); color: #ef4444; }

.dash-stat-label {
    font-size: 11px;
    color: #6F89A7;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 600;
    margin-bottom: 4px;
}
.dash-stat-value {
    font-size: 26px;
    font-weight: 800;
    color: #F5F7FB;
    line-height: 1;
}

/* Bottom Grid */
.dash-bottom {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.dash-card {
    background: #102E4D;
    border: 1px solid #1A4168;
    border-radius: 12px;
    overflow: hidden;
}
.dash-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    border-bottom: 1px solid #1A4168;
}
.dash-card-title {
    font-weight: 700;
    font-size: 14px;
    color: #F5F7FB;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-card-title i {
    font-size: 14px;
    color: #3F6DF5;
}
.dash-card-link {
    font-size: 12px;
    color: #3F6DF5;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color 0.15s;
}
.dash-card-link:hover { color: #5B8DF8; }
.dash-card-link i { font-size: 10px; }

/* Recent Conversations */
.dash-recent-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-bottom: 1px solid rgba(26,65,104,0.4);
    transition: background 0.15s;
    cursor: pointer;
    text-decoration: none;
}
.dash-recent-item:last-child { border-bottom: none; }
.dash-recent-item:hover { background: rgba(63,109,245,0.05); }
.dash-recent-avatar {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: linear-gradient(135deg, #3F6DF5, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    color: #fff;
    flex-shrink: 0;
}
.dash-recent-body {
    flex: 1;
    min-width: 0;
}
.dash-recent-name {
    font-weight: 600;
    font-size: 13px;
    color: #F5F7FB;
    margin-bottom: 1px;
}
.dash-recent-msg {
    font-size: 12px;
    color: #6F89A7;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-recent-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.dash-recent-time {
    font-size: 10px;
    color: #6F89A7;
}
.dash-recent-status {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.dash-recent-status.st-open { background: #36C98F; }
.dash-recent-status.st-pending { background: #f59e0b; }

/* Channel Stats */
.dash-channel-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid rgba(26,65,104,0.4);
}
.dash-channel-item:last-child { border-bottom: none; }
.dash-channel-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #3F6DF5, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    flex-shrink: 0;
}
.dash-channel-info {
    flex: 1;
}
.dash-channel-name {
    font-weight: 700;
    font-size: 13px;
    color: #F5F7FB;
    margin-bottom: 2px;
}
.dash-channel-count {
    font-size: 11px;
    color: #6F89A7;
}
.dash-channel-bar {
    width: 60px;
    height: 6px;
    border-radius: 3px;
    background: #1A3A5C;
    overflow: hidden;
}
.dash-channel-bar-fill {
    height: 100%;
    border-radius: 3px;
    background: linear-gradient(90deg, #3F6DF5, #8b5cf6);
    transition: width 0.6s cubic-bezier(.22,1,.36,1);
}
.dash-channel-open {
    font-size: 12px;
    font-weight: 700;
    color: #36C98F;
    min-width: 20px;
    text-align: right;
}

/* Quick Actions */
.dash-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 16px 18px;
}
.dash-action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #1A4168;
    background: transparent;
    color: #8FA8C4;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
}
.dash-action-btn:hover {
    border-color: #3F6DF5;
    color: #3F6DF5;
    background: rgba(63,109,245,0.05);
}
.dash-action-btn i { font-size: 14px; }

/* Empty state */
.dash-empty {
    padding: 2rem;
    text-align: center;
    color: #6F89A7;
    font-size: 13px;
}
.dash-empty i {
    font-size: 2rem;
    color: #214D78;
    margin-bottom: 8px;
    display: block;
}

/* Responsive */
@media (max-width: 1100px) {
    .dash-stats { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 900px) {
    .dash-bottom { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .dash-stats { grid-template-columns: repeat(2, 1fr); }
    .dash-actions { grid-template-columns: 1fr; }
}

/* Animate in */
.dash-stat, .dash-card {
    animation: dashFadeUp 0.4s ease-out both;
}
.dash-stat:nth-child(1) { animation-delay: 0.05s; }
.dash-stat:nth-child(2) { animation-delay: 0.1s; }
.dash-stat:nth-child(3) { animation-delay: 0.15s; }
.dash-stat:nth-child(4) { animation-delay: 0.2s; }
.dash-stat:nth-child(5) { animation-delay: 0.25s; }
.dash-card:nth-child(1) { animation-delay: 0.3s; }
.dash-card:nth-child(2) { animation-delay: 0.35s; }

@keyframes dashFadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Pulse animation for unread */
@keyframes pulse-dot {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
    50% { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
}
.dash-stat.s-unread .dash-stat-value {
    animation: pulse-dot 2s infinite;
}
</style>
@endpush

@section('content')
@php $admin = Auth::guard('admin')->user(); @endphp

<div class="dash-greeting">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
        <x-user-avatar :user="$admin" :size="42" radius="11px" />
        <h1>Selamat datang, {{ $admin->name }}</h1>
    </div>
    <p>Berikut ringkasan aktivitas live chat hari ini.</p>
    @php $channels = $admin->assignedChannels; @endphp
    @if($channels->count() > 0)
    <div class="dash-channels">
        @foreach($channels as $ch)
        <span class="dash-chip">
            <i class="fas fa-gamepad"></i> {{ $ch->name }}
        </span>
        @endforeach
    </div>
    @endif
</div>

<!-- Stats -->
<div class="dash-stats">
    <div class="dash-stat s-total">
        <div class="dash-stat-icon i-total"><i class="fas fa-comments"></i></div>
        <div class="dash-stat-label">Total Percakapan</div>
        <div class="dash-stat-value" data-count="{{ $totalConversations }}">0</div>
    </div>
    <div class="dash-stat s-open">
        <div class="dash-stat-icon i-open"><i class="fas fa-comment-dots"></i></div>
        <div class="dash-stat-label">Sedang Open</div>
        <div class="dash-stat-value" data-count="{{ $openConversations }}">0</div>
    </div>
    <div class="dash-stat s-pending">
        <div class="dash-stat-icon i-pending"><i class="fas fa-hourglass-half"></i></div>
        <div class="dash-stat-label">Menunggu</div>
        <div class="dash-stat-value" data-count="{{ $pendingConversations }}">0</div>
    </div>
    <div class="dash-stat s-closed">
        <div class="dash-stat-icon i-closed"><i class="fas fa-archive"></i></div>
        <div class="dash-stat-label">Selesai</div>
        <div class="dash-stat-value" data-count="{{ $closedConversations }}">0</div>
    </div>
    <div class="dash-stat s-unread">
        <div class="dash-stat-icon i-unread"><i class="fas fa-envelope-open-text"></i></div>
        <div class="dash-stat-label">Belum Dibaca</div>
        <div class="dash-stat-value" data-count="{{ $unreadMessages }}">0</div>
    </div>
</div>

<!-- Today Stats Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px">
    <div class="dash-stat" style="border-left:3px solid #3F6DF5">
        <div style="display:flex;align-items:center;gap:10px">
            <div class="dash-stat-icon i-total" style="margin-bottom:0"><i class="fas fa-paper-plane"></i></div>
            <div>
                <div class="dash-stat-label">Pesan Hari Ini</div>
                <div class="dash-stat-value" style="font-size:22px" data-count="{{ $messagesToday }}">0</div>
            </div>
        </div>
    </div>
    <div class="dash-stat" style="border-left:3px solid #36C98F">
        <div style="display:flex;align-items:center;gap:10px">
            <div class="dash-stat-icon i-open" style="margin-bottom:0"><i class="fas fa-reply-all"></i></div>
            <div>
                <div class="dash-stat-label">Balasan Admin Hari Ini</div>
                <div class="dash-stat-value" style="font-size:22px" data-count="{{ $adminRepliesToday }}">0</div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Cards -->
<div class="dash-bottom">
    <!-- Recent Conversations -->
    <div class="dash-card">
        <div class="dash-card-header">
            <span class="dash-card-title"><i class="fas fa-clock"></i> Percakapan Terbaru</span>
            <a href="{{ route('lcadmin.conversations') }}" class="dash-card-link">Lihat Semua <i class="fas fa-chevron-right"></i></a>
        </div>
        @if($recentConversations->count() > 0)
            @foreach($recentConversations as $conv)
            <a href="{{ route('lcadmin.conversations') }}?open={{ $conv->id }}" class="dash-recent-item">
                <x-user-avatar :user="$conv->user" :name="$conv->user->name ?? 'User'" :size="40" radius="10px" class="dash-recent-avatar" />
                <div class="dash-recent-body">
                    <div class="dash-recent-name">{{ $conv->user->name ?? 'User' }}</div>
                    <div class="dash-recent-msg">{{ $conv->lastMessage?->message ?? $conv->channel->name ?? '—' }}</div>
                </div>
                <div class="dash-recent-meta">
                    <span class="dash-recent-time">{{ $conv->last_message_at?->diffForHumans() ?? '' }}</span>
                    @if($conv->status === 'open')
                    <span class="dash-recent-status st-open"></span>
                    @elseif($conv->status === 'pending')
                    <span class="dash-recent-status st-pending"></span>
                    @endif
                </div>
            </a>
            @endforeach
        @else
            <div class="dash-empty">
                <i class="fas fa-inbox"></i>
                <p>Belum ada percakapan aktif</p>
            </div>
        @endif
    </div>

    <!-- Channel Stats -->
    <div class="dash-card">
        <div class="dash-card-header">
            <span class="dash-card-title"><i class="fas fa-gamepad"></i> Statistik Channel</span>
        </div>
        @if($channelStats->count() > 0)
            @php $maxTotal = $channelStats->max('total') ?: 1; @endphp
            @foreach($channelStats as $cs)
            <div class="dash-channel-item">
                <div class="dash-channel-icon"><i class="fas fa-gamepad"></i></div>
                <div class="dash-channel-info">
                    <div class="dash-channel-name">{{ $cs['channel']->name }}</div>
                    <div class="dash-channel-count">{{ $cs['total'] }} percakapan</div>
                </div>
                <div class="dash-channel-bar">
                    <div class="dash-channel-bar-fill" style="width:{{ ($cs['total'] / $maxTotal) * 100 }}%"></div>
                </div>
                <div class="dash-channel-open">{{ $cs['open'] }}</div>
            </div>
            @endforeach
        @else
            <div class="dash-empty">
                <i class="fas fa-gamepad"></i>
                <p>Belum ada channel ditugaskan</p>
            </div>
        @endif

        <!-- Quick Actions -->
        <div class="dash-actions">
            <a href="{{ route('lcadmin.conversations') }}" class="dash-action-btn">
                <i class="fas fa-comments"></i> Buka Chat
            </a>
            <a href="{{ route('lcadmin.dashboard') }}" class="dash-action-btn" onclick="location.reload();return false">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animated counters
    document.querySelectorAll('[data-count]').forEach(function(el) {
        const target = parseInt(el.dataset.count) || 0;
        if (target === 0) { el.textContent = '0'; return; }
        const duration = 800;
        const start = performance.now();
        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(eased * target);
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });

    // Animate channel bars
    document.querySelectorAll('.dash-channel-bar-fill').forEach(function(bar) {
        const w = bar.style.width;
        bar.style.width = '0';
        setTimeout(function() { bar.style.width = w; }, 400);
    });
});
</script>
@endpush
