@props([
    'user' => null,
    'name' => null,
    'src' => null,
    'size' => 36,
    'radius' => '50%',
])

@php
    $displayName = trim((string) ($name ?: ($user?->name ?? 'User')));
    $avatarSource = $src ?: ($user?->avatar ? media_url($user->avatar) : null);
    $initial = mb_strtoupper(mb_substr($displayName ?: 'U', 0, 1));
@endphp

<span
    {{ $attributes->except('style')->class(['user-profile-avatar']) }}
    style="display:inline-flex;width:{{ (int) $size }}px;height:{{ (int) $size }}px;flex:0 0 {{ (int) $size }}px;align-items:center;justify-content:center;overflow:hidden;border-radius:{{ $radius }};background:linear-gradient(135deg,#0987f5,#8b5cf6);color:#fff;font-size:{{ max(10, (int) $size * 0.38) }}px;font-weight:800;line-height:1;vertical-align:middle;{{ $attributes->get('style') }}"
    aria-label="Foto profil {{ $displayName }}"
    title="{{ $displayName }}"
>
    @if($avatarSource)
        <img src="{{ $avatarSource }}" alt="Foto profil {{ $displayName }}" style="display:block;width:100%;height:100%;object-fit:cover" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
        <span hidden style="width:100%;height:100%;align-items:center;justify-content:center">{{ $initial }}</span>
    @else
        <span style="display:flex;width:100%;height:100%;align-items:center;justify-content:center">{{ $initial }}</span>
    @endif
</span>
