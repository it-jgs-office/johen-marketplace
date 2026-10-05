@extends('layouts.topup')

@section('title', 'Checkout - ' . config('app.name'))

@section('content')
@php
    // Mapping channel code -> kategori, sama dengan halaman detail game supaya
    // tampilan metode pembayaran tidak berbeda.
    $channelCategory = function (string $code): string {
        $code = strtolower($code);
        if (in_array($code, ['qris'], true)) return 'qris';
        if (in_array($code, ['gopay', 'dana', 'ovo', 'shopeepay', 'linkaja', 'gcash'], true)) return 'ewallet';
        if (in_array($code, ['bca', 'bca_va', 'bri', 'bri_va', 'bni', 'bni_va', 'mandiri', 'mandiri_va', 'permata', 'permata_va', 'sa', 'saham', 'other_bank'], true)) return 'va';
        if (in_array($code, ['alfamart', 'indomaret'], true)) return 'convenience_store';
        return 'ewallet';
    };
    $categories = [
        'qris' => 'QRIS',
        'ewallet' => 'E-Wallet',
        'va' => 'Virtual Account / Bank',
        'convenience_store' => 'Convenience Store',
    ];
    $groupedPay = $paymentMethods->groupBy(fn ($m) => $channelCategory($m->code));
    $selectedPay = (string) old('payment_method', '');
    $checkoutError = session('error');
    $errAll = $errors->all();
@endphp

<style>
.ck-wrap{max-width:1040px;margin:0 auto;padding:2.5rem 1.25rem 5rem;}
.ck-head{margin-bottom:1.6rem;}
.ck-head h1{font-family:var(--font-display);font-size:1.6rem;font-weight:800;color:var(--text);}
.ck-head p{font-size:.85rem;color:var(--text-mute);margin-top:.25rem;}

.ck-grid{display:grid;grid-template-columns:1fr 320px;gap:1.25rem;align-items:start;}
.ck-main{display:flex;flex-direction:column;gap:1.25rem;}

