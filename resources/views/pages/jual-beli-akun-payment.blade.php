@extends('layouts.topup')

@section('title', 'Pembayaran - ' . $listing->product_name . ' - ' . config('app.name'))

@section('content')
<div class="jpay-wrap">
  <div class="jpay-header">
    <div class="jpay-header-badge">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    </div>
    <div>
      <h1 class="jpay-title">Detail Pembayaran</h1>
      <p class="jpay-sub">Selesaikan pembayaran untuk melanjutkan pemesanan akun</p>
    </div>
  </div>

  <div class="jpay-grid">
    <div class="jpay-left">

      <div class="jpay-card">
        <div class="jpay-card-head">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <span>Status Pembayaran</span>
        </div>
        <div class="jpay-status" id="jpayStatusBody">
          <div class="jpay-status-left">
            <div class="jpay-status-icon" id="jpayStatusIcon">
              <svg class="spin-slow" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" width="44" height="44">
                <path d="M21 12a9 9 0 11-6.219-8.56"/>
              </svg>
            </div>
            <div class="jpay-status-info">
              <div class="jpay-status-label" id="jpayStatusLabel">Menunggu Pembayaran</div>
              <div class="jpay-status-sub" id="jpayStatusSub">Selesaikan pembayaran sesuai petunjuk di bawah</div>
            </div>
          </div>
          <div class="jpay-status-right">
            <span class="jpay-status-pill" id="jpayStatusPill">PENDING</span>
          </div>
        </div>
      </div>

      <div class="jpay-card">
        <div class="jpay-card-head">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          <span>Scan QRIS untuk Membayar</span>
        </div>
        <div class="jpay-method" id="jpayMethodBody">

          <div class="jpay-method-result" id="jpayMethodResult">
            <div class="jpay-method-result-head">
              <div class="jpay-method-result-icon">
                @if(payment_logo_asset($accountOrder->payment_method ?? $paymentMethod?->code))
                  <img src="{{ payment_logo_asset($accountOrder->payment_method ?? $paymentMethod?->code) }}" alt="{{ $paymentMethod?->name ?? $accountOrder->payment_method }}" class="jpay-method-logo">
                @elseif($paymentMethod && $paymentMethod->icon)
                  <span>{{ $paymentMethod->icon }}</span>
                @else
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--purple-light)" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                @endif
              </div>
              <div>
                <div class="jpay-method-result-label">Pembayaran via</div>
                <div class="jpay-method-result-name">{{ $paymentMethod?->name ?: strtoupper((string) $accountOrder->payment_method) }}</div>
              </div>
            </div>

            @if($gatewayType === 'qris')
            <div class="jpay-method-qr-wrap">
              <div class="jpay-method-qr">
                <div class="jpay-qr-dummy">
                  @if($isDynamic && $qrString)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrString) }}" alt="QRIS" width="220" height="220" style="display:block;border-radius:6px;background:#fff" loading="lazy">
                  @elseif($qrisImage)
                    <img src="{{ media_url($qrisImage) }}" alt="QRIS" width="220" height="220" style="display:block;border-radius:6px;">
                  @else
                    <div class="jpay-qr-empty">
                      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="1.2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                      <p>QRIS belum diatur admin. Silakan hubungi admin.</p>
                    </div>
                  @endif
                </div>
              </div>
              <p class="jpay-method-hint">Scan QR code menggunakan aplikasi e-wallet atau mobile banking</p>
            </div>

            <div class="jpay-money-box">
              <div class="jpay-nominal-label">Nominal Pembayaran</div>
              <div class="jpay-nominal-value">Rp {{ number_format($accountOrder->total_price, 0, ',', '.') }}</div>
              @if($isDynamic)
                <p class="jpay-nominal-note" style="color:#34d399;font-weight:600">Nominal otomatis sudah terisi pada QRIS ketika kamu scan &mdash; tanpa perlu memasukkan angka secara manual.</p>
              @else
                <p class="jpay-nominal-note">Nominal sudah dihitung otomatis dari harga akun. Masukkan angka ini saat membayar.</p>
              @endif
            </div>
            @elseif($gatewayType === 'va')
            <div class="jpay-money-box">
              <div class="jpay-money-label">Nomor Virtual Account</div>
              <div class="jpay-money-value" id="jpayMoneyValue">{{ $vaNumber }}</div>
              @if($gatewayExtra['bank_code'] ?? null)
                <div class="jpay-money-note">{{ $gatewayExtra['bank_code'] }} Virtual Account</div>
              @endif
              <button class="jpay-copy-btn jpay-copy-big" data-copy="{{ $vaNumber }}">Salin Nomor VA</button>
            </div>
            <div class="jpay-nominal-box">
              <div class="jpay-nominal-label">Total Pembayaran</div>
              <div class="jpay-nominal-value">Rp {{ number_format($accountOrder->total_price, 0, ',', '.') }}</div>
              <p class="jpay-nominal-note">Transfer tepat nominal ini ke nomor VA di atas (dibatasi jumlah).</p>
            </div>
            <p class="jpay-next-step">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
              <span>Bayar melalui m-banking / ATM / internet banking. Setelah transfer, status di halaman ini ter-update otomatis.</span>
            </p>
            @elseif($gatewayType === 'retail')
            <div class="jpay-money-box">
              <div class="jpay-money-label">Kode Pembayaran</div>
              <div class="jpay-money-value" id="jpayMoneyValue">{{ $paymentCode }}</div>
              <div class="jpay-money-note">{{ $gatewayExtra['label'] ?? 'Minimarket' }}</div>
              <button class="jpay-copy-btn jpay-copy-big" data-copy="{{ $paymentCode }}">Salin Kode</button>
            </div>
            <div class="jpay-nominal-box">
              <div class="jpay-nominal-label">Total Pembayaran</div>
              <div class="jpay-nominal-value">Rp {{ number_format($accountOrder->total_price, 0, ',', '.') }}</div>
              <p class="jpay-nominal-note">Tunjukkan kode ini ke kasir di gerai {{ $gatewayExtra['label'] ?? 'minimarket' }} terdekat.</p>
            </div>
            <p class="jpay-next-step">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
              <span>Setelah membayar di kasir, status di halaman ini ter-update otomatis.</span>
            </p>
            @elseif($gatewayType === 'ewallet')
            <div class="jpay-money-box">
              <div class="jpay-money-label">Total Pembayaran</div>
              <div class="jpay-nominal-value">Rp {{ number_format($accountOrder->total_price, 0, ',', '.') }}</div>
            </div>
            @if($checkoutUrl)
              <a href="{{ $checkoutUrl }}" class="jpay-pay-btn" id="jpayPayBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                Lanjut ke Pembayaran
              </a>
              <p class="jpay-next-step">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <span>Kamu akan dialihkan ke halaman pembayaran. Setelah selesai, status di halaman ini ter-update otomatis.</span>
              </p>
            @endif
            @else
            <div class="jpay-money-box">
              <div class="jpay-money-label">Total Pembayaran</div>
              <div class="jpay-nominal-value">Rp {{ number_format($accountOrder->total_price, 0, ',', '.') }}</div>
            </div>
            @if($invoiceUrl)
              <a href="{{ $invoiceUrl }}" class="jpay-pay-btn" id="jpayPayBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                Bayar Sekarang
              </a>
              <p class="jpay-next-step">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <span>Kamu akan dialihkan ke halaman pembayaran. Setelah selesai, status di halaman ini ter-update otomatis.</span>
              </p>
            @endif
            @endif

            <div class="jpay-method-trans-id">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-mute)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              <span class="jpay-trans-id-label">No. Pesanan</span>
              <span class="jpay-trans-id-value">{{ $accountOrder->order_ref ?: '#ORD-' . str_pad($accountOrder->id, 6, '0', STR_PAD_LEFT) }}</span>
              <button class="jpay-copy-btn" data-copy="{{ $accountOrder->order_ref ?: '#ORD-' . str_pad($accountOrder->id, 6, '0', STR_PAD_LEFT) }}">Salin</button>
            </div>
          </div>

          <div class="jpay-method-success" id="jpayMethodSuccess" style="display:none">
            <div class="jpay-success-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="3" width="56" height="56"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <div class="jpay-success-label" id="jpaySuccessLabel">Pembayaran Berhasil</div>
            <div class="jpay-success-sub" id="jpaySuccessSub">Pesanan sedang diproses. Admin akan menghubungi kamu via WhatsApp.</div>
          </div>
          <div class="jpay-method-failed" id="jpayMethodFailed" style="display:none">
            <div class="jpay-failed-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" width="56" height="56"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="jpay-failed-label">Pembayaran Gagal</div>
            <div class="jpay-failed-sub">Pesanan dibatalkan. Silakan hubungi admin</div>
          </div>
        </div>
      </div>
    </div>

    <div class="jpay-right">
      <div class="jpay-card jpay-card-sticky">
        <div class="jpay-card-head">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          <span>Ringkasan Pemesanan</span>
        </div>
        <div class="jpay-ringkasan">
          <div class="jpay-ringkasan-product">
            <div class="jpay-ringkasan-icon">
              @if($listing->photo_url)
                <img src="{{ $listing->photo_url }}" alt="{{ $listing->product_name }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
              @else
                <svg width="24" height="24" viewBox="0 0 32 32" fill="none"><defs><linearGradient id="rg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#9d5cf5"/><stop offset="100%" stop-color="#4c1d95"/></linearGradient></defs><polygon points="16,2 27,11 22,30 10,30 5,11" fill="url(#rg)"/></svg>
              @endif
            </div>
            <div>
              <div class="jpay-ringkasan-game">{{ $listing->game }}</div>
              <div class="jpay-ringkasan-pkg">{{ $listing->product_name }}</div>
            </div>
          </div>
          <div class="jpay-ringkasan-details">
            <div class="jpay-ringkasan-row">
              <span class="jpay-r-label">Pemesan</span>
              <span class="jpay-r-value">{{ $accountOrder->customer_name }}</span>
            </div>
            <div class="jpay-ringkasan-row">
              <span class="jpay-r-label">Email</span>
              <span class="jpay-r-value">{{ $accountOrder->customer_email }}</span>
            </div>
            <div class="jpay-ringkasan-row">
              <span class="jpay-r-label">No. WhatsApp</span>
              <span class="jpay-r-value">{{ $accountOrder->customer_phone }}</span>
            </div>
            <div class="jpay-ringkasan-row">
              <span class="jpay-r-label">Metode Bayar</span>
              <span class="jpay-r-value">{{ $paymentMethod?->name ?: strtoupper((string) $accountOrder->payment_method) }}</span>
            </div>
            <div class="jpay-ringkasan-row">
              <span class="jpay-r-label">Status</span>
              <span class="jpay-status-dot">
                <span class="jpay-dot pending" id="jpaySummaryDot"></span>
                <span id="jpaySummaryStatus">Pending</span>
              </span>
            </div>
            <div class="jpay-ringkasan-row">
              <span class="jpay-r-label">Tanggal</span>
              <span class="jpay-r-value">{{ $accountOrder->created_at->format('d M Y, H:i') }}</span>
            </div>
          </div>
          <div class="jpay-rincian">
            <div class="jpay-rincian-row">
              <span>Harga Akun</span>
              <span>Rp {{ number_format($listing->price, 0, ',', '.') }}</span>
            </div>
            <div class="jpay-rincian-row">
              <span>Biaya Layanan</span>
              <span class="jpay-green">Gratis</span>
            </div>
            <div class="jpay-rincian-total">
              <span>Total Pembayaran</span>
              <span class="jpay-total-price">Rp {{ number_format($accountOrder->total_price, 0, ',', '.') }}</span>
            </div>
          </div>
        </div>
        <div class="jpay-ringkasan-actions">
          @auth
          <a href="{{ route('jual-beli-akun.orders') }}" class="btn btn-outline btn-full">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            Pesanan Saya Akun
          </a>
          @endauth
          <a href="{{ route('jual-beli-akun') }}" class="btn btn-outline btn-full">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Kembali ke Jual Beli Akun
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.jpay-wrap {
  width: 100%;
  max-width: var(--layout-max);
  margin: 0 auto;
  padding: 2rem var(--layout-gutter) 4rem;
}
.jpay-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.jpay-header-badge {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--purple-light), var(--purple));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 6px 20px -4px var(--purple-glow);
}
.jpay-header-badge svg { color: #fff; }
.jpay-title { font-size: 1.3rem; font-weight: 700; line-height: 1.2; }
.jpay-sub { font-size: .82rem; color: var(--text-mute); margin-top: .1rem; }

.jpay-grid { display: grid; grid-template-columns: 1.3fr 1fr; gap: 1.5rem; align-items: start; }
@media (max-width: 920px) { .jpay-grid { grid-template-columns: 1fr; } }

.jpay-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; margin-bottom: 1.2rem; }
.jpay-card-sticky { position: sticky; top: 2rem; }
.jpay-card-head { display: flex; align-items: center; gap: .55rem; padding: .9rem 1.2rem; border-bottom: 1px solid var(--border); font-size: .88rem; font-weight: 700; color: var(--text); }
.jpay-card-head svg { color: var(--purple-light); flex-shrink: 0; }

