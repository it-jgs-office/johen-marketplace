@extends('layouts.topup')

@section('title', 'Pembayaran ' . $checkout->checkout_ref . ' - ' . config('app.name'))

@section('content')
@php
    $methodLabels = [
        'qris' => 'QRIS',
        'dana' => 'DANA',
        'gopay' => 'GoPay',
        'ovo' => 'OVO',
        'shopeepay' => 'ShopeePay',
        'linkaja' => 'LinkAja',
        'bca_va' => 'BCA Virtual Account', 'bca' => 'BCA Virtual Account',
        'bni_va' => 'BNI Virtual Account', 'bni' => 'BNI Virtual Account',
        'bri_va' => 'BRI Virtual Account', 'bri' => 'BRI Virtual Account',
        'mandiri_va' => 'Mandiri Virtual Account', 'mandiri' => 'Mandiri Virtual Account',
        'permata_va' => 'Permata Virtual Account', 'permata' => 'Permata Virtual Account',
        'alfamart' => 'Alfamart',
        'indomaret' => 'Indomaret',
    ];
    $methodLabel = $methodLabels[strtolower((string) $checkout->payment_method)] ?? 'QRIS';
    $gatewayType = (string) $checkout->gateway_type;
    $extra = (array) $checkout->gateway_extra;

    $statusMeta = match ($checkout->status) {
        'success' => ['label' => 'PEMBAYARAN SUKSES', 'color' => '#10b981'],
        'processing' => ['label' => 'SEDANG DIPROSES', 'color' => '#f59e0b'],
        'failed' => ['label' => 'PEMBAYARAN GAGAL', 'color' => '#ef4444'],
        default => ['label' => 'MENUNGGU PEMBAYARAN', 'color' => '#854DEA'],
    };
    $successCount = $orders->where('status', 'success')->count();
    $failedCount = $orders->where('status', 'failed')->count();
@endphp

<style>
/* Sebagian kelas pd-* ini hanya ada di inline style halaman detail pembayaran,
   jadi dideklarasikan ulang di sini supaya halaman ini konsisten. */
.pd-breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--text-dim);margin:-.2rem 0 1.2rem;}
.pd-breadcrumb a{color:var(--text-dim);text-decoration:none;transition:color .2s;}
.pd-breadcrumb a:hover{color:var(--text);}
.pd-breadcrumb span{color:var(--text-mute);}
.pd-header-titles{flex:1;min-width:0;}
.pd-status-card{border-bottom:1px solid var(--border);}
.pd-help{padding:1.1rem;}
.pd-help-text{font-size:.82rem;color:var(--text-dim);line-height:1.5;margin-bottom:1rem;}

