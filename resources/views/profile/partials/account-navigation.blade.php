<aside class="account-hub-sidebar" aria-label="Menu akun">
    <div class="account-hub-brand">
        <span class="account-hub-brand-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        </span>
        <span class="account-hub-brand-copy">
            <small>Pusat akun</small>
            <strong>Johen Gaming</strong>
        </span>
    </div>

    <nav class="account-hub-nav" aria-label="Navigasi akun">
        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'is-active' : '' }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            Profil Saya
        </a>
        @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 9v12"/></svg>
                Admin Panel
            </a>
        @else
            <a href="{{ route('orders.my') }}" class="{{ request()->routeIs('orders.my') ? 'is-active' : '' }}" @if(request()->routeIs('orders.my')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12l2 4v13H4V7l2-4Z"/><path d="M4 8h16M9 12h6"/></svg>
                Pesanan Saya
            </a>
            <a href="{{ route('points.index') }}" class="{{ request()->routeIs('points.*') ? 'is-active' : '' }}" @if(request()->routeIs('points.*')) aria-current="page" @endif>
                <x-johen-point-icon size="24" />
                Johen Point
            </a>
            <a href="{{ route('jual-beli-akun.orders') }}" class="{{ request()->routeIs('jual-beli-akun.orders') ? 'is-active' : '' }}" @if(request()->routeIs('jual-beli-akun.orders')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M5 17c.5-2 2-3 3-3s2.5 1 3 3M14 9h5M14 13h5"/></svg>
                Pesanan Akun
            </a>
            <a href="{{ route('vouchers.index') }}" class="{{ request()->routeIs('vouchers.*') ? 'is-active' : '' }}" @if(request()->routeIs('vouchers.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v5a2 2 0 0 0 0 4v5H4v-5a2 2 0 0 0 0-4V5Z"/><path d="M12 5v14" stroke-dasharray="2 3"/></svg>
                Voucher Saya
            </a>
            <a href="{{ route('reviews.my') }}" class="{{ request()->routeIs('reviews.my') ? 'is-active' : '' }}" @if(request()->routeIs('reviews.my')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10Z"/><path d="m12 6 1.2 2.4 2.7.4-2 1.9.5 2.7-2.4-1.3-2.4 1.3.5-2.7-2-1.9 2.7-.4L12 6Z"/></svg>
                Ulasan Saya
            </a>
            <a href="{{ route('my-inquiries') }}" class="{{ request()->routeIs('my-inquiries') ? 'is-active' : '' }}" @if(request()->routeIs('my-inquiries')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                Riwayat Pesan
            </a>
        @endif
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="account-hub-logout">
        @csrf
        <button type="submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>
            Keluar
        </button>
    </form>
</aside>
@once
    @push('scripts')
        <script>
            document.querySelectorAll('.account-hub-nav').forEach(function (nav) {
                var active = nav.querySelector('[aria-current="page"]');
                if (active && nav.scrollWidth > nav.clientWidth) {
                    nav.scrollLeft = active.offsetLeft - nav.offsetLeft - (nav.clientWidth - active.clientWidth) / 2;
                }
            });
        </script>
    @endpush
@endonce
