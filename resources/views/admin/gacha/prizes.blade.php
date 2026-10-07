@extends('admin.layouts.app')
@section('title', 'Gacha Voucher')
@section('content')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Gacha Voucher</h2>
        <span class="badge badge-neutral">{{ $prizes->total() }} sektor</span>
    </div>
    <button type="button" class="btn btn-primary" onclick="openPrizeModal()">
        <i class="fas fa-plus"></i><span>Tambah Hadiah</span>
    </button>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Status Gacha</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem" id="statEnabled">{{ $stats['enabled'] ? 'Aktif' : 'Nonaktif' }}</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Batas Putar / Hari</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem" id="statLimit">{{ $stats['daily_limit'] }}x</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Putaran Hari Ini</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem" id="statSpins">{{ $stats['spins_today'] }}</div>
    </div>
    <div class="card-glass p-4">
        <div style="font-size:.72rem;color:var(--text-muted)">Total Voucher Terbit</div>
        <div style="font-size:1.15rem;font-weight:700;margin-top:.2rem" id="statIssued">{{ $stats['vouchers_issued'] }}</div>
    </div>
</div>

<div class="card-glass p-4 mb-5" style="display:flex;align-items:flex-start;gap:.75rem">
    <i class="fas fa-sliders-h" style="color:var(--accent);margin-top:0.2rem"></i>
    <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6;flex:1">
        <b>Bobot (weight)</b> menentukan peluang sektor terpilih sekaligus besar sudut sektor di roda,
        jadi yang tampil di roda sama dengan peluang sebenarnya. <b>Kuota</b> kosong berarti tanpa batas;
        sektor habis otomatis hilang dari roda. <b>Masa berlaku</b> adalah masa berlaku kode voucher yang
        diterbitkan, dihitung sejak voucher dibuat. Voucher hasil gacha berkuota 1 (sekali pakai).
    </div>
    <button type="button" class="btn btn-ghost btn-xs" onclick="openSettingsModal()" title="Pengaturan gacha">
        <i class="fas fa-cog"></i>
    </button>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Hadiah</th>
                    <th class="text-center">Nilai</th>
                    <th class="text-center">Min. Belanja</th>
                    <th class="text-center">Bobot</th>
                    <th class="text-center">Peluang</th>
                    <th class="text-center">Kuota</th>
                    <th class="text-center">Terbit</th>
                    <th class="text-center">Berlaku</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $totalWeight = $totalWeight ?? max(1, $prizes->sum('weight')); @endphp
                @forelse($prizes as $prize)
                <tr>
                    <td class="text-center" style="color:var(--text-muted);font-size:.78rem">{{ $prize->sort_order + 1 }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span style="width:12px;height:12px;border-radius:3px;flex-shrink:0;background:{{ $prize->color }}"></span>
                            <span class="font-semibold" style="font-size:0.86rem">{{ $prize->label }}</span>
                        </div>
                        <div style="font-size:0.72rem;color:var(--text-muted)">
                            {{ $prize->discount_type === 'percent' ? 'Diskon persen' : 'Potongan nominal' }}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $prize->value_label }}</span>
                    </td>
                    <td class="text-center" style="font-size:0.82rem;color:var(--text-muted)">
                        {{ $prize->min_spend > 0 ? 'Rp '.number_format($prize->min_spend, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-center" style="font-size:0.82rem">{{ $prize->weight }}</td>
                    <td class="text-center">
                        <span class="badge badge-success">{{ round($prize->weight / $totalWeight * 100, 1) }}%</span>
                    </td>
                    <td class="text-center">
                        @if($prize->quota === null)
                            <span class="badge badge-neutral">Tak terbatas</span>
                        @else
                            <span class="badge {{ $prize->quota > 0 ? 'badge-info' : 'badge-error' }}">{{ $prize->quota }}</span>
                        @endif
                    </td>
                    <td class="text-center" style="font-size:0.82rem">{{ $prize->won_count }}</td>
                    <td class="text-center" style="font-size:0.8rem;color:var(--text-muted)">{{ $prize->validity_days }} hr</td>
                    <td class="text-center">
                        <span class="badge {{ $prize->is_active && $prize->hasQuota() ? 'badge-success' : 'badge-neutral' }}">
                            {{ $prize->is_active ? ($prize->hasQuota() ? 'Tayang' : 'Habis') : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" class="btn btn-ghost btn-xs"
                                data-prize='{{ json_encode(array_merge($prize->only(['id', 'label', 'discount_type', 'discount_value', 'min_spend', 'weight', 'quota', 'validity_days', 'color', 'sort_order', 'is_active']))) }}'
                                onclick="openPrizeModal(this)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.gacha-prizes.toggle', $prize) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-xs" title="{{ $prize->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="fas {{ $prize->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger btn-xs" onclick='confirmDelete(@js(route('admin.gacha-prizes.destroy', $prize)), @js('Hapus hadiah '.$prize->label.'?'))'>
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11">
                        <div class="empty-state">
                            <i class="fas fa-dice"></i>
                            <p>Belum ada hadiah gacha. Tambahkan sektor agar roda bisa diputar.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $prizes->links('vendor.pagination.admin') }}</div>

<!-- MODAL HADIAH -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="prizeModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closePrizeModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:620px;margin:0 1rem;max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold" id="prizeModalTitle">Tambah Hadiah</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closePrizeModal()">&times;</button>
        </div>
        <form id="prizeForm" class="p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" id="prizeFormMethod" name="_method" value="POST">
            <input type="hidden" id="prizeId" name="prize_id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1.5">Label Hadiah</label>
                    <input type="text" name="label" id="f_label" required maxlength="120" class="input-field" placeholder="cth: Diskon 10%">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_label"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Tipe Diskon</label>
                    <select name="discount_type" id="f_discount_type" class="input-field" onchange="updatePrizePreview()">
                        <option value="percent">Persen (%)</option>
                        <option value="fixed">Nominal (Rp)</option>
                    </select>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_discount_type"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Nilai Diskon</label>
                    <input type="number" name="discount_value" id="f_discount_value" required min="0" step="0.01" class="input-field" oninput="updatePrizePreview()">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_discount_value"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Minimal Belanja (Rp)</label>
                    <input type="number" name="min_spend" id="f_min_spend" min="0" step="1000" class="input-field" placeholder="0">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_min_spend"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Masa Berlaku Voucher (hari)</label>
                    <input type="number" name="validity_days" id="f_validity_days" required min="1" max="3650" class="input-field">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_validity_days"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Bobot Peluang</label>
                    <input type="number" name="weight" id="f_weight" required min="1" max="10000" class="input-field" oninput="updatePrizePreview()">
                    <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem">Semakin besar bobot, semakin besar sudut sektor di roda.</p>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_weight"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Kuota Hadiah</label>
                    <input type="number" name="quota" id="f_quota" min="0" step="1" class="input-field" placeholder="Kosong = tak terbatas">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_quota"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Urutan Sektor</label>
                    <input type="number" name="sort_order" id="f_sort_order" min="0" step="1" class="input-field" placeholder="0">
                    <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem">Menentukan urutan di roda.</p>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_sort_order"></p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1.5">Warna Sektor</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="color" id="f_color" value="#7c3aed" class="input-field" style="width:64px;height:42px;padding:.2rem;cursor:pointer">
                        <div style="font-size:0.74rem;color:var(--text-dim)">Dipakai untuk warna sektor roda.</div>
                    </div>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_color"></p>
                </div>
            </div>

            <div class="mt-4 card-glass p-3" style="border-radius:14px">
                <div style="font-size:0.78rem;color:var(--text-muted)">
                    Preview: <b id="preview_value" style="color:var(--accent)">-</b>
                    <span id="preview_weight" style="color:var(--text-dim)"></span>
                </div>
            </div>

            <div class="flex justify-between items-center mt-5">
                <label class="flex items-center gap-2" style="cursor:pointer">
                    <input type="checkbox" name="is_active" id="f_is_active" value="1" checked
                           style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer">
                    <span class="text-sm font-medium">Aktif</span>
                </label>
                <div class="flex gap-3">
                    <button type="button" class="btn btn-ghost" onclick="closePrizeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="prizeSubmitBtn">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PENGATURAN -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="settingsModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeSettingsModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:440px;margin:0 1rem;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold">Pengaturan Gacha</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeSettingsModal()">&times;</button>
        </div>
        <form id="settingsForm" class="p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" id="gachaSettingsCurrent" value="{{ $stats['enabled'] ? '1' : '0' }}">

            <div class="mb-4">
                <label class="flex items-center gap-2" style="cursor:pointer">
                    <input type="checkbox" name="enabled" id="s_enabled" value="1" {{ $stats['enabled'] ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer">
                    <span class="text-sm font-medium">Gacha Aktif</span>
                </label>
                <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.3rem">Jika nonaktif, widget gacha disembunyikan untuk pengunjung.</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">Batas Putar per Hari</label>
                <input type="number" name="daily_limit" id="s_daily_limit" min="0" max="50" class="input-field" value="{{ $stats['daily_limit'] }}">
                <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.3rem">Isi 0 untuk mematikan gacha sementara.</p>
            </div>

            <div class="flex justify-end gap-3 mt-5">
                <button type="button" class="btn btn-ghost" onclick="closeSettingsModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="settingsSubmitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let editPrizeId = null;

function updatePrizePreview() {
    const type = document.getElementById('f_discount_type').value;
    const value = parseFloat(document.getElementById('f_discount_value').value || 0);
    const weight = parseInt(document.getElementById('f_weight').value || 0, 10);

    let label = '-';
    if (type === 'percent') {
        label = (Math.round(value * 100) / 100) + '%';
    } else if (value > 0) {
        label = 'Rp ' + Math.round(value).toLocaleString('id-ID');
    }

    document.getElementById('preview_value').textContent = label;
    document.getElementById('preview_weight').textContent = weight > 0 ? ' (bobot ' + weight + ')' : '';
}

function openPrizeModal(btn) {
    clearPrizeErrors();
    editPrizeId = null;

    if (btn) {
        const p = JSON.parse(btn.dataset.prize);
        editPrizeId = p.id;
        document.getElementById('prizeModalTitle').textContent = 'Edit Hadiah';
        document.getElementById('prizeSubmitBtn').textContent = 'Simpan Perubahan';
        document.getElementById('prizeFormMethod').value = 'PUT';
        document.getElementById('prizeId').value = p.id;
        document.getElementById('f_label').value = p.label;
        document.getElementById('f_discount_type').value = p.discount_type;
        document.getElementById('f_discount_value').value = p.discount_value;
        document.getElementById('f_min_spend').value = p.min_spend || 0;
        document.getElementById('f_weight').value = p.weight;
        document.getElementById('f_quota').value = p.quota === null ? '' : p.quota;
        document.getElementById('f_validity_days').value = p.validity_days;
        document.getElementById('f_sort_order').value = p.sort_order;
        document.getElementById('f_color').value = p.color || '#7c3aed';
        document.getElementById('f_is_active').checked = !!p.is_active;
    } else {
        document.getElementById('prizeModalTitle').textContent = 'Tambah Hadiah';
        document.getElementById('prizeSubmitBtn').textContent = 'Simpan';
        document.getElementById('prizeFormMethod').value = 'POST';
        document.getElementById('prizeId').value = '';
        document.getElementById('prizeForm').reset();
        document.getElementById('f_color').value = '#7c3aed';
        document.getElementById('f_weight').value = 10;
        document.getElementById('f_validity_days').value = 30;
        document.getElementById('f_is_active').checked = true;
    }

    document.getElementById('prizeModal').style.display = 'flex';
    updatePrizePreview();
}

function closePrizeModal() {
    document.getElementById('prizeModal').style.display = 'none';
}

function clearPrizeErrors() {
    document.querySelectorAll('#prizeForm [id^="err_"]').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

function openSettingsModal() {
    document.getElementById('settingsModal').style.display = 'flex';
}

function closeSettingsModal() {
    document.getElementById('settingsModal').style.display = 'none';
}

document.getElementById('prizeForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('prizeSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const formData = new FormData(this);
    const isEdit = editPrizeId !== null;
    if (isEdit) formData.set('_method', 'PUT');

    const url = isEdit
        ? '{{ route('admin.gacha-prizes.update', '__ID__') }}'.replace('__ID__', editPrizeId)
        : '{{ route('admin.gacha-prizes.store') }}';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        });
        const data = await res.json();
        if (res.ok) {
            closePrizeModal();
            showModal('success', data.message || 'Hadiah berhasil disimpan');
            setTimeout(() => location.reload(), 800);
        } else {
            const errors = data.errors || {};
            clearPrizeErrors();
            for (const field in errors) {
                const el = document.getElementById('err_' + field.toLowerCase());
                if (el) { el.textContent = errors[field][0]; el.classList.remove('hidden'); }
            }
            btn.disabled = false;
            btn.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan';
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan. Silakan coba lagi.');
        btn.disabled = false;
        btn.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan';
    }
});

document.getElementById('settingsForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('settingsSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const enabled = document.getElementById('s_enabled').checked;
    const payload = {
        gacha_enabled: enabled ? '1' : '0',
        gacha_daily_limit: String(document.getElementById('s_daily_limit').value || 0)
    };

    try {
        const res = await fetch('{{ route('admin.gacha-prizes.settings') }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            closeSettingsModal();
            document.getElementById('statEnabled').textContent = data.enabled ? 'Aktif' : 'Nonaktif';
            document.getElementById('statLimit').textContent = data.daily_limit + 'x';
            showModal('success', data.message || 'Pengaturan gacha disimpan');
        } else {
            showModal('error', (data.errors && Object.values(data.errors)[0][0]) || 'Gagal menyimpan pengaturan.');
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan. Silakan coba lagi.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closePrizeModal();
        closeSettingsModal();
    }
});
</script>
@endpush
@endsection
