@extends('admin.layouts.app')
@section('title', 'Voucher')
@section('content')

@php
    $editableVouchers = $vouchers->map(fn ($item) => [
        'id' => $item->id,
        'code' => $item->code,
        'label' => $item->label,
        'discount_type' => $item->discount_type,
        'discount_value' => (float) $item->discount_value,
        'min_spend' => (int) $item->min_spend,
        'quota' => (int) $item->quota,
        'user_id' => $item->user_id,
    ])->values();
@endphp

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Voucher</h2>
        <span class="badge badge-neutral">{{ $vouchers->total() }} total</span>
    </div>
    <button type="button" class="btn btn-primary" onclick="openVoucherModal()">
        <i class="fas fa-ticket"></i><span>Terbitkan Voucher</span>
    </button>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-5">
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Total Terbit</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem">{{ $stats['total'] }}</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Masih Aktif</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem;color:#22c55e">{{ $stats['active'] }}</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Sudah Dipakai</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem;color:#9ca3af">{{ $stats['used'] }}</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Sudah Diklaim</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem;color:var(--accent)">{{ $stats['claimed'] }}</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Milik Tamu</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem;color:#f59e0b">{{ $stats['guest'] }}</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Dari Gacha</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem">{{ $stats['from_gacha'] }}</div>
    </div>
</div>

<div class="card-glass p-4 mb-5" style="display:flex;align-items:flex-start;gap:.75rem">
    <i class="fas fa-chart-line" style="color:var(--accent);margin-top:0.2rem"></i>
    <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6">
        Putaran gacha <b id="spinToday">{{ $spinsToday }}</b> kali hari ini,
        <b id="spinWeek">{{ $spinsWeek }}</b> kali dalam 7 hari terakhir.
        Voucher milik tamu bisa diklaim pemiliknya lewat halaman <b>Voucher Saya</b>.
    </div>
    <a href="{{ route('admin.gacha-prizes') }}" class="btn btn-ghost btn-xs" style="flex-shrink:0">
        <i class="fas fa-dice"></i> Atur Hadiah
    </a>
</div>

<form method="GET" class="admin-filter-bar">
    <input type="text" name="search" value="{{ request('search') }}" class="input-field" style="flex:1;min-width:200px" placeholder="Cari kode voucher atau nama user">
    <select name="status" class="input-field" style="width:auto;min-width:150px;padding:0.35rem 0.75rem;font-size:0.82rem">
        <option value="">Semua Status</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Masih Aktif</option>
        <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Sudah Dipakai</option>
        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
        <option value="guest" {{ request('status') === 'guest' ? 'selected' : '' }}>Milik Tamu (belum diklaim)</option>
    </select>
    <button type="submit" class="btn btn-primary btn-xs">Filter</button>
</form>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Label / Hadiah</th>
                    <th class="text-center">Nilai</th>
                    <th class="text-center">Min. Belanja</th>
                    <th class="text-center">Pakai</th>
                    <th>Pemilik</th>
                    <th class="text-center">Sumber</th>
                    <th class="text-center">Kedaluwarsa</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $voucher)
                <tr>
                    <td>
                        <code style="font-family:ui-monospace,Menlo,monospace;font-size:.82rem;font-weight:700;letter-spacing:.5px">{{ $voucher->code }}</code>
                    </td>
                    <td>
                        <div style="font-size:.86rem">{{ $voucher->label ?: 'Voucher Diskon' }}</div>
                        @if($voucher->prize)
                            <div style="font-size:.72rem;color:var(--text-muted)">Hadiah: {{ $voucher->prize->label }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $voucher->value_label }}</span>
                    </td>
                    <td class="text-center" style="font-size:.8rem;color:var(--text-muted)">
                        {{ $voucher->min_spend > 0 ? 'Rp '.number_format($voucher->min_spend, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-center" style="font-size:.8rem">{{ $voucher->used_count }}{{ $voucher->quota ? '/'.$voucher->quota : '' }}</td>
                    <td style="font-size:.8rem">
                        @if($voucher->user)
                            <div>{{ $voucher->user->name }}</div>
                            <div style="font-size:.72rem;color:var(--text-muted)">{{ $voucher->user->email }}</div>
                        @else
                            <span class="badge badge-warning">Tamu</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $voucher->source === 'gacha' ? 'badge-success' : 'badge-neutral' }}">{{ $voucher->source }}</span>
                    </td>
                    <td class="text-center" style="font-size:.78rem;color:var(--text-muted)">
                        {{ $voucher->expires_at ? $voucher->expires_at->format('d/m/Y') : '-' }}
                    </td>
                    <td class="text-center">
                        @if($voucher->is_exhausted)
                            <span class="badge badge-neutral">Terpakai</span>
                        @elseif($voucher->is_expired)
                            <span class="badge badge-error">Kedaluwarsa</span>
                        @else
                            <span class="badge badge-success">Aktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" class="btn btn-ghost btn-xs" onclick='copyText(@js($voucher->code), this)' title="Salin kode">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick='editVoucher({{ $voucher->id }})' title="Ubah voucher">
                                <i class="fas fa-pen"></i>
                            </button>
                            @if($voucher->is_expired)
                                <form action="{{ route('admin.vouchers.renew', $voucher) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-ghost btn-xs" title="Perpanjang 30 hari">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                </form>
                            @endif
                            <button type="button" class="btn btn-danger btn-xs" onclick='confirmDelete(@js(route('admin.vouchers.destroy', $voucher)), @js('Hapus voucher '.$voucher->code.'?'))'>
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="empty-state">
                            <i class="fas fa-ticket"></i>
                            <p>Belum ada voucher. Terbitkan manual atau tunggu hasil undian gacha.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $vouchers->links('vendor.pagination.admin') }}</div>

