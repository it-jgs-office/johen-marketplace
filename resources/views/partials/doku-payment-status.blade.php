@if(data_get($paymentOrder->gateway_extra, 'integration') === 'direct' && data_get($paymentOrder->gateway_extra, 'provider') === 'doku')
<section class="jg-payment-live" data-payment-live data-expires="{{ data_get($paymentOrder->gateway_extra, 'expired_date') }}" data-status="{{ $paymentOrder->status }}" aria-label="Informasi pembayaran">
    <div class="jg-payment-live-heading">
        <strong>Pembayaran di Johen Gaming</strong>
        <span data-connection role="status">Status diperbarui otomatis</span>
    </div>
    <p>Bayar sesuai QR atau kode yang tertera. Konfirmasi pembayaran akan muncul di halaman ini.</p>
    <div class="jg-payment-live-footer">
        <span data-expiry-label>Batas pembayaran</span>
        <time datetime="{{ data_get($paymentOrder->gateway_extra, 'expired_date') }}" data-expiry-value>
            {{ \Carbon\Carbon::parse(data_get($paymentOrder->gateway_extra, 'expired_date'))->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
        </time>
    </div>
</section>
@once
@push('styles')
<style>
.jg-payment-live{margin:0 0 1.25rem;padding:1rem 1.25rem;border:1px solid var(--border);border-left:3px solid #0ea5e9;border-radius:12px;background:var(--surface-2);color:var(--text);}
.jg-payment-live-heading,.jg-payment-live-footer{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;}
.jg-payment-live-heading strong{font-family:var(--font-display);font-size:.95rem;}
.jg-payment-live-heading span{font-size:.75rem;color:var(--text-dim);}
.jg-payment-live p{margin:.65rem 0;font-size:.82rem;line-height:1.6;color:var(--text-dim);}
.jg-payment-live-footer{padding-top:.65rem;border-top:1px solid var(--border);font-size:.78rem;}
.jg-payment-live-footer time{font-variant-numeric:tabular-nums;font-weight:600;}
</style>
@endpush
@push('scripts')
<script>
(() => {
    const panel = document.querySelector('[data-payment-live]');
    if (!panel) return;
    const connection = panel.querySelector('[data-connection]');
    document.addEventListener('payment:updated', event => {
        const data = event.detail;
        panel.dataset.status = data.status;
        connection.textContent = data.status === 'pending'
            ? 'Diperbarui ' + new Date().toLocaleTimeString('id-ID')
            : data.payment_status === 'EXPIRED' ? 'Pembayaran kedaluwarsa'
            : data.payment_status === 'CANCELLED' ? 'Pembayaran dibatalkan'
            : data.payment_status === 'SUCCESS' ? 'Pembayaran telah diterima'
            : data.status === 'failed' ? 'Transaksi gagal' : 'Pembayaran telah diterima';
        if (data.status !== 'pending') {
            panel.querySelector('p').textContent = data.status === 'failed'
                ? 'Lihat status pesanan untuk rincian. Hubungi CS jika pembayaran sudah dilakukan.'
                : 'Tidak perlu membayar lagi. Ikuti perkembangan pesanan pada halaman ini.';
        }
    });
    document.addEventListener('payment:offline', () => {
        connection.textContent = 'Koneksi terputus. Mencoba memperbarui status…';
    });
})();
</script>
@endpush
@endonce
@endif
