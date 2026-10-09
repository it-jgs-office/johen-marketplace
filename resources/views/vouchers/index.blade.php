@extends('layouts.topup')

@section('title', 'Voucher Saya - ' . config('app.name'))

@section('content')
<div class="vc-page">
  <div class="vc-hero">
    <div class="vc-hero-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h14a2 2 0 002-2v-6z"/>
        <path d="M16 6a4 4 0 00-8 0"/>
      </svg>
    </div>
    <h1>Voucher Saya</h1>
    <p>Semua kode diskon dari Gacha Voucher, siap dipakai di halaman checkout.</p>
    <div class="vc-stats">
      <div class="vc-stat">
        <strong id="vcTotalCount">{{ $vouchers->count() }}</strong>
        <span>Total Voucher</span>
      </div>
      <div class="vc-stat">
        <strong id="vcActiveCount">{{ $vouchers->filter(fn($v) => $v->isRedeemable())->count() }}</strong>
        <span>Belum Dipakai</span>
      </div>
    </div>
  </div>

  <div class="vc-claim">
    <h2>Punya kode dari Gacha sebagai tamu?</h2>
    <p>Masukkan kodenya di sini supaya tersimpan permanen di akun kamu.</p>
    <form method="POST" action="{{ route('vouchers.claim') }}" class="vc-claim-form">
      @csrf
      <input type="text" name="code" value="{{ old('code') }}" placeholder="JHN-XXXX-XXXX" maxlength="32" required>
      <button type="submit" class="vc-btn">Klaim Voucher</button>
    </form>
  </div>

  <div class="vc-list">
    @forelse($vouchers as $voucher)
      @php
        $status = $voucher->is_exhausted ? ['label' => 'Sudah Dipakai', 'class' => 'used'] : ($voucher->is_expired ? ['label' => 'Kedaluwarsa', 'class' => 'expired'] : ['label' => 'Aktif', 'class' => 'active']);
      @endphp
      <div class="vc-card vc-{{ $status['class'] }}" data-voucher-code="{{ $voucher->code }}">
        <div class="vc-card-main">
          <div class="vc-card-head">
            <span class="vc-badge vc-badge-{{ $status['class'] }}">{{ $status['label'] }}</span>
            @if($voucher->source === \App\Models\Voucher::SOURCE_GACHA)
              <span class="vc-badge vc-badge-gacha">Gacha</span>
            @endif
          </div>
          <h3 class="vc-card-label">{{ $voucher->label ?: 'Voucher Diskon' }}</h3>
          <div class="vc-card-meta">
            @if($voucher->min_spend > 0)
              <span>Min. belanja {{ 'Rp '.number_format($voucher->min_spend, 0, ',', '.') }}</span>
            @endif
            <span>
              @if($voucher->quota)
                Sisa {{ max(0, (int) $voucher->quota - (int) $voucher->used_count) }}/{{ $voucher->quota }} pakai
              @else
                Tidak terbatas
              @endif
            </span>
            <span>
              @if($voucher->expires_at)
                Berlaku s/d {{ $voucher->expires_at->format('d/m/Y') }}
              @else
                Berlaku selamanya
              @endif
            </span>
          </div>
        </div>
        <div class="vc-card-side">
          <strong class="vc-card-value">{{ $voucher->value_label }}</strong>
          <div class="vc-code-row">
            <code class="vc-code">{{ $voucher->code }}</code>
            <button type="button" class="vc-copy" data-vc-copy onclick="copyVoucherCode(this)">Salin</button>
          </div>
        </div>
      </div>
    @empty
      <div class="vc-empty">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18"/>
        </svg>
        <h3>Belum ada voucher</h3>
        <p>Putarkan roda Gacha Voucher di pojok kiri bawah untuk mendapatkan kode diskon pertamamu.</p>
      </div>
    @endforelse
  </div>
</div>

