@php
    $last = $conv->lastMessage;
    $preview = 'Belum ada pesan';
    if ($last) {
        $preview = match ($last->message_type) {
            'image' => '📷 Foto',
            'video' => '🎬 Video',
            'audio' => '🎤 Pesan suara',
            default => mb_strlen((string) $last->message) > 60 ? mb_substr((string) $last->message, 0, 60) . '…' : (string) $last->message,
        };
    }
    $initial = strtoupper(mb_substr((string) ($conv->guest_name ?: 'G'), 0, 1));
    $closed = $conv->status === 'closed';
    $ended = $conv->status === 'ended';
    $isArchived = !empty($conv->archived_at);
    $lcPreviewIcon = $last ? match ($last->message_type) { 'image' => 'image', 'video' => 'video', 'audio' => 'microphone', default => null } : null;
@endphp
<div class="lc-item {{ $closed ? 'is-closed' : '' }} {{ $ended ? 'is-ended' : '' }} {{ $conv->admin_unread_count > 0 ? 'has-unread' : '' }} {{ $conv->is_pinned ? 'pinned' : '' }}"
     id="conv-{{ $conv->id }}"
     data-id="{{ $conv->id }}"
     data-name="{{ $conv->guest_name }}"
     data-channel="{{ $conv->channel?->name }}"
     data-preview="{{ $preview }}"
     data-status="{{ $conv->status }}"
     data-unread="{{ $conv->admin_unread_count }}"
     onclick="openConversation({{ $conv->id }})">
    <x-user-avatar :user="$conv->user" :name="$conv->guest_name ?: 'Guest'" :size="42" radius="12px" class="lc-item-avatar" />
    <div class="lc-item-body">
        <div class="lc-item-row1">
            <span class="lc-online-dot {{ ($closed || $ended) ? 'off' : '' }}"></span>
            <span class="lc-item-name">{{ $conv->guest_name ?: 'Guest' }}</span>
            @if ($ended)
                <span class="lc-item-ended">Sesi berakhir</span>
            @elseif ($closed)
                <i class="fas fa-lock" style="font-size:10px;color:#8FA8C4;flex-shrink:0" title="Ditutup"></i>
            @endif
            @if ($conv->is_pinned)
                <i class="fas fa-thumbtack lc-item-pin" title="Disematkan"></i>
            @endif
        </div>
        <div class="lc-item-channel">{{ $conv->channel?->name }}</div>
        <div class="lc-item-preview">
            @if($last && $lcPreviewIcon && !($last->message ?? null))
                <i class="fas fa-{{ $lcPreviewIcon }}" style="color:#5B9CF5;margin-right:4px"></i>
                {{ $last->message_type === 'audio' ? 'Pesan suara' : ucfirst($last->message_type) }}
            @else
                {{ $last->message ?? 'Belum ada pesan' }}
            @endif
        </div>
    </div>
    <div class="lc-item-meta">
        <span class="lc-item-time">{{ $conv->last_message_at ? $conv->last_message_at->format('H:i') : '' }}</span>
        <div style="display:flex;align-items:center;gap:6px">
            @if ($conv->admin_unread_count > 0)
                <span class="lc-item-unread">{{ $conv->admin_unread_count > 99 ? '99+' : $conv->admin_unread_count }}</span>
            @endif
        </div>
    </div>
    <div class="lc-item-actions">
        <button class="lc-item-arrow" onclick="event.stopPropagation();toggleItemDropdown(this)" title="Aksi" aria-label="Menu aksi"><i class="fas fa-ellipsis-v"></i></button>
        <div class="lc-item-dropdown">
            @if ($isArchived)
                <button onclick="event.stopPropagation();unarchiveChat({{ $conv->id }})"><i class="fas fa-archive"></i> Batal Arsip</button>
            @else
                <button onclick="event.stopPropagation();archiveChat({{ $conv->id }})"><i class="fas fa-archive"></i> Arsip Chat</button>
            @endif
            <button class="danger" onclick="event.stopPropagation();deleteChat({{ $conv->id }})"><i class="fas fa-trash-alt"></i> Hapus Chat</button>
        </div>
    </div>
</div>
