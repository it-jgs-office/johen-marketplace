@extends('admin.layouts.app')

@section('title', 'Detail Pesanan Akun')

@php
$paymentLabels = [
    'qris' => 'QRIS', 'gopay' => 'GoPay', 'dana' => 'DANA', 'ovo' => 'OVO', 'shopeepay' => 'ShopeePay', 'linkaja' => 'LinkAja',
    'bca_va' => 'BCA Virtual Account', 'bni_va' => 'BNI Virtual Account', 'bri_va' => 'BRI Virtual Account',
    'bsi_va' => 'BSI Virtual Account', 'cimb_va' => 'CIMB Virtual Account',
    'mandiri_va' => 'Mandiri Virtual Account', 'permata_va' => 'Permata Virtual Account',
    'alfamart' => 'Alfamart', 'indomaret' => 'Indomaret',
];
@endphp

@section('content')
<div style="max-width:800px;margin:0 auto">
    <a href="{{ route('admin.account-orders') }}" class="btn btn-ghost mb-4" style="padding-left:0">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <div class="card-glass p-6 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-semibold">Detail Pesanan Akun</h2>
                <p style="font-size:0.85rem;color:var(--text-muted);font-family:monospace;margin-top:0.25rem">{{ $accountOrder->order_ref }}</p>
            </div>
            <span class="badge" style="font-size:0.85rem;padding:0.35rem 1rem;
                @if($accountOrder->status === 'success') background:rgba(16,185,129,0.12);color:var(--success)
                @elseif($accountOrder->status === 'pending') background:rgba(245,158,11,0.12);color:var(--warning)
                @elseif($accountOrder->status === 'processing') background:rgba(59,130,246,0.12);color:var(--info)
                @else background:rgba(239,68,68,0.12);color:var(--error) @endif">
                <i class="fas fa-circle" style="font-size:0.4rem;margin-right:0.35rem;vertical-align:middle"></i>
                {{ ucfirst($accountOrder->status) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Pemesan</p>
                <div class="flex items-center gap-2">
                    <x-user-avatar :user="$accountOrder->user" :name="$accountOrder->customer_name" :size="38" radius="9px" />
                    <div>
                        <p class="font-semibold">{{ $accountOrder->customer_name }}</p>
                        <p style="font-size:0.85rem;color:var(--text-muted)">{{ $accountOrder->customer_email }}</p>
                        <p style="font-size:0.85rem;color:var(--text-muted)">{{ $accountOrder->customer_phone }}</p>
                    </div>
                </div>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Tanggal</p>
                <p class="font-semibold">{{ $accountOrder->created_at->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Akun</p>
                <p class="font-semibold">{{ $accountOrder->listing->product_name ?? '-' }}</p>
                <p style="font-size:0.85rem;color:var(--text-muted)">{{ $accountOrder->listing->game ?? '' }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Pembayaran</p>
                <p class="font-semibold">{{ $paymentLabels[strtolower((string) $accountOrder->payment_method)] ?? $accountOrder->payment_method ?: 'QRIS' }}</p>
                <p style="font-size:0.85rem;color:var(--text-muted)">{{ $accountOrder->user ? 'Dipesan oleh ' . $accountOrder->user->name : '' }}</p>
            </div>
        </div>

        @if($accountOrder->notes)
        <div style="margin-bottom:1rem">
            <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Catatan</p>
            <p style="font-size:0.85rem;color:var(--text-muted)">{{ $accountOrder->notes }}</p>
        </div>
        @endif

        <div style="border-top:1px solid var(--glass-border);padding-top:1rem;display:flex;justify-content:space-between;align-items:center">
            <p style="color:var(--text-muted)">Total Pembayaran</p>
            <p style="font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,var(--accent),#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Rp {{ number_format((float) $accountOrder->total_price, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="card-glass p-6 mb-6">
        <h3 class="font-semibold mb-4">
            <i class="fas fa-credit-card" style="color:var(--accent);margin-right:0.5rem"></i>
            Informasi Gateway Pembayaran
        </h3>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Metode</p>
                <p class="font-semibold">{{ $accountOrder->payment_method ?: '-' }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Tipe Gateway</p>
                <p class="font-semibold">{{ $accountOrder->gateway_type ?: ($accountOrder->qr_string ? 'qris' : '-') }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Gateway Invoice ID</p>
                <p class="font-semibold" style="font-family:monospace;word-break:break-all">{{ $accountOrder->gateway_invoice_id ?: '-' }}</p>
            </div>
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">No. VA / Kode Pembayaran</p>
                <p class="font-semibold" style="font-family:monospace">{{ $accountOrder->va_number ?: ($accountOrder->payment_code ?: '-') }}</p>
            </div>
            @if($accountOrder->qr_string)
            <div style="grid-column:span 2">
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">QR String (QRIS Dinamis)</p>
                <p style="font-size:0.8rem;color:var(--text-muted);font-family:monospace;word-break:break-all">{{ $accountOrder->qr_string }}</p>
            </div>
            @endif
            @if($accountOrder->gateway_invoice_url)
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Invoice URL</p>
                <a href="{{ $accountOrder->gateway_invoice_url }}" target="_blank" rel="noopener" style="font-size:0.85rem">{{ $accountOrder->gateway_invoice_url }}</a>
            </div>
            @endif
            @if($accountOrder->checkout_url)
            <div>
                <p style="font-size:0.78rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.25rem">Checkout URL (E-wallet)</p>
                <a href="{{ $accountOrder->checkout_url }}" target="_blank" rel="noopener" style="font-size:0.85rem">{{ $accountOrder->checkout_url }}</a>
            </div>
            @endif
        </div>
    </div>

    <div class="card-glass p-6">
        <h3 class="font-semibold mb-4">
            <i class="fas fa-arrow-rotate" style="color:var(--accent);margin-right:0.5rem"></i>
            Konfirmasi & Update Status
        </h3>
        <form action="{{ route('admin.account-orders.status', $accountOrder) }}" method="POST" class="flex items-center gap-4">
            @csrf
            @method('PATCH')
            <select name="status" class="input-field" style="max-width:200px">
                <option value="pending" {{ $accountOrder->status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ $accountOrder->status === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="success" {{ $accountOrder->status === 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed" {{ $accountOrder->status === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="cancelled" {{ $accountOrder->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
        <p style="font-size:0.72rem;color:var(--text-muted);margin-top:0.75rem">
            <i class="fas fa-info-circle mr-1"></i>
            Set <strong>Success</strong> setelah konfirmasi pembayaran diterima & akun dikirim. Akun otomatis ditandai terjual. Set <strong>Failed/Cancelled</strong> untuk membatalkan.
        </p>
    </div>
</div>
@endsection
