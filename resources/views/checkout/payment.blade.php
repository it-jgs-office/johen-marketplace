@extends('layouts.topup')

@section('title', 'Pembayaran ' . $checkout->checkout_ref . ' - ' . config('app.name'))

@section('content')
@php
    $methodLabels = [
        'qris' => 'QRIS',
        'dana' => 'DANA',
        'bca_va' => 'BCA Virtual Account', 'bca' => 'BCA Virtual Account',
        'bni_va' => 'BNI Virtual Account', 'bni' => 'BNI Virtual Account',
        'cimb_va' => 'CIMB Virtual Account', 'cimb' => 'CIMB Virtual Account',
        'bri_va' => 'BRI Virtual Account', 'bri' => 'BRI Virtual Account',
        'mandiri_va' => 'Mandiri Virtual Account', 'mandiri' => 'Mandiri Virtual Account',
        'alfamart' => 'Alfamart',
        'bss_va' => 'BSS Virtual Account', 'bss' => 'BSS Virtual Account',
    ];
    $methodLabel = $methodLabels[strtolower((string) $checkout->payment_method)] ?? 'QRIS';
    $methodCode = strtolower((string) $checkout->payment_method);
    $methodCode = ['bca' => 'bca_va', 'bni' => 'bni_va', 'bri' => 'bri_va', 'bss' => 'bss_va', 'cimb' => 'cimb_va', 'mandiri' => 'mandiri_va'][$methodCode] ?? $methodCode;
    $paymentLogo = payment_logo_asset($methodCode);

    $statusMeta = match ($checkout->status) {
        'success' => ['label' => 'PEMBAYARAN SUKSES', 'color' => '#10b981'],
        'processing' => ['label' => 'SEDANG DIPROSES', 'color' => '#f59e0b'],
        'failed' => ['label' => 'PEMBAYARAN GAGAL', 'color' => '#ef4444'],
        'expired' => ['label' => 'PEMBAYARAN EXPIRED', 'color' => '#ef4444'],
        'cancelled' => ['label' => 'PESANAN DIBATALKAN', 'color' => '#ef4444'],
        default => ['label' => 'MENUNGGU PEMBAYARAN', 'color' => '#f59e0b'],
    };
    $successCount = $orders->where('status', 'success')->count();
    $failedCount = $orders->where('status', 'failed')->count();
@endphp

