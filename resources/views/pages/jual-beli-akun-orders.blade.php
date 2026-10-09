@extends('layouts.topup')

@section('title', 'Pesanan Saya Akun - ' . config('app.name'))

@php
$paymentLabels = [
    'qris' => 'QRIS', 'gopay' => 'GoPay', 'dana' => 'DANA', 'ovo' => 'OVO', 'shopeepay' => 'ShopeePay', 'linkaja' => 'LinkAja',
    'bca_va' => 'BCA Virtual Account', 'bni_va' => 'BNI Virtual Account', 'bri_va' => 'BRI Virtual Account',
    'mandiri_va' => 'Mandiri Virtual Account', 'permata_va' => 'Permata Virtual Account',
    'alfamart' => 'Alfamart', 'indomaret' => 'Indomaret',
];
@endphp

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

:root{--aco-primary:#854DEA;--aco-primary-light:#a575ff;--aco-primary-glow:rgba(133,77,234,.35);--aco-primary-subtle:rgba(133,77,234,.12)}
.aco-page{width:100%;max-width:var(--layout-max);margin:0 auto;padding:2rem var(--layout-gutter) 4rem;font-family:'Poppins',sans-serif;animation:acoFadeIn .4s ease}
@keyframes acoFadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.aco-breadcrumb{font-size:.78rem;color:var(--text-mute);margin-bottom:.75rem;display:flex;align-items:center;gap:.35rem}
.aco-breadcrumb a{color:var(--text-mute);text-decoration:none}
.aco-breadcrumb a:hover{color:var(--aco-primary)}
.aco-breadcrumb .sep{opacity:.4}
.aco-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap}
.aco-header-left{min-width:0}
.aco-title{font-family:'Poppins',sans-serif;font-size:1.75rem;font-weight:800;letter-spacing:-.02em}
.aco-subtitle{font-size:.85rem;color:var(--text-mute);margin-top:.15rem}
.aco-shop-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.55rem 1.1rem;border-radius:10px;background:var(--aco-primary-subtle);color:var(--text);font-size:.8rem;font-weight:700;border:1px solid transparent;transition:all .2s;text-decoration:none;white-space:nowrap;font-family:'Poppins',sans-serif}
.aco-shop-btn:hover{background:rgba(133,77,234,.18);border-color:var(--border-strong);transform:translateY(-2px);box-shadow:0 4px 14px -4px var(--aco-primary-glow)}

