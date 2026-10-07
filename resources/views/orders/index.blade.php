@extends('layouts.topup')

@section('title', 'Pesanan Saya — ' . config('app.name'))

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

:root{--ord-primary:#854DEA;--ord-primary-light:#a575ff;--ord-primary-glow:rgba(133,77,234,.35);--ord-primary-subtle:rgba(133,77,234,.12)}
.ord-page{max-width:1000px;margin:0 auto;padding:2rem 1.5rem 4rem;font-family:'Poppins',sans-serif;animation:ordFadeIn .4s ease}
@keyframes ordFadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.ord-breadcrumb{font-size:.78rem;color:var(--text-mute);margin-bottom:.75rem;display:flex;align-items:center;gap:.35rem}
.ord-breadcrumb a{color:var(--text-mute);text-decoration:none}
.ord-breadcrumb a:hover{color:var(--ord-primary)}
.ord-breadcrumb .sep{opacity:.4}
.ord-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap}
.ord-header-left{min-width:0}
.ord-title{font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:800;letter-spacing:-.02em}
.ord-subtitle{font-size:.85rem;color:var(--text-mute);margin-top:.15rem}
.ord-help-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.55rem 1.1rem;border-radius:10px;background:var(--ord-primary-subtle);color:var(--text);font-size:.8rem;font-weight:700;border:1px solid transparent;transition:all .2s;text-decoration:none;white-space:nowrap;font-family:'Poppins',sans-serif}
.ord-help-btn:hover{background:rgba(133,77,234,.18);border-color:var(--border-strong);transform:translateY(-2px);box-shadow:0 4px 14px -4px var(--ord-primary-glow)}
.ord-tabs{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap}
.ord-balance{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
.ord-balance::after{content:'';position:absolute;inset:0 0 auto auto;width:220px;height:220px;margin-top:-90px;margin-right:-60px;border-radius:50%;background:radial-gradient(circle,rgba(133,77,234,.16),transparent 70%);pointer-events:none}
.ord-balance-main{position:relative;z-index:1}
.ord-balance-label{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-mute)}
.ord-balance-value{font-size:1.75rem;font-weight:800;letter-spacing:-.02em;margin-top:.15rem;color:var(--text)}
.ord-balance-hint{font-size:.78rem;color:var(--text-dim);margin-top:.35rem;max-width:60ch}
.ord-balance-log{margin-top:1rem;border-top:1px solid var(--border);padding-top:.75rem;position:relative;z-index:1}
.ord-balance-log summary{cursor:pointer;font-size:.8rem;font-weight:600;color:var(--ord-primary);list-style:none;display:inline-flex;align-items:center;gap:.35rem}
.ord-balance-log summary::-webkit-details-marker{display:none}
.ord-balance-log summary::before{content:'+';font-weight:800;line-height:1}
.ord-balance-log[open] summary::before{content:'\2212'}
.ord-balance-list{list-style:none;margin:.85rem 0 0;padding:0;display:flex;flex-direction:column;gap:.5rem}
.ord-balance-item{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.6rem .75rem;background:var(--surface-2);border-radius:10px}
.ord-balance-item-title{font-size:.82rem;font-weight:600;color:var(--text)}
.ord-balance-item-sub{font-size:.72rem;color:var(--text-mute);margin-top:.1rem}
.ord-balance-item-amount{font-size:.88rem;font-weight:700;white-space:nowrap}
.ord-balance-item-amount.is-plus{color:#34d399}
.ord-balance-item-amount.is-minus{color:#f87171}
.ord-balance-item-amount.is-neutral{color:var(--text-mute);font-weight:500}
.ord-refund-note{display:inline-flex;align-items:center;gap:.4rem;margin-top:.4rem;padding:.3rem .6rem;border-radius:8px;background:rgba(148,163,184,.15);color:#94a3b8;font-size:.7rem;font-weight:600}
.ord-tab{padding:.5rem 1.2rem;border-radius:999px;font-size:.8rem;font-weight:600;background:var(--surface);border:1px solid var(--border);color:var(--text-dim);cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem;font-family:'Poppins',sans-serif}
.ord-tab:hover{border-color:var(--ord-primary);color:var(--text)}
.ord-tab.active{background:var(--ord-primary);color:#fff;border-color:var(--ord-primary);box-shadow:0 0 20px -4px var(--ord-primary-glow)}
.ord-tab svg{width:16px;height:16px;flex-shrink:0}
.ord-filter-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.ord-filter-group{display:flex;align-items:center;gap:.5rem;min-width:120px}
.ord-filter-group:first-child{margin-left:auto}
.ord-filter-group:last-child{flex:1}
.ord-filter-group label{font-size:.72rem;font-weight:600;color:var(--text-mute);white-space:nowrap}
.ord-filter-select,.ord-filter-input{padding:.45rem .7rem;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);color:var(--text);font-size:.78rem;font-family:'Poppins',sans-serif;transition:border-color .2s;width:100%;max-width:200px}
.ord-filter-select:focus,.ord-filter-input:focus{outline:none;border-color:var(--ord-primary)}
.ord-filter-input[type=date]{max-width:150px;color-scheme:light dark}
.ord-search-wrap{position:relative;min-width:180px;max-width:260px;margin-right:auto}
.ord-search-wrap svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--text-mute);pointer-events:none}
.ord-search-wrap input{width:100%;padding:.45rem .7rem .45rem 2rem;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);color:var(--text);font-size:.78rem;font-family:'Poppins',sans-serif;transition:border-color .2s}
.ord-search-wrap input:focus{outline:none;border-color:var(--ord-primary)}
.ord-list{display:flex;flex-direction:column;gap:24px}
.ord-card{display:flex;background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px 32px;transition:all .25s ease;cursor:default;animation:ordFadeIn .35s ease both}
.ord-card:hover{border-color:var(--ord-primary);transform:translateY(-4px);box-shadow:0 10px 35px rgba(133,77,234,.18)}
.ord-card-left{display:flex;gap:28px;flex:0 0 52%;max-width:52%;align-items:flex-start}
.ord-thumb{width:145px;min-width:145px;height:145px;border-radius:12px;background:var(--surface-2);background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--text-mute);overflow:hidden}
.ord-thumb img{width:100%;height:100%;object-fit:cover}
.ord-info{display:flex;flex-direction:column;gap:0;min-width:0;flex:1}
.ord-info-top{display:flex;align-items:center;gap:0}
.ord-product-name{font-size:18px;font-weight:700;line-height:1.1;color:var(--text);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ord-status-badge{display:inline-flex;align-items:center;font-size:.65rem;font-weight:700;padding:0 14px;height:30px;border-radius:999px;text-transform:uppercase;letter-spacing:.04em;margin-left:14px;white-space:nowrap;flex-shrink:0}
.ord-status-badge--success{background:rgba(52,211,153,.15);color:#34d399}
.ord-status-badge--pending{background:rgba(251,191,36,.15);color:#fbbf24}
.ord-status-badge--processing{background:rgba(96,165,250,.15);color:#60a5fa}
.ord-status-badge--failed{background:rgba(248,113,113,.15);color:#f87171}
.ord-status-badge--refund{background:rgba(148,163,184,.15);color:#94a3b8}
.ord-meta{display:flex;flex-direction:column;gap:10px;margin-top:6px}
.ord-meta-item{font-size:12px;color:var(--text-dim);display:flex;align-items:center;gap:10px}
.ord-meta-item svg{width:20px;height:20px;color:var(--text-dim);flex-shrink:0}
.ord-meta-item strong{color:var(--text);font-weight:600;font-size:14px}
.ord-meta-item span{font-size:14px}
.ord-separator{width:1px;background:var(--border);flex-shrink:0;margin:0 28px;height:80%;align-self:center}
.ord-card-right{flex:1;min-width:0;display:flex;flex-direction:column;padding:2px 0}
.ord-right-order-id{font-size:18px;font-weight:700;color:var(--text);line-height:1.1;margin-bottom:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ord-right-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;margin-bottom:10px}
.ord-right-label{font-size:12px;color:var(--text-mute);font-weight:500;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
.ord-right-value{font-size:14px;color:var(--text);font-weight:500;line-height:1.4}
.ord-payment-display{display:flex;align-items:center;gap:6px}
.ord-payment-display img,.ord-payment-display svg{height:26px;max-width:90px;object-fit:contain;flex-shrink:0}
.ord-right-total{font-size:18px;font-weight:700;color:var(--text);line-height:1;margin-bottom:24px}
.ord-left-total{margin-bottom:0;margin-top:20px}
.ord-date-value{font-size:12px}
.ord-detail-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:34px;padding:0 18px;border-radius:8px;background:var(--ord-primary);color:#fff;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .25s;text-decoration:none;font-family:'Poppins',sans-serif;width:fit-content;margin-top:10px}
.ord-detail-btn svg{width:16px;height:16px;transition:transform .25s}
.ord-detail-btn:hover{background:var(--ord-primary-light);box-shadow:0 4px 18px -4px var(--ord-primary-glow)}
.ord-detail-btn:hover svg{transform:translateX(4px)}
.ord-empty{text-align:center;padding:5rem 1.5rem;background:var(--surface);border-radius:16px;border:1px solid var(--border)}
.ord-empty-icon{display:flex;align-items:center;justify-content:center;color:var(--text-mute);margin-bottom:1.25rem;opacity:.4}
.ord-empty-title{font-size:1.15rem;font-weight:600;margin-bottom:.35rem}
.ord-empty-desc{color:var(--text-mute);font-size:.9rem;margin-bottom:1.5rem;max-width:360px;margin-left:auto;margin-right:auto}
.ord-no-results{display:none;text-align:center;padding:3rem 1.5rem}
.ord-no-results.show{display:block}
.ord-no-results-title{font-size:1rem;font-weight:600;margin-bottom:.25rem}
.ord-no-results-desc{font-size:.82rem;color:var(--text-mute)}
.ord-help-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:2.5rem;padding:1.25rem 1.5rem;background:var(--surface);border:1px solid var(--border);border-radius:16px;flex-wrap:wrap}
.ord-help-left{display:flex;align-items:center;gap:.85rem}
.ord-help-icon{width:44px;height:44px;border-radius:12px;background:var(--ord-primary-subtle);display:flex;align-items:center;justify-content:center;color:var(--ord-primary);flex-shrink:0}
.ord-help-icon svg{width:22px;height:22px}
.ord-help-text{min-width:0}
.ord-help-title{font-size:.9rem;font-weight:700}
.ord-help-desc{font-size:.78rem;color:var(--text-mute)}
.ord-help-actions{display:flex;gap:.5rem;flex-wrap:wrap}
.ord-help-btn-sm{display:inline-flex;align-items:center;gap:.35rem;padding:.45rem 1rem;border-radius:8px;font-size:.78rem;font-weight:700;text-decoration:none;transition:all .2s;font-family:'Poppins',sans-serif;height:36px}
.ord-help-btn-sm.primary{background:var(--ord-primary);color:#fff;box-shadow:0 4px 14px -4px var(--ord-primary-glow)}
.ord-help-btn-sm.primary:hover{background:var(--ord-primary-light);transform:translateY(-2px)}
.ord-help-btn-sm.outline{border:1px solid var(--border);color:var(--text-dim);background:transparent}
.ord-help-btn-sm.outline:hover{border-color:var(--ord-primary);color:var(--ord-primary)}
.ord-pagination{display:flex;align-items:center;justify-content:center;gap:.35rem;margin-top:1.5rem;flex-wrap:wrap}
.ord-page-btn{padding:.4rem .75rem;border-radius:8px;font-size:.78rem;font-weight:600;background:var(--surface-2);border:1px solid var(--border);color:var(--text-dim);cursor:pointer;transition:all .2s;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:.25rem}
.ord-page-btn:hover{background:var(--surface-3);border-color:var(--border-strong)}
.ord-page-btn.active{background:var(--ord-primary);color:#fff;border-color:var(--ord-primary);box-shadow:0 0 16px -4px var(--ord-primary-glow)}
.ord-page-dots{padding:.3rem .2rem;color:var(--text-mute);font-size:.85rem;letter-spacing:.1em}
.ord-hidden{display:none!important}
@supports(-webkit-touch-callout:none){@media(min-width:601px){.ord-product-name{font-size:30px}}}
@media(max-width:900px){
.ord-card{flex-direction:column;padding:24px;gap:20px;min-height:auto}
.ord-card-left{flex:1;max-width:100%}
.ord-separator{width:100%;height:1px;margin:0}
.ord-card-right{flex:1;max-width:100%}
.ord-product-name{font-size:18px}
.ord-right-order-id{font-size:18px}
.ord-right-total{font-size:18px}
.ord-thumb{width:100px;min-width:100px;height:100px}
.ord-card-left{gap:16px}
}
@media(max-width:768px){
.ord-page{padding:1.5rem .75rem 3rem}
.ord-header{flex-direction:column;align-items:stretch}
.ord-filter-card{flex-direction:column;align-items:stretch}
.ord-filter-group{min-width:0}
.ord-filter-select,.ord-filter-input,.ord-search-wrap{max-width:none;width:100%}
.ord-help-footer{flex-direction:column;text-align:center}
.ord-help-left{flex-direction:column;text-align:center}
}
@media(max-width:600px){
.ord-list{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ord-card{display:flex;flex-direction:column;padding:14px 25px;gap:12px;border-radius:14px;width:100%;box-sizing:border-box;overflow:hidden}
.ord-card-left,.ord-card-right{width:100%;box-sizing:border-box}
.ord-card-left{display:flex;flex-direction:column;gap:10px}
.ord-separator{width:100%;height:1px;margin:0}
.ord-thumb{width:100%;height:100px;border-radius:10px;background-size:cover;background-position:center;overflow:hidden}
.ord-thumb img{width:100%;height:100%;object-fit:cover}
.ord-info{width:100%}
.ord-info-top{display:flex;flex-direction:column;gap:4px;align-items:flex-start}
.ord-product-name{font-size:12px;font-weight:700;line-height:1.25;overflow-wrap:anywhere;color:var(--text)}
.ord-status-badge{font-size:9px;height:20px;padding:0 8px;align-self:flex-start;order:-1}
.ord-meta{display:none}
.ord-meta-item{font-size:10px;color:var(--text-dim);display:flex;align-items:center;gap:6px;overflow-wrap:anywhere}
.ord-meta-item svg{width:12px;height:12px;flex-shrink:0}
.ord-meta-item strong{color:var(--text);font-weight:600;font-size:11px}
.ord-left-total{font-size:13px;font-weight:700;color:var(--text);margin-top:6px}
.ord-card-right{display:flex;flex-direction:column;gap:8px}
.ord-right-order-id{font-size:11px;font-weight:700;color:var(--text);overflow-wrap:anywhere;word-break:break-word}
.ord-right-grid{display:none}
.ord-detail-btn{display:inline-flex;align-items:center;justify-content:center;gap:4px;width:100%;height:28px;padding:0 10px;border-radius:6px;background:var(--ord-primary);color:#fff;font-size:10px;font-weight:600;border:none;cursor:pointer;text-decoration:none;font-family:'Poppins',sans-serif;box-sizing:border-box}
.ord-detail-btn svg{width:12px;height:12px}
.ord-tabs{gap:.35rem}
.ord-tab{font-size:.72rem;padding:.4rem .8rem}
}
</style>
@endpush

@section('content')
@php
  $ordWa = \App\Models\SiteSetting::get('contact_whatsapp', '');
  $ordWaDigits = preg_replace('/\D+/', '', $ordWa);
  if (str_starts_with($ordWaDigits, '0')) {
      $ordWaDigits = '62' . substr($ordWaDigits, 1);
  }
  $ordWaHref = $ordWaDigits ? 'https://wa.me/' . $ordWaDigits : route('kontak');
@endphp
<div class="ord-page">
  <div class="ord-header">
    <div class="ord-header-left">
      <h1 class="ord-title">PESANAN SAYA</h1>
      <p class="ord-subtitle">Lihat seluruh riwayat transaksi Top Up, Joki, dan Marketplace.</p>
    </div>
    <a href="{{ $ordWaHref }}" class="ord-help-btn" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h.01M12 10h.01M16 10h.01"/></svg>
      Butuh Bantuan?
    </a>
  </div>

  {{-- Panel Saldo: pembayaran masuk ke sini, baru dipotong setelah topup
       berhasil, dan dikembalikan otomatis kalau topup gagal / lewat batas waktu. --}}
  <div class="ord-balance">
    <div class="ord-balance-main">
      <div class="ord-balance-label">Saldo Saya</div>
      <div class="ord-balance-value">Rp{{ number_format((float) $balance, 0, ',', '.') }}</div>
      <div class="ord-balance-hint">
        @if($balance > 0)
          Saldo ini otomatis kembali ke Anda bila ada topup yang gagal atau tidak selesai.
        @else
          Saldo muncul di sini bila ada pembayaran yang belum terpakai atau topup gagal.
        @endif
      </div>
    </div>

    @if($balanceTransactions->isNotEmpty())
    <details class="ord-balance-log">
      <summary>Lihat riwayat mutasi saldo</summary>
      <ul class="ord-balance-list">
        @foreach($balanceTransactions as $tx)
          <li class="ord-balance-item">
            <div class="ord-balance-item-text">
              <div class="ord-balance-item-title">{{ $tx->typeLabel() }}</div>
              <div class="ord-balance-item-sub">
                {{ $tx->order_id ? \App\Models\Order::find($tx->order_id)?->order_id : 'Saldo' }}
                &middot; {{ $tx->created_at->format('d M Y, H:i') }}
              </div>
            </div>
            <div class="ord-balance-item-amount {{ $tx->affectsBalance() ? ($tx->amount >= 0 ? 'is-plus' : 'is-minus') : 'is-neutral' }}">
              @if($tx->affectsBalance())
                {{ $tx->amount >= 0 ? '+' : '-' }}Rp{{ number_format(abs((float) $tx->amount), 0, ',', '.') }}
              @else
                &mdash;
              @endif
            </div>
          </li>
        @endforeach
      </ul>
    </details>
    @endif
  </div>

  <div class="ord-tabs">
    <button class="ord-tab active" data-tab="all">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Semua
    </button>
    <button class="ord-tab" data-tab="topup">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Riwayat Top Up
    </button>
    <button class="ord-tab" data-tab="joki">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Riwayat Joki
    </button>
    <button class="ord-tab" data-tab="marketplace">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      Marketplace
    </button>
  </div>

  @if($orders->isNotEmpty())
  <div class="ord-filter-card">
    <div class="ord-search-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4.35-4.35"/></svg>
      <input type="text" id="ordSearchInput" placeholder="Cari Order ID atau Nama Game">
    </div>
    <div class="ord-filter-group">
      <input type="date" class="ord-filter-input" id="ordDateFilter">
    </div>
    <div class="ord-filter-group">
      <select class="ord-filter-select" id="ordStatusFilter">
        <option value="all">Semua Status</option>
        <option value="success">Berhasil</option>
        <option value="processing">Diproses</option>
        <option value="pending">Pending</option>
        <option value="failed">Gagal</option>
        <option value="refund">Refund</option>
      </select>
    </div>
  </div>
  @endif

  @if($orders->isEmpty())
    <div class="ord-empty">
      <div class="ord-empty-icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
      </div>
      <h3 class="ord-empty-title">Belum Ada Transaksi</h3>
      <p class="ord-empty-desc">Kamu belum memiliki riwayat transaksi. Mulai Top Up, Joki, atau Marketplace sekarang.</p>
      <a href="{{ route('home') }}" class="btn btn-solid">Mulai Belanja</a>
    </div>
  @else
    <div class="ord-list" id="ordList">
      @foreach($orders as $order)
        @php
          $brand = $brands->get(strtolower((string) $order->brand));
          $product = $products->get($order->buyer_sku_code);
          $thumbnail = $brand?->thumbnail_url;
          $paymentType = $order->transaction?->payment_type;
          $cat = strtolower($order->category ?? 'topup');
          $tabCat = in_array($cat, ['joki', 'marketplace']) ? $cat : 'topup';
          $dateStr = $order->created_at->format('d M Y');
          $timeStr = $order->created_at->format('H:i') . ' WIB';
          $payLogo = payment_logo_asset($paymentType);
        @endphp
        <div class="ord-card" data-category="{{ $tabCat }}" data-status="{{ $order->status }}" data-date="{{ $order->created_at->format('Y-m-d') }}" data-search="{{ strtolower($order->order_id . ' ' . $order->brand . ' ' . $order->product_name) }}">
          <div class="ord-card-left">
            <div class="ord-thumb" @if($thumbnail) style="background-image:url('{{ $thumbnail }}')" @endif>
              @if(!$thumbnail)
                <span>{{ strtoupper(substr($order->brand, 0, 2)) }}</span>
              @endif
            </div>
            <div class="ord-info">
              <div class="ord-info-top">
                <span class="ord-product-name">{{ $order->product_name }}</span>
                <span class="ord-status-badge ord-status-badge--{{ $order->status === 'refund' ? 'refund' : $order->status }}">
                  @if($order->status === 'success') SUKSES
                  @elseif($order->status === 'pending') PENDING
                  @elseif($order->status === 'processing') DIPROSES
                  @elseif($order->status === 'failed') GAGAL
                  @elseif($order->status === 'refund') REFUND
                  @else {{ strtoupper($order->status) }}
                  @endif
                </span>
              </div>
              @if($order->isRefunded())
                <span class="ord-refund-note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                  Saldo Rp{{ number_format((float) $order->saldo_amount, 0, ',', '.') }} sudah dikembalikan
                </span>
              @elseif($order->status === 'processing' && $order->saldo_status === 'held')
                <span class="ord-refund-note" style="background:rgba(96,165,250,.15);color:#60a5fa">
                  Saldo Rp{{ number_format((float) $order->saldo_amount, 0, ',', '.') }} sedang diproses topup
                </span>
              @endif
              <div class="ord-meta">
                <span class="ord-meta-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/></svg>
                  <strong>{{ $order->brand }}</strong>
                </span>
                <span class="ord-meta-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 9H2"/></svg>
                  <strong>{{ $order->customer_number }}</strong>
                  @if($order->effective_zone_id)
                    <span>({{ $order->effective_zone_id }})</span>
                  @endif
                </span>
              </div>
              <div class="ord-right-total ord-left-total">Rp{{ number_format($order->price, 0, ',', '.') }}</div>
            </div>
          </div>

          <div class="ord-separator"></div>

          <div class="ord-card-right">
            <div class="ord-right-order-id">#{{ $order->order_id }}</div>

            <div class="ord-right-grid">
              <div>
                <div class="ord-right-label">Tanggal Order</div>
                <div class="ord-right-value ord-date-value">{{ $dateStr }} {{ $timeStr }}</div>
              </div>
              <div>
                <div class="ord-right-label">Metode Pembayaran</div>
                <div class="ord-right-value">
                  @if($payLogo)
                    <div class="ord-payment-display">
                      <img src="{{ $payLogo }}" alt="{{ $paymentType }}" loading="lazy">
                    </div>
                  @elseif($paymentType)
                    <div class="ord-payment-display">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8h20"/></svg>
                      <span>{{ $paymentType }}</span>
                    </div>
                  @else
                    <span style="color:var(--text-mute);font-size:13px">—</span>
                  @endif
                </div>
              </div>
            </div>

            <a href="{{ route('orders.show', $order) }}" class="ord-detail-btn">
              Detail Transaksi
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
            </a>
          </div>
        </div>
      @endforeach

      <div class="ord-no-results" id="ordNoResults">
        <div class="ord-no-results-title">Tidak ada transaksi ditemukan</div>
        <div class="ord-no-results-desc">Coba ubah filter atau kata kunci pencarian.</div>
      </div>
    </div>

    @if($orders->hasPages())
    <div class="ord-pagination">
      @if($orders->onFirstPage())
        <span class="ord-page-btn" style="opacity:.4;cursor:default">&lsaquo; Sebelumnya</span>
      @else
        <a href="{{ $orders->previousPageUrl() }}" class="ord-page-btn">&lsaquo; Sebelumnya</a>
      @endif

      @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
        @if($page == $orders->currentPage())
          <span class="ord-page-btn active">{{ $page }}</span>
        @elseif($page == 1 || $page == $orders->lastPage() || abs($page - $orders->currentPage()) <= 2)
          <a href="{{ $url }}" class="ord-page-btn">{{ $page }}</a>
        @elseif($page == 2 || $page == $orders->lastPage() - 1)
          <span class="ord-page-dots">…</span>
        @endif
      @endforeach

      @if($orders->hasMorePages())
        <a href="{{ $orders->nextPageUrl() }}" class="ord-page-btn">Selanjutnya &rsaquo;</a>
      @else
        <span class="ord-page-btn" style="opacity:.4;cursor:default">Selanjutnya &rsaquo;</span>
      @endif
    </div>
    @endif
  @endif

  <div class="ord-help-footer">
    <div class="ord-help-left">
      <div class="ord-help-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <div class="ord-help-text">
        <div class="ord-help-title">Butuh Bantuan?</div>
        <div class="ord-help-desc">Tim support kami siap membantu 24 jam.</div>
      </div>
    </div>
    <div class="ord-help-actions">
      <a href="{{ $ordWaHref }}" class="ord-help-btn-sm primary" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        Chat Admin
      </a>
      <a href="{{ route('faq') }}" class="ord-help-btn-sm outline">FAQ</a>
    </div>
  </div>
</div>

@if($orders->isNotEmpty())
<script>
(function(){
  const tabs = document.querySelectorAll('.ord-tab');
  const cards = document.querySelectorAll('.ord-card');
  const statusFilter = document.getElementById('ordStatusFilter');
  const dateFilter = document.getElementById('ordDateFilter');
  const searchInput = document.getElementById('ordSearchInput');
  const noResults = document.getElementById('ordNoResults');
  let activeTab = 'all';

  function filterCards(){
    let visibleCount = 0;
    cards.forEach(card => {
      const cat = card.dataset.category;
      const status = card.dataset.status;
      const date = card.dataset.date;
      const search = card.dataset.search;
      const searchVal = searchInput.value.trim().toLowerCase();

      const matchTab = activeTab === 'all' || cat === activeTab;
      const matchStatus = statusFilter.value === 'all' || status === statusFilter.value;
      const matchDate = !dateFilter.value || date === dateFilter.value;
      const matchSearch = !searchVal || search.includes(searchVal);

      const show = matchTab && matchStatus && matchDate && matchSearch;
      card.classList.toggle('ord-hidden', !show);
      if (show) visibleCount++;
    });
    noResults.classList.toggle('show', visibleCount === 0);
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', function(){
      tabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      activeTab = this.dataset.tab;
      filterCards();
    });
  });

  statusFilter.addEventListener('change', filterCards);
  dateFilter.addEventListener('input', filterCards);
  searchInput.addEventListener('input', filterCards);
})();
</script>
@endif
@endsection
