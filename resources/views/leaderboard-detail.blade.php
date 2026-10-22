@extends('layouts.topup')

@section('title', $title . ' — ' . config('app.name'))

@section('content')
<div class="lbd-page">
    <div class="lbd-hero">
        <div class="lbd-hero-top">
            <h1 class="lbd-title">{{ $title }}</h1>
            <a href="{{ route('leaderboard') }}" class="lbd-back-btn">← Kembali</a>
        </div>
        <p class="lbd-hero-desc">Berikut merupakan data 10 pembelian terbanyak yang dilakukan oleh pelanggan kami.</p>
    </div>

    <div class="lbd-filter" id="lbdFilter">
        <div class="lbd-filter-label">Filter Nominal Top Up</div>
        <div class="lbd-filter-row">
            <input type="number" id="lbdMinNominal" min="0" step="1000" placeholder="Min Rp" class="lbd-filter-input">
            <span class="lbd-filter-sep">–</span>
            <input type="number" id="lbdMaxNominal" min="0" step="1000" placeholder="Maks Rp" class="lbd-filter-input">
            <button class="lbd-filter-btn" id="lbdFilterApply">Terapkan</button>
            <button class="lbd-filter-btn lbd-filter-btn--ghost" id="lbdFilterReset">Reset</button>
        </div>
    </div>

    <div class="lbd-layout">
        <main class="lbd-main">
            <div class="lbd-table-wrap" id="lbdTableWrap">
                <div class="lbd-skeleton" id="lbdSkeleton">
                    @for($i=0;$i<5;$i++)
                        <div class="lbd-sk-row">
                            <div class="lbd-sk-circle"></div>
                            <div class="lbd-sk-line"></div>
                            <div class="lbd-sk-line"></div>
                            <div class="lbd-sk-line lbd-sk-line--short"></div>
                            <div class="lbd-sk-line lbd-sk-line--xs"></div>
                        </div>
                    @endfor
                </div>
                <table class="lbd-table" id="lbdTable">
                    <thead>
                        <tr class="lbd-thead">
                            <th class="lbd-th lbd-th--rank">Peringkat</th>
                            <th class="lbd-th">Pelanggan</th>
                            <th class="lbd-th">Game</th>
                            <th class="lbd-th lbd-th--amount">Total Pembelian</th>
                            <th class="lbd-th lbd-th--count">Jumlah Top Up</th>
                            <th class="lbd-th lbd-th--time">Waktu Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="lbd-tbody" id="lbdTbody"></tbody>
                </table>

                <div class="lbd-table-footer">
                    <div class="lbd-info">Menampilkan <span id="lbdInfoStart">0</span> data dari <span id="lbdInfoTotal">0</span> data</div>
                    <div class="lbd-pagination" id="lbdPagination"></div>
                </div>
            </div>
        </main>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const period = '{{ $period }}';
    const tbody = document.getElementById('lbdTbody');
    const skeleton = document.getElementById('lbdSkeleton');
    const table = document.getElementById('lbdTable');
    const paginationEl = document.getElementById('lbdPagination');
    const infoStart = document.getElementById('lbdInfoStart');
    const infoTotal = document.getElementById('lbdInfoTotal');
    const minNominal = document.getElementById('lbdMinNominal');
    const maxNominal = document.getElementById('lbdMaxNominal');
    const filterApply = document.getElementById('lbdFilterApply');
    const filterReset = document.getElementById('lbdFilterReset');

    const initialParams = new URLSearchParams(location.search);
    let currentPage = parseInt(initialParams.get('page')) || 1;
    if (initialParams.get('min_nominal')) minNominal.value = initialParams.get('min_nominal');
    if (initialParams.get('max_nominal')) maxNominal.value = initialParams.get('max_nominal');

    function updateUrl() {
        const params = new URLSearchParams();
        if (currentPage > 1) params.set('page', currentPage);
        if (minNominal.value) params.set('min_nominal', minNominal.value);
        if (maxNominal.value) params.set('max_nominal', maxNominal.value);
        const qs = params.toString();
        const newUrl = '/leaderboard/' + period + (qs ? '?' + qs : '');
        window.history.replaceState(null, '', newUrl);
    }

    function fetchData() {
        skeleton.style.display = 'flex';
        table.style.display = 'none';
        paginationEl.innerHTML = '';

        const params = new URLSearchParams({
            period: period,
            page: currentPage,
            per_page: 10
        });
        if (minNominal.value) params.set('min_nominal', minNominal.value);
        if (maxNominal.value) params.set('max_nominal', maxNominal.value);

        fetch('{{ route("api.leaderboard") }}?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            renderTable(data);
            renderPagination(data);
            infoStart.textContent = data.total > 0 ? ((data.current_page - 1) * data.per_page + 1) + '-' + Math.min(data.current_page * data.per_page, data.total) : '0';
            infoTotal.textContent = data.total;
            skeleton.style.display = 'none';
            table.style.display = '';
        })
        .catch(() => {
            skeleton.style.display = 'none';
            table.style.display = '';
        });
    }

    function renderTable(data) {
        tbody.innerHTML = data.data.map((u, i) => `
            <tr class="lbd-row${i % 2 === 1 ? ' lbd-row--alt' : ''}">
                <td class="lbd-cell lbd-cell--rank">
                    <span class="lbd-rank-badge lbd-rank-badge--${u.rank <= 3 ? u.rank : 'other'}">${u.rank}</span>
                </td>
                <td class="lbd-cell lbd-cell--name"><span class="lbd-user"><span class="lb-avatar">${u.avatar_url ? `<img src="${escapeHtml(u.avatar_url)}" alt="Foto profil ${escapeHtml(u.customer)}" onerror="this.hidden=true;this.nextElementSibling.hidden=false"><span hidden>${initialOf(u.customer)}</span>` : `<span>${initialOf(u.customer)}</span>`}</span><span>${escapeHtml(u.customer)}</span></span></td>
                <td class="lbd-cell lbd-cell--game">${u.game || 'Top Up'}</td>
                <td class="lbd-cell lbd-cell--amount">Rp${new Intl.NumberFormat('id-ID').format(u.total_purchase)}</td>
                <td class="lbd-cell lbd-cell--count">${u.total_transactions}x</td>
                <td class="lbd-cell lbd-cell--time">${u.last_transaction}</td>
            </tr>
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

    function renderPagination(data) {
        if (data.last_page <= 1) { paginationEl.innerHTML = ''; return; }

        let html = '';

        if (data.current_page > 1) {
            html += `<button class="lbd-page-btn" data-page="${data.current_page - 1}">‹ Sebelumnya</button>`;
        }

        const maxVisible = 5;
        let start = Math.max(1, data.current_page - Math.floor(maxVisible / 2));
        let end = Math.min(data.last_page, start + maxVisible - 1);
        if (end - start < maxVisible - 1) { start = Math.max(1, end - maxVisible + 1); }

        if (start > 1) {
            html += `<button class="lbd-page-btn" data-page="1">1</button>`;
            if (start > 2) html += `<span class="lbd-page-dots">…</span>`;
        }

        for (let i = start; i <= end; i++) {
            html += `<button class="lbd-page-btn ${i === data.current_page ? 'lbd-page-btn--active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (end < data.last_page) {
            if (end < data.last_page - 1) html += `<span class="lbd-page-dots">…</span>`;
            html += `<button class="lbd-page-btn" data-page="${data.last_page}">${data.last_page}</button>`;
        }

        if (data.current_page < data.last_page) {
            html += `<button class="lbd-page-btn" data-page="${data.current_page + 1}">Selanjutnya ›</button>`;
        }

        paginationEl.innerHTML = html;

        paginationEl.querySelectorAll('.lbd-page-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentPage = parseInt(this.dataset.page);
                updateUrl();
                fetchData();
                window.scrollTo({ top: document.querySelector('.lbd-main').offsetTop - 100, behavior: 'smooth' });
            });
        });
    }

    fetchData();

    function resetPageAndFetch() {
        currentPage = 1;
        updateUrl();
        fetchData();
    }

    filterApply.addEventListener('click', resetPageAndFetch);
    filterReset.addEventListener('click', function() {
        minNominal.value = '';
        maxNominal.value = '';
        resetPageAndFetch();
    });
    minNominal.addEventListener('keydown', function(e) { if (e.key === 'Enter') resetPageAndFetch(); });
    maxNominal.addEventListener('keydown', function(e) { if (e.key === 'Enter') resetPageAndFetch(); });
});
</script>
@endpush
@endsection
