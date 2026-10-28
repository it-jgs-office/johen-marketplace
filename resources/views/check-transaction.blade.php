@extends('layouts.topup')

@section('title', 'Cek Transaksi - ' . config('app.name'))

@section('content')
@php
    $recentStatusMap = [
        'success' => ['label' => 'Berhasil', 'class' => 'success'],
        'paid' => ['label' => 'Berhasil', 'class' => 'success'],
        'completed' => ['label' => 'Berhasil', 'class' => 'success'],
        'processing' => ['label' => 'Diproses', 'class' => 'processing'],
        'pending' => ['label' => 'Menunggu', 'class' => 'pending'],
        'refund' => ['label' => 'Refund', 'class' => 'refund'],
        'failed' => ['label' => 'Gagal', 'class' => 'failed'],
        'expired' => ['label' => 'Expired', 'class' => 'failed'],
        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'failed'],
    ];
@endphp

<section class="transaction-page">
    <div class="transaction-shell">
        <div class="card-glass transaction-check-card">
            <div class="transaction-check-heading">
                <h1>Cek Transaksi</h1>
                <p>Masukkan ID transaksi atau email kamu untuk melihat status pesanan.</p>
            </div>
            <form id="checkForm" class="modal-form transaction-form">
                <label for="checkInput">ID Transaksi / Email</label>
                <div class="transaction-input-row">
                    <input type="text" id="checkInput" required placeholder="JOHEN-XXXXXX atau email" class="input-field">
                    <button type="submit" class="btn btn-solid transaction-submit">
                        <i class="fas fa-search" aria-hidden="true"></i> Cek transaksi
                    </button>
                </div>
            </form>
            <div id="checkResult" class="transaction-result" aria-live="polite"></div>
        </div>

        <p class="recent-transactions-caption">Berikut ini terakhir data pesanan masuk terbaru <strong>JOHENGAMING.</strong></p>
        <div class="transaction-table-filters" role="tablist" aria-label="Filter jenis transaksi">
            <button type="button" class="transaction-table-filter is-active" data-transaction-filter="all" role="tab" aria-selected="true">Semua</button>
            <button type="button" class="transaction-table-filter" data-transaction-filter="topup" role="tab" aria-selected="false">Top Up</button>
            <button type="button" class="transaction-table-filter" data-transaction-filter="account" role="tab" aria-selected="false">Stock Akun</button>
        </div>

        <section class="recent-transactions recent-transactions--table" aria-label="Transaksi terbaru">
            <div class="transaction-table-wrap">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Nomor Invoice</th>
                            <th scope="col">No. Handphone</th>
                            <th scope="col">Harga</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                            @php($status = $recentStatusMap[$transaction['status']] ?? ['label' => ucfirst($transaction['status']), 'class' => 'failed'])
                            <tr data-transaction-type="{{ $transaction['type'] }}">
                                <td data-label="Tanggal">{{ $transaction['table_time'] ?? $transaction['time'] }}</td>
                                <td data-label="Nomor Invoice" class="transaction-table-reference">{{ $transaction['reference'] }}</td>
                                <td data-label="No. Handphone">{{ $transaction['phone'] }}</td>
                                <td data-label="Harga">IDR {{ number_format($transaction['total'], 0, ',', '.') }}</td>
                                <td data-label="Status"><span class="status-pill {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="transaction-table-empty">Belum ada transaksi terbaru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</section>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.transaction-page {
    min-height: 60vh;
    padding: 8rem var(--layout-gutter) 4rem;
}
.transaction-shell {
    width: 100%;
    max-width: 960px;
    margin: 0 auto;
}
.transaction-intro {
    max-width: 560px;
    margin: 0 auto 2rem;
    text-align: center;
}
.transaction-eyebrow {
    display: inline-block;
    margin-bottom: .45rem;
    color: var(--purple-light);
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
}
.transaction-intro h1 {
    margin: 0 0 .5rem;
    font-family: var(--font-display);
    font-size: clamp(1.6rem, 4vw, 2.15rem);
    font-weight: 800;
}
.transaction-intro p,
.recent-transactions-head p {
    margin: 0;
    color: var(--text-dim);
    font-size: .88rem;
}
.card-glass {
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--surface);
    transition: box-shadow .25s ease;
}
.card-glass:focus-within {
    box-shadow: 0 0 0 2px var(--purple), 0 8px 32px -12px var(--purple-glow);
}
.transaction-check-card {
    max-width: 560px;
    margin: 0 auto;
    padding: 1.5rem;
}
.transaction-form { display: block; }
.transaction-form label {
    display: block;
    margin-bottom: .5rem;
    color: var(--text-dim);
    font-size: .85rem;
    font-weight: 600;
}
.transaction-input-row {
    display: flex;
    gap: .75rem;
}
.transaction-input-row .input-field {
    flex: 1;
    min-width: 0;
    padding: .7rem .9rem;
    border: 1px solid var(--border);
    border-radius: 10px;
    outline: none;
    background: var(--surface);
    color: var(--text);
    font-size: .9rem;
}
.transaction-submit {
    padding: .7rem 1.35rem;
    white-space: nowrap;
}
.transaction-result { margin-top: 1rem; }
#checkInput:focus {
    border-color: var(--purple-light) !important;
    box-shadow: 0 0 0 3px var(--purple-glow) !important;
}
.status-box {
    padding: 1.25rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    background: var(--surface-2);
    animation: transactionFadeUp .3s ease;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .3rem .8rem;
    border-radius: 20px;
    background: rgba(16,185,129,.12);
    color: #34d399;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
    white-space: nowrap;
}
.status-pill.pending { background: rgba(245,158,11,.12); color: #f59e0b; }
.status-pill.processing { background: rgba(59,130,246,.12); color: #60a5fa; }
.status-pill.refund { background: rgba(148,163,184,.12); color: #94a3b8; }
.status-pill.failed { background: rgba(239,68,68,.12); color: #f87171; }
.recent-transactions {
    margin-top: 2.5rem;
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 20px;
    background: var(--surface);
    box-shadow: 0 24px 70px -42px rgba(0, 0, 0, .75);
}
.recent-transactions-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 2rem;
    padding: 1.4rem 1.5rem 1.15rem;
}
.recent-transactions-head .transaction-eyebrow { margin-bottom: .3rem; }
.recent-transactions-head h2 {
    margin: 0;
    font-family: var(--font-display);
    font-size: 1.2rem;
    font-weight: 800;
}
.recent-transactions-head p {
    max-width: 300px;
    text-align: right;
    font-size: .72rem;
}
.recent-tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem;
    padding: .55rem;
    border-block: 1px solid var(--border);
    background: var(--surface-2);
}
.recent-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    min-height: 44px;
    padding: .65rem 1rem;
    border: 1px solid transparent;
    border-radius: 11px;
    background: transparent;
    color: var(--text-dim);
    font-family: inherit;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    transition: color .2s ease, background .2s ease, border-color .2s ease;
}
.recent-tab:hover { color: var(--text); background: var(--surface); }
.recent-tab:focus-visible { outline: 2px solid var(--purple-light); outline-offset: 2px; }
.recent-tab.is-active {
    border-color: rgba(37, 99, 235, .42);
    background: rgba(37, 99, 235, .13);
    color: #60a5fa;
    box-shadow: inset 0 -2px 0 #3b82f6;
}
.recent-tab--account.is-active {
    border-color: rgba(139, 92, 246, .42);
    background: rgba(139, 92, 246, .13);
    color: #a78bfa;
    box-shadow: inset 0 -2px 0 #8b5cf6;
}
.recent-tab span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 .35rem;
    border-radius: 999px;
    background: var(--surface);
    color: var(--text);
    font-size: .68rem;
}
.recent-panel { padding: .35rem 1rem; }
.recent-item {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) auto;
    align-items: center;
    gap: .9rem;
    padding: 1rem .5rem;
    border-bottom: 1px solid var(--border);
}
.recent-item:last-child { border-bottom: 0; }
.recent-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border: 1px solid rgba(96, 165, 250, .25);
    border-radius: 12px;
    background: rgba(37, 99, 235, .11);
    color: #60a5fa;
}
.recent-item-icon--account {
    border-color: rgba(167, 139, 250, .25);
    background: rgba(139, 92, 246, .11);
    color: #a78bfa;
}
.recent-item-main { min-width: 0; }
.recent-item-main > strong {
    display: block;
    overflow: hidden;
    color: var(--text);
    font-size: .86rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.recent-item-meta {
    display: flex;
    align-items: center;
    gap: .4rem;
    min-width: 0;
    margin-top: .3rem;
    color: var(--text-dim);
    font-size: .7rem;
}
.recent-item-meta span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.recent-reference {
    color: var(--text-mute);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: .02em;
    white-space: nowrap;
}
.recent-item-summary {
    display: grid;
    grid-template-columns: minmax(110px, auto) minmax(88px, auto);
    align-items: center;
    gap: .35rem .8rem;
    text-align: right;
}
.recent-item-summary > strong { color: var(--text); font-size: .83rem; }
.recent-item-summary .status-pill { justify-self: end; }
.recent-item-summary time {
    grid-column: 1 / -1;
    color: var(--text-dim);
    font-size: .66rem;
}
.recent-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 210px;
    padding: 2rem;
    color: var(--text-dim);
    text-align: center;
}
.recent-empty i { margin-bottom: .8rem; color: var(--purple-light); font-size: 1.5rem; }
.recent-empty strong { color: var(--text); font-size: .9rem; }
.recent-empty span { margin-top: .25rem; font-size: .76rem; }