.ck-banner{border-radius:var(--radius-md);padding:.8rem 1rem;font-size:.82rem;margin-bottom:1.1rem;display:flex;gap:.55rem;align-items:flex-start;}
.ck-banner-bad{background:color-mix(in srgb,#f87171 12%,transparent);border:1px solid color-mix(in srgb,#f87171 32%,transparent);color:#fca5a5;}
.ck-banner-info{background:var(--surface);border:1px solid var(--border);color:var(--text-dim);}
.ck-banner ul{margin:.25rem 0 0;padding-left:1.1rem;}

.ck-brand-head{display:flex;align-items:center;gap:.7rem;margin-bottom:.9rem;}
.ck-brand-icon{width:38px;height:38px;border-radius:10px;background:var(--bg-soft);border:1px solid var(--border);overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.ck-brand-icon img{width:100%;height:100%;object-fit:contain;}
.ck-brand-name{font-family:var(--font-display);font-weight:800;font-size:1rem;color:var(--text);}
.ck-brand-items{font-size:.75rem;color:var(--text-mute);margin-top:.1rem;}

.ck-pkg-list{display:flex;flex-direction:column;gap:.35rem;margin-bottom:1rem;padding-bottom:.9rem;border-bottom:1px dashed var(--border);}
.ck-pkg{display:flex;justify-content:space-between;gap:.6rem;font-size:.8rem;color:var(--text-dim);}
.ck-pkg b{color:var(--text);font-weight:600;}
.ck-pkg span{color:var(--text-mute);white-space:nowrap;}

.ck-side{position:sticky;top:1.25rem;display:flex;flex-direction:column;gap:1rem;}
.ck-side-title{font-family:var(--font-display);font-weight:800;font-size:.95rem;color:var(--text);margin-bottom:.7rem;}
.ck-side-line{display:flex;justify-content:space-between;font-size:.78rem;color:var(--text-mute);padding:.3rem 0;}
.ck-side-line b{color:var(--text);font-weight:600;}
.ck-note{font-size:.72rem;color:var(--text-mute);margin-top:.9rem;line-height:1.5;}

.ck-pay-hint{font-size:.74rem;color:var(--text-mute);margin-top:.5rem;}
.ck-pay-hint.bad{color:#fbbf24;}

@media(max-width:900px){
    .ck-grid{grid-template-columns:1fr;}
    .ck-side{position:static;order:-1;}
}
</style>

<div class="ck-wrap">
    <div class="ck-head">
        <h1>Checkout</h1>
        <p data-role="cart-summary-items">{{ $totalQty }} item dari {{ $items->count() }} produk &mdash; satu pembayaran untuk semuanya.</p>
    </div>

    @if($checkoutError)
        <div class="ck-banner ck-banner-bad"><span>&#9888;</span><span>{{ $checkoutError }}</span></div>
    @endif

    @if($errAll)
        <div class="ck-banner ck-banner-bad">
            <span>&#9888;</span>
            <div>
                <strong>Periksa kembali isian kamu:</strong>
                <ul>
                    @foreach($errAll as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" id="checkoutForm">
        @csrf

        <div class="ck-grid">
            <div class="ck-main">
                {{-- ===== Data akun per brand ===== --}}
                <div class="gd-card">
                    <div class="gd-step-head">
                        <div class="gd-step-num">1</div>
                        <div class="gd-step-title">Data Akun Game</div>
                    </div>

                    @if($brandGroups->isEmpty())
                        <div class="gd-summary-empty">Semua item di keranjang sedang kosong. Kembali ke keranjang untuk menghapusnya.</div>
                    @endif

                    @foreach($brandGroups as $group)
                        @php
                            $brandKey = $group->key;
                            $value = $accounts[$brandKey] ?? [];
                            $numErr = $errors->first('accounts.'.$brandKey.'.customer_number');
                            $zoneErr = $errors->first('accounts.'.$brandKey.'.zone_id');
                        @endphp

                        <div class="ck-brand" style="padding:1rem 0;border-top:1px dashed var(--border);">
                            <div class="ck-brand-head">
                                <div class="ck-brand-icon">
                                    @if($group->thumbnail_url)
                                        <img src="{{ $group->thumbnail_url }}" alt="{{ $group->name }}">
                                    @endif
                                </div>
                                <div>
                                    <div class="ck-brand-name">{{ $group->name }}</div>
                                    <div class="ck-brand-items">
                                        {{ $group->items->pluck('product.product_name')->filter()->join(', ') }}
                                    </div>
                                </div>
                            </div>

                            <div class="gd-field">
                                <label for="cn_{{ $brandKey }}">User ID / Akun {{ $group->requires_zone_id ? '' : '' }}</label>
                                <input type="text" inputmode="numeric" id="cn_{{ $brandKey }}"
                                       name="accounts[{{ $brandKey }}][customer_number]"
                                       value="{{ $value['customer_number'] ?? '' }}"
                                       placeholder="Masukkan {{ $group->name }} User ID"
                                       autocomplete="off" required
                                       class="{{ $numErr ? 'error' : '' }}">
                                <div class="gd-field-error {{ $numErr ? 'show' : '' }}">{{ $numErr }}</div>
                            </div>

                            @if($group->requires_zone_id)
                                <div class="gd-field" style="margin-top:.7rem;">
                                    <label for="zi_{{ $brandKey }}">Zone / Server ID <span style="color:#f87171;">*</span></label>
                                    <input type="text" inputmode="numeric" id="zi_{{ $brandKey }}"
                                           name="accounts[{{ $brandKey }}][zone_id]"
                                           value="{{ $value['zone_id'] ?? '' }}"
                                           placeholder="Contoh: 1234"
                                           autocomplete="off" required
                                           class="{{ $zoneErr ? 'error' : '' }}">
                                    <div class="gd-field-error {{ $zoneErr ? 'show' : '' }}">{{ $zoneErr }}</div>
                                    <div class="gd-field-hint">{{ $group->name }} wym Zone ID. Kosongkan juga bila akunmu tidak memakai zone.</div>
                                </div>
                            @endif

                            <div class="gd-field" style="margin-top:.7rem;">
                                <label for="cn2_{{ $brandKey }}">Nickname <span style="color:var(--text-mute);">(opsional)</span></label>
                                <input type="text" id="cn2_{{ $brandKey }}"
                                       name="accounts[{{ $brandKey }}][customer_name]"
                                       value="{{ $value['customer_name'] ?? '' }}"
                                       placeholder="Bantu kami cek bila pesanan bermasalah"
                                       autocomplete="off">
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ===== Kontak ===== --}}
                <div class="gd-card">
                    <div class="gd-step-head">
                        <div class="gd-step-num">2</div>
                        <div class="gd-step-title">Kontak & Promo</div>
                    </div>

                    <div class="gd-field-row">
                        <div class="gd-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" placeholder="email@domain.com">
                            <div class="gd-field-hint">E-mail invoice dikirim ke alamat ini.</div>
                        </div>
                        <div class="gd-field">
                            <label for="phone">WhatsApp</label>
                            <input type="text" id="phone" name="phone" inputmode="numeric" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
                            <div class="gd-field-hint">Hanya untuk pemberitahuan status pesanan.</div>
                        </div>
                    </div>

                    <div class="gd-field" style="margin-top:.9rem;">
                        <label for="promo">Kode Promo</label>
                        <input type="text" id="promo" name="promo_code" value="{{ old('promo_code') }}" placeholder="Contoh: JOHENI10" autocomplete="off" style="text-transform:uppercase;">
                        <div class="gd-field-hint">Diskon voucher dihitung otomatis dari total gabungan semua item.</div>
                    </div>
                </div>

                {{-- ===== Pembayaran ===== --}}
                <div class="gd-card">
                    <div class="gd-step-head">
                        <div class="gd-step-num">3</div>
                        <div class="gd-step-title">Pilih Pembayaran</div>
                    </div>

                    <input type="hidden" name="payment_method" id="payInput" value="{{ $selectedPay }}">

                    @if($paymentMethods->isEmpty())
                        <div class="gd-summary-empty">Belum ada metode pembayaran aktif. Hubungi CS kami.</div>
                    @else
                        <div class="gd-pay-group" id="payGroup">
                            @foreach($categories as $catKey => $catLabel)
                                @php $catMethods = $groupedPay->get($catKey, collect()); @endphp
                                @if($catMethods->isNotEmpty())
                                    <div class="gd-pay-category open" data-category="{{ $catKey }}">
                                        <button type="button" class="gd-pay-cat-head">
                                            <span class="gd-pay-cat-label">{{ $catLabel }}</span>
                                            <svg class="gd-pay-cat-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        <div class="gd-pay-cat-body">
                                            @foreach($catMethods as $pm)
                                                @php($pmMin = $minAmounts[$catKey] ?? 1000)
                                                <div class="gd-pay-row {{ $selectedPay === $pm->code ? 'selected' : '' }}" data-key="{{ $pm->code }}" data-min="{{ $pmMin }}">
                                                    <input type="radio" name="pay_choice" value="{{ $pm->code }}" {{ $selectedPay === $pm->code ? 'checked' : '' }} hidden>
                                                    <button type="button" class="gd-pay-row-head">
                                                        <span class="gd-pay-icon">
                                                            @if($pm->photo_url)
                                                                <img src="{{ $pm->photo_url }}" alt="{{ $pm->name }}" class="pay-badge-img" @if($pm->photo_light_url) data-light="{{ $pm->photo_light_url }}" @endif>
                                                            @else
                                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                                                            @endif
                                                        </span>
                                                        <span class="gd-pay-label">
                                                            <span class="gd-pay-t">{{ $pm->name }}</span>
                                                            @if($pmMin > 1000)
                                                                <span class="gd-pay-min">min. Rp {{ number_format($pmMin, 0, ',', '.') }}</span>
                                                            @endif
                                                        </span>
                                                        <span class="gd-pay-radio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="ck-pay-hint" id="payHint">Pilih satu metode pembayaran untuk seluruh item.</div>
                    @endif
                </div>
            </div>

            {{-- ===== Ringkasan ===== --}}
            <aside class="ck-side">
                <div class="gd-card">
                    <div class="ck-side-title">Ringkasan (<span data-role="cart-payable-qty">{{ $totalQty }}</span> item)</div>

                    @foreach($items as $item)
                        <div class="ck-side-line">
                            <span style="max-width:190px;">{{ $item->product->product_name }} <span style="color:var(--text-mute);">&times;{{ $item->quantity }}</span></span>
                            <b>Rp {{ number_format($item->line_total, 0, ',', '.') }}</b>
                        </div>
                    @endforeach

                    <div class="gd-summary-total" style="margin-top:.6rem;">
                        <span>Subtotal</span>
                        <span data-role="cart-subtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>

                    <button type="submit" class="btn btn-solid btn-full btn-lg" style="margin-top:1.1rem;" id="submitBtn">
                        Bayar Sekarang
                    </button>

                    <div class="ck-note">
                        Harga flash deal dan ketersediaan stok dikunci ulang saat kamu menekan tombol ini.
                        Promo yang berlaku dipotong otomatis dari total gabungan.
                    </div>

                    <a href="{{ route('cart.index') }}" class="btn btn-outline btn-full" style="margin-top:.8rem;">Ubah Keranjang</a>
                </div>
            </aside>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('checkoutForm');
    if (!form) return;

    var payInput = document.getElementById('payInput');
    var payHint = document.getElementById('payHint');
    var submitBtn = document.getElementById('submitBtn');
    var subtotal = @json((int) $subtotal);
    var submitting = false;

    // Buka kategori pertama supaya user tidak mencari-cari metode.
    document.querySelectorAll('.gd-pay-category').forEach(function (cat, i) {
        if (i === 0) cat.classList.add('open');
    });

    function selectPay(row) {
        document.querySelectorAll('.gd-pay-row').forEach(function (r) { r.classList.remove('selected'); });
        row.classList.add('selected');
        var radio = row.querySelector('input[name="pay_choice"]');
        if (radio) radio.checked = true;
        payInput.value = row.dataset.key;

        var min = parseInt(row.dataset.min || '0', 10);
        if (min > 0 && subtotal < min) {
            payHint.textContent = 'Total Rp ' + subtotal.toLocaleString('id-ID') +
                ' di bawah minimal Rp ' + min.toLocaleString('id-ID') + ' untuk ' + row.querySelector('.gd-pay-t').textContent +
                '. Server akan menolak, pilih QRIS atau e-wallet.';
            payHint.classList.add('bad');
        } else {
            payHint.textContent = 'Satu pembayaran untuk seluruh ' + @json((int) $totalQty) + ' item.';
            payHint.classList.remove('bad');
        }
    }

    form.addEventListener('click', function (e) {
        var head = e.target.closest('.gd-pay-cat-head');
        if (head) {
            var cat = head.closest('.gd-pay-category');
            document.querySelectorAll('.gd-pay-category').forEach(function (c) { c.classList.remove('open'); });
            cat.classList.toggle('open');
            return;
        }

        var rowHead = e.target.closest('.gd-pay-row-head');
        if (rowHead) selectPay(rowHead.closest('.gd-pay-row'));
    });

    // Kembalikan pilihan metode setelah validasi gagal.
    var preSelected = document.querySelector('.gd-pay-row.selected');
    if (preSelected) selectPay(preSelected);

    form.addEventListener('submit', function (e) {
        if (submitting) return;

        if (!payInput.value) {
            e.preventDefault();
            showToast('Pilih metode pembayaran dulu', true);
            return;
        }

        // Validasi Zone ID hanya di sisi server, tapi cek cepat di sini.
        var zoneRequired = @json($brandGroups->filter(fn ($g) => $g->requires_zone_id)->pluck('key')->values());
        var missing = false;
        zoneRequired.forEach(function (key) {
            var zone = form.querySelector('[name="accounts[' + key + '][zone_id]"]');
            if (zone && zone.value.trim() === '') missing = true;
        });
        if (missing) {
            e.preventDefault();
            showToast('Zone ID wajib diisi untuk game yang diminta', true);
            return;
        }

        submitting = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Memproses...';
    });

    // Ikon pembayaran terang di background gelap.
    document.querySelectorAll('.gd-pay-icon img[data-light]').forEach(function (img) {
        img.src = img.dataset.light;
    });

    // Keranjang bisa berubah di tab lain atau setelah user kembali dari halaman
    // Detail Game lewat tombol back (bfcache). Tarik state terbaru supaya angka
    // di header dan ringkasan tidak basi. Server tetap memvalidasi ulang saat submit.
    if (typeof refreshCartState === 'function') {
        var recheck = function () {
            refreshCartState().then(function (state) {
                if (!state) return;

                if ((parseInt(state.count, 10) || 0) < 1) {
                    showToast('Keranjangmu sudah kosong, buka ulang halaman keranjang.', true);
                    submitBtn.disabled = true;
                }
            });
        };

        window.addEventListener('pageshow', function (e) { if (e.persisted) recheck(); });
        document.addEventListener('visibilitychange', function () { if (!document.hidden) recheck(); });
    }
})();
</script>
@endpush
@endsection