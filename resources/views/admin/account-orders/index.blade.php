@extends('admin.layouts.app')

@push('styles')
<style>
.ord-modal{position:fixed;inset:0;z-index:50;display:none;align-items:center;justify-content:center}
.ord-overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}
.ord-box{position:relative;background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:640px;margin:0 1rem;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,.5)}
.ord-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--glass-border);position:sticky;top:0;background:var(--bg-card);z-index:1;border-radius:20px 20px 0 0}
.ord-title{font-size:1.05rem;font-weight:700}
.ord-close{background:none;border:none;color:var(--text-dim);font-size:1.5rem;cursor:pointer;line-height:1;padding:.25rem;transition:color .2s}
.ord-close:hover{color:var(--text)}
.ord-body{padding:1.5rem}
.ord-section{background:var(--bg-card);border:1px solid var(--glass-border);border-radius:14px;padding:1.25rem;margin-bottom:1rem}
.ord-section:last-child{margin-bottom:0}
.ord-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem 1.5rem}
.ord-label{font-size:.73rem;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.2rem}
.ord-value{font-size:.88rem;font-weight:600}
.ord-total{font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,var(--accent),#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.ord-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem 1rem;border-radius:999px;font-size:.82rem;font-weight:600}
.ord-loading{text-align:center;padding:3rem;color:var(--text-dim)}
</style>
@endpush

@section('title', 'Pesanan Akun')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <h2 class="text-lg font-semibold">Pesanan Jual Beli Akun</h2>
    <span class="badge badge-neutral">{{ $orders->total() }} total</span>
</div>

<form method="GET" action="{{ route('admin.account-orders') }}" class="admin-filter-bar" style="max-width:640px">
    <div class="search-wrap flex-1">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" value="{{ request('search') }}" class="input-field" placeholder="Cari no. pesanan, nama, akun..." style="padding-left:2.4rem">
    </div>
    <select name="status" class="input-field" style="max-width:200px" onchange="this.form.submit()">
        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Status</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
    </select>
</form>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Akun</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Tanggal</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td style="font-size:0.82rem;font-family:monospace">{{ $order->order_ref }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700;color:#fff;flex-shrink:0">
                                {{ substr($order->customer_name ?: ($order->user->name ?? '?'), 0, 1) }}
                            </div>
                            <span>{{ $order->customer_name ?: ($order->user->name ?? 'N/A') }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;font-weight:600">{{ $order->listing->product_name ?? '-' }}</div>
                        <div style="font-size:0.72rem;color:var(--text-muted)">{{ $order->listing->game ?? '' }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $order->status === 'pending' ? 'badge-warning badge-pulse' : '' }}"
                            style="@if($order->status === 'success') background:rgba(16,185,129,0.12);color:var(--success)
                                @elseif($order->status === 'pending') background:rgba(245,158,11,0.12);color:var(--warning)
                                @elseif($order->status === 'processing') background:rgba(59,130,246,0.12);color:var(--info)
                                @else background:rgba(239,68,68,0.12);color:var(--error) @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="text-right font-semibold" style="color:var(--accent);font-size:0.88rem">Rp {{ number_format((float) $order->total_price, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-size:0.8rem;color:var(--text-muted)">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-primary btn-xs"
                            data-order='{{ json_encode($order->load('user', 'listing')->toArray()) }}'
                            onclick="openOrderModal(this)">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-gamepad"></i>
                            <p>Belum ada pesanan akun</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="pagination-wrap">{{ $orders->links('vendor.pagination.admin') }}</div>

<!-- ===== MODAL DETAIL PESANAN ===== -->
<div class="ord-modal" id="orderModal">
    <div class="ord-overlay" onclick="closeOrderModal()"></div>
    <div class="ord-box">
        <div class="ord-header">
            <span class="ord-title">Detail Pesanan Akun</span>
            <button type="button" class="ord-close" onclick="closeOrderModal()">&times;</button>
        </div>
        <div class="ord-body" id="orderModalBody">
            <div class="ord-loading">Memuat...</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const METHOD_LABELS = {
    qris: 'QRIS', gopay: 'GoPay', dana: 'DANA', ovo: 'OVO', shopeepay: 'ShopeePay', linkaja: 'LinkAja',
    bca_va: 'BCA Virtual Account', bni_va: 'BNI Virtual Account', bri_va: 'BRI Virtual Account',
    mandiri_va: 'Mandiri Virtual Account', permata_va: 'Permata Virtual Account',
    alfamart: 'Alfamart', indomaret: 'Indomaret',
};
function methodLabel(code) {
    return METHOD_LABELS[code] || code || 'QRIS';
}

function openOrderModal(btn) {
    const d = JSON.parse(btn.dataset.order);
    const body = document.getElementById('orderModalBody');

    const statusColors = {
        success: { bg: 'rgba(16,185,129,0.12)', color: 'var(--success)' },
        pending: { bg: 'rgba(245,158,11,0.12)', color: 'var(--warning)' },
        processing: { bg: 'rgba(59,130,246,0.12)', color: 'var(--info)' },
        failed: { bg: 'rgba(239,68,68,0.12)', color: 'var(--error)' },
        cancelled: { bg: 'rgba(239,68,68,0.12)', color: 'var(--error)' },
    };
    const sc = statusColors[d.status] || { bg: 'rgba(134,142,161,0.12)', color: 'var(--text-dim)' };

    body.innerHTML = `
        <div class="ord-section">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem">
                <div>
                    <div style="font-size:.82rem;font-weight:700">Pesanan Akun</div>
                    <div style="font-size:.78rem;font-family:monospace;color:var(--text-dim);margin-top:.15rem">${d.order_ref}</div>
                </div>
                <span class="ord-badge" style="background:${sc.bg};color:${sc.color}">
                    <i class="fas fa-circle" style="font-size:.35rem"></i> ${d.status.charAt(0).toUpperCase() + d.status.slice(1)}
                </span>
            </div>
            <div class="ord-grid">
                <div>
                    <div class="ord-label">Pemesan</div>
                    <div class="ord-value">${d.customer_name || 'N/A'}</div>
                    <div style="font-size:.82rem;color:var(--text-dim)">${d.customer_email || ''}</div>
                    <div style="font-size:.82rem;color:var(--text-dim)">${d.customer_phone || ''}</div>
                </div>
                <div>
                    <div class="ord-label">Akun</div>
                    <div class="ord-value">${d.listing?.product_name || '-'}</div>
                    <div style="font-size:.82rem;color:var(--text-dim)">${d.listing?.game || ''}</div>
                </div>
                <div>
                    <div class="ord-label">Tanggal</div>
                    <div class="ord-value">${new Date(d.created_at).toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})}</div>
                </div>
                <div>
                    <div class="ord-label">Pembayaran</div>
                    <div class="ord-value">${methodLabel(d.payment_method)}</div>
                    <div style="font-size:.82rem;color:var(--text-dim)">${d.user ? 'via ' + (d.user.name || '') : ''}</div>
                </div>
            </div>
            ${d.notes ? `<div style="margin-top:1rem"><div class="ord-label">Catatan</div><div style="font-size:.83rem;color:var(--text-muted)">${d.notes}</div></div>` : ''}
            <div style="border-top:1px solid var(--glass-border);padding-top:1rem;margin-top:1rem;display:flex;justify-content:space-between;align-items:center">
                <div style="font-size:.82rem;color:var(--text-dim)">Total Pembayaran</div>
                <div class="ord-total">Rp ${Number(d.total_price).toLocaleString('id-ID')}</div>
            </div>
        </div>

        <div class="ord-section">
            <h3 style="font-size:.82rem;font-weight:700;margin-bottom:.75rem">
                <i class="fas fa-arrow-rotate" style="color:var(--accent);margin-right:.4rem"></i>Konfirmasi & Update Status
            </h3>
            <div class="flex items-center gap-3">
                <select id="statusSelect" class="input-field" style="max-width:200px">
                    <option value="pending" ${d.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="processing" ${d.status === 'processing' ? 'selected' : ''}>Processing</option>
                    <option value="success" ${d.status === 'success' ? 'selected' : ''}>Success</option>
                    <option value="failed" ${d.status === 'failed' ? 'selected' : ''}>Failed</option>
                    <option value="cancelled" ${d.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
                <button type="button" class="btn btn-primary" id="updateStatusBtn" onclick="updateStatus(${d.id})">Update</button>
            </div>
            <p class="text-red-400 text-xs mt-2 hidden" id="err_status"></p>
            <p class="text-green-400 text-xs mt-2 hidden" id="ok_status"></p>
        </div>
    `;

    document.getElementById('orderModal').style.display = 'flex';
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}

async function updateStatus(orderId) {
    const select = document.getElementById('statusSelect');
    const status = select.value;
    const btn = document.getElementById('updateStatusBtn');
    const errEl = document.getElementById('err_status');
    const okEl = document.getElementById('ok_status');
    errEl.classList.add('hidden');
    okEl.classList.add('hidden');

    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await fetch('{{ route('admin.account-orders.status', '__ID__') }}'.replace('__ID__', orderId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: new URLSearchParams({ _method: 'PATCH', status: status })
        });
        const data = await res.json();
        if (res.ok) {
            okEl.textContent = data.message;
            okEl.classList.remove('hidden');
            setTimeout(() => { okEl.classList.add('hidden'); }, 3000);

            // Update modal badge
            const modalBadge = document.querySelector('#orderModalBody .ord-badge');
            if (modalBadge) {
                const badgeColors = {
                    success: { bg: 'rgba(16,185,129,0.12)', color: 'var(--success)' },
                    pending: { bg: 'rgba(245,158,11,0.12)', color: 'var(--warning)' },
                    processing: { bg: 'rgba(59,130,246,0.12)', color: 'var(--info)' },
                    failed: { bg: 'rgba(239,68,68,0.12)', color: 'var(--error)' },
                    cancelled: { bg: 'rgba(239,68,68,0.12)', color: 'var(--error)' },
                };
                const c = badgeColors[status] || { bg: 'rgba(134,142,161,0.12)', color: 'var(--text-dim)' };
                modalBadge.style.background = c.bg;
                modalBadge.style.color = c.color;
                modalBadge.innerHTML = '<i class="fas fa-circle" style="font-size:.35rem"></i> ' + (status.charAt(0).toUpperCase() + status.slice(1));
            }

            // Update row (badge + pill) via reload setelah delay singkat
            setTimeout(() => location.reload(), 600);
        } else {
            errEl.textContent = data.message || 'Gagal update status.';
            errEl.classList.remove('hidden');
        }
    } catch (err) {
        errEl.textContent = 'Terjadi kesalahan.';
        errEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Update';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const m = document.getElementById('orderModal');
            if (m.style.display === 'flex') closeOrderModal();
        }
    });
});
</script>
@endpush
@endsection