.jpay-status { display: flex; align-items: center; justify-content: space-between; padding: 1.2rem; gap: .8rem; }
.jpay-status-left { display: flex; align-items: center; gap: .8rem; }
.jpay-status-icon { flex-shrink: 0; line-height: 0; }
.jpay-status-label { font-weight: 700; font-size: .95rem; }
.jpay-status-sub { font-size: .75rem; color: var(--text-mute); margin-top: .1rem; }
.jpay-status-pill { display: inline-block; padding: .25rem .7rem; border-radius: 6px; font-size: .7rem; font-weight: 700; background: rgba(251, 191, 36, .15); color: #fbbf24; white-space: nowrap; }

.jpay-method-result { padding: 1.2rem; }
.jpay-method-result-head { display: flex; align-items: center; gap: .7rem; margin-bottom: 1.2rem; }
.jpay-method-result-icon { width: 34px; height: 34px; flex-shrink: 0; line-height: 0; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid rgba(15,23,42,.12); border-radius: 8px; }
.jpay-method-result-icon .jpay-method-logo { width: 26px; height: 26px; object-fit: contain; border-radius: 5px; }
.jpay-method-result-label { font-size: .72rem; color: var(--text-mute); }
.jpay-method-result-name { font-weight: 700; font-size: .9rem; }

.jpay-money-box { display: flex; flex-direction: column; align-items: center; gap: .35rem; padding: 1.2rem 1rem; background: rgba(76, 29, 149, .1); border: 1.5px dashed rgba(124, 58, 237, .35); border-radius: 12px; margin-bottom: 1rem; text-align: center; }
.jpay-money-label { font-size: .72rem; color: var(--text-mute); }
.jpay-money-value { font-family: var(--font-display); font-size: 1.5rem; font-weight: 800; color: var(--text); letter-spacing: .02em; word-break: break-all; line-height: 1.2; }
.jpay-money-note { font-size: .7rem; color: var(--text-dim); }
.jpay-copy-big { margin-top: .35rem; }
.jpay-pay-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .85rem 1.5rem; border: none; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; font-weight: 700; font-size: .95rem; cursor: pointer; transition: transform .2s, box-shadow .2s; box-shadow: 0 4px 20px -4px rgba(37,99,235,.45); font-family: var(--font-body); text-decoration: none; box-sizing: border-box; }
.jpay-pay-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 30px -4px rgba(37,99,235,.6); }
.jpay-pay-btn:active { transform: translateY(0); }