<style>
@include('payment.partials.detail-consistency-styles')
.pd-help{padding:1.1rem;}
.pd-help-text{font-size:.82rem;color:var(--text-dim);line-height:1.5;margin-bottom:1rem;}
.ckp-actions{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.ckp-actions .pd-card{margin-bottom:0;}

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
.ckp-failed-lordicon{display:block;width:88px;height:88px;object-fit:contain;}

@media(max-width:1000px){
    .pd-grid{grid-template-columns:1fr;}
    .pd-right{position:static;}
}
@media(max-width:768px){
    .ckp-actions{grid-template-columns:1fr;}
    .ckp-item{flex-wrap:wrap;}
    .ckp-item-main{flex:1 1 100%;}
    .ckp-icon-qr img{width:170px;height:170px;}
}
</style>

<div class="pd-wrap invoice-theme" id="ckpInvoiceTheme" data-invoice-state="{{ $checkout->status === 'success' ? 'success' : (in_array($checkout->status, ['failed', 'expired', 'cancelled']) ? 'failed' : 'pending') }}">

    <nav class="pd-breadcrumb">
        <a href="{{ route('cart.index') }}">Keranjang</a>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span>Pembayaran</span>
    </nav>

    <div class="pd-header">
        <div class="pd-header-titles">
            <h1 class="pd-title">Detail Pembayaran</h1>
            <p class="pd-sub">Selesaikan pembayaran {{ $checkout->item_count }} item sebelum waktu habis.</p>
        </div>
    </div>

    @if($isDemo)
        <div class="pd-demo-banner">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 002 8v8a2 2 0 001 1.73l7 4a2 2 0 002 2l7-4A2 2 0 0021 16z"/><circle cx="12" cy="12" r="4"/></svg>
            <span>@if($isSimulation)Mode Simulasi Aktif &mdash; tidak ada pembayaran sungguhan. Tekan &ldquo;Simulasi Bayar&rdquo; untuk menyelesaikan seluruh item.@else Mode Demo &mdash; pembayaran belum dikonfigurasi.@endif</span>
        </div>
    @endif

    <div class="pd-grid pd-grid-single">
        <div class="pd-left">

            {{-- Progress transaksi --}}
            <div class="pd-card">
                <div class="pd-card-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    <span>Progress Status</span>
                </div>
                <div class="pd-steps">
                    <div class="pd-step" id="ckpStepA" data-state="pending"><div class="pd-step-dot"><span class="pd-step-num">1</span></div><div class="pd-step-label">Menunggu Pembayaran</div></div>
                    <div class="pd-step" id="ckpStepB" data-state="pending"><div class="pd-step-dot"><span class="pd-step-num">2</span></div><div class="pd-step-label">Pembayaran Diterima</div></div>
                    <div class="pd-step" id="ckpStepC" data-state="pending"><div class="pd-step-dot"><span class="pd-step-num">3</span></div><div class="pd-step-label">Pesanan Diproses</div></div>
                    <div class="pd-step" id="ckpStepD" data-state="pending"><div class="pd-step-dot"><span class="pd-step-num">4</span></div><div class="pd-step-label">Pesanan Selesai</div></div>
                </div>
            </div>

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
            <div class="pd-card pd-method-card">
                <div class="pd-card-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    <span>Metode Pembayaran</span>
                </div>

                <div class="pd-detail-grid">
                    <div class="pd-detail-col">
                        <div class="pd-detail-item"><span class="pd-detail-label">Kode Transaksi</span><span class="pd-detail-value pd-detail-mono">{{ $checkout->checkout_ref }}</span></div>
                        <div class="pd-detail-item"><span class="pd-detail-label">Merchant</span><span class="pd-detail-value">Johen Gaming</span></div>
                        <div class="pd-detail-item">
                            <span class="pd-detail-label">Metode</span>
                            <span class="pd-detail-value">@if($paymentLogo)<img src="{{ $paymentLogo }}" alt="" class="pd-detail-paylogo">@endif {{ $methodLabel }}</span>
                        </div>
                        <div class="pd-detail-item">
                            <span class="pd-detail-label">Status</span>
                            <span class="pd-detail-value"><span class="pd-detail-badge" id="ckpDetailStatusBadge" style="background:{{ $statusMeta['color'] }}20;color:{{ $statusMeta['color'] }}"><span class="pd-detail-badge-dot" id="ckpDetailStatusDot" style="background:{{ $statusMeta['color'] }}"></span><span id="ckpDetailStatusText">{{ $statusMeta['label'] }}</span></span></span>
                        </div>
                    </div>
                    <div class="pd-detail-col">
                        <div class="pd-detail-item"><span class="pd-detail-label">Jumlah Item</span><span class="pd-detail-value">{{ $checkout->item_count }} item</span></div>
                        <div class="pd-detail-item"><span class="pd-detail-label">Email</span><span class="pd-detail-value">{{ $checkout->email ?: '-' }}</span></div>
                        @if($checkout->discount > 0)
                            <div class="pd-detail-item"><span class="pd-detail-label">Diskon</span><span class="pd-detail-value pd-green">- Rp {{ number_format($checkout->discount, 0, ',', '.') }}</span></div>
                        @endif
                        @if($checkout->points_used > 0)
                            <div class="pd-detail-item"><span class="pd-detail-label">Johen Point</span><span class="pd-detail-value pd-green">-{{ number_format($checkout->points_used, 0, ',', '.') }} JP</span></div>
                        @endif
                        <div class="pd-detail-item"><span class="pd-detail-label">Total Pembayaran</span><span class="pd-detail-value pd-total-price">Rp {{ number_format($checkout->cash_amount, 0, ',', '.') }}</span></div>
                    </div>
                </div>

                <div class="pd-method">
                    <div class="pd-method-placeholder">
                        <div class="pd-pay-cta-head">
                            <div class="pd-pay-cta-icon">
                                @if($paymentLogo)
                                    <img src="{{ $paymentLogo }}" alt="{{ $methodLabel }}">
                                @else
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M7 15h4"/></svg>
                                @endif
                            </div>
                            <div class="pd-pay-cta-copy"><span>Pembayaran belum selesai</span><strong>Selesaikan pembayaran</strong></div>
                        </div>
                        <div class="pd-pay-cta-meta">
                            <div><span>Metode</span><strong>{{ $methodLabel }}</strong></div>
                            <div><span>Total</span><strong>Rp {{ number_format($checkout->cash_amount, 0, ',', '.') }}</strong></div>
                        </div>
                        @if($isDemo)
                            <form method="POST" action="{{ route('checkout.simulate', $checkout) }}" id="ckpSimForm">
                                @csrf
                                <button class="btn btn-solid btn-lg pd-pay-action" type="submit" id="ckpSimBtn">
                                    <span>Lanjut Bayar</span>
                                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                </button>
                            </form>
                            <p class="pd-pay-cta-note">Mode simulasi aktif, tidak ada transaksi pembayaran nyata.</p>
                        @elseif($checkout->gateway_invoice_url)
                            <button class="btn btn-solid btn-lg pd-pay-action" type="button" id="ckpInvoiceBtn">
                                <span>Lanjut Bayar</span>
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                            </button>
                            <p class="pd-pay-cta-note">Klik tombol untuk melanjutkan proses pembayaran.</p>
                        @else
                            <p class="pd-method-placeholder-text">Informasi pembayaran tidak tersedia. Muat ulang halaman atau hubungi CS kami.</p>
                        @endif
                    </div>

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
                            <span class="pd-dot {{ $order->status === 'success' ? 'success' : (in_array($order->status, ['failed', 'expired'], true) ? 'failed' : ($order->status === 'processing' ? 'processing' : 'pending')) }}" data-role="dot"></span>
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

            <div class="ckp-actions">
                <div class="pd-card">
                    <div class="pd-card-head">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                        <span>Navigasi Pesanan</span>
                    </div>
                    <div class="pd-help">
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
                        <p class="pd-help-text">Pembayaran sudah masuk tapi item belum masuk? Kirim kode transaksi ini ke CS kami.</p>
                        <a href="#live-chat" class="btn btn-outline btn-full" data-open-live-chat>Live Chat CS</a>
                    </div>
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
    var detailStatusBadge = document.getElementById('ckpDetailStatusBadge');
    var detailStatusDot = document.getElementById('ckpDetailStatusDot');
    var detailStatusText = document.getElementById('ckpDetailStatusText');

    var STATUS_MAP = {
        pending:    { label: 'MENUNGGU PEMBAYARAN', color: '#f59e0b', sub: 'Selesaikan pembayaran sebelum batas waktu habis.', icon: '<svg class="spin-slow" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" width="44" height="44"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>' },
        processing: { label: 'SEDANG DIPROSES',     color: '#f59e0b', sub: 'Pembayaran diterima, item sedang dikirim ke game.', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" width="44" height="44"><path d="M12 6v6l4 2"/></svg>' },
        success:    { label: 'PEMBAYARAN SUKSES',    color: '#10b981', sub: 'Semua item sudah masuk ke akun kamu.', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" width="44" height="44"><path d="M20 6L9 17l-5-5"/></svg>' },
        failed:     { label: 'PEMBAYARAN GAGAL',    color: '#ef4444', sub: 'Silakan coba lagi atau hubungi admin.', icon: '<img class="ckp-failed-lordicon" src="{{ asset('assets/icon/payment-failed.svg') }}" alt="Pembayaran gagal">' },
        expired:    { label: 'PEMBAYARAN EXPIRED',  color: '#ef4444', sub: 'Batas waktu pembayaran telah habis.', icon: '<img class="ckp-failed-lordicon" src="{{ asset('assets/icon/payment-expired.svg') }}" alt="Waktu pembayaran habis">' },
        cancelled:  { label: 'PESANAN DIBATALKAN', color: '#ef4444', sub: 'Pesanan ini telah dibatalkan.', icon: '<img class="ckp-failed-lordicon" src="{{ asset('assets/icon/payment-failed.svg') }}" alt="Pesanan dibatalkan">' },
    };

    var DOT_CLASS = { pending: 'pending', processing: 'processing', success: 'success', failed: 'failed', expired: 'failed', cancelled: 'failed' };

    function updateProgressSteps(status) {
        var states = status === 'success'
            ? ['done', 'done', 'done', 'done']
            : status === 'processing'
                ? ['done', 'done', 'active', 'pending']
                : ['failed', 'expired', 'cancelled'].indexOf(status) !== -1
                    ? ['done', 'active', 'pending', 'pending']
                    : ['active', 'pending', 'pending', 'pending'];

        ['ckpStepA', 'ckpStepB', 'ckpStepC', 'ckpStepD'].forEach(function (id, index) {
            var step = document.getElementById(id);
            if (step) step.dataset.state = states[index];
        });
    }

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
        var invoiceTheme = document.getElementById('ckpInvoiceTheme');
        if (invoiceTheme) {
            invoiceTheme.dataset.invoiceState = status === 'success'
                ? 'success'
                : ['failed', 'expired', 'cancelled'].indexOf(status) !== -1 ? 'failed' : 'pending';
        }
        statusIcon.innerHTML = map.icon;
        statusLabel.textContent = map.label;
        statusSub.textContent = map.sub;
        statusPill.textContent = map.label;
        statusPill.style.background = map.color + '20';
        statusPill.style.color = map.color;
        updateProgressSteps(status);

        if (detailStatusBadge && detailStatusDot && detailStatusText) {
            detailStatusBadge.style.background = map.color + '20';
            detailStatusBadge.style.color = map.color;
            detailStatusDot.style.background = map.color;
            detailStatusText.textContent = map.label;
        }

        if (data) {
            progress.textContent = data.success + ' dari ' + data.count + ' item selesai'
                + (data.failed > 0 ? ' · ' + data.failed + ' gagal' : '');
            renderItems(data.items || []);
        }
    }

    // Polling selama halaman terbuka: satu pembayaran, banyak item.
    var pollingStarted = false;
    function startPolling() {
        if (pollingStarted) return;
        pollingStarted = true;

        (function check() {
            fetch(statusUrl, { cache: 'no-store', headers: { 'Accept': 'application/json' } })
                .then(function (r) { if (!r.ok) throw new Error('Status unavailable'); return r.json(); })
                .then(function (data) {
                    updateStatus(data.status, data);
                    document.dispatchEvent(new CustomEvent('payment:updated', {detail: data}));
                    if (data.status === 'success' || data.status === 'failed' || data.status === 'expired' || data.status === 'cancelled') return;
                    setTimeout(check, document.hidden ? 15000 : 3000);
                })
                .catch(function () {
                    document.dispatchEvent(new Event('payment:offline'));
                    setTimeout(check, 5000);
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

    // Buka halaman pembayaran.
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
