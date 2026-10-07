@extends('layouts.topup')

@section('title', 'Detail Transaksi — ' . $order->order_id)

@section('content')
@php
  $statusBadge = match($order->status) {
    'success' => ['label' => 'PEMBELIAN SUKSES', 'color' => '#10b981'],
    'processing' => ['label' => 'SEDANG DIPROSES', 'color' => '#f59e0b'],
    'failed' => ['label' => 'PEMBAYARAN GAGAL', 'color' => '#ef4444'],
    'refund' => ['label' => 'REFUND', 'color' => '#94a3b8'],
    default => ['label' => 'MENUNGGU PEMBAYARAN', 'color' => '#854DEA'],
  };
  $paymentMethod = $order->transaction?->payment_type;
  $paymentLogo = payment_logo_asset($paymentMethod);
@endphp

<div class="dt-wrap">
  {{-- BREADCRUMB --}}
  <nav class="dt-breadcrumb">
    <a href="{{ route('orders.my') }}">Pesanan Saya</a>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
    <span>Detail Transaksi</span>
  </nav>

  {{-- HEADER --}}
  <div class="dt-header">
    <div class="dt-header-left">
      <h1 class="dt-title">Detail Transaksi</h1>
      <p class="dt-subtitle">Informasi lengkap transaksi Top Up kamu.</p>
    </div>
    <div class="dt-header-actions">
      <a href="{{ route('orders.my') }}" class="dt-btn-outline">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
      </a>
      <a href="{{ route('kontak') }}" class="dt-btn-outline">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 14h3a2 2 0 012 2v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a9 9 0 0118 0v7a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3"/></svg>
        Butuh Bantuan?
      </a>
    </div>
  </div>

  {{-- SUMMARY ORDER CARD --}}
  <div class="dt-summary">
    <div class="dt-summary-left">
      <div class="dt-summary-thumb">
        @if($brand && $brand->thumbnail_url)
          <img src="{{ $brand->thumbnail_url }}" alt="{{ $order->brand }}">
        @else
          <span>{{ $brand->icon ?? '🎮' }}</span>
        @endif
      </div>
    </div>
    <div class="dt-summary-body">
      <div class="dt-summary-badge" style="background:{{ $statusBadge['color'] }}20;color:{{ $statusBadge['color'] }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
        {{ $statusBadge['label'] }}
      </div>
      <div class="dt-summary-invoice">#{{ $order->order_id }}</div>
      <div class="dt-summary-info">
        <div class="dt-summary-info-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          <span class="dt-summary-info-label">Produk</span>
          <span class="dt-summary-info-value">{{ $order->product_name }}@if($order->quantity > 1) ({{ $order->quantity }}x)@endif</span>
        </div>
        <div class="dt-summary-info-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <span class="dt-summary-info-label">User ID</span>
          <span class="dt-summary-info-value">{{ $order->customer_number }}@if($order->effective_zone_id) ({{ $order->effective_zone_id }})@endif</span>
        </div>
        <div class="dt-summary-info-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 2v4"/><path d="M6 2v4"/><path d="M18 2v4"/></svg>
          <span class="dt-summary-info-label">Game</span>
          <span class="dt-summary-info-value">{{ $order->brand }}</span>
        </div>
        <div class="dt-summary-info-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <span class="dt-summary-info-label">Tanggal Order</span>
          <span class="dt-summary-info-value">{{ $order->created_at->format('d M Y, H:i') }} WIB</span>
        </div>
      </div>
    </div>
  </div>

  {{-- GRID 2 KOLOM --}}
  <div class="dt-grid">
    {{-- LEFT COLUMN --}}
    <div class="dt-grid-left">

      {{-- DETAIL TRANSAKSI --}}
      <div class="dt-card">
        <div class="dt-card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Detail Transaksi
        </div>
        <div class="dt-divider"></div>
        <div class="dt-detail-list">
          <div class="dt-detail-row">
            <span class="dt-detail-label">Order ID</span>
            <span class="dt-detail-value dt-mono">#{{ $order->order_id }}</span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row">
            <span class="dt-detail-label">Status</span>
            <span class="dt-status-badge" style="background:{{ $statusBadge['color'] }}20;color:{{ $statusBadge['color'] }}">
              <span class="dt-status-dot" style="background:{{ $statusBadge['color'] }}"></span>
              {{ $statusBadge['label'] }}
            </span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row">
            <span class="dt-detail-label">Tanggal</span>
            <span class="dt-detail-value">{{ $order->created_at->format('d M Y, H:i') }} WIB</span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row">
            <span class="dt-detail-label">Produk</span>
            <span class="dt-detail-value">{{ $order->product_name }} @if($order->quantity > 1)({{ $order->quantity }}x)@endif</span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row">
            <span class="dt-detail-label">Game</span>
            <span class="dt-detail-value">{{ $order->brand }}</span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row">
            <span class="dt-detail-label">User ID</span>
            <span class="dt-detail-value">{{ $order->customer_number }}@if($order->effective_zone_id) ({{ $order->effective_zone_id }})@endif</span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row">
            <span class="dt-detail-label">Metode Pembayaran</span>
            <span class="dt-detail-value dt-payment">
              @if($paymentLogo)
                <img src="{{ $paymentLogo }}" alt="{{ $paymentMethod }}" class="dt-payment-logo">
              @endif
              <span>{{ $paymentMethod ? ucwords(str_replace('_', ' ', $paymentMethod)) : '-' }}</span>
            </span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row dt-detail-total">
            <span class="dt-detail-label">Total Pembayaran</span>
            <span class="dt-detail-value dt-price">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
          </div>
        </div>
      </div>

      {{-- AKTIVITAS ORDER --}}
      <div class="dt-card">
        <div class="dt-card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Aktivitas Order
        </div>
        <div class="dt-divider"></div>
        <div class="dt-timeline">
          @php
            $steps = [
              ['label' => 'Pesanan Dibuat', 'desc' => 'Pesanan berhasil dibuat dan tercatat', 'time' => $order->created_at->format('d M H:i')],
              ['label' => 'Pembayaran Berhasil', 'desc' => 'Pembayaran telah dikonfirmasi', 'time' => $order->transaction?->updated_at?->format('d M H:i') ?? '-'],
              ['label' => 'Top Up Diproses', 'desc' => 'Pesanan sedang diproses sistem', 'time' => in_array($order->status, ['processing', 'success']) ? $order->updated_at->format('d M H:i') : '-'],
              ['label' => 'Top Up Berhasil', 'desc' => $order->note ? 'SN: '.$order->note : 'Saldo sudah masuk ke akun game', 'time' => $order->status === 'success' ? $order->updated_at->format('d M H:i') : '-'],
            ];
            $completedUntil = match($order->status) {
              'success' => 4,
              'processing' => 3,
              default => 1,
            };
          @endphp
          @foreach($steps as $i => $step)
            @php $done = $i < $completedUntil; $active = $i === $completedUntil - 1; @endphp
            <div class="dt-timeline-item">
              <div class="dt-timeline-line">
                <div class="dt-timeline-dot {{ $done ? 'done' : ($active ? 'active' : '') }}">
                  @if($done)
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" width="12" height="12"><path d="M20 6 9 17l-5-5"/></svg>
                  @endif
                </div>
                @if(!$loop->last)<div class="dt-timeline-connector"></div>@endif
              </div>
              <div class="dt-timeline-content">
                <div class="dt-timeline-label">{{ $step['label'] }}</div>
                <div class="dt-timeline-desc">{{ $step['desc'] }}</div>
              </div>
              <div class="dt-timeline-time">{{ $step['time'] }}</div>
            </div>
          @endforeach
        </div>
      </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="dt-grid-right">

      {{-- RINCIAN PEMBELIAN --}}
      <div class="dt-card">
        <div class="dt-card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          Rincian Pembelian
        </div>
        <div class="dt-divider"></div>
        <div class="dt-purchase-product">
          <div class="dt-purchase-thumb">
            @if($product && $product->photo_url)
              <img src="{{ $product->photo_url }}" alt="{{ $order->product_name }}">
            @elseif($brand && $brand->thumbnail_url)
              <img src="{{ $brand->thumbnail_url }}" alt="{{ $order->brand }}">
            @else
              <span>{{ $brand->icon ?? '🎮' }}</span>
            @endif
          </div>
          <div class="dt-purchase-info">
            <div class="dt-purchase-name">{{ $order->product_name }}</div>
            <div class="dt-purchase-game">{{ $order->brand }}</div>
          </div>
          <div class="dt-purchase-price">Rp {{ number_format($order->price / max($order->quantity, 1), 0, ',', '.') }}</div>
        </div>
        <div class="dt-divider"></div>
        <div class="dt-purchase-details">
          <div class="dt-purchase-row">
            <span>Subtotal</span>
            <span>Rp {{ number_format($order->price, 0, ',', '.') }}</span>
          </div>
          <div class="dt-purchase-row">
            <span>Biaya Admin</span>
            <span class="dt-green">Gratis</span>
          </div>
          <div class="dt-purchase-row">
            <span>Voucher / Diskon</span>
            @if($order->voucher)
              <span class="dt-green">{{ $order->voucher->code }} ({{ $order->voucher->value_label }})</span>
            @else
              <span>-</span>
            @endif
          </div>
          <div class="dt-divider"></div>
          <div class="dt-purchase-total">
            <span>Total Pembayaran</span>
            <span class="dt-total-price">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
          </div>
        </div>
      </div>

      {{-- INFORMASI AKUN --}}
      <div class="dt-card">
        <div class="dt-card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Informasi Akun
        </div>
        <div class="dt-divider"></div>
        <div class="dt-detail-list">
          <div class="dt-detail-row">
            <span class="dt-detail-label">User ID</span>
            <span class="dt-detail-value">{{ $order->customer_number }}</span>
          </div>
          <div class="dt-divider"></div>
          <div class="dt-detail-row">
            <span class="dt-detail-label">Server / Zone</span>
            <span class="dt-detail-value">{{ $order->effective_zone_id ?: '-' }}</span>
          </div>
        </div>
      </div>

      {{-- BANTUAN & INFORMASI --}}
      <div class="dt-card">
        <div class="dt-card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Bantuan & Informasi
        </div>
        <div class="dt-divider"></div>
        <div class="dt-faq-list">
          <a href="{{ route('faq') }}#cara-top-up" class="dt-faq-item">
            <span>Bagaimana Cara Top Up?</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </a>
          <div class="dt-divider"></div>
          <a href="{{ route('faq') }}#top-up-belum-masuk" class="dt-faq-item">
            <span>Kenapa Top Up Belum Masuk?</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </a>
          <div class="dt-divider"></div>
          <a href="{{ route('faq') }}#salah-id" class="dt-faq-item">
            <span>Bagaimana Jika Salah ID?</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </a>
          <div class="dt-divider"></div>
          <a href="{{ route('kontak') }}" class="dt-faq-item">
            <span>Cara Menghubungi Admin</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </a>
        </div>
      </div>

    </div>
  </div>

  {{-- REKOMENDASI PRODUK --}}
  @if(isset($recommendedProducts) && $recommendedProducts->isNotEmpty())
  <section class="dt-recommend">
    <div class="dt-recommend-head">
      <h3>Top Up Lagi?</h3>
      <a href="{{ route('games.show', $order->brand) }}" class="dt-recommend-link">
        Lihat Semua
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
      </a>
    </div>
    <div class="gd-pkg-grid">
      @foreach($recommendedProducts as $product)
      <a href="{{ route('games.show', $order->brand) }}?product={{ $product->id }}" class="gd-pkg-card">
        @if($product->photo_url)
          <img class="gd-pkg-img" src="{{ $product->photo_url }}" alt="{{ $product->product_name }}">
        @else
          <svg class="gd-gem" viewBox="0 0 32 32" width="28" height="28">
            <defs><linearGradient id="rg{{ $loop->index }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#854DEA"/><stop offset="100%" stop-color="#4c1d95"/></linearGradient></defs>
            <polygon points="16,2 27,11 22,30 10,30 5,11" fill="url(#rg{{ $loop->index }})"/>
            <polygon points="16,2 27,11 16,14" fill="#e4d9ff" opacity=".55"/>
            <polygon points="16,2 5,11 16,14" fill="#f4eeff" opacity=".8"/>
            <polygon points="5,11 16,14 10,30" fill="#6d33d6" opacity=".7"/>
            <polygon points="27,11 16,14 22,30" fill="#4c1d95" opacity=".75"/>
          </svg>
        @endif
        <span class="gd-pkg-info">
          <span class="gd-pkg-amt">{{ $product->product_name }}</span>
          <span class="gd-pkg-price">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
        </span>
      </a>
      @endforeach
    </div>
  </section>
  @endif
