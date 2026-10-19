@props(['size' => 20, 'alt' => ''])

<img
    src="{{ asset('assets/icon/johen-point.png') }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    {{ $attributes->class(['johen-point-icon']) }}
    style="display:inline-block;width:{{ $size }}px;height:{{ $size }}px;object-fit:contain;vertical-align:middle;flex:0 0 auto"
>
