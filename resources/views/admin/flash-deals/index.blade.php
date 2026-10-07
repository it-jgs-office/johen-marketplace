@extends('admin.layouts.app')
@section('title', 'Flash Deal')
@section('content')

@php
    $now = now();
    $dealStatus = function ($deal) use ($now) {
        if (!$deal->is_active) return ['label' => 'Nonaktif', 'class' => 'badge-neutral'];
        if ($deal->starts_at && $deal->starts_at > $now) return ['label' => 'Terjadwal', 'class' => 'badge-warning'];
        if ($deal->ends_at && $deal->ends_at < $now) return ['label' => 'Berakhir', 'class' => 'badge-neutral'];
        return ['label' => 'Menayang', 'class' => 'badge-success'];
    };
@endphp

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Flash Deal</h2>
        <span class="badge badge-neutral">{{ $flashDeals->total() }} total</span>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-bolt"></i><span>Tambah Flash Deal</span>
    </button>
</div>

<div class="card-glass p-4 mb-5" style="display:flex;align-items:flex-start;gap:.75rem">
    <i class="fas fa-info-circle" style="color:var(--accent);margin-top:0.2rem"></i>
    <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6">
        Flash deal menampilkan produk dengan <b>diskon khusus</b> di beranda (section FLASH DEAL) dengan
        <b>kuota terbatas</b> dan <b>durasi tayang</b>. Harga flash dihitung otomatis:
        <code style="font-size:0.75rem">harga jual &times; (1 - diskon%)</code>.
        Isi <b>Durasi Tayang (jam)</b> — waktu selesai terhitung otomatis dari waktu mulai.
        Kuota otomatis dikembalikan jika pesanan dibatalkan/gagal.
    </div>
</div>

