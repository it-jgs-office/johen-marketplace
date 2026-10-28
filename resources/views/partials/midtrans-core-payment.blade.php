@if(data_get($paymentOrder->gateway_extra, 'provider') === 'midtrans' && data_get($paymentOrder->gateway_extra, 'integration') === 'core_api')
<section class="jg-core-payment" aria-label="Pembayaran Midtrans Core API">
    <span class="jg-core-mark">J</span>
    <div class="jg-core-copy">
        <strong>Pembayaran Johen</strong>
        <span>Instruksi pembayaran dibuat khusus untuk pesanan ini.</span>
    </div>
    <span class="jg-core-state">{{ strtoupper((string) data_get($paymentOrder->gateway_extra, 'environment', 'sandbox')) }}</span>
</section>
@once
@push('styles')
<style>
.jg-core-payment{position:relative;display:flex;align-items:center;gap:.8rem;margin:0 0 1.25rem;padding:.8rem 1rem;overflow:hidden;border:1px solid rgba(91,213,255,.28);border-radius:14px;background:linear-gradient(115deg,rgba(21,38,84,.92),rgba(44,26,88,.88));box-shadow:0 16px 30px -24px rgba(72,187,255,.85);}
.jg-core-payment::after{content:"";position:absolute;inset:0 0 0 auto;width:38%;background:repeating-linear-gradient(135deg,transparent 0 8px,rgba(255,255,255,.035) 8px 9px);pointer-events:none;}
.jg-core-mark{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:10px;background:#5bd5ff;color:#101d49;font-family:var(--font-display);font-weight:900;font-size:1.05rem;box-shadow:0 0 0 4px rgba(91,213,255,.12);}
.jg-core-copy{position:relative;z-index:1;display:grid;gap:.12rem;min-width:0;}
.jg-core-copy strong{color:#fff;font:800 .86rem var(--font-display);letter-spacing:.02em;}.jg-core-copy span{color:#cbd9ff;font-size:.72rem;line-height:1.35;}
.jg-core-state{position:relative;z-index:1;margin-left:auto;padding:.28rem .48rem;border:1px solid rgba(91,213,255,.38);border-radius:999px;color:#9ce7ff;font:800 .62rem var(--font-display);letter-spacing:.1em;}
@media(max-width:520px){.jg-core-state{display:none}.jg-core-payment{padding:.75rem .85rem;}}
</style>
@endpush
@endonce
@endif