</div>

<style>
.dt-wrap{max-width:1400px;margin:1.5rem auto 3rem;padding:0 2rem;}
.dt-breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--text-dim);margin-bottom:1.5rem;}
.dt-breadcrumb a{color:var(--text-dim);text-decoration:none;transition:color .2s;}
.dt-breadcrumb a:hover{color:var(--text);}
.dt-breadcrumb span{color:var(--text-muted);}
.dt-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem;gap:1rem;}
.dt-title{font-size:2.6rem;font-weight:800;line-height:1.1;margin:0 0 .3rem;letter-spacing:-.02em;}
.dt-subtitle{color:var(--text-dim);font-size:.92rem;margin:0;}
.dt-header-actions{display:flex;gap:.6rem;flex-shrink:0;}
.dt-btn-outline{display:inline-flex;align-items:center;gap:.45rem;padding:.5rem 1.1rem;border-radius:10px;font-size:.82rem;font-weight:600;color:var(--text-dim);background:transparent;border:1px solid var(--border);text-decoration:none;transition:all .25s;white-space:nowrap;}
.dt-btn-outline:hover{background:var(--surface-2);border-color:var(--border-strong);color:var(--text);}
.dt-summary{display:flex;align-items:center;gap:1.5rem;padding:1.5rem 2rem;background:var(--surface);border:1px solid var(--border);border-radius:16px;margin-bottom:2rem;}
.dt-summary-thumb{width:90px;height:90px;border-radius:10px;overflow:hidden;background:var(--bg-soft);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:2.4rem;}
.dt-summary-thumb img{width:100%;height:100%;object-fit:cover;}
.dt-summary-body{flex:1;min-width:0;}
.dt-summary-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .65rem;border-radius:6px;font-size:.68rem;font-weight:700;letter-spacing:.04em;margin-bottom:.5rem;}
.dt-summary-invoice{font-size:1.3rem;font-weight:800;margin-bottom:.7rem;letter-spacing:-.01em;color:var(--text);}
.dt-summary-info{display:flex;flex-wrap:wrap;gap:.6rem 1.8rem;}
.dt-summary-info-item{display:flex;align-items:center;gap:.4rem;font-size:.8rem;}
.dt-summary-info-label{color:var(--text-dim);}
.dt-summary-info-value{color:var(--text);font-weight:500;}
.dt-summary-info-item svg{color:var(--text-dim);flex-shrink:0;width:14px;height:14px;}
.dt-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:1.5rem;margin-bottom:2.5rem;}
.dt-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.3rem 1.5rem;margin-bottom:1.2rem;transition:box-shadow .3s;}
.dt-card:hover{box-shadow:0 8px 32px -8px rgba(0,0,0,.15);}
.dt-card-title{display:flex;align-items:center;gap:.55rem;font-size:.92rem;font-weight:700;color:var(--text);margin-bottom:0;}
.dt-card-title svg{color:#854DEA;flex-shrink:0;}
.dt-divider{height:1px;background:var(--border);margin:.7rem 0;}
.dt-detail-list{display:flex;flex-direction:column;}
.dt-detail-row{display:flex;justify-content:space-between;align-items:center;padding:.25rem 0;gap:1rem;}
.dt-detail-label{color:var(--text-dim);font-size:.84rem;white-space:nowrap;}
.dt-detail-value{color:var(--text);font-size:.84rem;font-weight:500;text-align:right;display:flex;align-items:center;gap:.5rem;}
.dt-mono{font-family:monospace;font-size:.8rem;}
.dt-detail-total{padding:.5rem 0 0;}
.dt-detail-total .dt-detail-label{font-weight:700;font-size:.9rem;}
.dt-price{font-size:1rem;font-weight:800;color:#854DEA;}
.dt-status-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .65rem;border-radius:6px;font-size:.72rem;font-weight:700;white-space:nowrap;}
.dt-status-dot{width:6px;height:6px;border-radius:50%;display:inline-block;}
.dt-payment-logo{width:20px;height:20px;object-fit:contain;border-radius:4px;}

/* timeline */
.dt-timeline{padding:.25rem 0;}
.dt-timeline-item{display:flex;gap:.8rem;position:relative;}
.dt-timeline-line{display:flex;flex-direction:column;align-items:center;width:24px;flex-shrink:0;}
.dt-timeline-dot{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:2px solid var(--border);background:transparent;transition:all .3s;}
.dt-timeline-dot.done{background:#854DEA;border-color:#854DEA;}
.dt-timeline-dot.active{background:#854DEA;border-color:#854DEA;animation:dtPulse 2s infinite;}
@keyframes dtPulse{0%,100%{box-shadow:0 0 0 0 rgba(133,77,234,.5)}50%{box-shadow:0 0 0 8px rgba(133,77,234,0)}}
.dt-timeline-connector{width:2px;flex:1;min-height:24px;background:var(--border);margin:4px 0;}
.dt-timeline-content{flex:1;padding-bottom:1.2rem;min-width:0;}
.dt-timeline-label{font-size:.84rem;font-weight:600;color:var(--text);}
.dt-timeline-desc{font-size:.75rem;color:var(--text-dim);margin-top:.15rem;}
.dt-timeline-time{font-size:.72rem;color:var(--text-dim);white-space:nowrap;padding-top:.15rem;}

/* purchase details */
.dt-purchase-product{display:flex;align-items:center;gap:.8rem;}
.dt-purchase-thumb{width:48px;height:48px;border-radius:10px;overflow:hidden;background:var(--bg-soft);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.4rem;}
.dt-purchase-thumb img{width:100%;height:100%;object-fit:cover;}
.dt-purchase-info{flex:1;min-width:0;}
.dt-purchase-name{font-size:.84rem;font-weight:600;color:var(--text);}
.dt-purchase-game{font-size:.74rem;color:var(--text-dim);}
.dt-purchase-price{font-size:.84rem;font-weight:700;color:#854DEA;white-space:nowrap;}
.dt-purchase-details{padding:.25rem 0;}
.dt-purchase-row{display:flex;justify-content:space-between;align-items:center;padding:.35rem 0;font-size:.84rem;color:var(--text-dim);}
.dt-purchase-total{display:flex;justify-content:space-between;align-items:center;padding:.5rem 0 0;font-size:.92rem;font-weight:700;color:var(--text);}
.dt-total-price{font-size:1.1rem;font-weight:800;color:#854DEA;}
.dt-green{color:#10b981;}

/* faq */
.dt-faq-list{display:flex;flex-direction:column;}
.dt-faq-item{display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;color:var(--text-dim);text-decoration:none;font-size:.84rem;transition:color .25s;cursor:pointer;}
.dt-faq-item:hover{color:#854DEA;}
.dt-faq-item svg{flex-shrink:0;opacity:.4;transition:opacity .25s;}
.dt-faq-item:hover svg{opacity:1;}

/* recommendations */
.dt-recommend{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.5rem 2rem;}
.dt-recommend-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;}
.dt-recommend-head h3{font-size:1.1rem;font-weight:700;margin:0;color:var(--text);}
.dt-recommend-link{display:inline-flex;align-items:center;gap:.35rem;font-size:.82rem;font-weight:600;color:#854DEA;text-decoration:none;transition:gap .25s;}
.dt-recommend-link:hover{gap:.6rem;}

.gd-pkg-grid{display:grid;grid-template-columns:1fr;gap:.6rem;}
.gd-pkg-card{display:flex;align-items:center;gap:.6rem;text-align:left;background:var(--surface-2);border:1.5px solid var(--border);border-radius:11px;padding:.75rem .8rem;cursor:pointer;transition:border-color .16s ease,background .16s ease,transform .12s ease;font-family:inherit;color:inherit;width:100%;text-decoration:none;}
.gd-pkg-card:hover{border-color:var(--border-strong);transform:translateY(-1px);}
.gd-gem{width:28px;height:28px;flex-shrink:0;}
.gd-pkg-img{width:32px;height:32px;border-radius:6px;object-fit:contain;flex-shrink:0;}
.gd-pkg-info{flex:1;min-width:0;}
.gd-pkg-amt{font-size:.78rem;font-weight:600;display:block;line-height:1.3;color:var(--text);}
.gd-pkg-price{font-size:.78rem;font-weight:700;color:#854DEA;margin-top:.1rem;display:block;}

@media(min-width:640px){
  .gd-pkg-grid{grid-template-columns:repeat(2,1fr);}
}
@media(min-width:860px){
  .gd-pkg-grid{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:1024px){
  .dt-grid{grid-template-columns:1fr;}
}
@media(max-width:768px){
  .dt-header{flex-direction:column;}
  .dt-header-actions{width:100%;}
  .dt-header-actions .dt-btn-outline{flex:1;justify-content:center;}
  .dt-summary{flex-direction:column;align-items:flex-start;padding:1.2rem;}
  .dt-summary-thumb{width:68px;height:68px;}
  .dt-summary-info{flex-direction:column;gap:.35rem;}
  .dt-title{font-size:1.8rem;}
}
@media(max-width:480px){
  .dt-wrap{padding:0 1rem;}
  .dt-timeline-time{display:none;}
}
</style>
@endsection