<div class="admin-filter-bar">
    <select class="input-field" id="statusFilter" style="width:auto;min-width:140px;padding:0.35rem 0.75rem;font-size:0.82rem" onchange="applyFilter()">
        <option value="">Semua Status</option>
        <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Menayang</option>
        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
        <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Berakhir / Nonaktif</option>
    </select>
    <select class="input-field" id="brandFilter" style="width:auto;min-width:160px;padding:0.35rem 0.75rem;font-size:0.82rem" onchange="applyFilter()">
        <option value="">Semua Game</option>
        @foreach($brands as $b)
            <option value="{{ $b }}" {{ request('brand') === $b ? 'selected' : '' }}>{{ $b }}</option>
        @endforeach
    </select>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="text-center">Diskon</th>
                    <th class="text-right">Harga Normal</th>
                    <th class="text-right">Harga Flash</th>
                    <th class="text-center">Durasi</th>
                    <th>Jadwal</th>
                    <th class="text-center">Kuota</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($flashDeals as $deal)
                @php $st = $dealStatus($deal); @endphp
                <tr>
                    <td>
                        <div class="font-semibold" style="font-size:0.86rem">{{ $deal->product?->product_name ?? 'Produk dihapus' }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $deal->product?->brand }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-error">{{ rtrim(rtrim(number_format($deal->discount_percent, 2, ',', '.'), '0'), ',') }}%</span>
                    </td>
                    <td class="text-right" style="color:var(--text-muted);text-decoration:line-through;font-size:0.84rem">
                        Rp {{ number_format($deal->original_price, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-semibold" style="color:var(--accent)">
                        Rp {{ number_format($deal->flash_price, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $deal->duration_label }}</span>
                    </td>
                    <td style="font-size:0.8rem;color:var(--text-muted)">
                        {{ $deal->starts_at->format('d/m/Y H:i') }} &rarr;<br>
                        {{ $deal->ends_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $deal->stock > 0 ? 'badge-success' : 'badge-error' }}">{{ $deal->stock }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" class="btn btn-ghost btn-xs"
                                data-deal='{{ json_encode(array_merge(
                                    $deal->only(['id', 'product_id', 'discount_percent', 'stock', 'is_active']),
                                    ['starts_at' => $deal->starts_at?->format('Y-m-d\TH:i'),
                                     'ends_at' => $deal->ends_at?->format('Y-m-d\TH:i'),
                                     'image' => $deal->image]
                                )) }}'
                                onclick="openEditModal(this)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.flash-deals.toggle', $deal) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-xs" title="{{ $deal->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="fas {{ $deal->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger btn-xs" onclick="confirmDelete('{{ route('admin.flash-deals.destroy', $deal) }}', 'Hapus flash deal {{ $deal->product?->product_name ?? 'ini' }}?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="fas fa-bolt"></i>
                            <p>Belum ada flash deal. Buat flash deal pertama kamu sekarang.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $flashDeals->links('vendor.pagination.admin') }}</div>

<!-- MODAL FLASH DEAL -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="dealModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDealModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:600px;margin:0 1rem;max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold" id="dealModalTitle">Tambah Flash Deal</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeDealModal()">&times;</button>
        </div>
        <form id="dealForm" class="p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" id="dealFormMethod" name="_method" value="POST">
            <input type="hidden" id="dealId" name="deal_id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1.5">Produk</label>
                    <select name="product_id" id="f_product_id" required class="input-field" onchange="updateProductPreview()">
                        <option value="">— Pilih Produk —</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->selling_price }}">{{ $p->brand }} — {{ $p->product_name }} (Rp {{ number_format($p->selling_price, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_product_id"></p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1.5">Gambar Flash Deal <span style="color:var(--text-dim);font-weight:400">(opsional)</span></label>
                    <input type="file" name="image" id="f_image" accept="image/jpeg,image/png,image/webp" class="input-field" style="padding:0.4rem">
                    <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem">Kosongkan untuk memakai gambar card game secara otomatis.</p>
                    <div class="flex items-center gap-3 mt-2" id="imagePreviewWrap" style="display:none">
                        <img id="imagePreview" alt="Preview" style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid var(--glass-border)">
                        <label class="flex items-center gap-1.5" style="cursor:pointer;color:var(--text-muted);font-size:0.8rem">
                            <input type="checkbox" name="remove_image" id="f_remove_image" value="1" style="width:14px;height:14px;accent-color:var(--accent)">
                            Hapus gambar (pakai default)
                        </label>
                    </div>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_image"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Diskon (%)</label>
                    <input type="number" name="discount_percent" id="f_discount_percent" required min="1" max="100" step="0.01" class="input-field" oninput="updateProductPreview()">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_discount_percent"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Kuota (Tersedia)</label>
                    <input type="number" name="stock" id="f_stock" required min="1" class="input-field">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_stock"></p>
                </div>
            </div>

            <div class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Mulai Tayang</label>
                        <input type="datetime-local" name="starts_at" id="f_starts_at" required class="input-field" onchange="updateEndsAt()">
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_starts_at"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Durasi Tayang (jam)</label>
                        <input type="number" name="duration_hours" id="f_duration_hours" min="0.1" max="720" step="0.1" class="input-field" placeholder="cth: 24" oninput="updateEndsAt()">
                        <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem">Waktu selesai terisi otomatis dari durasi ini.</p>
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_duration_hours"></p>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1.5">Selesai Tayang <span style="color:var(--text-dim);font-weight:400">(dihitung otomatis)</span></label>
                    <div class="input-field" id="f_ends_at_preview" style="background:var(--bg-input);cursor:not-allowed;opacity:.75">—</div>
                </div>
            </div>

            <div class="mt-4 card-glass p-3" style="border-radius:14px">
                <div style="font-size:0.78rem;color:var(--text-muted);display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
                    <span>Harga Normal: <b id="preview_normal" style="color:var(--text-muted)">Rp 0</b></span>
                    <span>Harga Flash: <b id="preview_flash" style="color:var(--accent)">Rp 0</b></span>
                    <span>Hemat: <b id="preview_disc" style="color:#f59e0b">Rp 0</b></span>
                </div>
            </div>

            <div class="flex justify-between items-center mt-5">
                <label class="flex items-center gap-2" style="cursor:pointer">
                    <input type="checkbox" name="is_active" id="f_is_active" value="1" checked
                           style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer">
                    <span class="text-sm font-medium">Aktif</span>
                </label>
                <div class="flex gap-3">
                    <button type="button" class="btn btn-ghost" onclick="closeDealModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="dealSubmitBtn">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let editDealId = null;

