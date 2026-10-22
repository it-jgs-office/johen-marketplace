@extends('admin.layouts.app')
@section('title', 'Live Chat Conversations')

@push('styles')
<style>
.lc-conv-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    width: 100%;
}
.lc-conv-filters select.input-field {
    flex: 0 1 auto;
    min-width: 150px;
    width: auto;
}
.lc-conv-filters .input-field[name="search"] {
    flex: 1 1 170px;
    max-width: 320px;
    min-width: 170px;
}
.lc-conv-filters .btn { flex: 0 0 auto; }
.lc-conv-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.lc-conv-wrap table { min-width: 900px; }
.lc-conv-wrap .lc-conv-lastmsg { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

@media (max-width: 1023px) {
    .lc-conv-wrap { overflow: visible; border: 0; border-radius: 0; background: transparent; min-width: 0; }
    .lc-conv-wrap table,
    .lc-conv-wrap thead,
    .lc-conv-wrap tbody,
    .lc-conv-wrap tr,
    .lc-conv-wrap td { display: block; width: 100%; box-sizing: border-box; }
    .lc-conv-wrap table { min-width: 0; border-spacing: 0; }
    .lc-conv-wrap thead { display: none; }
    .lc-conv-wrap tbody { display: flex; flex-direction: column; gap: 12px; padding: 2px; }
    .lc-conv-wrap tbody tr {
        background: var(--bg-card);
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        padding: 8px 14px 12px;
        border-bottom: 1px solid var(--glass-border);
    }
    .lc-conv-wrap tbody tr:hover { background: var(--bg-card); transform: none; }
    .lc-conv-wrap td {
        display: grid;
        grid-template-columns: 104px 1fr;
        align-items: center;
        gap: 3px 12px;
        padding: 4px 0 !important;
        border: 0 !important;
    }
    .lc-conv-wrap td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.66rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--text-dim);
    }
    .lc-conv-wrap td:not([data-label])::before { display: none; }
    .lc-conv-wrap td:not([data-label]) { display: block; }
    .lc-conv-wrap td[data-label="User"] { grid-template-columns: 1fr; padding-top: 8px; }
    .lc-conv-wrap td[data-label="User"]::before { grid-column: 1 / -1; margin-bottom: 2px; }
    .lc-conv-wrap .lc-conv-lastmsg { max-width: 100%; white-space: normal; overflow: visible; text-overflow: clip; }
    .lc-conv-wrap .lc-conv-lastmsg div { white-space: normal; overflow: visible; }
    .lc-conv-wrap td[data-label="Aksi"] {
        margin-top: 6px; padding-top: 10px !important;
        box-shadow: inset 0 1px 0 0 var(--glass-border);
    }
    .lc-conv-wrap td[data-label="Aksi"] .btn { justify-self: end; min-width: 96px; }
}

@media (max-width: 767px) {
    .lc-conv-filters select.input-field {
        min-width: 0 !important;
        flex: 1 1 44%;
        width: auto;
    }
    .lc-conv-filters .input-field[name="search"] {
        flex: 1 1 100%;
        max-width: none;
        min-width: 0 !important;
    }
    .lc-conv-filters .btn { flex: 1 1 44%; }
}

@media (max-width: 479px) {
    .lc-conv-filters select.input-field,
    .lc-conv-filters .input-field[name="search"],
    .lc-conv-filters .btn {
        flex: 1 1 100%;
        max-width: none;
        width: 100%;
    }
    .lc-conv-wrap td { grid-template-columns: 96px 1fr; }
    .lc-conv-wrap tbody { gap: 10px; }
}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Conversations</h1>
        <p class="page-subtitle">Daftar percakapan live chat</p>
    </div>
</div>

<div class="card-glass">
    <div class="card-header">
        <form method="GET" class="lc-conv-filters">
            <select name="channel_id" class="input-field" onchange="this.form.submit()">
                <option value="">Semua Channel</option>
                @foreach($channels ?? [] as $channel)
                <option value="{{ $channel->id }}" {{ request('channel_id') == $channel->id ? 'selected' : '' }}>{{ $channel->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input-field" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="ended" {{ request('status') == 'ended' ? 'selected' : '' }}>Ended</option>
                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
            <input type="text" name="search" class="input-field" placeholder="Cari user..." value="{{ request('search') }}" autocomplete="off">
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['channel_id', 'status', 'search']))
            <a href="{{ route('admin.live-chat.conversations') }}" class="btn">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-wrap lc-conv-wrap">
        <table class="w-full">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Channel</th>
                    <th>Last Message</th>
                    <th>Time</th>
                    <th>Unread</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversations as $conv)
                <tr>
                    <td data-label="User">
                        <div style="display:flex;align-items:center;gap:8px">
                            <x-user-avatar :user="$conv->isGuest() ? null : $conv->user" :name="$conv->isGuest() ? ($conv->guest_name ?? 'Guest') : ($conv->user->name ?? 'User')" :size="32" radius="8px" />
                            <div>
                                <div style="font-weight:600;font-size:13px;color:var(--text)">{{ $conv->isGuest() ? ($conv->guest_name ?? 'Guest') : ($conv->user->name ?? 'User') }}</div>
                                <div style="font-size:11px;color:var(--text-mute)">{{ $conv->isGuest() ? 'Pengunjung' : ($conv->user->email ?? '') }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Channel"><span class="badge badge-info">{{ $conv->channel->name ?? '' }}</span></td>
                    <td class="lc-conv-lastmsg" data-label="Last Message" style="color:var(--text-dim);font-size:13px">
                        {{ $conv->last_message?->message ?? '—' }}
                    </td>
                    <td data-label="Time" style="font-size:12px;color:var(--text-mute)">{{ $conv->last_message_at?->diffForHumans() ?? '—' }}</td>
                    <td data-label="Unread">
                        @if($conv->admin_unread_count > 0)
                        <span class="badge badge-error">{{ $conv->admin_unread_count }}</span>
                        @else
                        <span style="color:var(--text-mute)">0</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        @if($conv->status === 'open')
                        <span class="badge badge-success">Open</span>
                        @elseif($conv->status === 'pending')
                        <span class="badge badge-warning">Pending</span>
                        @elseif($conv->status === 'ended')
                        <span class="badge badge-neutral">Ended</span>
                        @else
                        <span class="badge badge-neutral">Closed</span>
                        @endif
                    </td>
                    <td data-label="Aksi">
                        <a href="{{ route('admin.live-chat.conversations.show', $conv) }}" class="btn btn-sm">Buka</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon">💬</div>
                            <div>Belum ada percakapan</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="padding:16px">
        {{ $conversations->withQueryString()->links('vendor.pagination.admin') }}
    </div>
</div>
@endsection
