@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card" style="--accent-color: #0987F5">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent-color),#8b5cf6);border-radius:16px 16px 0 0"></div>
        <div class="flex items-center justify-between">
            <div>
                <p style="color:var(--text-muted);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Total Produk</p>
                <p style="font-size:1.75rem;font-weight:800;margin-top:0.25rem" id="stat1">{{ $stats['total_products'] }}</p>
            </div>
            <div class="stat-icon" style="background:rgba(9,135,245,0.12);color:#0987F5">
                <i class="fas fa-box"></i>
            </div>
        </div>
        <p style="color:var(--success);font-size:0.78rem;margin-top:0.5rem">
            <i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:0.3rem"></i>
            {{ $stats['active_products'] }} aktif
        </p>
    </div>

    <div class="stat-card" style="--accent-color: #3b82f6">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent-color),#06b6d4);border-radius:16px 16px 0 0"></div>
        <div class="flex items-center justify-between">
            <div>
                <p style="color:var(--text-muted);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Total Pesanan</p>
                <p style="font-size:1.75rem;font-weight:800;margin-top:0.25rem" id="stat2">{{ $stats['total_orders'] }}</p>
            </div>
            <div class="stat-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="flex items-center gap-3" style="margin-top:0.5rem">
            <p style="color:var(--warning);font-size:0.78rem">
                <i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:0.3rem"></i>
                {{ $stats['pending_orders'] }} pending
            </p>
            <div style="flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden">
                <div style="height:100%;width:{{ $stats['total_orders'] > 0 ? ($stats['pending_orders']/$stats['total_orders']*100) : 0 }}%;background:var(--warning);border-radius:2px;transition:width 0.6s ease"></div>
            </div>
        </div>
    </div>

    <div class="stat-card" style="--accent-color: #10b981">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent-color),#34d399);border-radius:16px 16px 0 0"></div>
        <div class="flex items-center justify-between">
            <div>
                <p style="color:var(--text-muted);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Pendapatan</p>
                <p style="font-size:1.75rem;font-weight:800;margin-top:0.25rem;color:var(--success)" id="stat3">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
            </div>
            <div class="stat-icon" style="background:rgba(16,185,129,0.12);color:#10b981">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
        <p style="color:var(--success);font-size:0.78rem;margin-top:0.5rem">
            <i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:0.3rem"></i>
            {{ $stats['success_orders'] }} transaksi sukses
        </p>
    </div>

    <div class="stat-card" style="--accent-color: #f59e0b">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent-color),#f97316);border-radius:16px 16px 0 0"></div>
        <div class="flex items-center justify-between">
            <div>
                <p style="color:var(--text-muted);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Total Pengguna</p>
                <p style="font-size:1.75rem;font-weight:800;margin-top:0.25rem" id="stat4">{{ $stats['total_users'] }}</p>
            </div>
            <div class="stat-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <div class="stat-card" style="--accent-color: #10b981">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent-color),#34d399);border-radius:16px 16px 0 0"></div>
        <div class="flex items-center justify-between">
            <div>
                <p style="color:var(--text-muted);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Digiflazz</p>
                <p style="font-size:1.2rem;font-weight:800;margin-top:0.25rem">
                    @if($stats['digiflazz_configured'])
                        <span style="color:var(--success)">
                            <i class="fas fa-check-circle" style="margin-right:0.3rem"></i> Terhubung
                        </span>
                    @else
                        <span style="color:var(--error)">
                            <i class="fas fa-times-circle" style="margin-right:0.3rem"></i> Belum config
                        </span>
                    @endif
                </p>
            </div>
            <div class="stat-icon" style="background:rgba(16,185,129,0.12);color:#10b981">
                <i class="fas fa-database"></i>
            </div>
        </div>
        @if($stats['digiflazz_configured'])
            <p style="color:var(--text-muted);font-size:0.78rem;margin-top:0.5rem">
                {{ $stats['digiflazz_product_count'] }} produk
                @if($stats['digiflazz_last_sync'])
                    &middot; sinkron {{ \Carbon\Carbon::parse($stats['digiflazz_last_sync'])->diffForHumans() }}
                @endif
            </p>
        @endif
    </div>

    <div class="stat-card" style="--accent-color: #8b5cf6">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent-color),#a78bfa);border-radius:16px 16px 0 0"></div>
        <div class="flex items-center justify-between">
            <div>
                <p style="color:var(--text-muted);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Pesanan Akun</p>
                <p style="font-size:1.75rem;font-weight:800;margin-top:0.25rem">{{ $stats['account_total'] }}</p>
            </div>
            <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>
        <div class="flex items-center gap-3" style="margin-top:0.5rem">
            <p style="color:var(--warning);font-size:0.78rem">
                <i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:0.3rem"></i>
                {{ $stats['account_pending'] }} menunggu konfirmasi
            </p>
            <div style="flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden">
                <div style="height:100%;width:{{ $stats['account_total'] > 0 ? ($stats['account_pending']/$stats['account_total']*100) : 0 }}%;background:var(--warning);border-radius:2px;transition:width 0.6s ease"></div>
            </div>
        </div>
        <p style="color:var(--text-muted);font-size:0.78rem;margin-top:0.5rem">
            {{ $stats['account_success'] }} sukses
            @if($stats['account_revenue'] > 0)
                &middot; Rp {{ number_format($stats['account_revenue'], 0, ',', '.') }}
            @endif
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2">
        <div class="card-glass">
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
                <h2 class="font-semibold" style="font-size:0.95rem">
                    <i class="fas fa-clock" style="color:var(--accent);margin-right:0.5rem"></i>
                    Pesanan Terbaru
                </h2>
                <a href="{{ route('admin.orders') }}" class="btn btn-ghost btn-xs">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:left">Order ID</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:left">Pelanggan</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:left">Produk</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:center">Status</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                        <tr style="border-bottom:1px solid var(--glass-border);transition:background 0.15s" onmouseover="this.style.background='rgba(9,135,245,0.03)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:0.7rem 1rem;font-size:0.82rem;font-family:monospace">{{ $order->order_id }}</td>
                            <td style="padding:0.7rem 1rem;font-size:0.85rem">
                                <div class="flex items-center gap-2">
                                    <div style="width:26px;height:26px;border-radius:6px;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700;color:#fff;flex-shrink:0">
                                        {{ substr($order->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <span>{{ $order->user->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td style="padding:0.7rem 1rem;font-size:0.82rem;color:var(--text-muted)">{{ $order->product_name }}</td>
                            <td style="padding:0.7rem 1rem;text-align:center">
                                <span class="badge
                                    @if($order->status === 'success') badge-success
                                    @elseif($order->status === 'pending') badge-warning badge-pulse
                                    @elseif($order->status === 'processing') badge-info
                                    @else badge-error @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td style="padding:0.7rem 1rem;text-align:right;font-weight:700;color:var(--accent);font-size:0.85rem">Rp {{ number_format($order->price, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="empty-state" style="padding:2rem"><i class="fas fa-shopping-cart"></i><p>Belum ada pesanan</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-glass" style="margin-top:1rem">
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
                <h2 class="font-semibold" style="font-size:0.95rem">
                    <i class="fas fa-file-invoice-dollar" style="color:#8b5cf6;margin-right:0.5rem"></i>
                    Pesanan Akun Terbaru (QRIS)
                    @if($stats['account_pending'] > 0)
                    <span class="badge badge-warning badge-pulse" style="margin-left:0.5rem;font-size:0.68rem">{{ $stats['account_pending'] }} menunggu konfirmasi</span>
                    @endif
                </h2>
                <a href="{{ route('admin.account-orders') }}" class="btn btn-ghost btn-xs">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:left">No. Pesanan</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:left">Pemesan</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:left">Akun</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:center">Status</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:right">Total</th>
                            <th style="padding:0.7rem 1rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-dim);font-weight:600;text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAccountOrders as $aorder)
                        <tr style="border-bottom:1px solid var(--glass-border);transition:background 0.15s" onmouseover="this.style.background='rgba(9,135,245,0.03)'" onmouseout="this.style.background='transparent'">
                            <td style="padding:0.7rem 1rem;font-size:0.82rem;font-family:monospace">{{ $aorder->order_ref }}</td>
                            <td style="padding:0.7rem 1rem;font-size:0.85rem">
                                <div class="flex items-center gap-2">
                                    <div style="width:26px;height:26px;border-radius:6px;background:linear-gradient(135deg,#8b5cf6,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700;color:#fff;flex-shrink:0">
                                        {{ substr($aorder->customer_name ?: ($aorder->user->name ?? '?'), 0, 1) }}
                                    </div>
                                    <span>{{ $aorder->customer_name ?: ($aorder->user->name ?? 'N/A') }}</span>
                                </div>
                            </td>
                            <td style="padding:0.7rem 1rem;font-size:0.82rem;color:var(--text-muted)">{{ $aorder->listing->product_name ?? '-' }}</td>
                            <td style="padding:0.7rem 1rem;text-align:center">
                                <span class="badge acc-badge-{{ $aorder->id }}
                                    @if($aorder->status === 'success') badge-success
                                    @elseif($aorder->status === 'pending') badge-warning badge-pulse
                                    @elseif($aorder->status === 'processing') badge-info
                                    @else badge-error @endif">
                                    {{ ucfirst($aorder->status) }}
                                </span>
                            </td>
                            <td style="padding:0.7rem 1rem;text-align:right;font-weight:700;color:#8b5cf6;font-size:0.85rem">Rp {{ number_format((float) $aorder->total_price, 0, ',', '.') }}</td>
                            <td style="padding:0.7rem 1rem;text-align:center">
                                @if($aorder->status === 'pending')
                                <button type="button" class="btn btn-success btn-xs acc-confirm-btn" data-id="{{ $aorder->id }}" onclick="confirmAccountPayment(this)">
                                    <i class="fas fa-check"></i> Konfirmasi
                                </button>
                                @else
                                <a href="{{ route('admin.account-orders.show', $aorder) }}" class="btn btn-ghost btn-xs">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="empty-state" style="padding:2rem"><i class="fas fa-store"></i><p>Belum ada pesanan akun</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="card-glass" style="margin-bottom:1rem">
            <div class="px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
                <h2 class="font-semibold" style="font-size:0.95rem">
                    <i class="fas fa-bolt" style="color:var(--accent);margin-right:0.5rem"></i>
                    Aksi Cepat
                </h2>
            </div>
            <div style="padding:0.75rem;display:flex;flex-direction:column;gap:0.5rem">
                <a href="{{ route('admin.orders') }}" class="btn btn-ghost" style="justify-content:flex-start;padding:0.65rem 0.9rem;gap:0.6rem">
                    <i class="fas fa-eye" style="color:var(--warning)"></i>
                    <span>Lihat Pesanan Baru</span>
                </a>
                <a href="{{ route('admin.account-orders') }}" class="btn btn-ghost" style="justify-content:flex-start;padding:0.65rem 0.9rem;gap:0.6rem">
                    <i class="fas fa-file-invoice-dollar" style="color:#8b5cf6"></i>
                    <span>Konfirmasi Pesanan Akun</span>
                    @if($stats['account_pending'] > 0)
                    <span class="badge badge-warning" style="margin-left:auto;padding:0.1rem 0.5rem;font-size:0.68rem">{{ $stats['account_pending'] }}</span>
                    @endif
                </a>
                <form action="{{ route('admin.products.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-ghost" style="justify-content:flex-start;padding:0.65rem 0.9rem;gap:0.6rem;width:100%">
                        <i class="fas fa-sync" style="color:var(--success)"></i>
                        <span>Sinkronisasi Digiflazz</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="card-glass">
            <div class="px-5 py-4" style="border-bottom:1px solid var(--glass-border)">
                <h2 class="font-semibold" style="font-size:0.95rem">
                    <i class="fas fa-chart-simple" style="color:var(--accent);margin-right:0.5rem"></i>
                    Ringkasan
                </h2>
            </div>
            <div style="padding:1rem 1.25rem;display:flex;flex-direction:column;gap:0.9rem">
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span style="font-size:0.82rem;color:var(--text-muted)">Produk Aktif</span>
                        <span style="font-size:0.82rem;font-weight:700">{{ $stats['active_products'] }}/{{ $stats['total_products'] }}</span>
                    </div>
                    <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:{{ $stats['total_products'] > 0 ? ($stats['active_products']/$stats['total_products']*100) : 0 }}%;background:linear-gradient(90deg,var(--success),#34d399);border-radius:3px;transition:width 0.6s ease"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span style="font-size:0.82rem;color:var(--text-muted)">Sukses / Total Pesanan</span>
                        <span style="font-size:0.82rem;font-weight:700">{{ $stats['success_orders'] }}/{{ $stats['total_orders'] }}</span>
                    </div>
                    <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:{{ $stats['total_orders'] > 0 ? ($stats['success_orders']/$stats['total_orders']*100) : 0 }}%;background:linear-gradient(90deg,var(--accent),#8b5cf6);border-radius:3px;transition:width 0.6s ease"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function confirmAccountPayment(btn) {
    const id = btn.dataset.id;
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const res = await fetch('{{ route('admin.account-orders.status', '__ID__') }}'.replace('__ID__', id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: new URLSearchParams({ _method: 'PATCH', status: 'success' })
        });
        const data = await res.json();
        if (res.ok) {
            const badge = document.querySelector('.acc-badge-' + id);
            if (badge) {
                badge.className = 'badge acc-badge-' + id + ' badge-success';
                badge.textContent = 'Success';
            }
            btn.outerHTML = '<a href="{{ route('admin.account-orders') }}" class="btn btn-ghost btn-xs"><i class="fas fa-gem"></i> SUKSES</a>';
            setTimeout(() => location.reload(), 700);
        } else {
            btn.disabled = false;
            btn.innerHTML = original;
            alert(data.message || 'Gagal konfirmasi.');
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = original;
        alert('Terjadi kesalahan. Coba lagi.');
    }
}
</script>
@endpush
@endsection
