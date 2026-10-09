@extends('layouts.topup')

@section('title', 'Keranjang Saya - ' . config('app.name'))

@section('content')
<style>
.cart-page{width:100%;max-width:var(--layout-max);margin:0 auto;padding:2.5rem var(--layout-gutter) 5rem;}

.cart-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.75rem;}
.cart-head h1{font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--text);}
.cart-head p{font-size:.85rem;color:var(--text-mute);margin-top:.25rem;}
.cart-clear{background:none;border:none;color:var(--text-mute);font-size:.8rem;cursor:pointer;font-family:inherit;text-decoration:underline;text-underline-offset:3px;}
.cart-clear:hover{color:#f87171;}

.cart-list{display:flex;flex-direction:column;gap:.9rem;margin-bottom:1.5rem;}

.cart-item{display:flex;gap:1rem;align-items:center;padding:1rem 1.1rem;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);}
.cart-item.cart-item-out{opacity:.55;}

.cart-thumb{width:58px;height:58px;flex-shrink:0;border-radius:var(--radius-sm);background:var(--bg-soft);border:1px solid var(--border);overflow:hidden;display:flex;align-items:center;justify-content:center;}
.cart-thumb img{width:100%;height:100%;object-fit:contain;}

.cart-body{flex:1;min-width:0;}
.cart-brand{font-size:.7rem;color:var(--text-mute);text-transform:uppercase;letter-spacing:.04em;}
.cart-name{font-weight:600;color:var(--text);font-size:.95rem;margin-top:.1rem;line-height:1.3;}
.cart-note{font-size:.75rem;color:var(--text-mute);margin-top:.25rem;}
.cart-note.cart-note-warn{color:#fbbf24;}

.cart-side{display:flex;align-items:center;gap:1rem;flex-shrink:0;}
.cart-price{font-family:var(--font-display);font-weight:700;color:var(--text);font-size:1rem;white-space:nowrap;}
.cart-old{display:block;font-size:.7rem;color:var(--text-mute);text-decoration:line-through;}

.cart-qty{display:flex;align-items:center;gap:.15rem;background:var(--bg-soft);border:1px solid var(--border);border-radius:999px;padding:.15rem;}
.cart-qty button{width:26px;height:26px;border-radius:50%;border:none;background:transparent;color:var(--text-dim);font-size:1rem;cursor:pointer;line-height:1;}
.cart-qty button:hover:not(:disabled){background:var(--purple);color:#fff;}
.cart-qty button:disabled{opacity:.35;cursor:not-allowed;}
.cart-qty span{min-width:26px;text-align:center;font-size:.85rem;font-weight:600;color:var(--text);}

.cart-remove{background:none;border:none;color:var(--text-mute);cursor:pointer;font-size:.72rem;text-decoration:underline;text-underline-offset:3px;font-family:inherit;padding:.2rem;}
.cart-remove:hover{color:#f87171;}

.cart-foot{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.25rem 1.4rem;}
.cart-line{display:flex;justify-content:space-between;font-size:.87rem;color:var(--text-dim);padding:.4rem 0;}
.cart-line strong{color:var(--text);font-weight:600;}
.cart-total{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);margin-top:.7rem;padding-top:.9rem;}
.cart-total span{font-weight:700;font-size:.9rem;color:var(--text);}
.cart-total strong{font-family:var(--font-display);font-weight:800;font-size:1.35rem;color:var(--gold);}

.cart-warn{display:flex;gap:.5rem;align-items:flex-start;font-size:.78rem;color:#fbbf24;background:color-mix(in srgb,#fbbf24 10%,transparent);border:1px solid color-mix(in srgb,#fbbf24 25%,transparent);border-radius:var(--radius-sm);padding:.6rem .8rem;margin-top:1rem;}

.cart-empty{text-align:center;padding:4rem 1.5rem;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);}
.cart-empty-icon{font-size:2.6rem;margin-bottom:.75rem;}
.cart-empty h2{font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:.4rem;}
.cart-empty p{color:var(--text-mute);font-size:.87rem;margin-bottom:1.5rem;}

/* Aturan display di atas lebih kuat daripada atribut hidden bawaan browser. */
.cart-warn[hidden],.cart-old[hidden],.cart-note[hidden]{display:none;}

/* Tombol lanjut checkout aktif/nonaktif mengikuti state keranjang. */
.btn-disabled{opacity:.5;cursor:not-allowed;}

@media(max-width:640px){
    .cart-item{flex-wrap:wrap;}
    .cart-body{flex:1 1 100%;order:2;}
    .cart-side{flex:1 1 100%;order:3;justify-content:space-between;}
}
</style>

<div class="cart-page">
    @if(session('error'))
        <div class="cart-warn" style="margin-bottom:1rem;">{{ session('error') }}</div>
    @endif

    {{-- Kedua blok ini selalu dirender supaya halaman bisa langsung berganti ke
         tampilan "keranjang kosong" tanpa reload setelah item terakhir dihapus. --}}
    <div class="cart-empty" data-role="cart-empty" @if(! $items->isEmpty()) hidden @endif>
        <div class="cart-empty-icon">&#128722;</div>
        <h2>Keranjang kamu masih kosong</h2>
        <p>Pilih nominal di halaman game lalu tekan &ldquo;Tambah ke Keranjang&rdquo;.</p>
        <a href="{{ route('home') }}#topup" class="btn btn-solid">Mulai Top Up</a>
    </div>

    <div data-role="cart-filled" @if($items->isEmpty()) hidden @endif>
        <div class="cart-head">
            <div>
                <h1>Keranjang Saya</h1>
                <p><span data-role="cart-summary-qty">{{ $count }}</span> item di keranjang</p>
            </div>
            <form method="POST" action="{{ route('cart.clear') }}" class="js-clear-form">
                @csrf
                <button type="submit" class="cart-clear">Kosongkan</button>
            </form>
        </div>

        <div class="cart-list" id="cartList">
            @foreach($items as $item)
                @php
                    $unit = $item->line_total > 0 && $item->quantity > 0
                        ? (int) round($item->line_total / $item->quantity)
                        : (int) $item->product->selling_price;
                    $maxQty = \App\Services\CartService::MAX_QTY;
                    $qtyLimit = (int) $item->product->stock > 0
                        ? min($maxQty, (int) $item->product->stock)
                        : $maxQty;
                @endphp
                <div class="cart-item {{ $item->unavailable ? 'cart-item-out' : '' }}" data-item="{{ $item->id }}" data-max="{{ $item->product->stock }}">
                    <div class="cart-thumb">
                        @if($item->product->photo_url)
                            <img src="{{ $item->product->photo_url }}" alt="{{ $item->product->product_name }}" loading="lazy">
                        @endif
                    </div>

                    <div class="cart-body">
                        <div class="cart-brand">{{ $item->product->brand }}</div>
                        <div class="cart-name">{{ $item->product->product_name }}</div>
                        <div class="cart-note cart-note-warn" data-role="row-out-note" @if(! $item->unavailable) hidden @endif>Stok habis &mdash; hapus dulu sebelum checkout.</div>
                        <div class="cart-note" data-role="row-flash-note" @if($item->unavailable || ! $item->flash_deal) hidden @endif>&#9889; Harga flash aktif, Lock saat checkout.</div>
                    </div>

                    <div class="cart-side">
                        @if(! $item->unavailable)
                            <div class="cart-qty">
                                <button type="button" data-act="dec" aria-label="Kurangi">&minus;</button>
                                <span data-role="qty">{{ $item->quantity }}</span>
                                <button type="button" data-act="inc" aria-label="Tambah" @if($item->quantity >= $qtyLimit) disabled @endif>+</button>
                            </div>
                        @endif

                        <div class="cart-price">
                            <span data-role="line-total">@if($item->unavailable)&mdash;@else Rp {{ number_format($item->line_total, 0, ',', '.') }}@endif</span>
                            @if(! $item->unavailable && $item->original_price > $unit)
                                <span class="cart-old" data-role="line-old">Rp {{ number_format($item->original_price * $item->quantity, 0, ',', '.') }}</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('cart.destroy', $item->id) }}" class="js-remove-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cart-remove">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="cart-foot">
            <div class="cart-line">
                <span>Subtotal (<span data-role="cart-payable-qty">{{ $totalQty }}</span> item)</span>
                <strong data-role="cart-subtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
            </div>
            <div class="cart-total">
                <span>Total</span>
                <strong data-role="cart-total">Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
            </div>

            <div class="cart-warn" data-role="cart-unavailable" @if($unavailable < 1) hidden @endif>
                <span>&#9888;</span>
                <span><span data-role="cart-unavailable-count">{{ $unavailable }}</span> item tidak bisa dibayar karena stoknya habis. Hapus dulu.</span>
            </div>

            <div class="cart-warn">
                <span>&#8505;</span>
                <span>Harga flash &amp; ketersediaan stok divalidasi ulang saat kamu menekan tombol checkout.</span>
            </div>

            <a href="{{ $unavailable > 0 ? '#' : route('checkout.create') }}"
               class="btn btn-solid btn-full btn-lg {{ $unavailable > 0 ? 'btn-disabled' : '' }}"
               style="margin-top:1.1rem;"
               data-role="cart-checkout-btn"
               data-checkout-url="{{ route('checkout.create') }}">
                Lanjut ke Checkout
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var list = document.getElementById('cartList');
    if (!list) return;

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function send(url, method, body) {
        return fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body
        }).then(function (r) {
            // Endpoint selalu JSON, tapi tetap aman bila server balas error HTML.
            return r.json().catch(function () { return {}; }).then(function (data) {
                if (!r.ok && !data.success) {
                    data.success = false;
                    data.message = data.message || 'Permintaan gagal (' + r.status + ')';
                }
                return data;
            });
        });
    }

    // Tombol "+" mati saat qty sudah menyentuh batas stok, tombol "Hapus"
    // hanya dinonaktifkan sementara request berjalan.
    function syncRowButtons(row) {
        var qtyEl = row.querySelector('[data-role="qty"]');
        var qty = parseInt(qtyEl ? qtyEl.textContent : '1', 10) || 1;
        var max = parseInt(row.dataset.max || '0', 10);
        var limit = max > 0 ? Math.min(max, 99) : 99;

        row.querySelectorAll('.cart-qty button').forEach(function (b) {
            b.disabled = b.dataset.act === 'inc' && qty >= limit;
        });
    }

    function setBusy(row, busy) {
        if (!row) return;

        var removeBtn = row.querySelector('.cart-remove');
        if (removeBtn) removeBtn.disabled = busy;

        if (busy) {
            row.querySelectorAll('.cart-qty button').forEach(function (b) { b.disabled = true; });
        } else {
            syncRowButtons(row);
        }
    }

    // Terapkan angka dari server: badge navbar, jumlah item, subtotal, total,
    // dan status stok per baris. Tidak ada reload halaman.
    function paint(data) {
        if (typeof applyCartState === 'function') applyCartState(data.state);
    }

    function changeQty(row, next) {
        var id = row.dataset.item;
        var max = parseInt(row.dataset.max || '0', 10);
        next = Math.max(1, Math.min(next, max > 0 ? max : 99));

        var span = row.querySelector('[data-role="qty"]');
        var previous = parseInt(span ? span.textContent : '1', 10) || 1;
        if (next === previous) return;

        // Tampilkan angka baru duluan supaya terasa responsif, lalu kembalikan
        // ke angka lama kalau server menolak (mis. stok habis di tengah).
        if (span) span.textContent = next;

        var fd = new FormData();
        fd.append('quantity', next);
        fd.append('_method', 'PATCH');

        setBusy(row, true);

        send('{{ url('/keranjang/item') }}/' + id, 'POST', fd)
            .then(function (data) {
                if (!data.success) {
                    if (span) span.textContent = previous;
                    showToast(data.message || 'Gagal memperbarui jumlah', true);
                    return;
                }
                paint(data);
            })
            .catch(function (e) {
                if (span) span.textContent = previous;
                showToast('Gagal: ' + e.message, true);
            })
            .finally(function () { setBusy(row, false); });
    }

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('.cart-qty button');
        if (!btn || btn.disabled) return;

        var row = btn.closest('.cart-item');
        var span = row.querySelector('[data-role="qty"]');
        var current = parseInt(span.textContent, 10) || 1;

        changeQty(row, btn.dataset.act === 'inc' ? current + 1 : current - 1);
    });

    // Hapus item tanpa reload halaman.
    list.addEventListener('submit', function (e) {
        var form = e.target.closest('.js-remove-form');
        if (!form) return;

        e.preventDefault();
        if (!confirm('Hapus item ini dari keranjang?')) return;

        var row = form.closest('.cart-item');
        setBusy(row, true);

        send(form.action, 'POST', new FormData(form))
            .then(function (data) {
                if (!data.success) { showToast(data.message || 'Gagal menghapus', true); setBusy(row, false); return; }
                row.remove();
                paint(data);
            })
            .catch(function (err) { showToast('Gagal: ' + err.message, true); setBusy(row, false); });
    });

    // Kosongkan keranjang tanpa reload: badge langsung jadi 0 dan tampilan
    // "keranjang kosong" muncul otomatis.
    var clearForm = document.querySelector('.js-clear-form');
    if (clearForm) {
        clearForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!confirm('Kosongkan keranjang?')) return;

            var btn = clearForm.querySelector('button');
            if (btn) btn.disabled = true;

            send(clearForm.action, 'POST', new FormData(clearForm))
                .then(function (data) {
                    if (!data.success) { showToast(data.message || 'Gagal mengosongkan keranjang', true); return; }
                    list.innerHTML = '';
                    paint(data);
                    showToast(data.message || 'Keranjang dikosongkan.');
                })
                .catch(function (err) { showToast('Gagal: ' + err.message, true); })
                .finally(function () { if (btn) btn.disabled = false; });
        });
    }

    // Tombol lanjut ke checkout tetap dijaga ketika item stoknya habis baru
    // muncul setelah halaman dimuat (mis. stok habis karena pembelian lain).
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-role="cart-checkout-btn"]');
        if (!btn) return;

        var state = window.__cartState;
        var unavailable = state ? (parseInt(state.unavailable, 10) || 0) : 0;

        if (unavailable > 0) {
            e.preventDefault();
            showToast('Hapus dulu item yang stoknya habis', true);
            return;
        }

        // Arahkan ke URL checkout yang benar setelah state berubah.
        if (btn.getAttribute('href') === '#') {
            e.preventDefault();
            window.location.href = btn.dataset.checkoutUrl;
        }
    });
})();
</script>
@endpush
@endsection
