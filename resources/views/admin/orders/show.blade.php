@extends('admin.layouts.app')

@section('title', 'Detail Pesanan')

@push('styles')
<style>
    .transaction-actions-bar { display:flex; flex-wrap:wrap; align-items:center; gap:1rem; }
    .transaction-actions-bar > h3 { margin:0; }
    .transaction-actions { display:flex; flex-wrap:wrap; align-items:center; gap:.65rem; margin-left:auto; }
    .transaction-actions form { margin:0; }
    .transaction-actions .btn:disabled { cursor:not-allowed; opacity:.45; transform:none; box-shadow:none; }
    .transaction-action-modal { position:fixed; inset:0; z-index:1200; display:flex; align-items:center; justify-content:center; padding:1rem; }
    .transaction-action-modal[hidden] { display:none; }
    .transaction-action-modal__backdrop { position:absolute; inset:0; background:rgba(2,6,23,.76); backdrop-filter:blur(5px); }
    .transaction-action-modal__panel { position:relative; width:min(100%,460px); padding:1.4rem; border:1px solid var(--glass-border); border-radius:16px; background:var(--bg-card); box-shadow:0 24px 70px rgba(2,6,23,.42); }
    .transaction-action-modal__head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .transaction-action-modal__head h3 { font-size:1rem; font-weight:700; }
    .transaction-action-modal__head p { margin-top:.3rem; color:var(--text-muted); font-size:.8rem; line-height:1.5; }
    .transaction-action-modal__close { width:34px; height:34px; flex:0 0 auto; border:1px solid var(--glass-border); border-radius:9px; background:transparent; color:var(--text-muted); cursor:pointer; }
    .transaction-action-modal__actions { display:flex; justify-content:flex-end; gap:.65rem; margin-top:1rem; }
    .refund-policy-dropdown > summary { display:flex; align-items:center; justify-content:space-between; gap:1rem; cursor:pointer; list-style:none; }
    .refund-policy-dropdown > summary::-webkit-details-marker { display:none; }
    .refund-policy-dropdown__title { display:flex; align-items:center; font-weight:600; }
    .refund-policy-dropdown__chevron { color:var(--text-muted); transition:transform .2s ease; }
    .refund-policy-dropdown[open] .refund-policy-dropdown__chevron { transform:rotate(180deg); }
    .refund-policy-dropdown__content { margin-top:1rem; padding-top:1rem; border-top:1px solid var(--glass-border); }
    .refund-policy { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
    .refund-policy__step { display:flex; align-items:flex-start; gap:.7rem; padding:.9rem; border:1px solid var(--glass-border); border-radius:12px; background:rgba(15,23,42,.22); }
    .refund-policy__number { display:grid; place-items:center; width:28px; height:28px; flex:0 0 auto; border-radius:9px; background:rgba(59,130,246,.15); color:#60a5fa; font-size:.75rem; font-weight:800; }
    .refund-policy__step strong { display:block; margin-bottom:.25rem; color:var(--text); font-size:.8rem; }
    .refund-policy__step p { color:var(--text-muted); font-size:.75rem; line-height:1.55; }
    @media (max-width:640px) {
        .transaction-actions-bar { align-items:stretch; }
        .transaction-actions, .transaction-actions form, .transaction-actions .btn { width:100%; }
        .transaction-actions { margin-left:0; }
        .transaction-action-modal__actions .btn { flex:1; }
        .refund-policy { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
@php($isRefunded = $order->saldo_status === 'refunded')
<div style="max-width:1100px;margin:0 auto">
    <a href="{{ route('admin.orders') }}" class="btn btn-ghost mb-4" style="padding-left:0">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <div class="card-glass p-6 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-semibold">Detail Pesanan</h2>
                <p style="font-size:0.85rem;color:var(--text-muted);font-family:monospace;margin-top:0.25rem">{{ $order->order_id }}</p>
            </div>
            <span class="badge" style="font-size:0.85rem;padding:0.35rem 1rem;
                @if($order->status === 'success') background:rgba(16,185,129,0.12);color:var(--success)
                @elseif($order->status === 'pending') background:rgba(245,158,11,0.12);color:var(--warning)
                @elseif($order->status === 'processing') background:rgba(59,130,246,0.12);color:var(--info)
                @else background:rgba(239,68,68,0.12);color:var(--error) @endif">
                <i class="fas fa-circle" style="font-size:0.4rem;margin-right:0.35rem;vertical-align:middle"></i>
                {{ ucfirst($order->status) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Pelanggan</p>
                <p class="font-semibold">{{ $order->user->name ?? 'N/A' }}</p>
                <p style="font-size:0.85rem;color:var(--text-muted)">{{ $order->user->email ?? '' }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Tanggal</p>
                <p class="font-semibold">{{ $order->created_at->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Produk</p>
                <p class="font-semibold">{{ $order->product_name }}</p>
                <p style="font-size:0.85rem;color:var(--text-muted)">{{ $order->brand }} - {{ $order->category }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Penerima</p>
                <p class="font-semibold">{{ $order->customer_number }}@if($order->effective_zone_id) &middot; Zone {{ $order->effective_zone_id }}@endif</p>
                <p style="font-size:0.85rem;color:var(--text-muted)">{{ $order->customer_name ?? 'Tanpa nickname' }}</p>
            </div>
        </div>

        <div style="border-top:1px solid var(--glass-border);padding-top:1rem;display:flex;justify-content:space-between;align-items:center">
            <div><p style="color:var(--text-muted)">Total Pesanan</p><small style="display:flex;align-items:center;gap:.35rem;color:var(--text-dim)">Gateway Rp {{ number_format($order->cash_amount, 0, ',', '.') }} + <x-johen-point-icon size="16" /> {{ number_format($order->points_used, 0, ',', '.') }} JP</small></div>
            <p style="font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,var(--accent),#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Rp {{ number_format($order->price, 0, ',', '.') }}</p>
        </div>
    </div>

    @if($order->transaction)
    <div class="card-glass p-6 mb-6">
        <h3 class="font-semibold mb-4">
            <i class="fas fa-receipt" style="color:var(--accent);margin-right:0.5rem"></i>
            Info Transaksi
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem">Transaction ID</p>
                <p style="font-size:0.85rem;font-family:monospace">{{ $order->transaction->transaction_id ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem">Tipe Pembayaran</p>
                <p style="font-size:0.85rem">{{ $order->transaction->payment_type ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem">Status</p>
                <p style="font-size:0.85rem">{{ $order->transaction->status ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem">Fraud Status</p>
                <p style="font-size:0.85rem">{{ $order->transaction->fraud_status ?? '-' }}</p>
            </div>
        </div>
        <div style="margin-top:1rem">
            <p style="font-size:.78rem;color:var(--text-dim);font-weight:700;text-transform:uppercase;margin-bottom:.5rem">Last Provider Response</p>
            <pre style="max-height:260px;overflow:auto;padding:1rem;border-radius:10px;background:rgba(2,6,23,.55);font-size:.76rem;white-space:pre-wrap">{{ json_encode($order->transaction->raw_response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
    @endif

    <details class="card-glass p-6 mb-6 refund-policy-dropdown">
        <summary>
            <span class="refund-policy-dropdown__title"><i class="fas fa-shield-halved" style="color:#60a5fa;margin-right:.5rem"></i>Sistem Refund Topup</span>
            <i class="fas fa-chevron-down refund-policy-dropdown__chevron" aria-hidden="true"></i>
        </summary>
        <div class="refund-policy-dropdown__content">
            <div class="refund-policy">
                <div class="refund-policy__step">
                    <span class="refund-policy__number">1</span>
                    <div><strong>Error provider ditahan</strong><p>Produk gangguan atau respons provider gagal tidak langsung direfund. Transaksi tetap diproses dan dana ditahan selama {{ (int) config('digiflazz.reconcile.provider_error_grace_minutes', 10) }} menit.</p></div>
                </div>
                <div class="refund-policy__step">
                    <span class="refund-policy__number">2</span>
                    <div><strong>Perbaiki lalu retry</strong><p>Setelah gangguan diperbaiki, klik Retry Topup sebelum batas waktu. Retry hanya dapat dilakukan sekali untuk transaksi yang sama.</p></div>
                </div>
                <div class="refund-policy__step">
                    <span class="refund-policy__number">3</span>
                    <div><strong>Refund otomatis</strong><p>Jika error tidak terselesaikan, dana otomatis masuk ke <x-johen-point-icon size="16" /> Johen Point dengan alasan dari respons provider. Refund manual juga hanya dapat dilakukan sekali.</p></div>
                </div>
            </div>
        </div>
    </details>

    <div class="card-glass p-6 mb-6">
        <div class="transaction-actions-bar">
            <h3 class="font-semibold"><i class="fas fa-screwdriver-wrench" style="color:var(--accent);margin-right:.5rem"></i>Aksi Transaksi</h3>
            <div class="transaction-actions">
                <form method="POST" action="{{ route('admin.orders.retry', $order) }}">@csrf<button class="btn btn-ghost" {{ $isRefunded || $order->saldo_status !== 'held' || $order->status === 'success' || $order->adminActions->where('action','retry')->isNotEmpty() ? 'disabled' : '' }}>Retry Topup</button></form>
                <form method="POST" action="{{ route('admin.orders.check-provider', $order) }}">@csrf<button class="btn btn-primary" {{ $isRefunded || $order->saldo_status !== 'held' || $order->status === 'success' || $order->adminActions->where('action','check_provider')->isNotEmpty() ? 'disabled' : '' }}><i class="fas fa-rotate mr-1"></i>Cek Status Provider</button></form>
                <button type="button" class="btn" style="background:#059669;color:white" data-open-transaction-modal="manualSuccessModal" {{ $isRefunded || $order->status === 'success' || $order->saldo_status !== 'held' || $order->adminActions->where('action','manual_success')->isNotEmpty() ? 'disabled' : '' }}>Manual Success</button>
                <button type="button" class="btn btn-danger" data-open-transaction-modal="refundModal" {{ $isRefunded || $order->saldo_status !== 'held' || $order->adminActions->where('action','manual_refund')->isNotEmpty() ? 'disabled' : '' }}><x-johen-point-icon size="18" /> Refund ke Johen Point</button>
            </div>
        </div>
        @if($order->reconcile_status === 'provider_error' && $order->provider_error_at)
            <div style="margin-top:1rem;padding:.85rem 1rem;border:1px solid rgba(245,158,11,.35);border-radius:12px;background:rgba(245,158,11,.1);color:#fbbf24;font-size:.8rem;line-height:1.55">
                <strong style="display:block">Error provider menunggu tindakan</strong>
                <span style="color:var(--text-muted)">{{ $order->provider_error_reason ?: $order->note }} Retry transaksi setelah gangguan diperbaiki. Jika tidak di-retry, refund otomatis ke Johen Point pada {{ $order->provider_error_at->copy()->addMinutes((int) config('digiflazz.reconcile.provider_error_grace_minutes', 10))->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB.</span>
            </div>
        @endif
    </div>

    <div class="transaction-action-modal" id="manualSuccessModal" role="dialog" aria-modal="true" aria-labelledby="manualSuccessTitle" hidden>
        <div class="transaction-action-modal__backdrop" data-close-transaction-modal></div>
        <div class="transaction-action-modal__panel">
            <div class="transaction-action-modal__head">
                <div><h3 id="manualSuccessTitle">Manual Success</h3><p>Masukkan alasan sebelum transaksi ditandai sukses.</p></div>
                <button type="button" class="transaction-action-modal__close" data-close-transaction-modal aria-label="Tutup modal"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.orders.manual-success', $order) }}">
                @csrf
                <textarea class="input-field" name="reason" required maxlength="1000" rows="4" placeholder="Alasan wajib"></textarea>
                <div class="transaction-action-modal__actions"><button type="button" class="btn btn-ghost" data-close-transaction-modal>Batal</button><button class="btn" style="background:#059669;color:white">Tandai Sukses</button></div>
            </form>
        </div>
    </div>

    <div class="transaction-action-modal" id="refundModal" role="dialog" aria-modal="true" aria-labelledby="refundTitle" hidden>
        <div class="transaction-action-modal__backdrop" data-close-transaction-modal></div>
        <div class="transaction-action-modal__panel">
            <div class="transaction-action-modal__head">
                <div><h3 id="refundTitle"><x-johen-point-icon size="22" /> Refund ke Johen Point</h3><p>Refund hanya dapat dilakukan sekali. Dana otomatis masuk ke Johen Point pengguna.</p></div>
                <button type="button" class="transaction-action-modal__close" data-close-transaction-modal aria-label="Tutup modal"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.orders.refund', $order) }}">
                @csrf
                <textarea class="input-field" name="reason" required maxlength="1000" rows="4" placeholder="Alasan refund wajib"></textarea>
                <div class="transaction-action-modal__actions"><button type="button" class="btn btn-ghost" data-close-transaction-modal>Batal</button><button class="btn btn-danger">Refund Sekali</button></div>
            </form>
        </div>
    </div>

    <div class="card-glass p-6 mb-6">
        <h3 class="font-semibold mb-4">Riwayat Attempt Provider</h3>
        <div style="overflow:auto"><table style="width:100%;font-size:.82rem"><thead><tr style="text-align:left;color:var(--text-dim)"><th style="padding:.7rem">Ke</th><th>Aksi</th><th>Ref Provider</th><th>Status</th><th>Pesan</th><th>Waktu</th></tr></thead><tbody>@forelse($order->providerAttempts as $attempt)<tr style="border-top:1px solid var(--glass-border)"><td style="padding:.7rem">{{ $loop->iteration }}</td><td>{{ $attempt->action }}</td><td>{{ $attempt->provider_ref ?: '-' }}</td><td>{{ $attempt->status ?: '-' }}</td><td>{{ $attempt->message ?: '-' }}</td><td>{{ $attempt->created_at->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="6" style="padding:1rem;text-align:center;color:var(--text-dim)">Belum ada attempt tercatat.</td></tr>@endforelse</tbody></table></div>
    </div>

    <div class="card-glass p-6">
        <h3 class="font-semibold mb-4">Audit Aksi Admin</h3>
        <div style="overflow:auto"><table style="width:100%;font-size:.82rem"><thead><tr style="text-align:left;color:var(--text-dim)"><th style="padding:.7rem">Aksi</th><th>Admin</th><th>Alasan</th><th>Nilai</th><th>Waktu</th></tr></thead><tbody>@forelse($order->adminActions as $action)<tr style="border-top:1px solid var(--glass-border)"><td style="padding:.7rem">{{ $action->action }}</td><td>{{ $action->admin->name ?? 'System' }}</td><td>{{ $action->reason ?: '-' }}</td><td>{{ $action->amount !== null ? number_format($action->amount,0,',','.') : '-' }}</td><td>{{ $action->created_at->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="5" style="padding:1rem;text-align:center;color:var(--text-dim)">Belum ada aksi admin.</td></tr>@endforelse</tbody></table></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-open-transaction-modal]').forEach(function (button) {
    button.addEventListener('click', function () {
        var modal = document.getElementById(button.dataset.openTransactionModal);
        if (!modal || button.disabled) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('textarea')?.focus();
    });
});

function closeTransactionModal(modal) {
    modal.hidden = true;
    document.body.style.overflow = '';
}

document.querySelectorAll('[data-close-transaction-modal]').forEach(function (button) {
    button.addEventListener('click', function () {
        var modal = button.closest('.transaction-action-modal');
        if (modal) closeTransactionModal(modal);
    });
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.transaction-action-modal:not([hidden])').forEach(closeTransactionModal);
});
</script>
@endpush