function fmtRp(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function updateProductPreview() {
    const sel = document.getElementById('f_product_id');
    const opt = sel.options[sel.selectedIndex];
    const normal = opt ? parseFloat(opt.dataset.price || 0) : 0;
    const disc = parseFloat(document.getElementById('f_discount_percent').value || 0);
    const flash = Math.round(normal * (1 - disc / 100));

    document.getElementById('preview_normal').textContent = fmtRp(normal);
    document.getElementById('preview_flash').textContent = fmtRp(flash);
    document.getElementById('preview_disc').textContent = fmtRp(normal - flash);
}

function updateEndsAt() {
    const start = document.getElementById('f_starts_at').value;
    const hours = parseFloat(document.getElementById('f_duration_hours').value || 0);
    const el = document.getElementById('f_ends_at_preview');

    if (!start || !hours || hours <= 0) {
        el.textContent = '—';
        return;
    }

    const d = new Date(start);
    d.setTime(d.getTime() + hours * 3600 * 1000);

    const pad = n => String(n).padStart(2, '0');
    el.textContent = pad(d.getFullYear()) + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
        'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function openCreateModal() {
    editDealId = null;
    document.getElementById('dealModalTitle').textContent = 'Tambah Flash Deal';
    document.getElementById('dealSubmitBtn').textContent = 'Simpan';
    document.getElementById('dealFormMethod').value = 'POST';
    document.getElementById('dealId').value = '';
    document.getElementById('dealForm').reset();
    document.getElementById('f_is_active').checked = true;
    document.getElementById('f_ends_at_preview').textContent = '—';
    resetImageField();
    clearDealErrors();
    document.getElementById('dealModal').style.display = 'flex';
    updateProductPreview();
}

function openEditModal(btn) {
    const d = JSON.parse(btn.dataset.deal);
    editDealId = d.id;
    document.getElementById('dealModalTitle').textContent = 'Edit Flash Deal';
    document.getElementById('dealSubmitBtn').textContent = 'Simpan Perubahan';
    document.getElementById('dealFormMethod').value = 'PUT';
    document.getElementById('dealId').value = d.id;
    document.getElementById('f_product_id').value = d.product_id;
    document.getElementById('f_discount_percent').value = d.discount_percent;
    document.getElementById('f_stock').value = d.stock;
    document.getElementById('f_starts_at').value = d.starts_at;
    document.getElementById('f_duration_hours').value = '';
    document.getElementById('f_is_active').checked = d.is_active;
    document.getElementById('f_ends_at_preview').textContent = d.ends_at;
    resetImageField(d.image);
    clearDealErrors();
    document.getElementById('dealModal').style.display = 'flex';
    updateProductPreview();
}

function resetImageField(currentImage) {
    const input = document.getElementById('f_image');
    const wrap = document.getElementById('imagePreviewWrap');
    const img = document.getElementById('imagePreview');
    const removeChk = document.getElementById('f_remove_image');

    input.value = '';
    removeChk.checked = false;

    if (currentImage) {
        img.src = '{{ asset('storage') }}' + '/' + currentImage;
        wrap.style.display = 'flex';
    } else {
        img.src = '';
        wrap.style.display = 'none';
    }
}

document.getElementById('f_image').addEventListener('change', function() {
    const file = this.files && this.files[0];
    const wrap = document.getElementById('imagePreviewWrap');
    const img = document.getElementById('imagePreview');
    const removeChk = document.getElementById('f_remove_image');

    if (file) {
        img.src = URL.createObjectURL(file);
        wrap.style.display = 'flex';
        removeChk.checked = false;
    } else {
        img.src = '';
        wrap.style.display = 'none';
    }
});

function closeDealModal() {
    document.getElementById('dealModal').style.display = 'none';
}

function clearDealErrors() {
    document.querySelectorAll('#dealForm [id^="err_"]').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

function applyFilter() {
    const url = new URL(window.location.href);
    const status = document.getElementById('statusFilter').value;
    const brand = document.getElementById('brandFilter').value;
    url.searchParams.delete('page');
    if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
    if (brand) url.searchParams.set('brand', brand); else url.searchParams.delete('brand');
    window.location.href = url.toString();
}

document.getElementById('dealForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('dealSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const formData = new FormData(this);
    const isEdit = editDealId !== null;
    if (isEdit) formData.set('_method', 'PUT');

    const url = isEdit
        ? '{{ route('admin.flash-deals.update', '__ID__') }}'.replace('__ID__', editDealId)
        : '{{ route('admin.flash-deals.store') }}';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        });
        const data = await res.json();
        if (res.ok) {
            closeDealModal();
            showModal('success', data.message || (isEdit ? 'Flash deal berhasil diperbarui' : 'Flash deal berhasil ditambahkan'));
            setTimeout(() => location.reload(), 800);
        } else {
            const errors = data.errors || {};
            clearDealErrors();
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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDealModal();
});
</script>
@endpush
@endsection
