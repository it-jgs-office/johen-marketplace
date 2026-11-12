@extends('layouts.topup')

@section('title', 'Keranjang Akun - ' . config('app.name'))

@section('content')
<style>
.cart-page{--cart-accent:var(--gold);width:100%;max-width:1280px;min-height:calc(100vh - 72px);margin:0 auto;padding:clamp(2rem,4vw,3.5rem) clamp(1rem,3vw,2rem) 5rem}
.cart-head{display:flex;align-items:center;justify-content:space-between;gap:1.25rem;margin-bottom:1.5rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border)}
.cart-title-row{display:flex;align-items:center;gap:.75rem;margin-top:.35rem}
.cart-eyebrow{display:block;color:var(--cart-accent);font-size:.68rem;font-weight:800;letter-spacing:.12em}
.cart-head h1{margin:0;font-family:var(--font-display);font-size:clamp(1.7rem,3vw,2.3rem);letter-spacing:-.04em}
.cart-head p{margin:.35rem 0 0;color:var(--text-mute);font-size:.86rem}
.cart-count{display:inline-flex;align-items:center;justify-content:center;min-height:28px;padding:.25rem .65rem;border:1px solid color-mix(in srgb,var(--cart-accent) 42%,var(--border));border-radius:999px;background:color-mix(in srgb,var(--cart-accent) 12%,transparent);color:var(--cart-accent);font-size:.7rem;font-weight:800;white-space:nowrap}
.cart-clear{min-height:38px;padding:.5rem .8rem;border:1px solid var(--border);border-radius:9px;background:var(--surface);color:var(--text-mute);font:inherit;font-size:.75rem;font-weight:700;cursor:pointer;transition:border-color .2s,color .2s,background .2s}
.cart-clear:hover{border-color:rgba(248,113,113,.45);background:rgba(239,68,68,.09);color:#fca5a5}
.cart-remove{border:0;background:none;color:var(--text-mute);font:inherit;font-size:.75rem;cursor:pointer;text-decoration:underline;text-underline-offset:3px}
.cart-remove:hover{color:#f87171}
.cart-layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:1.5rem;align-items:start}
.cart-list{display:grid;gap:.85rem}
.cart-item{display:grid;grid-template-columns:96px minmax(0,1fr) auto;align-items:center;gap:1rem;padding:1rem;border:1px solid var(--border);border-radius:16px;background:var(--surface);box-shadow:0 16px 34px -30px rgba(0,0,0,.9);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
.cart-item:hover{transform:translateY(-2px);border-color:color-mix(in srgb,var(--cart-accent) 42%,var(--border));box-shadow:0 18px 38px -28px rgba(0,0,0,.95)}
.cart-item.cart-item-out{opacity:.58}
.cart-thumb{width:96px;height:96px;overflow:hidden;border:1px solid var(--border);border-radius:12px;background:var(--bg-soft)}
.cart-thumb img{width:100%;height:100%;object-fit:cover}
.cart-body{min-width:0}
.cart-brand{color:var(--cart-accent);font-size:.67rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase}
.cart-name{max-width:680px;margin-top:.3rem;font-size:1rem;font-weight:700;line-height:1.45}
.cart-note{margin-top:.4rem;color:#fbbf24;font-size:.75rem;line-height:1.45}
.cart-side{display:grid;min-width:150px;justify-items:end;gap:.65rem;padding-left:1rem;border-left:1px solid var(--border)}
.cart-price{font-family:var(--font-display);font-size:1.05rem;font-weight:800;white-space:nowrap}
.cart-actions{display:flex;align-items:center;gap:.7rem}
.cart-actions .btn{min-height:38px;padding:.55rem .9rem;font-size:.74rem}
.cart-summary{position:sticky;top:88px;padding:1.25rem;border:1px solid color-mix(in srgb,var(--cart-accent) 30%,var(--border));border-radius:16px;background:var(--surface);box-shadow:0 20px 38px -30px rgba(0,0,0,.95)}
.cart-summary::before{content:"";display:block;width:42px;height:3px;margin-bottom:1rem;border-radius:999px;background:var(--cart-accent)}
.cart-summary-label{color:var(--text-mute);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.cart-summary-total{margin:.3rem 0 1rem;color:var(--text);font-family:var(--font-display);font-size:1.65rem;font-weight:800}
.cart-summary-line{display:flex;justify-content:space-between;gap:1rem;padding:.75rem 0;border-top:1px solid var(--border);color:var(--text-dim);font-size:.8rem}
.cart-summary-line strong{color:var(--text);font-weight:700}
.cart-summary-note{margin:1rem 0 0;color:var(--text-mute);font-size:.72rem;line-height:1.55}
.cart-empty{max-width:620px;margin:2rem auto;text-align:center;padding:clamp(2.5rem,7vw,4.5rem) 1.5rem;border:1px solid color-mix(in srgb,var(--cart-accent) 26%,var(--border));border-radius:18px;background:var(--surface)}
.cart-empty-icon{display:grid;width:58px;height:58px;margin:0 auto 1rem;place-items:center;border-radius:16px;background:color-mix(in srgb,var(--cart-accent) 15%,transparent);font-size:1.7rem}
.cart-empty h2{margin-bottom:.45rem;font-family:var(--font-display);font-size:1.3rem}
.cart-empty p{max-width:330px;margin:0 auto 1.4rem;color:var(--text-mute);font-size:.86rem;line-height:1.55}
@media(max-width:900px){.cart-layout{grid-template-columns:1fr}.cart-summary{position:static;order:-1}.cart-item{grid-template-columns:82px minmax(0,1fr) auto}.cart-thumb{width:82px;height:82px}}
@media(max-width:640px){.cart-page{min-height:calc(100vh - 64px);padding-bottom:calc(var(--mobile-nav-height,68px) + 2rem)}.cart-head{align-items:flex-start}.cart-clear{flex-shrink:0}.cart-item{grid-template-columns:72px minmax(0,1fr);gap:.8rem}.cart-thumb{width:72px;height:72px}.cart-side{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;min-width:0;padding: .8rem 0 0;border-left:0;border-top:1px solid var(--border)}.cart-actions{gap:1rem}.cart-summary-total{font-size:1.4rem}}
@media(max-width:420px){.cart-head{flex-direction:column}.cart-clear{width:100%}.cart-title-row{align-items:flex-start;flex-direction:column;gap:.45rem}.cart-actions .btn{padding:.55rem .7rem}.cart-name{font-size:.9rem}}
@media(prefers-reduced-motion:reduce){.cart-item{transition:none}}
</style>

<div class="cart-page">
  @if(session('error'))<p class="cart-note" style="margin-bottom:1rem">{{ session('error') }}</p>@endif

  @if($items->isEmpty())
    <div class="cart-empty">
      <div class="cart-empty-icon">&#128722;</div>
      <h2>Keranjang akunmu masih kosong</h2>
      <p>Pilih akun game yang ingin dibeli, lalu tambahkan ke keranjang.</p>
      <a href="{{ route('jual-beli-akun') }}" class="btn btn-solid">Cari Stock Akun</a>
    </div>
  @else
    <div class="cart-head">
      <div>
        <span class="cart-eyebrow">STOCK AKUN</span>
        <div class="cart-title-row">
          <h1>Keranjang Akun</h1>
          <span class="cart-count" aria-label="{{ $count }} akun di keranjang">{{ $count }} akun</span>
        </div>
        <p>Siap checkout akun pilihanmu.</p>
      </div>
      <form method="POST" action="{{ route('cart.clear') }}">@csrf<button type="submit" class="cart-clear">Kosongkan keranjang</button></form>
    </div>

    <div class="cart-layout">
      <div class="cart-list">
        @foreach($items as $item)
          @php($listing = $item->listing)
          <article class="cart-item {{ $item->unavailable ? 'cart-item-out' : '' }}">
            <div class="cart-thumb">@if($listing?->photo_url)<img src="{{ $listing->photo_url }}" alt="{{ $listing->product_name }}" loading="lazy">@endif</div>
            <div class="cart-body">
              <div class="cart-brand">{{ $listing?->game ?? 'Akun tidak tersedia' }}</div>
              <div class="cart-name">{{ $listing?->product_name ?? 'Listing tidak ditemukan' }}</div>
              @if($item->unavailable)<div class="cart-note">Akun ini sudah tidak tersedia. Hapus dari keranjang.</div>@endif
            </div>
            <div class="cart-side">
              <span class="cart-price">{{ $item->unavailable ? '—' : 'Rp '.number_format($item->line_total, 0, ',', '.') }}</span>
              <div class="cart-actions">
                @if(! $item->unavailable)<a href="{{ route('jual-beli-akun.checkout', $listing->id) }}" class="btn btn-solid">Checkout</a>@endif
                <form method="POST" action="{{ route('cart.destroy', $item->id) }}">@csrf @method('DELETE')<button type="submit" class="cart-remove">Hapus</button></form>
              </div>
            </div>
          </article>
        @endforeach
      </div>

      <aside class="cart-summary" aria-label="Ringkasan keranjang">
        <div class="cart-summary-label">Nilai keranjang</div>
        <div class="cart-summary-total">Rp {{ number_format($subtotal, 0, ',', '.') }}</div>
        <div class="cart-summary-line"><span>Akun tersedia</span><strong>{{ $totalQty }} akun</strong></div>
        @if($unavailable > 0)<div class="cart-summary-line"><span>Tidak tersedia</span><strong>{{ $unavailable }} akun</strong></div>@endif
        <p class="cart-summary-note">Setiap akun memiliki proses verifikasi dan pembayaran tersendiri.</p>
      </aside>
    </div>
  @endif
</div>
@endsection