.aco-list{display:flex;flex-direction:column;gap:24px}
.aco-card{display:flex;background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px 32px;transition:all .25s ease;animation:acoFadeIn .35s ease both}
.aco-card:hover{border-color:var(--aco-primary);transform:translateY(-4px);box-shadow:0 10px 35px rgba(133,77,234,.18)}
.aco-card-left{display:flex;gap:28px;flex:0 0 52%;max-width:52%;align-items:flex-start}
.aco-thumb{width:145px;min-width:145px;height:145px;border-radius:12px;background:var(--surface-2);background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--text-mute);overflow:hidden}
.aco-thumb img{width:100%;height:100%;object-fit:cover}
.aco-info{display:flex;flex-direction:column;min-width:0;flex:1}
.aco-info-top{display:flex;align-items:center;gap:0}
.aco-product-name{font-size:18px;font-weight:700;line-height:1.1;color:var(--text);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.aco-status-badge{display:inline-flex;align-items:center;font-size:.65rem;font-weight:700;padding:0 14px;height:30px;border-radius:999px;text-transform:uppercase;letter-spacing:.04em;margin-left:14px;white-space:nowrap;flex-shrink:0}
.aco-status-badge--success{background:rgba(52,211,153,.15);color:#34d399}
.aco-status-badge--pending{background:rgba(251,191,36,.15);color:#fbbf24}
.aco-status-badge--processing{background:rgba(96,165,250,.15);color:#60a5fa}
.aco-status-badge--failed{background:rgba(248,113,113,.15);color:#f87171}
.aco-meta{display:flex;flex-direction:column;gap:10px;margin-top:6px}
.aco-meta-item{font-size:12px;color:var(--text-dim);display:flex;align-items:center;gap:10px}
.aco-meta-item svg{width:20px;height:20px;color:var(--text-dim);flex-shrink:0}
.aco-meta-item strong{color:var(--text);font-weight:600;font-size:14px}
.aco-separator{width:1px;background:var(--border);flex-shrink:0;margin:0 28px;height:80%;align-self:center}
.aco-card-right{flex:1;min-width:0;display:flex;flex-direction:column;padding:2px 0}
.aco-right-order-id{font-size:18px;font-weight:700;color:var(--text);line-height:1.1;margin-bottom:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aco-right-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;margin-bottom:16px}
.aco-right-label{font-size:12px;color:var(--text-mute);font-weight:500;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px}
.aco-right-value{font-size:14px;color:var(--text);font-weight:500;line-height:1.4}
.aco-right-total{font-size:20px;font-weight:800;color:var(--aco-primary);line-height:1;margin-bottom:12px}
.aco-left-total{margin-bottom:0;margin-top:20px;color:var(--text)}
.aco-actions{display:flex;gap:10px;margin-top:auto;flex-wrap:wrap}
.aco-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:36px;padding:0 18px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:all .25s;font-family:'Poppins',sans-serif}
.aco-btn svg{width:16px;height:16px}
.aco-btn.primary{background:var(--aco-primary);color:#fff;box-shadow:0 4px 14px -4px var(--aco-primary-glow)}
.aco-btn.primary:hover{background:var(--aco-primary-light);transform:translateY(-2px)}
.aco-btn.outline{border:1px solid var(--border);color:var(--text-dim);background:transparent}
.aco-btn.outline:hover{border-color:var(--aco-primary);color:var(--aco-primary)}
.aco-note{font-size:.78rem;color:var(--text-mute);margin-top:4px;font-style:italic}
.aco-empty{text-align:center;padding:5rem 1.5rem;background:var(--surface);border-radius:16px;border:1px solid var(--border)}
.aco-empty-icon{display:flex;align-items:center;justify-content:center;color:var(--text-mute);margin-bottom:1.25rem;opacity:.4}
.aco-empty-title{font-size:1.15rem;font-weight:600;margin-bottom:.35rem}
.aco-empty-desc{color:var(--text-mute);font-size:.9rem;margin-bottom:1.5rem;max-width:380px;margin-left:auto;margin-right:auto}
.aco-pagination{display:flex;align-items:center;justify-content:center;gap:.35rem;margin-top:1.5rem;flex-wrap:wrap}
.aco-page-btn{padding:.4rem .75rem;border-radius:8px;font-size:.78rem;font-weight:600;background:var(--surface-2);border:1px solid var(--border);color:var(--text-dim);cursor:pointer;transition:all .2s;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:.25rem}
.aco-page-btn:hover{background:var(--surface-3);border-color:var(--border-strong)}
.aco-page-btn.active{background:var(--aco-primary);color:#fff;border-color:var(--aco-primary);box-shadow:0 0 16px -4px var(--aco-primary-glow)}
.aco-page-dots{padding:.3rem .2rem;color:var(--text-mute);font-size:.85rem;letter-spacing:.1em}
@media(max-width:900px){
.aco-card{flex-direction:column;padding:24px;gap:20px}
.aco-card-left{flex:1;max-width:100%}
.aco-separator{width:100%;height:1px;margin:0}
.aco-card-right{flex:1;max-width:100%}
.aco-thumb{width:100px;min-width:100px;height:100px}
.aco-card-left{gap:16px}
}
@media(max-width:600px){
.aco-page{padding:1.5rem var(--layout-gutter) 3rem}
.aco-header{flex-direction:column;align-items:stretch}
.aco-info-top{flex-direction:column;gap:4px;align-items:flex-start}
.aco-product-name{font-size:14px}
.aco-status-badge{font-size:9px;height:20px;padding:0 8px;align-self:flex-start;order:-1}
.aco-meta{display:none}
.aco-right-grid{grid-template-columns:1fr 1fr}
.aco-actions .aco-btn{flex:1}
}
</style>
@endpush

@section('content')
@php
  $acoWa = \App\Models\SiteSetting::get('contact_whatsapp', '');
  $acoWaDigits = preg_replace('/\D+/', '', (string) $acoWa);
  if (str_starts_with($acoWaDigits, '0')) {
      $acoWaDigits = '62' . substr($acoWaDigits, 1);
  }
@endphp
<div class="aco-page">
  <div class="aco-breadcrumb">
    <a href="{{ route('jual-beli-akun') }}">Jual Beli Akun</a>
    <span class="sep">›</span>
    <span>Pesanan Saya</span>
  </div>
  <div class="aco-header">
    <div class="aco-header-left">
      <h1 class="aco-title">PESANAN SAYA AKUN</h1>
      <p class="aco-subtitle">Pantau status pembelian akun & lanjutkan pembayaran yang belum selesai.</p>
    </div>
    <a href="{{ route('jual-beli-akun') }}" class="aco-shop-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      Cari Akun Lain
    </a>
  </div>

  @if($orders->isEmpty())
    <div class="aco-empty">
      <div class="aco-empty-icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
      <h3 class="aco-empty-title">Belum Ada Pembelian Akun</h3>
      <p class="aco-empty-desc">Kamu belum memiliki riwayat pembelian akun. Yuk temukan akun game impianmu sekarang.</p>
      <a href="{{ route('jual-beli-akun') }}" class="btn btn-solid">Mulai Belanja</a>
    </div>
  @else
    <div class="aco-list">
      @foreach($orders as $order)
        @php
          $listing = $order->listing;
          $sellerWa = $listing?->whatsapp;
          $waDigits = preg_replace('/\D+/', '', (string) ($sellerWa ?: $acoWa));
          if (str_starts_with($waDigits, '0')) {
              $waDigits = '62' . substr($waDigits, 1);
          }
          $waHref = $waDigits ? 'https://wa.me/' . $waDigits
              . '?text=' . rawurlencode('Halo, saya mau menanyakan pesanan akun ' . $order->order_ref) : route('kontak');
        @endphp
        <div class="aco-card">
          <div class="aco-card-left">
            <div class="aco-thumb" @if($listing && $listing->photo_url) style="background-image:url('{{ $listing->photo_url }}')" @endif>
              @if(!$listing || !$listing->photo_url)
                <svg width="40" height="40" viewBox="0 0 32 32" fill="none"><defs><linearGradient id="acoRg{{ $order->id }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#9d5cf5"/><stop offset="100%" stop-color="#4c1d95"/></linearGradient></defs><polygon points="16,2 27,11 22,30 10,30 5,11" fill="url(#acoRg{{ $order->id }})"/></svg>
              @endif
            </div>
            <div class="aco-info">
              <div class="aco-info-top">
                <span class="aco-product-name">{{ $listing->product_name ?? 'Akun Terjual' }}</span>
                <span class="aco-status-badge aco-status-badge--{{ $order->status }}">
                  @if($order->status === 'success') SUKSES
                  @elseif($order->status === 'pending') PENDING
                  @elseif($order->status === 'processing') DIPROSES
                  @elseif($order->status === 'cancelled') BATAL
                  @else GAGAL
                  @endif
                </span>
              </div>
              <div class="aco-meta">
                <span class="aco-meta-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/></svg>
                  <strong>{{ $listing->game ?? ($order->payment_method ?: 'Jual Beli Akun') }}</strong>
                </span>
                <span class="aco-meta-item">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 9H2"/></svg>
                  <strong>{{ $order->customer_phone }}</strong>
                </span>
              </div>
              <div class="aco-right-total aco-left-total">Rp{{ number_format((float) $order->total_price, 0, ',', '.') }}</div>
              @if($order->notes)
                <div class="aco-note">&ldquo;{{ \Illuminate\Support\Str::limit($order->notes, 80) }}&rdquo;</div>
              @endif
            </div>
          </div>

          <div class="aco-separator"></div>

          <div class="aco-card-right">
            <div class="aco-right-order-id">{{ $order->order_ref }}</div>
            <div class="aco-right-grid">
              <div>
                <div class="aco-right-label">Tanggal Order</div>
                <div class="aco-right-value">{{ $order->created_at->format('d M Y') }} &middot; {{ $order->created_at->format('H:i') }} WIB</div>
              </div>
              <div>
                <div class="aco-right-label">Metode Bayar</div>
                <div class="aco-right-value">{{ $paymentLabels[strtolower((string) $order->payment_method)] ?? $order->payment_method ?: 'QRIS' }}</div>
              </div>
            </div>

            <div class="aco-actions">
              @if($order->status === 'pending' && $listing && $listing->is_active && !$listing->is_sold)
                <a href="{{ route('jual-beli-akun.payment', $order) }}" class="aco-btn primary">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                  Bayar Sekarang
                </a>
              @endif
              <a href="{{ $waHref }}" class="aco-btn outline" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                Chat Admin
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    @if($orders->hasPages())
    <div class="aco-pagination">
      @if($orders->onFirstPage())
        <span class="aco-page-btn" style="opacity:.4;cursor:default">&lsaquo; Sebelumnya</span>
      @else
        <a href="{{ $orders->previousPageUrl() }}" class="aco-page-btn">&lsaquo; Sebelumnya</a>
      @endif
      @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
        @if($page == $orders->currentPage())
          <span class="aco-page-btn active">{{ $page }}</span>
        @elseif($page == 1 || $page == $orders->lastPage() || abs($page - $orders->currentPage()) <= 2)
          <a href="{{ $url }}" class="aco-page-btn">{{ $page }}</a>
        @elseif($page == 2 || $page == $orders->lastPage() - 1)
          <span class="aco-page-dots">…</span>
        @endif
      @endforeach
      @if($orders->hasMorePages())
        <a href="{{ $orders->nextPageUrl() }}" class="aco-page-btn">Selanjutnya &rsaquo;</a>
      @else
        <span class="aco-page-btn" style="opacity:.4;cursor:default">Selanjutnya &rsaquo;</span>
      @endif
    </div>
    @endif
  @endif
</div>
@endsection
