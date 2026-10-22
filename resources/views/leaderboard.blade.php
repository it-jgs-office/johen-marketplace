@extends('layouts.topup')

@section('title', 'Leaderboard — ' . config('app.name'))

@section('content')
<div class="lb-page">
    <div class="lb-hero">
        <h1 class="lb-hero-title">Leaderboard</h1>
        <p class="lb-hero-desc">10 besar pengguna dengan transaksi terbanyak</p>
    </div>

    <div class="lb-filters">
        <div class="lb-filter-card lb-filter-card--narrow">
            <div class="lb-filter-label">Jenis Layanan</div>
            <div class="lb-toggle-group">
                <button class="lb-toggle lb-toggle--active" data-service="topup">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12H4"/><path d="M12 4v16"/></svg>
                    Top Up
                </button>
                <button class="lb-toggle" data-service="joki">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    Joki
                </button>
            </div>
        </div>

        <div class="lb-filter-card lb-filter-card--wide">
            <div class="lb-filter-label">Pilih Game</div>
            <div class="lb-pills-scroll-wrap">
                <button class="lb-scroll-btn lb-scroll-left" id="lbScrollLeft" aria-label="Scroll left">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <div class="lb-game-pills" id="lbGamePills">
                    <button class="lb-pill lb-pill--active" data-game="all">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h.01M10 12h.01M14 12h.01M18 12h.01"/></svg>
                        Semua Game
                    </button>
                    @foreach($popularBrands as $b)
                        <button class="lb-pill" data-game="{{ $b->name }}">
                            @if($b->thumbnail_url)
                                <img src="{{ $b->thumbnail_url }}" alt="" class="lb-pill-img">
                            @else
                                <span class="lb-pill-dot"></span>
                            @endif
                            {{ $b->name }}
                        </button>
                    @endforeach
                </div>
                <button class="lb-scroll-btn lb-scroll-right" id="lbScrollRight" aria-label="Scroll right">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="lb-updated">Data diperbarui secara real-time</div>

    <div class="lb-grid" id="lbGrid">
        <div class="lb-card">
            <div class="lb-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Top 10 — Hari ini
                <select id="lbSortDaily" class="lb-filter-select lb-filter-select--inline" data-period="daily">
                    <option value="largest">Terbesar</option>
                    <option value="most">Terbanyak</option>
                </select>
            </div>
            <div class="lb-list" data-period-list="daily">
                @foreach($leaderboard['today'] as $u)
                    <div class="lb-row">
                        <div class="lb-rank-badge lb-rank-badge--{{ $u['rank'] <= 3 ? $u['rank'] : 'other' }}">
                            {{ $u['rank'] }}
                        </div>
                        <span class="lb-user"><x-user-avatar :name="$u['name']" :src="$u['avatar'] ?? null" :size="28" /><span class="lb-name">{{ $u['name'] }}</span></span>
                        <span class="lb-amount">Rp{{ number_format($u['amount'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <button class="lb-btn" onclick="window.location='{{ route("leaderboard.detail", "daily") }}'">Lihat Selengkapnya →</button>
        </div>

        <div class="lb-card">
            <div class="lb-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Top 10 — Minggu ini
                <select id="lbSortWeekly" class="lb-filter-select lb-filter-select--inline" data-period="weekly">
                    <option value="largest">Terbesar</option>
                    <option value="most">Terbanyak</option>
                </select>
            </div>
            <div class="lb-list" data-period-list="weekly">
                @foreach($leaderboard['week'] as $u)
                    <div class="lb-row">
                        <div class="lb-rank-badge lb-rank-badge--{{ $u['rank'] <= 3 ? $u['rank'] : 'other' }}">
                            {{ $u['rank'] }}
                        </div>
                        <span class="lb-user"><x-user-avatar :name="$u['name']" :src="$u['avatar'] ?? null" :size="28" /><span class="lb-name">{{ $u['name'] }}</span></span>
                        <span class="lb-amount">Rp{{ number_format($u['amount'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <button class="lb-btn" onclick="window.location='{{ route("leaderboard.detail", "weekly") }}'">Lihat Selengkapnya →</button>
        </div>

        <div class="lb-card">
            <div class="lb-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                Top 10 — Bulan ini
                <select id="lbSortMonthly" class="lb-filter-select lb-filter-select--inline" data-period="monthly">
                    <option value="largest">Terbesar</option>
                    <option value="most">Terbanyak</option>
                </select>
            </div>
            <div class="lb-list" data-period-list="monthly">
                @foreach($leaderboard['month'] as $u)
                    <div class="lb-row">
                        <div class="lb-rank-badge lb-rank-badge--{{ $u['rank'] <= 3 ? $u['rank'] : 'other' }}">
                            {{ $u['rank'] }}
                        </div>
                        <span class="lb-user"><x-user-avatar :name="$u['name']" :src="$u['avatar'] ?? null" :size="28" /><span class="lb-name">{{ $u['name'] }}</span></span>
                        <span class="lb-amount">Rp{{ number_format($u['amount'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <button class="lb-btn" onclick="window.location='{{ route("leaderboard.detail", "monthly") }}'">Lihat Selengkapnya →</button>
        </div>
    </div>

    <div class="lb-skeleton" id="lbSkeleton" style="display:none">
        <div class="lb-card">@for($i=0;$i<3;$i++)<div class="lb-sk-title"></div><div class="lb-sk-list">@for($j=0;$j<5;$j++)<div class="lb-sk-row"><div class="lb-sk-circle"></div><div class="lb-sk-line"></div><div class="lb-sk-line lb-sk-line--short"></div></div>@endfor</div>@endfor</div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.lb-toggle');
    const pills = document.querySelectorAll('.lb-pill');
    const grid = document.getElementById('lbGrid');
    const skeleton = document.getElementById('lbSkeleton');

    toggles.forEach(btn => {
        btn.addEventListener('click', function() {
            toggles.forEach(b => b.classList.remove('lb-toggle--active'));
            this.classList.add('lb-toggle--active');

            const isJoki = this.dataset.service === 'joki';
            pills.forEach(p => {
                if (isJoki && p.dataset.game !== 'all' && p.dataset.game !== 'Mobile Legends') {
                    p.style.display = 'none';
                } else {
                    p.style.display = '';
                }
            });

            pills.forEach(p => p.classList.remove('lb-pill--active'));
            if (isJoki) {
                const mlPill = document.querySelector('.lb-pill[data-game="Mobile Legends"]');
                if (mlPill) mlPill.classList.add('lb-pill--active');
            } else {
                const allPill = document.querySelector('.lb-pill[data-game="all"]');
                if (allPill) allPill.classList.add('lb-pill--active');
            }

            fetchLeaderboard();
        });
    });

    // ---- HORIZONTAL SCROLL PILLS ----
    const pillsWrap = document.getElementById('lbGamePills');
    const scrollLeft = document.getElementById('lbScrollLeft');
    const scrollRight = document.getElementById('lbScrollRight');

    function updateScrollBtns() {
        const atStart = pillsWrap.scrollLeft <= 4;
        const atEnd = pillsWrap.scrollLeft + pillsWrap.clientWidth >= pillsWrap.scrollWidth - 4;
        scrollLeft.classList.toggle('disabled', atStart);
        scrollRight.classList.toggle('disabled', atEnd);
    }

    updateScrollBtns();
    pillsWrap.addEventListener('scroll', updateScrollBtns);
    window.addEventListener('resize', updateScrollBtns);

    scrollLeft.addEventListener('click', () => {
        pillsWrap.scrollBy({ left: -280, behavior: 'smooth' });
    });
    scrollRight.addEventListener('click', () => {
        pillsWrap.scrollBy({ left: 280, behavior: 'smooth' });
    });

    // update position when pills visibility changes (joki toggle)
    const pillObserver = new MutationObserver(updateScrollBtns);
    pillObserver.observe(pillsWrap, { attributes: true, subtree: true, attributeFilter: ['style'] });

    pills.forEach(btn => {
        btn.addEventListener('click', function() {
            pills.forEach(b => b.classList.remove('lb-pill--active'));
            this.classList.add('lb-pill--active');
            fetchLeaderboard();
        });
    });

    function fetchLeaderboard() {
        fetchPeriod('daily');
        fetchPeriod('weekly');
        fetchPeriod('monthly');
    }

    function updateCard(listEl, entries) {
        listEl.innerHTML = entries.map(u => `
            <div class="lb-row">
                <div class="lb-rank-badge lb-rank-badge--${u.rank <= 3 ? u.rank : 'other'}">${u.rank}</div>
                <span class="lb-user">
                    <span class="lb-avatar">
                        ${u.avatar ? `<img src="${escapeHtml(u.avatar)}" alt="Foto profil ${escapeHtml(u.name)}" onerror="this.hidden=true;this.nextElementSibling.hidden=false"><span hidden>${initialOf(u.name)}</span>` : `<span>${initialOf(u.name)}</span>`}
                    </span>
                    <span class="lb-name">${escapeHtml(u.name)}</span>
                </span>
                <span class="lb-amount">Rp${new Intl.NumberFormat('id-ID').format(u.amount)}</span>
            </div>
        `).join('');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function initialOf(value) {
        return escapeHtml(String(value || 'U').trim().charAt(0).toUpperCase() || 'U');
    }

    const sortSelects = {
        daily: document.getElementById('lbSortDaily'),
        weekly: document.getElementById('lbSortWeekly'),
        monthly: document.getElementById('lbSortMonthly')
    };

    function fetchPeriod(period) {
        const serviceType = document.querySelector('.lb-toggle--active')?.dataset.service || 'topup';
        const game = document.querySelector('.lb-pill--active')?.dataset.game || 'all';
        const sort = sortSelects[period].value;
        const listEl = document.querySelector(`.lb-list[data-period-list="${period}"]`);

        const params = new URLSearchParams({ service_type: serviceType, game: game, period: period, sort: sort });

        fetch('{{ route("leaderboard") }}?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            updateCard(listEl, data.data || []);
        })
        .catch(() => {});
    }

    Object.keys(sortSelects).forEach(period => {
        sortSelects[period].addEventListener('change', function() {
            fetchPeriod(period);
        });
    });
});
</script>
@endpush
@endsection
