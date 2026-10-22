@php($isArchived = !empty($conv->archived_at))
@php($isPinned = !empty($conv->is_pinned))
@php($lcContactName = $conv->user->name ?? ($conv->guest_name ?: ($conv->guest_email ?: 'User')))
<div class="lc-item {{ request('open') == $conv->id ? 'active' : '' }} {{ $isPinned ? 'pinned' : '' }}"
     data-id="{{ $conv->id }}"
     data-name="{{ strtolower($lcContactName) }}"
     data-channel="{{ strtolower($conv->channel->name ?? '') }}"
     data-unread="{{ $conv->admin_unread_count > 0 ? '1' : '0' }}"
     data-favorited="{{ $conv->is_favorited ? '1' : '0' }}"
     data-pinned="{{ $isPinned ? '1' : '0' }}"
     data-last="{{ $conv->last_message_at ? $conv->last_message_at->timestamp : 0 }}"
     onclick="openConversation({{ $conv->id }})"
     id="conv-{{ $conv->id }}">
    <x-user-avatar :user="$conv->user" :name="$lcContactName" :size="42" radius="12px" class="lc-item-avatar" />
    <div class="lc-item-body">
        <div class="lc-item-row1">
            <span class="lc-online-dot"></span>
            <span class="lc-item-name">{{ $lcContactName }}</span>
            <i class="fas fa-thumbtack lc-item-pin" title="Disematkan"></i>
        </div>
        <div class="lc-item-channel">{{ $conv->channel->name ?? '' }}@if($conv->isGuest() && $conv->guest_email) &bull; {{ $conv->guest_email }}@endif</div>
        @php($lcLastMsg = $conv->lastMessage)
        @php($lcPreviewIcon = $lcLastMsg ? match ($lcLastMsg->message_type) { 'image' => 'image', 'video' => 'video', 'audio' => 'microphone', default => null } : null)
        <div class="lc-item-preview">
            @if($lcLastMsg && $lcPreviewIcon && !($lcLastMsg->message ?? null))
                <i class="fas fa-{{ $lcPreviewIcon }}" style="color:#5B9CF5;margin-right:4px"></i>
                {{ $lcLastMsg->message_type === 'audio' ? 'Pesan suara' : ucfirst($lcLastMsg->message_type) }}
            @else
                {{ $lcLastMsg->message ?? 'Belum ada pesan' }}
            @endif
        </div>
    </div>
    <div class="lc-item-meta">
        <span class="lc-item-time">{{ $conv->last_message_at ? $conv->last_message_at->format('H:i') : '' }}</span>
        <div style="display:flex;align-items:center;gap:6px">
            <button class="lc-fav-btn {{ $conv->is_favorited ? 'active' : '' }}" onclick="event.stopPropagation();toggleFav({{ $conv->id }}, this)" title="Favorit">
                <i class="fas fa-star"></i>
            </button>
            @if($conv->admin_unread_count > 0)
            <span class="lc-item-unread">{{ $conv->admin_unread_count }}</span>
            @endif
        </div>
    </div>
    <div class="lc-item-actions">
        <button class="lc-item-arrow" onclick="event.stopPropagation();toggleItemDropdown(this)"><i class="fas fa-ellipsis-v"></i></button>
        <div class="lc-item-dropdown">
            <button class="lc-pin-action" onclick="event.stopPropagation();togglePin({{ $conv->id }}, this)"><i class="fas fa-thumbtack"></i> <span>{{ $isPinned ? 'Lepas Semat' : 'Sematkan Chat' }}</span></button>
            @if($isArchived)
            <button onclick="event.stopPropagation();unarchiveChat({{ $conv->id }})"><i class="fas fa-archive"></i> Batal Arsip</button>
            @else
            <button onclick="event.stopPropagation();archiveChat({{ $conv->id }})"><i class="fas fa-archive"></i> Arsip Chat</button>
            @endif
            <button class="danger" onclick="event.stopPropagation();deleteChat({{ $conv->id }})"><i class="fas fa-trash-alt"></i> Hapus Chat</button>
        </div>
    </div>
</div>
