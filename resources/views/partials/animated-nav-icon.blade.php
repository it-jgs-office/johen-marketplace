@switch($icon)
    @case('gem')
        <svg class="nav-animated-icon nav-icon-gem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path class="nav-icon-gem__facet" d="M10.5 3 8 9l4 13 4-13-2.5-6" />
            <g class="nav-icon-gem__body">
                <path d="M17 3a2 2 0 0 1 1.6.8l3 4a2 2 0 0 1 .013 2.382l-7.99 10.986a2 2 0 0 1-3.247 0l-7.99-10.986A2 2 0 0 1 2.4 7.8l2.998-3.997A2 2 0 0 1 7 3z" />
                <path d="M2 9h20" />
            </g>
        </svg>
        @break

    @case('gamepad')
        <svg class="nav-animated-icon nav-icon-gamepad" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <g class="nav-icon-gamepad__body">
                <path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z" />
                <g class="nav-icon-gamepad__pad">
                    <line x1="6" x2="10" y1="11" y2="11" />
                    <line x1="8" x2="8" y1="9" y2="13" />
                </g>
                <line class="nav-icon-gamepad__button nav-icon-gamepad__button--one" x1="15" x2="15.01" y1="12" y2="12" />
                <line class="nav-icon-gamepad__button nav-icon-gamepad__button--two" x1="18" x2="18.01" y1="10" y2="10" />
            </g>
        </svg>
        @break

    @case('clipboard-clock')
        <svg class="nav-animated-icon nav-icon-clipboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect class="nav-icon-clipboard__clip" x="8" y="2" width="8" height="4" rx="1" />
            <path d="M16 4h2a2 2 0 0 1 2 2v.832" />
            <path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h2" />
            <circle cx="16" cy="16" r="6" />
            <path class="nav-icon-clipboard__hand" d="M16 14v2.2" />
            <path d="m16 16.2 1.6 1" />
        </svg>
        @break

    @case('trophy')
        <svg class="nav-animated-icon nav-icon-trophy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <g class="nav-icon-trophy__body">
                <path d="M10 14.66V17a1 1 0 0 1-1 1 2 2 0 0 0-2 2v2" />
                <path d="M14 14.66V17a1 1 0 0 0 1 1 2 2 0 0 1 2 2v2" />
                <path d="M17.916 10H19.5A2.5 2.5 0 0 0 22 7.5V5a1 1 0 0 0-1-1h-3" />
                <path d="M4 22h16" />
                <path d="M6 9a6 6 0 0 0 12 0V3a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1z" />
                <path d="M6.084 10H4.5A2.5 2.5 0 0 1 2 7.5V5a1 1 0 0 1 1-1h3" />
            </g>
        </svg>
        @break

    @case('paperclip')
        <svg class="nav-animated-icon nav-icon-paperclip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path class="nav-icon-paperclip__body" d="m16 6-8.414 8.586a2 2 0 0 0 2.829 2.829l8.414-8.586a4 4 0 1 0-5.657-5.657l-8.379 8.551a6 6 0 1 0 8.485 8.485l8.379-8.551" />
        </svg>
        @break

    @case('shopping-cart')
        <svg class="nav-animated-icon nav-icon-cart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <g class="nav-icon-cart__body">
                <circle class="nav-icon-cart__wheel nav-icon-cart__wheel--left" cx="8" cy="21" r="1" />
                <circle class="nav-icon-cart__wheel nav-icon-cart__wheel--right" cx="19" cy="21" r="1" />
                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
            </g>
        </svg>
        @break
@endswitch