<!-- MODAL TERBITKAN VOUCHER -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="voucherModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeVoucherModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:560px;margin:0 1rem;max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold" id="voucherModalTitle">Terbitkan Voucher</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeVoucherModal()">&times;</button>
        </div>
        <form id="voucherForm" class="p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Kode Voucher</label>
                    <input type="text" name="code" id="v_code" maxlength="32" class="input-field" placeholder="Kosong = dibuat otomatis">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_code"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Label</label>
                    <input type="text" name="label" id="v_label" maxlength="120" class="input-field" placeholder="cth: Giveaway Instagram">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_label"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Tipe Diskon</label>
                    <select name="discount_type" id="v_discount_type" class="input-field">
                        <option value="percent">Persen (%)</option>
                        <option value="fixed">Nominal (Rp)</option>
                    </select>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_discount_type"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Nilai Diskon</label>
                    <input type="number" name="discount_value" id="v_discount_value" required min="1" step="0.01" class="input-field">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_discount_value"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Minimal Belanja (Rp)</label>
                    <input type="number" name="min_spend" id="v_min_spend" min="0" step="1000" class="input-field" placeholder="0">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_min_spend"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Jumlah Pakai</label>
                    <input type="number" name="quota" id="v_quota" min="1" max="10000" class="input-field" value="1">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_quota"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Masa Berlaku (hari)</label>
                    <input type="number" name="validity_days" id="v_validity_days" min="1" max="3650" class="input-field" value="30">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_validity_days"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">User (opsional)</label>
                    <input type="number" name="user_id" id="v_user_id" min="1" class="input-field" placeholder="ID user, kosongkan untuk tamu">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_user_id"></p>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-5">
                <button type="button" class="btn btn-ghost" onclick="closeVoucherModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="voucherSubmitBtn">Terbitkan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const VOUCHER_STORE_URL = @json(route('admin.vouchers.store'));
const VOUCHER_DATA = @json($editableVouchers);
let editingVoucherId = null;

function openVoucherModal() {
    editingVoucherId = null;
    clearVoucherErrors();
    document.getElementById('voucherForm').reset();
    document.getElementById('v_code').readOnly = false;
    document.getElementById('v_quota').value = 1;
    document.getElementById('v_validity_days').value = 30;
    document.getElementById('voucherModalTitle').textContent = 'Terbitkan Voucher';
    document.getElementById('voucherSubmitBtn').textContent = 'Terbitkan';
    document.getElementById('voucherModal').style.display = 'flex';
}

function editVoucher(id) {
    const voucher = VOUCHER_DATA.find(item => item.id === id);
    if (!voucher) return;

    clearVoucherErrors();
    document.getElementById('voucherForm').reset();
    editingVoucherId = voucher.id;
    document.getElementById('v_code').value = voucher.code;
    document.getElementById('v_label').value = voucher.label || '';
    document.getElementById('v_discount_type').value = voucher.discount_type;
    document.getElementById('v_discount_value').value = voucher.discount_value;
    document.getElementById('v_min_spend').value = voucher.min_spend;
    document.getElementById('v_quota').value = voucher.quota;
    document.getElementById('v_validity_days').value = 30;
    document.getElementById('v_user_id').value = voucher.user_id || '';
    document.getElementById('voucherModalTitle').textContent = 'Ubah Voucher ' + voucher.code;
    document.getElementById('voucherSubmitBtn').textContent = 'Simpan';
    document.getElementById('voucherModal').style.display = 'flex';
}

function closeVoucherModal() {
    document.getElementById('voucherModal').style.display = 'none';
}

function clearVoucherErrors() {
    document.querySelectorAll('#voucherForm [id^="err_"]').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

function copyText(value, btn) {
    const done = () => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = original; }, 1400);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(done).catch(() => {});
        return;
    }
    done();
}

document.getElementById('voucherForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('voucherSubmitBtn');
    const isEdit = editingVoucherId !== null;
    const originalLabel = isEdit ? 'Simpan' : 'Terbitkan';
    btn.disabled = true;
    btn.textContent = isEdit ? 'Menyimpan...' : 'Menerbitkan...';

    const formData = new FormData(this);

    try {
        const res = await fetch(isEdit ? VOUCHER_STORE_URL + '/' + editingVoucherId : VOUCHER_STORE_URL, {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        });
        const data = await res.json();
        if (res.ok) {
            closeVoucherModal();
            showModal('success', data.message || (isEdit ? 'Voucher berhasil diperbarui' : 'Voucher berhasil diterbitkan'));
            setTimeout(() => location.reload(), 900);
        } else {
            const errors = data.errors || {};
            clearVoucherErrors();
            for (const field in errors) {
                const el = document.getElementById('err_' + field.toLowerCase());
                if (el) { el.textContent = errors[field][0]; el.classList.remove('hidden'); }
            }
            btn.disabled = false;
            btn.textContent = originalLabel;
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan. Silakan coba lagi.');
        btn.disabled = false;
        btn.textContent = originalLabel;
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeVoucherModal();
});
</script>
@endpush
@endsection
