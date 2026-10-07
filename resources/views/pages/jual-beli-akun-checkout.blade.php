@extends('layouts.topup')

@section('title', 'Checkout - ' . $listing->product_name . ' - ' . config('app.name'))

@section('content')
<div class="jco-page">
  <div class="jco-header">
    <a href="{{ route('jual-beli-akun.detail', $listing) }}" class="jco-back">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
      Kembali
    </a>
    <h1 class="jco-title">Checkout</h1>
  </div>

  <div class="jco-layout">
    <div class="jco-form-side">
      <form method="POST" action="{{ route('jual-beli-akun.checkout.store', $listing) }}" class="jco-form" id="checkoutForm">
        @csrf

        <div class="jco-section">
          <div class="jco-section-header">
            <span class="jco-section-num">1</span>
            <div>
              <h2 class="jco-section-title">Data Pemesan</h2>
              <p class="jco-section-desc">Isi data diri kamu untuk proses pemesanan.</p>
            </div>
          </div>

          <div class="jco-fields">
            <div class="jco-field">
              <label for="customer_name">Nama Lengkap <span class="jco-req">*</span></label>
              <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" required placeholder="Masukkan nama lengkap">
              @error('customer_name')<span class="jco-error">{{ $message }}</span>@enderror
            </div>

            <div class="jco-field-row">
              <div class="jco-field">
                <label for="customer_email">Email <span class="jco-req">*</span></label>
                <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" required placeholder="email@contoh.com">
                @error('customer_email')<span class="jco-error">{{ $message }}</span>@enderror
              </div>
              <div class="jco-field">
                <label for="customer_phone">No. WhatsApp <span class="jco-req">*</span></label>
                <input type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}" required placeholder="08xxxxxxxxxx">
                @error('customer_phone')<span class="jco-error">{{ $message }}</span>@enderror
              </div>
            </div>
          </div>
        </div>

        <div class="jco-section">
          <div class="jco-section-header">
            <span class="jco-section-num">2</span>
            <div>
              <h2 class="jco-section-title">Metode Pembayaran</h2>
              <p class="jco-section-desc">Pilih cara pembayaran yang kamu inginkan.</p>
            </div>
          </div>

          @php
              $categories = [
                  'qris' => 'QRIS',
                  'ewallet' => 'E-Wallet',
                  'va' => 'Virtual Account',
                  'convenience_store' => 'Convenience Store',
              ];
              $groupedPay = collect($paymentMethods)->groupBy('category');
          @endphp
          @foreach($categories as $catKey => $catLabel)
            @php $catMethods = $groupedPay->get($catKey, collect()); @endphp
            @if($catMethods->isNotEmpty())
            <div class="jco-pay-group">
              <div class="jco-pay-group-label">{{ $catLabel }}</div>
              <div class="jco-payment-grid">
                @foreach($catMethods as $pm)
                <label class="jco-pay-opt">
                  <input type="radio" name="payment_method" value="{{ $pm->code }}" {{ old('payment_method') === $pm->code ? 'checked' : ($loop->first && $loop->parent->first ? 'checked' : '') }} required>
                  <div class="jco-pay-card">
                    @if(payment_logo_asset($pm->code))
                      <img src="{{ payment_logo_asset($pm->code) }}" alt="{{ $pm->name }}" class="jco-pay-img">
                    @elseif($pm->icon)
                      <span class="jco-pay-icon">{{ $pm->icon }}</span>
                    @else
                      <span class="jco-pay-initial">{{ strtoupper(substr($pm->name, 0, 2)) }}</span>
                    @endif
                    <span class="jco-pay-name">{{ $pm->name }}</span>
                    <div class="jco-pay-check">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                  </div>
                </label>
                @endforeach
              </div>
            </div>
            @endif
          @endforeach
          @if(empty($paymentMethods))
            <div class="jco-pay-empty">Metode pembayaran tidak tersedia</div>
          @endif
          @error('payment_method')<span class="jco-error">{{ $message }}</span>@enderror
        </div>

        <div class="jco-section">
          <div class="jco-section-header">
            <span class="jco-section-num">3</span>
            <div>
              <h2 class="jco-section-title">Catatan</h2>
              <p class="jco-section-desc">Tambahkan catatan jika diperlukan (opsional).</p>
            </div>
          </div>

          <div class="jco-field">
            <textarea name="notes" id="notes" rows="3" placeholder="Catatan tambahan untuk penjual...">{{ old('notes') }}</textarea>
            @error('notes')<span class="jco-error">{{ $message }}</span>@enderror
          </div>
        </div>

        <button type="submit" class="jco-submit">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          Buat Pesanan
        </button>
      </form>
    </div>

    <div class="jco-summary-side">
      <div class="jco-summary">
        <h3 class="jco-summary-title">Ringkasan Pesanan</h3>

        <div class="jco-summary-product">
          @if($listing->photo_url)
            <div class="jco-summary-img">
              <img src="{{ $listing->photo_url }}" alt="{{ $listing->product_name }}">
            </div>
          @endif
          <div class="jco-summary-info">
            <div class="jco-summary-game">{{ $listing->game }}</div>
            <div class="jco-summary-name">{{ $listing->product_name }}</div>
            @if($listing->owner_name)
              <div class="jco-summary-owner">{{ $listing->owner_name }}</div>
            @endif
          </div>
        </div>

        @if($listing->specifications)
        <div class="jco-summary-specs">
          <div class="jco-summary-specs-label">Spesifikasi</div>
          <div class="jco-summary-specs-text">{{ Str::limit($listing->specifications, 120) }}</div>
        </div>
        @endif

        <div class="jco-summary-divider"></div>

        <div class="jco-summary-row">
          <span>Harga</span>
          @if($listing->original_price)
            <span class="jco-summary-original">Rp {{ number_format($listing->original_price, 0, ',', '.') }}</span>
          @endif
        </div>
        @if($listing->original_price && $listing->original_price > $listing->price)
          <?php $save = $listing->original_price - $listing->price; ?>
          <div class="jco-summary-row jco-summary-save">
            <span>Hemat</span>
            <span>-Rp {{ number_format($save, 0, ',', '.') }}</span>
          </div>
        @endif

        <div class="jco-summary-divider"></div>

        <div class="jco-summary-total">
          <span>Total Pembayaran</span>
          <span class="jco-summary-price">Rp {{ number_format($listing->price, 0, ',', '.') }}</span>
        </div>
      </div>

      <div class="jco-trust">
        <div class="jco-trust-item">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span>Pembayaran aman & terpercaya</span>
        </div>
        <div class="jco-trust-item">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 9v6"/><path d="M9 12h6"/></svg>
          <span>Transfer ke <strong>BRI 114101707070565</strong> a/n JOSIA HENDRICO SIMANUNGKALIT</span>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.jco-page {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 4rem;
}