.ckp-items{display:flex;flex-direction:column;padding:0 1.1rem .4rem;}
.ckp-item{display:flex;align-items:center;gap:.7rem;padding:.7rem 0;border-bottom:1px dashed var(--border);}
.ckp-item:last-child{border-bottom:none;}
.ckp-item-main{flex:1;min-width:0;}
.ckp-item-name{font-size:.83rem;font-weight:600;color:var(--text);line-height:1.3;}
.ckp-item-meta{font-size:.72rem;color:var(--text-mute);margin-top:.2rem;}
.ckp-item-note{font-size:.72rem;color:#f59e0b;margin-top:.25rem;}
.ckp-item-price{font-family:var(--font-display);font-weight:700;font-size:.82rem;color:var(--text);white-space:nowrap;}
.ckp-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.22rem .6rem;border-radius:6px;font-size:.7rem;font-weight:700;white-space:nowrap;}
.ckp-progress{margin:.1rem 1.1rem .9rem;font-size:.75rem;color:var(--text-dim);}
.ckp-ref{display:flex;justify-content:space-between;align-items:center;gap:.6rem;padding:.5rem 0;border-bottom:1px dashed var(--border);font-size:.82rem;}
.ckp-ref:last-child{border-bottom:none;}
.ckp-ref span{color:var(--text-mute);}
.ckp-ref b{color:var(--text);font-weight:600;text-align:right;}
.ckp-ref-mono{font-family:var(--font-display);}
.ckp-icon-qr{display:inline-block;padding:12px;background:#fff;border-radius:12px;}
.ckp-icon-qr img{width:200px;height:200px;display:block;border-radius:6px;}

@media(max-width:1000px){
    .pd-grid{grid-template-columns:1fr;}
    .pd-right{position:static;}
}
@media(max-width:768px){
    .ckp-item{flex-wrap:wrap;}
    .ckp-item-main{flex:1 1 100%;}
    .ckp-icon-qr img{width:170px;height:170px;}
}
</style>

<div class="pd-wrap">

    <nav class="pd-breadcrumb">
        <a href="{{ route('cart.index') }}">Keranjang</a>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span>Pembayaran</span>
    </nav>

    <div class="pd-header">
        <div class="pd-header-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </div>
        <div class="pd-header-titles">
            <h1 class="pd-title">Selesaikan Pembayaran</h1>
            <p class="pd-sub">{{ $checkout->item_count }} item dalam satu transaksi.</p>
        </div>
        <div class="pd-header-actions">
            <a href="{{ route('orders.my') }}" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                Cek Transaksi
            </a>
        </div>
    </div>

    @if($isDemo)
        <div class="pd-demo-banner">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 8v8a2 2 0 001 1.73l7 4a2 2 0 002 2l7-4A2 2 0 0021 16z"/><circle cx="12" cy="12" r="4"/></svg>
            <span>@if($isSimulation)Mode Simulasi Aktif &mdash; tidak ada pembayaran sungguhan. Tekan &ldquo;Simulasi Bayar&rdquo; untuk menyelesaikan seluruh item.@else Mode Demo &mdash; Xendit belum dikonfigurasi.@endif</span>
        </div>
    @endif

    <div class="pd-grid">
        <div class="pd-left">

            {{-- Status pembayaran --}}
            <div class="pd-card pd-status-card">
                <div class="pd-status">
                    <div class="pd-status-left">
                        <div class="pd-status-icon" id="ckpStatusIcon">
                            <svg class="spin-slow" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" width="44" height="44"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                        </div>
                        <div class="pd-status-info">
                            <div class="pd-status-label" id="ckpStatusLabel">{{ $statusMeta['label'] }}</div>
                            <div class="pd-status-sub" id="ckpStatusSub">Selesaikan pembayaran sebelum batas waktu habis.</div>
                        </div>
                    </div>
                    <div class="pd-status-right">
                        <span class="pd-status-pill" id="ckpStatusPill">{{ $statusMeta['label'] }}</span>
                    </div>
                </div>
                <div class="ckp-progress" id="ckpProgress">{{ $successCount }} dari {{ $orders->count() }} item selesai</div>
            </div>

            {{-- Metode pembayaran --}}
            <div class="pd-card">
                <div class="pd-card-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    <span>Metode Pembayaran &mdash; {{ $methodLabel }}</span>
                </div>

                <div class="pd-method">
                    @if($isDemo)
                        <div class="pd-method-placeholder">
                            <div class="pd-method-illus">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="1.2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h4"/></svg>
                            </div>
                            <p class="pd-method-placeholder-text">
                                @if($isSimulation)
                                    Tekan tombol di bawah untuk menandai seluruh item lunas tanpa memanggil payment gateway.
                                @else
                                    Gateway belum dikonfigurasi, jadi pembayaran hanya bisa disimulasikan.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('checkout.simulate', $checkout) }}" id="ckpSimForm">
                                @csrf
                                <button class="btn btn-solid btn-lg" type="submit" id="ckpSimBtn">
                                    @if($isSimulation) Simulasi Bayar @else Simulasi Demo @endif
                                </button>
                            </form>
                        </div>
                    @elseif($gatewayType === 'qris' && $checkout->qr_string)
                        <div class="pd-method-qr-wrap">
                            <div class="ckp-icon-qr">
                                <img id="ckpQr" alt="QRIS"
                                     src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($checkout->qr_string) }}">
                            </div>
                            <p class="pd-method-hint">Scan QRIS di atas memakai e-wallet atau mobile banking. Satu pembayaran untuk {{ $checkout->item_count }} item.</p>
                        </div>
                    @elseif($gatewayType === 'va' && $checkout->va_number)
                        <div class="pd-method-va-wrap">
                            <div class="pd-va-box">
                                <div class="pd-va-label">{{ $extra['label'] ?? 'Nomor Virtual Account' }}</div>
                                <div class="pd-va-number">{{ $checkout->va_number }}</div>
                                <button class="pd-copy-btn" data-copy="{{ $checkout->va_number }}">Salin Nomor VA</button>
                            </div>
                            @if(! empty($extra['expected_amount']))
                                <p class="pd-method-hint">Transfer tepat <strong>Rp {{ number_format((int) $extra['expected_amount'], 0, ',', '.') }}</strong> agar pembayaran terdeteksi.</p>
                            @else
                                <p class="pd-method-hint">Transfer melalui mobile banking, ATM, atau internet banking.</p>
                            @endif
                        </div>
                    @elseif($gatewayType === 'retail' && $checkout->payment_code)
                        <div class="pd-method-va-wrap">
                            <div class="pd-va-box">
                                <div class="pd-va-label">Kode Pembayaran {{ $extra['retail_outlet_name'] ?? '' }}</div>
                                <div class="pd-va-number">{{ $checkout->payment_code }}</div>
                                <button class="pd-copy-btn" data-copy="{{ $checkout->payment_code }}">Salin Kode</button>
                            </div>
                            <p class="pd-method-hint">Bayar di gerai {{ $extra['retail_outlet_name'] ?? 'minimarket' }} terdekat.</p>
                        </div>
                    @elseif($gatewayType === 'ewallet' && $checkout->checkout_url)
                        <div class="pd-method-placeholder">
                            <div class="pd-method-illus">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--purple-light)" stroke-width="1.4"><rect x="5" y="2" width="14" height="20" rx="2.5"/><path d="M10 18.5h4"/></svg>
                            </div>
                            <p class="pd-method-placeholder-text">Kamu akan diarahkan ke aplikasi {{ $methodLabel }} untuk menyelesaikan pembayaran.</p>
                            <button class="btn btn-solid btn-lg" type="button" id="ckpEwalletBtn">Bayar di {{ $methodLabel }}</button>
                        </div>
                    @elseif($checkout->gateway_invoice_url)
                        <div class="pd-method-placeholder">
                            <div class="pd-method-illus">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--purple-light)" stroke-width="1.4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            </div>
                            <p class="pd-method-placeholder-text">Bayar melalui halaman invoice Xendit.</p>
                            <button class="btn btn-solid btn-lg" type="button" id="ckpInvoiceBtn">Buka Halaman Pembayaran</button>
                        </div>
                    @else
                        <div class="pd-method-placeholder">
                            <p class="pd-method-placeholder-text">Informasi pembayaran tidak tersedia. Muat ulang halaman atau hubungi CS kami.</p>
                        </div>
                    @endif

                    <div class="pd-method-trans-id">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span class="pd-trans-id-label">Kode Transaksi</span>
                        <span class="pd-trans-id-value">{{ $checkout->checkout_ref }}</span>
                        <button class="pd-copy-btn" data-copy="{{ $checkout->checkout_ref }}">Salin</button>
                    </div>
                </div>
            </div>

            {{-- Status per item --}}
            <div class="pd-card">
                <div class="pd-card-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    <span>Status Per Item ({{ $orders->count() }})</span>
                </div>
                <div class="ckp-items" id="ckpItems">
                    @foreach($orders as $order)
                        <div class="ckp-item" data-order="{{ $order->order_id }}">
                            <span class="pd-dot {{ $order->status === 'success' ? 'success' : ($order->status === 'failed' ? 'failed' : ($order->status === 'processing' ? 'processing' : 'pending')) }}" data-role="dot"></span>
                            <div class="ckp-item-main">
                                <div class="ckp-item-name">{{ $order->product_name }} <span style="color:var(--text-mute);">&times;{{ $order->quantity }}</span></div>
                                <div class="ckp-item-meta">{{ $order->brand }} &middot; User ID {{ $order->customer_number }}@if($order->effective_zone_id) &middot; Zone {{ $order->effective_zone_id }}@endif</div>
                                @if($order->note)
                                    <div class="ckp-item-note" data-role="note">{{ $order->note }}</div>
                                @endif
                            </div>
                            <span class="ckp-item-price">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
                            <span class="ckp-badge" data-role="badge" style="background:rgba(133,77,234,.15);color:#854DEA">{{ ucfirst($order->status) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="pd-right">
            <div class="pd-card pd-card-sticky">
                <div class="pd-card-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    <span>Ringkasan</span>
                </div>
                <div class="pd-ringkasan">
                    <div class="pd-rincian">
                        @if($checkout->discount > 0)
                            <div class="pd-rincian-row">
                                <span>Subtotal</span>
                                <span>Rp {{ number_format($checkout->subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="pd-rincian-row">
                                <span>Diskon promo</span>
                                <span class="pd-green">- Rp {{ number_format($checkout->discount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="pd-rincian-total">
                            <span>Total Pembayaran</span>
                            <span class="pd-total-price">Rp {{ number_format($checkout->total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div style="padding:.6rem 1.1rem 0;">
                        <div class="ckp-ref">
                            <span>Kode transaksi</span>
                            <b class="ckp-ref-mono">{{ $checkout->checkout_ref }}</b>
                        </div>
                        <div class="ckp-ref">
                            <span>Metode</span>
                            <b>{{ $methodLabel }}</b>
                        </div>
                        <div class="ckp-ref">
                            <span>Jumlah item</span>
                            <b>{{ $checkout->item_count }}</b>
                        </div>
                        @if($checkout->email)
                            <div class="ckp-ref">
                                <span>Email</span>
                                <b>{{ $checkout->email }}</b>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="pd-ringkasan-actions">
                    <a href="{{ route('orders.my') }}" class="btn btn-outline btn-full">Lihat Semua Pesanan</a>
                    <a href="{{ route('home') }}#topup" class="btn btn-solid btn-full" style="margin-top:.6rem;">Top Up Lagi</a>
                </div>
            </div>

            <div class="pd-card">
                <div class="pd-card-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Butuh Bantuan?</span>
                </div>
                <div class="pd-help">
                    <p class="pd-help-text">Pembayaran sudah masuk tapi item belum masuk? Screenshot halaman ini dan kirim ke CS kami, sertakan kode transaksi di atas.</p>
                    <a href="{{ route('kontak') }}" class="btn btn-outline btn-full">Hubungi CS</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    var statusUrl = @json(route('checkout.status', $checkout));
    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var statusIcon = document.getElementById('ckpStatusIcon');
    var statusLabel = document.getElementById('ckpStatusLabel');
    var statusSub = document.getElementById('ckpStatusSub');
    var statusPill = document.getElementById('ckpStatusPill');
    var progress = document.getElementById('ckpProgress');

    var STATUS_MAP = {
        pending:    { label: 'MENUNGGU PEMBAYARAN', color: '#854DEA', sub: 'Selesaikan pembayaran sebelum batas waktu habis.', icon: '<svg class="spin-slow" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" width="44" height="44"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>' },
        processing: { label: 'SEDANG DIPROSES',     color: '#f59e0b', sub: 'Pembayaran diterima, item sedang dikirim ke game.', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" width="44" height="44"><path d="M12 6v6l4 2"/></svg>' },
        success:    { label: 'PEMBAYARAN SUKSES',    color: '#10b981', sub: 'Semua item sudah masuk ke akun kamu.', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" width="44" height="44"><path d="M20 6L9 17l-5-5"/></svg>' },
        failed:     { label: 'PEMBAYARAN GAGAL',    color: '#ef4444', sub: 'Silakan coba lagi atau hubungi admin.', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" width="44" height="44"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' },
    };

    var DOT_CLASS = { pending: 'pending', processing: 'processing', success: 'success', failed: 'failed' };

    function refreshBadge(el, status, color) {
        el.style.background = color + '20';
        el.style.color = color;
        el.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    }

    function renderItems(items) {
        items.forEach(function (item) {
            var row = document.querySelector('.ckp-item[data-order="' + CSS.escape(item.order_id) + '"]');
            if (!row) return;

            var color = (STATUS_MAP[item.status] || STATUS_MAP.pending).color;
            var dot = row.querySelector('[data-role="dot"]');
            var badge = row.querySelector('[data-role="badge"]');

            if (dot) dot.className = 'pd-dot ' + (DOT_CLASS[item.status] || 'pending');
            if (badge) refreshBadge(badge, item.status, color);

            var note = row.querySelector('[data-role="note"]');
            if (item.note) {
                if (!note) {
                    note = document.createElement('div');
                    note.className = 'ckp-item-note';
                    note.setAttribute('data-role', 'note');
                    row.querySelector('.ckp-item-main').appendChild(note);
                }
                note.textContent = item.note;
            }
        });
    }

    function updateStatus(status, data) {
        var map = STATUS_MAP[status] || STATUS_MAP.pending;
        statusIcon.innerHTML = map.icon;
        statusLabel.textContent = map.label;
        statusSub.textContent = map.sub;
        statusPill.textContent = map.label;
        statusPill.style.background = map.color + '20';
        statusPill.style.color = map.color;

        if (data) {
            progress.textContent = data.success + ' dari ' + data.count + ' item selesai'
                + (data.failed > 0 ? ' · ' + data.failed + ' gagal' : '');
            renderItems(data.items || []);
        }
    }

    // Polling selama halaman terbuka: satu pembayaran, banyak item.
    var attempts = 0;
    var pollingStarted = false;
    function startPolling() {
        if (pollingStarted) return;
        pollingStarted = true;

        (function check() {
            fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    updateStatus(data.status, data);
                    if (data.status === 'success' || data.status === 'failed') return;
                    attempts++;
                    if (attempts < 400) setTimeout(check, 3000);
                })
                .catch(function () {
                    attempts++;
                    if (attempts < 400) setTimeout(check, 5000);
                });
        })();
    }

    updateStatus(@json($checkout->status), {
        success: @json($successCount),
        failed: @json($failedCount),
        count: @json($orders->count()),
        items: []
    });
    startPolling();

    // Simulasi: tandai lunas lalu biarkan polling yang memperbarui tampilan.
    var simForm = document.getElementById('ckpSimForm');
    if (simForm) {
        simForm.addEventListener('submit', function () {
            var btn = document.getElementById('ckpSimBtn');
            if (btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
        });
    }

    // E-wallet / invoice -&gt; pindah ke halaman gateway.
    var ewalletBtn = document.getElementById('ckpEwalletBtn');
    if (ewalletBtn) {
        var ewalletUrl = @json($checkout->checkout_url);
        ewalletBtn.addEventListener('click', function () { window.location.href = ewalletUrl; });
        setTimeout(function () { window.location.href = ewalletUrl; }, 1200);
    }

    var invoiceBtn = document.getElementById('ckpInvoiceBtn');
    if (invoiceBtn) {
        var invoiceUrl = @json($checkout->gateway_invoice_url);
        invoiceBtn.addEventListener('click', function () { window.location.href = invoiceUrl; });
    }

    // Salin nomor VA / kode pembayaran / kode transaksi.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.pd-copy-btn');
        if (!btn) return;

        navigator.clipboard.writeText(btn.dataset.copy).then(function () {
            var orig = btn.textContent;
            btn.textContent = 'Tersalin!';
            btn.style.background = 'rgba(16,185,129,.2)';
            btn.style.color = '#10b981';
            setTimeout(function () {
                btn.textContent = orig;
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        });
    });
})();
</script>
@endpush
@endsection