.jpay-method-qr-wrap { display: flex; flex-direction: column; align-items: center; margin-bottom: 1rem; }
.jpay-method-qr { background: #fff; border-radius: 10px; padding: 10px; display: inline-flex; margin-bottom: .5rem; }
.jpay-qr-dummy img { display: block; }
.jpay-qr-empty { width: 220px; height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem; text-align: center; padding: 1rem; box-sizing: border-box; }
.jpay-qr-empty p { font-size: .72rem; color: var(--text-mute); margin: 0; }
.jpay-method-hint { font-size: .72rem; color: var(--text-mute); text-align: center; max-width: 280px; }

.jpay-nominal-box { background: rgba(76, 29, 149, .1); border: 1.5px dashed rgba(124, 58, 237, .35); border-radius: 12px; padding: 1rem; text-align: center; margin-bottom: 1rem; }
.jpay-nominal-label { font-size: .72rem; color: var(--text-mute); margin-bottom: .25rem; }
.jpay-nominal-value { font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; color: var(--gold); line-height: 1.2; }
.jpay-nominal-note { font-size: .7rem; color: var(--text-mute); margin-top: .35rem; }

.jpay-copy-btn { display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .9rem; border: 1px solid var(--border-strong); border-radius: 8px; background: var(--surface-2); color: var(--text-dim); font-size: .75rem; font-weight: 600; cursor: pointer; transition: all .2s; font-family: var(--font-body); }
.jpay-copy-btn:hover { border-color: var(--purple-light); color: var(--purple-light); background: rgba(124, 58, 237, .1); }
.jpay-method-trans-id { display: flex; align-items: center; gap: .4rem; padding: .7rem .9rem; background: var(--bg-soft); border-radius: 10px; font-size: .75rem; flex-wrap: wrap; }
.jpay-trans-id-label { color: var(--text-mute); }
.jpay-trans-id-value { font-weight: 700; color: var(--text); font-family: var(--font-display); letter-spacing: .02em; margin-right: auto; }
.jpay-method-trans-id .jpay-copy-btn { padding: .25rem .6rem; font-size: .68rem; }

.jpay-next-step { display: flex; align-items: flex-start; gap: .45rem; margin-top: .8rem; margin-bottom: 1rem; padding: .7rem .9rem; background: var(--bg-soft); border-radius: 10px; font-size: .72rem; color: var(--text-dim); line-height: 1.5; }
.jpay-next-step svg { flex-shrink: 0; margin-top: 2px; }

.jpay-method-success, .jpay-method-failed { display: flex; flex-direction: column; align-items: center; padding: 2rem 1.5rem; text-align: center; }
.jpay-success-icon, .jpay-failed-icon { margin-bottom: .8rem; }
.jpay-success-label { font-weight: 800; font-size: 1.1rem; color: #34d399; }
.jpay-success-sub { font-size: .82rem; color: var(--text-dim); margin-top: .25rem; }
.jpay-failed-label { font-weight: 800; font-size: 1.1rem; color: #f87171; }
.jpay-failed-sub { font-size: .82rem; color: var(--text-dim); margin-top: .25rem; }

.jpay-ringkasan { display: flex; flex-direction: column; }
.jpay-ringkasan-product { display: flex; align-items: center; gap: .7rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--border); }
.jpay-ringkasan-icon { width: 44px; height: 44px; border-radius: 8px; background: var(--bg-soft); overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.jpay-ringkasan-game { font-size: .72rem; color: var(--text-mute); }
.jpay-ringkasan-pkg { font-weight: 700; font-size: .88rem; line-height: 1.2; }
.jpay-ringkasan-details { padding: .6rem 1.1rem; }
.jpay-ringkasan-row { display: flex; justify-content: space-between; align-items: center; padding: .35rem 0; font-size: .78rem; gap: .5rem; }
.jpay-r-label { color: var(--text-mute); white-space: nowrap; }
.jpay-r-value { color: var(--text); font-weight: 500; text-align: right; word-break: break-all; }
.jpay-status-dot { display: inline-flex; align-items: center; gap: .3rem; font-size: .78rem; }
.jpay-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.jpay-dot.pending { background: #fbbf24; box-shadow: 0 0 6px rgba(251,191,36,.5); }
.jpay-dot.processing { background: #60a5fa; box-shadow: 0 0 6px rgba(96,165,250,.5); }
.jpay-dot.success { background: #34d399; box-shadow: 0 0 6px rgba(52,211,153,.5); }
.jpay-dot.failed { background: #f87171; box-shadow: 0 0 6px rgba(248,113,113,.5); }

.jpay-rincian { border-top: 1px solid var(--border); padding: .8rem 1.1rem; }
.jpay-rincian-row { display: flex; justify-content: space-between; font-size: .78rem; color: var(--text-dim); padding: .25rem 0; }
.jpay-green { color: #34d399; }
.jpay-rincian-total { display: flex; justify-content: space-between; align-items: center; margin-top: .4rem; padding-top: .6rem; border-top: 1px solid var(--border); font-weight: 700; font-size: .88rem; }
.jpay-total-price { font-family: var(--font-display); color: var(--purple-light); font-size: 1.15rem; }

.jpay-ringkasan-actions { padding: .8rem 1.1rem; border-top: 1px solid var(--border); }

.spin-slow { animation: spin 1.2s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>

@push('scripts')
<script>
(function() {
  const statusUrl = @json(route('jual-beli-akun.payment.status', $accountOrder));
  const initialStatus = @json($accountOrder->status);
  const gatewayType = @json($gatewayType);
  const checkoutUrl = @json($checkoutUrl);
  const invoiceUrl = @json($invoiceUrl);

  // logo metode ikut tema (dark/light)
  function applyThemeToMethodLogos() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    document.querySelectorAll('.jpay-pay-themeable').forEach(function(img) {
      const darkSrc = img.getAttribute('data-src-dark');
      const lightSrc = img.getAttribute('data-src-light');
      if (darkSrc && lightSrc) img.src = isLight ? lightSrc : darkSrc;
    });
  }
  applyThemeToMethodLogos();
  document.addEventListener('themeChanged', function() { setTimeout(applyThemeToMethodLogos, 50); });

  // e-wallet / invoice: alihkan ke halaman pembayaran (sekali saja per pesanan).
  if (initialStatus === 'pending' && (gatewayType === 'ewallet' || gatewayType === 'invoice') && (checkoutUrl || invoiceUrl)) {
    const target = checkoutUrl || invoiceUrl;
    const shownKey = 'jpay_redirect_' + @json($accountOrder->order_ref);
    if (!sessionStorage.getItem(shownKey)) {
      sessionStorage.setItem(shownKey, '1');
      const sub = document.getElementById('jpayStatusSub');
      if (sub) sub.textContent = 'Mengalihkan ke halaman pembayaran...';
      setTimeout(function() { window.location.href = target; }, 1200);
    }
  }

  function setSummaryStatus(type) {
    const dot = document.getElementById('jpaySummaryDot');
    const txt = document.getElementById('jpaySummaryStatus');
    if (!dot || !txt) return;
    dot.className = 'jpay-dot ' + (type === 'success' || type === 'processing' ? type : 'failed');
    txt.textContent =
      type === 'success' ? 'Selesai' :
      type === 'processing' ? 'Diproses' :
      type === 'failed' || type === 'cancelled' ? 'Gagal' : 'Pending';
  }

  function updateStatus(type) {
    const icon = document.getElementById('jpayStatusIcon');
    const label = document.getElementById('jpayStatusLabel');
    const sub = document.getElementById('jpayStatusSub');
    const pill = document.getElementById('jpayStatusPill');
    const methodResult = document.getElementById('jpayMethodResult');
    const methodSuccess = document.getElementById('jpayMethodSuccess');
    const methodFailed = document.getElementById('jpayMethodFailed');

    if (type === 'processing') {
      icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" width="44" height="44"><path d="M12 6v6l4 2"/></svg>';
      label.textContent = 'Pembayaran Diterima';
      sub.textContent = 'Pesanan sedang diproses admin.';
      pill.textContent = 'DIPROSES';
      pill.style.background = 'rgba(251,191,36,.15)';
      pill.style.color = '#fbbf24';
      if (methodResult) methodResult.style.display = 'none';
      if (methodSuccess) methodSuccess.style.display = 'flex';
      if (methodFailed) methodFailed.style.display = 'none';
      setSummaryStatus('processing');
    } else if (type === 'success') {
      icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5" width="44" height="44"><path d="M20 6 9 17l-5-5"/></svg>';
      label.textContent = 'Pesanan Selesai';
      sub.textContent = 'Pembayaran dikonfirmasi. Admin akan mengirim akun via WhatsApp.';
      pill.textContent = 'BERHASIL';
      pill.style.background = 'rgba(52,211,153,.15)';
      pill.style.color = '#34d399';
      if (methodResult) methodResult.style.display = 'none';
      if (methodSuccess) methodSuccess.style.display = 'flex';
      if (methodFailed) methodFailed.style.display = 'none';
      setSummaryStatus('success');
    } else if (type === 'failed' || type === 'cancelled') {
      icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" width="44" height="44"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
      label.textContent = 'Pembayaran Gagal';
      sub.textContent = 'Pesanan dibatalkan. Silakan hubungi admin';
      pill.textContent = 'GAGAL';
      pill.style.background = 'rgba(248,113,113,.15)';
      pill.style.color = '#f87171';
      if (methodResult) methodResult.style.display = 'none';
      if (methodSuccess) methodSuccess.style.display = 'none';
      if (methodFailed) methodFailed.style.display = 'flex';
      setSummaryStatus('failed');
    }
  }

  // status awal (jika sudah bukan pending saat halaman dibuka)
  if (initialStatus !== 'pending') {
    updateStatus(initialStatus);
  }

  // polling -> perbarui UI saat admin konfirmasi
  let attempts = 0;
  (function check() {
    fetch(statusUrl)
      .then(r => r.json())
      .then(data => {
        if (data.status !== 'pending') {
          updateStatus(data.status);
          return;
        }
        attempts++;
        if (attempts < 600) setTimeout(check, 5000);
      })
      .catch(() => { if (attempts < 600) setTimeout(check, 10000); });
  })();

  // copy button
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.jpay-copy-btn');
    if (!btn) return;
    const text = btn.dataset.copy;
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
      const orig = btn.textContent;
      btn.textContent = 'Tersalin!';
      btn.style.background = 'rgba(52,211,153,.2)';
      btn.style.color = '#34d399';
      setTimeout(function() { btn.textContent = orig; btn.style.background = ''; btn.style.color = ''; }, 2000);
    });
  });
})();
</script>
@endpush
@endsection
