@extends('admin.layouts.app')
@section('title', 'Live Chat Channels')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Channels</h1>
        <p class="page-subtitle">Daftar channel live chat</p>
    </div>
</div>

<div class="card-glass">
    <div class="table-wrap">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Slug</th>
                    <th>Active Conversations</th>
                    <th>Admins</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channels as $channel)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--purple),var(--purple-light));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0">
                                {{ substr($channel->name, 0, 2) }}
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;color:var(--text)">{{ $channel->name }}</div>
                                <div style="font-size:11px;color:var(--text-mute)">{{ $channel->description ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td><code style="font-size:12px;color:var(--text-dim)">{{ $channel->slug }}</code></td>
                    <td><span class="badge badge-info">{{ $channel->conversations->count() }}</span></td>
                    <td>
                        @forelse($channel->operators as $op)
                        <span class="badge {{ $op->is_active ? 'badge-success' : 'badge-neutral' }}" style="display:inline-flex;align-items:center;gap:5px;margin:2px">
                            <x-user-avatar :user="$op->user" :name="$op->user->name ?? 'N/A'" :size="20" />
                            {{ $op->user->name ?? 'N/A' }}
                        </span>
                        @empty
                        <span style="color:var(--text-mute);font-size:12px">Belum ada admin</span>
                        @endforelse
                    </td>
                    <td>
                        @if($channel->is_active)
                        <span class="badge badge-success badge-pulse">Active</span>
                        @else
                        <span class="badge badge-neutral">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm" onclick="toggleChannel({{ $channel->id }})">
                            {{ $channel->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon">🎮</div>
                            <div>Belum ada channel</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';

async function toggleChannel(id) {
    try {
        const res = await fetch(`/admin/live-chat/channels/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (res.ok) location.reload();
    } catch (e) {}
}
</script>
@endsection