<style>
.vc-page{width:100%;max-width:var(--layout-max);margin:0 auto;padding:3rem var(--layout-gutter) 4rem;min-height:calc(100vh - 140px);}
.vc-hero{text-align:center;margin-bottom:2rem;}
.vc-hero-icon{width:58px;height:58px;margin:0 auto .9rem;border-radius:18px;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#fbbf24,#f97316);color:#431407;box-shadow:0 8px 24px -6px rgba(249,115,22,.5);}
.vc-hero h1{font-size:1.8rem;font-weight:800;margin-bottom:.3rem;}
.vc-hero>p{color:var(--text-dim);font-size:.95rem;max-width:520px;margin:0 auto;}
.vc-stats{display:flex;justify-content:center;gap:1.5rem;margin-top:1.4rem;}
.vc-stat{display:flex;flex-direction:column;align-items:center;}
.vc-stat strong{font-family:var(--font-display);font-size:1.5rem;font-weight:800;color:var(--purple-light);}
.vc-stat span{font-size:.75rem;color:var(--text-mute);}

.vc-claim{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.3rem 1.4rem;margin-bottom:1.5rem;}
.vc-claim h2{font-size:1rem;font-weight:700;margin-bottom:.25rem;}
.vc-claim p{font-size:.82rem;color:var(--text-dim);margin-bottom:.9rem;}
.vc-claim-form{display:flex;gap:.6rem;flex-wrap:wrap;}
.vc-claim-form input{flex:1;min-width:200px;padding:.7rem .9rem;border-radius:10px;background:var(--surface-2);
  border:1px solid var(--border);color:var(--text);font-family:ui-monospace,Menlo,monospace;letter-spacing:1px;font-size:.9rem;outline:none;}
.vc-claim-form input:focus{border-color:var(--purple);box-shadow:0 0 0 3px var(--purple-glow);}
.vc-btn{padding:.7rem 1.3rem;border-radius:10px;background:var(--action-gradient,linear-gradient(135deg,#2563eb,#0ea5e9));
  color:#fff;font-size:.86rem;font-weight:700;transition:filter .15s;}
.vc-btn:hover{filter:brightness(1.12);}

.vc-list{display:flex;flex-direction:column;gap:.9rem;}
.vc-card{display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;padding:1.1rem 1.2rem;border-radius:var(--radius-md);
  background:var(--surface);border:1px solid var(--border);position:relative;overflow:hidden;}
.vc-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--purple);}
.vc-card.vc-used::before{background:#6b7280;}
.vc-card.vc-expired::before{background:#f87171;}
.vc-card.vc-used,.vc-card.vc-expired{opacity:.62;}
.vc-card-main{flex:1;min-width:200px;}
.vc-card-head{display:flex;gap:.4rem;margin-bottom:.35rem;flex-wrap:wrap;}
.vc-badge{font-size:.66rem;font-weight:800;letter-spacing:.6px;padding:.16rem .5rem;border-radius:999px;text-transform:uppercase;}
.vc-badge-active{background:rgba(34,197,94,.16);color:#22c55e;}
.vc-badge-used{background:rgba(107,114,128,.2);color:#9ca3af;}
.vc-badge-expired{background:rgba(248,113,113,.16);color:#f87171;}
.vc-badge-gacha{background:color-mix(in srgb,var(--purple) 18%,transparent);color:var(--purple-light);}
.vc-card-label{font-size:1rem;font-weight:700;margin-bottom:.35rem;}
.vc-card-meta{display:flex;flex-wrap:wrap;gap:.35rem .9rem;font-size:.75rem;color:var(--text-mute);}
.vc-card-side{display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;}
.vc-card-value{font-family:var(--font-display);font-size:1.5rem;font-weight:800;color:var(--purple-light);line-height:1;}
.vc-code-row{display:flex;gap:.4rem;}
.vc-code{padding:.4rem .7rem;border-radius:8px;background:var(--surface-2);border:1px dashed var(--border-strong);
  color:var(--text);font-family:ui-monospace,Menlo,monospace;font-size:.82rem;font-weight:700;letter-spacing:1px;}
.vc-copy{padding:.4rem .7rem;border-radius:8px;background:var(--surface-2);border:1px solid var(--border);
  color:var(--text-dim);font-size:.74rem;font-weight:600;transition:color .15s,border-color .15s;}
.vc-copy:hover{color:var(--text);border-color:var(--purple);}

.vc-empty{text-align:center;padding:3.2rem 1.5rem;background:var(--surface);border:1px dashed var(--border);
  border-radius:var(--radius-md);color:var(--text-mute);}
.vc-empty h3{font-size:1.05rem;font-weight:700;color:var(--text);margin:.8rem 0 .35rem;}
.vc-empty p{font-size:.85rem;max-width:380px;margin:0 auto;}

@media (max-width:600px){
  .vc-card-side{align-items:flex-start;width:100%;}
  .vc-stats{gap:1.1rem;}
}
</style>

@push('scripts')
<script>
function copyVoucherCode(btn) {
    var card = btn.closest('.vc-card');
    var code = card ? card.dataset.voucherCode : null;
    if (!code) return;

    var done = function () {
        var original = btn.textContent;
        btn.textContent = 'Tersalin!';
        setTimeout(function () { btn.textContent = original; }, 1600);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(done).catch(function () {});
    } else {
        var helper = document.createElement('textarea');
        helper.value = code;
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(helper);
    }
}
</script>
@endpush
@endsection