/* Header */
.jco-header {
  margin-bottom: 2rem;
}
.jco-back {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-size: .82rem;
  font-weight: 500;
  color: var(--text-dim);
  margin-bottom: .75rem;
  transition: color .2s;
}
.jco-back:hover { color: var(--jba-accent, #9d5cf5); }
.jco-title {
  font-size: 1.6rem;
  font-weight: 800;
}

/* Layout */
.jco-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2rem;
  align-items: start;
}
@media (max-width: 900px) {
  .jco-layout { grid-template-columns: 1fr; }
  .jco-summary-side { order: -1; }
}

/* Sections */
.jco-section {
  background: var(--surface, #1a1a2e);
  border: 1px solid var(--border, rgba(255,255,255,.08));
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1rem;
}
.jco-section-header {
  display: flex;
  align-items: flex-start;
  gap: .85rem;
  margin-bottom: 1.25rem;
}
.jco-section-num {
  flex-shrink: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--jba-accent, #9d5cf5), var(--purple, #7c3aed));
  color: #fff;
  font-size: .78rem;
  font-weight: 700;
}
.jco-section-title {
  font-size: 1rem;
  font-weight: 700;
  margin: 0;
  line-height: 1.3;
}
.jco-section-desc {
  font-size: .78rem;
  color: var(--text-dim, #999);
  margin: .15rem 0 0;
}

/* Fields */
.jco-fields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.jco-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 600px) {
  .jco-field-row { grid-template-columns: 1fr; }
}
.jco-field label {
  display: block;
  font-size: .8rem;
  font-weight: 600;
  color: var(--text-dim, #999);
  margin-bottom: .4rem;
}
.jco-req { color: #f87171; }
.jco-field input[type="text"],
.jco-field input[type="email"],
.jco-field input[type="tel"],
.jco-field textarea {
  width: 100%;
  background: var(--bg-soft, rgba(255,255,255,.04));
  border: 1.5px solid var(--border, rgba(255,255,255,.08));
  border-radius: 10px;
  padding: .7rem .9rem;
  color: var(--text, #fff);
  font-size: .88rem;
  font-family: var(--font-body, inherit);
  transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}
.jco-field input:focus,
.jco-field textarea:focus {
  outline: none;
  border-color: var(--jba-accent, #9d5cf5);
  box-shadow: 0 0 0 3px rgba(157, 92, 245, .15);
}
.jco-field textarea {
  resize: vertical;
  min-height: 80px;
}
.jco-error {
  display: block;
  font-size: .72rem;
  color: #f87171;
  margin-top: .3rem;
}

/* Payment */
.jco-pay-group {
  margin-bottom: 1rem;
}
.jco-pay-group:last-child {
  margin-bottom: 0;
}
.jco-pay-group-label {
  font-size: .68rem;
  font-weight: 700;
  color: var(--text-dim, #999);
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: .5rem;
}
.jco-payment-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: .6rem;
}
@media (max-width: 500px) {
  .jco-payment-grid { grid-template-columns: 1fr; }
}
.jco-pay-opt {
  cursor: pointer;
}
.jco-pay-opt input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}
.jco-pay-card {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .7rem .85rem;
  border: 1.5px solid var(--border, rgba(255,255,255,.08));
  border-radius: 10px;
  background: var(--bg-soft, rgba(255,255,255,.04));
  transition: border-color .2s, background .2s, box-shadow .2s;
  position: relative;
}
.jco-pay-opt input:checked + .jco-pay-card {
  border-color: var(--jba-accent, #9d5cf5);
  background: rgba(157, 92, 245, .08);
  box-shadow: 0 0 0 3px rgba(157, 92, 245, .12);
}
.jco-pay-opt:hover .jco-pay-card {
  border-color: rgba(255,255,255,.15);
}
.jco-pay-img {
  width: 32px;
  height: 32px;
  object-fit: contain;
  border-radius: 6px;
  flex-shrink: 0;
  background: #fff;
  padding: 4px;
  border: 1px solid rgba(15,23,42,.12);
}
.jco-pay-icon {
  font-size: 1.3rem;
  flex-shrink: 0;
  line-height: 1;
  width: 32px;
  text-align: center;
}
.jco-pay-initial {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: linear-gradient(135deg, var(--jba-accent, #9d5cf5), var(--purple, #7c3aed));
  color: #fff;
  font-size: .7rem;
  font-weight: 700;
  flex-shrink: 0;
}
.jco-pay-name {
  font-size: .82rem;
  font-weight: 600;
  color: var(--text, #fff);
  flex: 1;
}
.jco-pay-check {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--jba-accent, #9d5cf5);
  color: #fff;
  flex-shrink: 0;
  opacity: 0;
  transform: scale(.6);
  transition: opacity .2s, transform .2s;
}
.jco-pay-opt input:checked + .jco-pay-card .jco-pay-check {
  opacity: 1;
  transform: scale(1);
}
.jco-pay-empty {
  grid-column: 1 / -1;
  text-align: center;
  color: var(--text-mute, #666);
  font-size: .82rem;
  padding: 1rem;
}

/* Submit */
.jco-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  width: 100%;
  padding: .85rem 1.5rem;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--jba-accent, #9d5cf5), var(--purple, #7c3aed));
  color: #fff;
  font-weight: 700;
  font-size: .95rem;
  cursor: pointer;
  transition: transform .2s, box-shadow .2s;
  box-shadow: 0 4px 20px -4px rgba(157, 92, 245, .5);
  font-family: var(--font-body, inherit);
}
.jco-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px -4px rgba(157, 92, 245, .65);
}
.jco-submit:active {
  transform: translateY(0);
}

/* Summary Sidebar */
.jco-summary {
  background: var(--surface, #1a1a2e);
  border: 1px solid var(--border, rgba(255,255,255,.08));
  border-radius: 16px;
  padding: 1.25rem;
  position: sticky;
  top: 2rem;
}
.jco-summary-title {
  font-size: .92rem;
  font-weight: 700;
  margin: 0 0 1rem;
}
.jco-summary-product {
  display: flex;
  gap: .75rem;
  align-items: flex-start;
}
.jco-summary-img {
  width: 64px;
  height: 64px;
  border-radius: 10px;
  overflow: hidden;
  background: var(--bg-soft, rgba(255,255,255,.04));
  flex-shrink: 0;
}
.jco-summary-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.jco-summary-info {
  min-width: 0;
}
.jco-summary-game {
  font-size: .65rem;
  color: var(--jba-accent, #9d5cf5);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.jco-summary-name {
  font-size: .88rem;
  font-weight: 600;
  margin: .1rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.jco-summary-owner {
  font-size: .72rem;
  color: var(--text-dim, #999);
}

.jco-summary-specs {
  margin-top: .75rem;
  padding: .6rem .75rem;
  background: var(--bg-soft, rgba(255,255,255,.04));
  border-radius: 8px;
}
.jco-summary-specs-label {
  font-size: .65rem;
  font-weight: 600;
  color: var(--text-dim, #999);
  text-transform: uppercase;
  letter-spacing: .03em;
  margin-bottom: .2rem;
}
.jco-summary-specs-text {
  font-size: .72rem;
  color: var(--text-dim, #999);
  line-height: 1.5;
}

.jco-summary-divider {
  height: 1px;
  background: var(--border, rgba(255,255,255,.08));
  margin: .85rem 0;
}

.jco-summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: .82rem;
  color: var(--text-dim, #999);
}
.jco-summary-original {
  text-decoration: line-through;
  opacity: .6;
}
.jco-summary-save {
  color: #22c55e;
  font-weight: 600;
  font-size: .78rem;
}

.jco-summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: .88rem;
  font-weight: 600;
}
.jco-summary-price {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--gold, #facc15);
}

/* Trust */
.jco-trust {
  margin-top: .75rem;
  display: flex;
  flex-direction: column;
  gap: .4rem;
}
.jco-trust-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .72rem;
  color: var(--text-dim, #999);
  padding: .5rem .75rem;
  background: rgba(157, 92, 245, .05);
  border: 1px solid rgba(157, 92, 245, .12);
  border-radius: 8px;
}
.jco-trust-item svg {
  flex-shrink: 0;
  color: var(--jba-accent, #9d5cf5);
}
.jco-trust-item strong {
  color: var(--text, #fff);
}
</style>

@push('scripts')
<script>
(function() {
  function applyThemeToPaymentImgs() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    document.querySelectorAll('.jco-pay-themeable').forEach(function(img) {
      const darkSrc = img.getAttribute('data-src-dark');
      const lightSrc = img.getAttribute('data-src-light');
      img.src = isLight ? lightSrc : darkSrc;
    });
  }

  applyThemeToPaymentImgs();
  document.addEventListener('themeChanged', function() {
    setTimeout(applyThemeToPaymentImgs, 50);
  });
})();
</script>
@endpush
@endsection