/* Latest transaction table */
.transaction-page {
    min-height: calc(100vh - 72px);
    padding: 5.75rem 0 4.5rem;
    background: transparent;
}
.transaction-shell {
    width: 100%;
    max-width: var(--layout-max);
    padding: 0 var(--layout-gutter);
}
.recent-transactions-caption {
    margin: 0 0 1.9rem;
    color: var(--text-dim);
    font-size: .86rem;
    text-align: center;
}
.recent-transactions-caption strong { font-weight: 800; }
.transaction-table-filters {
    display: flex;
    justify-content: center;
    gap: .55rem;
    margin: 0 0 1rem;
}
.transaction-table-filter {
    padding: .52rem .9rem;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: var(--surface);
    color: var(--text-dim);
    font: inherit;
    font-size: .75rem;
    font-weight: 750;
    cursor: pointer;
}
.transaction-table-filter:hover,
.transaction-table-filter.is-active {
    border-color: #2588df;
    background: #1677c6;
    color: #fff;
}
.transaction-table-filter:focus-visible { outline: 2px solid #4ba9ff; outline-offset: 2px; }
.recent-transactions--table {
    margin: 0;
    overflow: hidden;
    border: 1px solid rgba(11, 61, 120, .95);
    border-radius: 18px;
    background: rgba(12, 61, 120, .9);
    box-shadow: none;
}
.transaction-table-wrap { overflow-x: auto; }
.transaction-table {
    width: 100%;
    min-width: 790px;
    border-collapse: collapse;
    color: #fff;
    font-size: .75rem;
}
.transaction-table th,
.transaction-table td {
    padding: .95rem 1.5rem;
    text-align: left;
    white-space: nowrap;
}
.transaction-table th {
    border-bottom: 1px solid rgba(7, 42, 85, .9);
    background: rgba(16, 91, 177, .92);
    color: rgba(255, 255, 255, .98);
    font-size: .68rem;
    font-weight: 800;
}
.transaction-table tbody tr { background: rgba(8, 56, 113, .94); }
.transaction-table tbody tr:nth-child(even) { background: rgba(15, 96, 183, .9); }
.transaction-table tbody tr + tr td { border-top: 1px solid rgba(74, 157, 237, .2); }
.transaction-table td {
    color: rgba(255, 255, 255, .98);
    font-weight: 650;
}
.transaction-table-reference {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: .02em;
}
.transaction-table .status-pill {
    padding: .28rem .7rem;
    border: 0;
    background: #9ff7d0;
    color: #07503b;
    font-size: .63rem;
    line-height: 1;
}
.transaction-table .status-pill.processing { background: #13b9f0; color: #fff; }
.transaction-table .status-pill.pending { background: #ffe391; color: #735100; }
.transaction-table .status-pill.refund { background: #d6d9f7; color: #353662; }
.transaction-table .status-pill.failed { background: #ffc2ce; color: #8d1730; }
.transaction-table-empty {
    padding: 2.5rem !important;
    color: rgba(255, 255, 255, .78) !important;
    text-align: center !important;
}
.transaction-check-card {
    max-width: 640px;
    margin: 0 auto 2rem;
    border-color: var(--border);
    background: var(--surface);
    box-shadow: none;
}
.transaction-check-heading { margin-bottom: 1.2rem; }
.transaction-check-heading h1 {
    margin: 0 0 .35rem;
    color: var(--text);
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 800;
}
.transaction-check-heading p,
.transaction-form label { color: var(--text-dim); }
.transaction-check-heading p { margin: 0; font-size: .8rem; }
.transaction-input-row .input-field {
    border-color: var(--border);
    background: var(--surface-2);
    color: var(--text);
}
.transaction-input-row .input-field::placeholder { color: var(--text-mute); }
@media (max-width: 640px) {
    .transaction-page { padding-top: 5.8rem; padding-bottom: 2.5rem; }
    .recent-transactions-caption { margin-bottom: 1.2rem; font-size: .75rem; }
    .transaction-table-filters { justify-content: flex-start; overflow-x: auto; padding-bottom: .1rem; }
    .transaction-table-filter { flex: 0 0 auto; }
    .recent-transactions--table { border-radius: 13px; }
    .transaction-table th,
    .transaction-table td { padding: .85rem 1rem; }
    .transaction-check-card { padding: 1rem; }
    .transaction-input-row { flex-direction: column; }
    .transaction-submit { justify-content: center; width: 100%; }
    .recent-transactions { margin-top: 1.75rem; border-radius: 16px; }
    .recent-transactions--table { margin-top: 0; border-radius: 13px; }
    .recent-transactions-head { align-items: flex-start; flex-direction: column; gap: .45rem; padding: 1.1rem; }
    .recent-transactions-head p { text-align: left; }
    .recent-tab { gap: .4rem; padding-inline: .45rem; font-size: .72rem; }
    .recent-panel { padding: .2rem .75rem; }
    .recent-item { grid-template-columns: 36px minmax(0, 1fr); gap: .7rem; padding: .9rem .2rem; }
    .recent-item-icon { width: 36px; height: 36px; border-radius: 10px; }
    .recent-item-summary {
        grid-column: 2;
        grid-template-columns: auto auto;
        justify-content: space-between;
        text-align: left;
    }
    .recent-item-summary .status-pill { justify-self: end; }
    .recent-item-summary time { text-align: left; }
}
@media (prefers-reduced-motion: reduce) {
    .status-box { animation: none; }
}
@keyframes transactionFadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@push('scripts')
<script>
const STATUS_MAP = {
    success: { cls: 'status-pill', label: 'Berhasil' },
    processing: { cls: 'status-pill processing', label: 'Diproses' },
    pending: { cls: 'status-pill pending', label: 'Pending' },
    refund: { cls: 'status-pill refund', label: 'Refund' },
    failed: { cls: 'status-pill failed', label: 'Gagal' },
    expired: { cls: 'status-pill failed', label: 'Expired' }
};

function escapeTransactionHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

document.getElementById('checkForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const val = document.getElementById('checkInput')?.value?.trim();
    const result = document.getElementById('checkResult');
    if (!val || !result) return;

    result.innerHTML = '<div style="text-align:center;color:var(--text-mute);padding:1rem;">Memeriksa...</div>';
    try {
        const response = await fetch('/api/orders/check?q=' + encodeURIComponent(val));
        const data = await response.json();
        const safeVal = escapeTransactionHtml(val);

        if (response.status === 404 || (response.status === 422 && (!data.transactions || !data.transactions.length))) {
            result.innerHTML = '<div class="status-box"><span class="status-pill failed">Tidak Ditemukan</span><p style="font-size:.85rem;color:var(--text-dim);margin-top:.5rem;">Transaksi <strong style="color:var(--text)">' + safeVal + '</strong> tidak ditemukan. Periksa kembali ID transaksi atau email Anda.</p></div>';
            return;
        }
        if (!response.ok) {
            result.innerHTML = '<div class="status-box"><span class="status-pill failed">Error</span><p style="font-size:.85rem;color:var(--text-dim);margin-top:.5rem;">' + escapeTransactionHtml(data.message || 'Gagal memeriksa transaksi. Coba lagi nanti.') + '</p></div>';
            return;
        }

        const transactions = data.transactions || [];
        const heading = '<p style="font-size:.8rem;color:var(--text-dim);margin:0 0 .75rem;">Ditemukan <strong style="color:var(--text)">' + transactions.length + ' transaksi</strong> untuk <strong style="color:var(--text)">' + safeVal + '</strong></p>';
        const list = transactions.map((transaction) => {
            const status = STATUS_MAP[transaction.status] || STATUS_MAP.failed;
            return '<div class="status-box" style="margin-bottom:.75rem">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">' +
                '<strong style="color:var(--text);font-size:.9rem">' + escapeTransactionHtml(transaction.order_id) + '</strong>' +
                '<span class="' + status.cls + '">' + status.label + '</span>' +
                '</div>' +
                '<p style="font-size:.85rem;color:var(--text-dim);margin:.5rem 0 0">' +
                (transaction.product_name ? 'Produk: ' + escapeTransactionHtml(transaction.product_name) + '<br>' : '') +
                (transaction.customer_number ? 'ID: ' + escapeTransactionHtml(transaction.customer_number) + '<br>' : '') +
                (transaction.price != null ? 'Total: Rp ' + Number(transaction.price).toLocaleString('id-ID') + '<br>' : '') +
                (transaction.processed_at ? 'Diproses: ' + escapeTransactionHtml(transaction.processed_at) : '') +
                '</p></div>';
        }).join('');
        result.innerHTML = '<div class="status-box" style="margin-bottom:.75rem">' + heading + '</div>' + list;
    } catch (error) {
        result.innerHTML = '<div class="status-box"><span class="status-pill failed">Error</span><p style="font-size:.85rem;color:var(--text-dim);margin-top:.5rem;">Gagal memeriksa transaksi. Coba lagi nanti.</p></div>';
    }
});

const transactionFilters = Array.from(document.querySelectorAll('[data-transaction-filter]'));
const transactionRows = Array.from(document.querySelectorAll('[data-transaction-type]'));

transactionFilters.forEach((filter) => {
    filter.addEventListener('click', () => {
        const selectedType = filter.dataset.transactionFilter;

        transactionFilters.forEach((item) => {
            const isActive = item === filter;
            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-selected', String(isActive));
        });

        transactionRows.forEach((row) => {
            row.hidden = selectedType !== 'all' && row.dataset.transactionType !== selectedType;
        });
    });
});

</script>
@endpush
@endsection
