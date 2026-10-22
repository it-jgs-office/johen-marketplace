@extends('admin.layouts.app')
@section('title', 'Kelola Admin Live Chat')

@section('content')
@if(session('success'))
<div id="toast-success" style="position:fixed;top:24px;right:24px;z-index:9999;display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:12px;background:#10b981;color:#fff;font-size:0.85rem;font-weight:600;box-shadow:0 8px 30px -8px rgba(0,0,0,.4);animation:slideIn .3s ease" onclick="this.remove()">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
<style>@keyframes slideIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}</style>
<script>setTimeout(function(){var t=document.getElementById('toast-success');if(t)t.remove()},4000)</script>
@endif

<div style="margin-bottom:28px">
    <h1 style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.6rem;color:#fff;margin:0 0 4px">Kelola Admin Live Chat</h1>
    <p style="font-size:0.88rem;color:#9CB5D2;margin:0">Atur foto, status, dan jadwal admin</p>
</div>

<div class="lc-grid" id="channels-grid">
    @foreach($channels as $channel)
    @php
        $admin = $channel->admins->first();
        $adminUser = $admin ? $admin->user : null;
        $ops = $channel->operators->whereNull('user_id')->sortBy('id');
    @endphp
    <div class="lc-card" data-channel-id="{{ $channel->id }}">

        {{-- Header --}}
        <div class="lc-card-header">
            <div class="lc-avatar">
                @if($admin && $admin->photo_path)
                    <img src="{{ asset('storage/' . $admin->photo_path) }}" alt="{{ $channel->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:14px">
                @else
                    <span style="font-family:'Poppins',sans-serif;font-weight:800;font-size:18px;color:#fff">{{ substr($channel->name, 0, 2) }}</span>
                @endif
            </div>
            <div class="lc-info">
                <div class="lc-name">{{ $channel->name }}</div>
                <div class="lc-channel">{{ $channel->name }}</div>
                <div class="lc-email">{{ $adminUser ? $adminUser->email : 'Belum ditugaskan' }}</div>
            </div>
            <div class="lc-badge-wrap">
                @if($admin && $admin->is_active)
                <span class="lc-badge lc-badge-active">
                    <span class="lc-dot lc-dot-active"></span> Aktif
                </span>
                @else
                <span class="lc-badge lc-badge-inactive">
                    <span class="lc-dot lc-dot-inactive"></span> Nonaktif
                </span>
                @endif
            </div>
        </div>

        {{-- Divider --}}
        <div class="lc-divider"></div>

        {{-- Jadwal Operator --}}
        <div class="lc-section-title">Jadwal Admin</div>

        <div class="lc-schedule-body">
            @if($ops->count() > 0)
                @foreach($ops as $op)
                    @php
                        $schedule = $op->schedules->first();
                        $timeRange = $schedule ? substr($schedule->start_time, 0, 5) . ' — ' . substr($schedule->end_time, 0, 5) : '—';
                    @endphp
                    <div class="lc-op-row">
                        <x-user-avatar :user="$op->user" :name="$op->name ?? $op->user->name ?? 'N/A'" :size="26" radius="7px" />
                        <span class="lc-op-name">{{ $op->name ?? $op->user->name ?? 'N/A' }}</span>
                        <span class="lc-op-time">{{ $timeRange }}</span>
                    </div>
                @endforeach
            @else
                <div class="lc-op-empty">Belum ada jadwal</div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="lc-card-footer">
            <a href="{{ route('admin.live-chat.admins.edit', $channel) }}" class="lc-edit-btn">
                <i class="fas fa-pen" style="font-size:12px"></i> Edit
            </a>
        </div>
    </div>
    @endforeach
</div>

<style>
.lc-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}
.lc-card {
    background: #112F50;
    border: 1px solid #1D4670;
    border-radius: 16px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    min-height: 320px;
    transition: border-color .2s, background .2s;
}
.lc-card:hover {
    border-color: #2a5a8a;
    background: #143558;
}
.lc-card-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.lc-avatar {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    background: linear-gradient(135deg, #0987F5, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 18px;
    flex-shrink: 0;
    overflow: hidden;
}
.lc-info {
    flex: 1;
    min-width: 0;
}
.lc-name {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 16px;
    color: #fff;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lc-channel {
    font-size: 13px;
    font-weight: 600;
    color: #39BDF5;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lc-email {
    font-size: 12px;
    color: #9CB5D2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lc-badge-wrap {
    flex-shrink: 0;
}
.lc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    height: 28px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
.lc-badge-active {
    background: rgba(16, 185, 129, .12);
    color: #34d399;
}
.lc-badge-inactive {
    background: rgba(100, 116, 139, .12);
    color: #94a3b8;
}
.lc-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.lc-dot-active {
    background: #34d399;
}
.lc-dot-inactive {
    background: #94a3b8;
}
.lc-divider {
    border-top: 1px solid #1D4670;
    margin: 16px 0;
}
.lc-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #9CB5D2;
    margin-bottom: 12px;
}
.lc-schedule-body {
    flex: 1;
}
.lc-op-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}
.lc-op-row + .lc-op-row {
    border-top: 1px solid rgba(255, 255, 255, .04);
}
.lc-op-name {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
}
.lc-op-time {
    font-size: 13px;
    color: #9CB5D2;
    font-family: 'Poppins', monospace;
}
.lc-op-empty {
    font-size: 14px;
    color: #9CB5D2;
}
.lc-card-footer {
    margin-top: auto;
    padding-top: 16px;
}
.lc-edit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    height: 40px;
    border-radius: 10px;
    border: 1px solid #1D4670;
    background: transparent;
    color: #9CB5D2;
    font-size: 13px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    text-decoration: none;
    transition: all .2s;
}
.lc-edit-btn:hover {
    border-color: #0987F5;
    background: rgba(9, 135, 245, .08);
    color: #fff;
}

@media (max-width: 1024px) {
    .lc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .lc-grid { grid-template-columns: 1fr; }
    .lc-card { min-height: auto; }
}
</style>
@endsection
