@extends('layouts.topup')

@section('title', 'Ulasan Saya - ' . config('app.name'))

@section('content')
<section class="my-review-page">
  <header class="my-review-header">
    <div>
      <span class="my-review-eyebrow">RIWAYAT ULASAN</span>
      <h1>Ulasan Saya</h1>
      <p>Semua rating dan ulasan yang sudah kamu kirim.</p>
    </div>
    <div class="my-review-count">
      <strong>{{ $reviews->total() }}</strong>
      <span>Total ulasan</span>
    </div>
  </header>

  <div class="my-review-list">
    @forelse($reviews as $review)
      @php
        $isAccount = $review->service_type === 'jual-beli-akun';
        $serviceLabel = $isAccount ? 'Jual Beli Akun' : 'Top Up';
        $rating = max(1, min(5, (int) $review->rating));
      @endphp
      <article class="my-review-card">
        <div class="my-review-card-head">
          <div class="my-review-service-icon {{ $isAccount ? 'account' : 'topup' }}" aria-hidden="true">
            @if($isAccount)
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="10" r="2"/><path d="M5 17c.5-2 2-3 3-3s2.5 1 3 3M14 9h5M14 13h5M14 17h4"/></svg>
            @else
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 01-8 0"/></svg>
            @endif
          </div>
          <div class="my-review-product">
            <span>{{ $serviceLabel }}</span>
            <h2>{{ $review->game ?: $serviceLabel }}</h2>
          </div>
          <span class="my-review-status {{ $review->status === 'approved' ? 'approved' : 'pending' }}">
            {{ $review->status === 'approved' ? 'Ditampilkan' : 'Ditinjau' }}
          </span>
        </div>

        <div class="my-review-stars" aria-label="Rating {{ $rating }} dari 5">
          @for($i = 1; $i <= 5; $i++)
            <svg class="{{ $i <= $rating ? 'active' : '' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          @endfor
          <strong>{{ number_format($rating, 1) }}</strong>
        </div>

        <p class="my-review-comment {{ $review->comment ? '' : 'empty' }}">
          {{ $review->comment ?: 'Rating dikirim tanpa komentar.' }}
        </p>

        <footer class="my-review-meta">
          <span>{{ $review->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
          <code>{{ $review->order_id }}</code>
        </footer>
      </article>
    @empty
      <div class="my-review-empty">
        <span class="my-review-empty-icon" aria-hidden="true">★</span>
        <h2>Belum ada ulasan</h2>
        <p>Ulasan dapat dikirim setelah transaksi top up atau pembelian akun berhasil.</p>
        <a href="{{ route('orders.my') }}" class="btn btn-solid">Lihat Pesanan</a>
      </div>
    @endforelse
  </div>

  @if($reviews->hasPages())
    <div class="my-review-pagination">{{ $reviews->links() }}</div>
  @endif
</section>

<style>
.my-review-page{width:100%;max-width:1080px;margin:0 auto;padding:2.4rem var(--layout-gutter) 5rem}
.my-review-header{display:flex;align-items:flex-end;justify-content:space-between;gap:1.5rem;margin-bottom:1.3rem;padding-bottom:1.2rem;border-bottom:1px solid var(--border)}
.my-review-eyebrow{display:block;margin-bottom:.35rem;color:var(--purple-light);font-size:.65rem;font-weight:800;letter-spacing:.14em}
.my-review-header h1{font-size:clamp(1.45rem,3vw,2rem);font-weight:800;letter-spacing:-.035em}
.my-review-header p{margin-top:.3rem;color:var(--text-mute);font-size:.82rem}
.my-review-count{display:flex;align-items:center;gap:.65rem;flex:0 0 auto;padding:.65rem .85rem;background:var(--surface);border:1px solid var(--border);border-radius:12px}
.my-review-count strong{color:var(--purple-light);font-family:var(--font-display);font-size:1.25rem;line-height:1}
.my-review-count span{color:var(--text-mute);font-size:.68rem}
.my-review-list{display:grid;gap:.8rem}
.my-review-card{padding:1rem 1.05rem;background:var(--surface);border:1px solid var(--border);border-radius:15px;box-shadow:0 10px 28px rgba(0,0,0,.06)}
.my-review-card-head{display:flex;align-items:center;gap:.75rem}
.my-review-service-icon{display:grid;place-items:center;flex:0 0 40px;width:40px;height:40px;color:#62b6ff;background:rgba(37,127,240,.12);border:1px solid rgba(62,184,255,.18);border-radius:11px}
.my-review-service-icon.account{color:#f4c95d;background:rgba(244,201,93,.1);border-color:rgba(244,201,93,.2)}
.my-review-service-icon svg{width:20px;height:20px}
.my-review-product{min-width:0;flex:1}
.my-review-product span{display:block;color:var(--text-mute);font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
.my-review-product h2{margin-top:.12rem;overflow:hidden;color:var(--text);font-size:.9rem;font-weight:700;text-overflow:ellipsis;white-space:nowrap}
.my-review-status{flex:0 0 auto;padding:.28rem .55rem;font-size:.62rem;font-weight:750;border-radius:999px}
.my-review-status.approved{color:#34d399;background:rgba(52,211,153,.11);border:1px solid rgba(52,211,153,.18)}
.my-review-status.pending{color:#fbbf24;background:rgba(251,191,36,.11);border:1px solid rgba(251,191,36,.18)}
.my-review-stars{display:flex;align-items:center;gap:.2rem;margin-top:.85rem}
.my-review-stars svg{width:17px;height:17px;color:color-mix(in srgb,var(--text-mute) 36%,transparent)}
.my-review-stars svg.active{color:#f6bd25}
.my-review-stars strong{margin-left:.3rem;color:var(--text-dim);font-size:.72rem}
.my-review-comment{margin:.65rem 0 0;color:var(--text-dim);font-size:.8rem;line-height:1.65}
.my-review-comment.empty{color:var(--text-mute);font-style:italic}
.my-review-meta{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:.85rem;padding-top:.7rem;border-top:1px solid var(--border);color:var(--text-mute);font-size:.65rem}
.my-review-meta code{max-width:55%;overflow:hidden;color:var(--text-dim);font-size:.64rem;text-overflow:ellipsis;white-space:nowrap}
.my-review-empty{display:flex;flex-direction:column;align-items:center;padding:3rem 1.2rem;text-align:center;background:var(--surface);border:1px dashed var(--border-strong);border-radius:16px}
.my-review-empty-icon{display:grid;place-items:center;width:54px;height:54px;margin-bottom:.8rem;color:#f6bd25;font-size:1.45rem;background:rgba(246,189,37,.1);border-radius:50%}
.my-review-empty h2{font-size:1.05rem}
.my-review-empty p{max-width:430px;margin:.35rem 0 1.1rem;color:var(--text-mute);font-size:.78rem;line-height:1.55}
.my-review-pagination{margin-top:1.2rem}
[data-theme="light"] .my-review-card{box-shadow:0 10px 28px rgba(38,75,116,.07)}
@media(max-width:640px){.my-review-page{padding:1.5rem var(--layout-gutter) 4rem}.my-review-header{align-items:flex-start}.my-review-count{padding:.55rem .65rem}.my-review-count span{display:none}.my-review-card{padding:.9rem}.my-review-meta{align-items:flex-start;flex-direction:column;gap:.3rem}.my-review-meta code{max-width:100%}}
</style>
@endsection
