@extends('admin.layouts.app')
@section('title', 'Popup Banner Promo')
@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Popup Banner Promo</h2>
        <span class="badge badge-neutral">{{ $popupBanners->total() }} total</span>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i><span>Tambah Popup Banner</span>
    </button>
</div>

<div class="card-glass p-4 mb-5" style="display:flex;align-items:flex-start;gap:.75rem">
    <i class="fas fa-info-circle" style="color:var(--accent);margin-top:0.2rem"></i>
    <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6">
        Popup modal iklan ini muncul saat pengunjung pertama kali membuka website.
        Banner <b>portrait</b> tampil memanjang (kolom), dan banner <b>landscape</b> tampil melebar.
        Jika lebih dari satu banner aktif, pengunjung dapat berpindah antar slide.
        Gunakan jadwal (mulai/selesai) untuk banner promo khusus event.
    </div>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Judul</th>
                    <th>Orientasi</th>
                    <th class="text-center">Urutan</th>
                    <th>Jadwal</th>
                    <th class="text-center">Status</th>
                    <th>Dibuat</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($popupBanners as $popup)
                <tr>
                    <td>
                        @if($popup->image_url)
                            <div style="width:60px;height:44px;border-radius:8px;background:var(--bg-input);border:1px solid var(--glass-border);display:flex;align-items:center;justify-content:center;overflow:hidden">
                                <img src="{{ $popup->image_url }}" alt="{{ $popup->title ?? 'Popup' }}"
                                     style="width:100%;height:100%;object-fit:cover">
                            </div>
                        @else
                            <span style="font-size:1.2rem">🖼️</span>
                        @endif
                    </td>
                    <td class="font-semibold">{{ $popup->title ?? 'Tanpa judul' }}</td>
                    <td>
                        <span class="badge {{ $popup->orientation === 'portrait' ? 'badge-info' : 'badge-warning' }}">
                            {{ $popup->orientation === 'portrait' ? 'Portrait' : 'Landscape' }}
                        </span>
                    </td>
                    <td class="text-center text-sm" style="color:var(--text-muted)">{{ $popup->sort_order }}</td>
                    <td style="font-size:0.78rem;color:var(--text-muted)">
                        @if($popup->starts_at && $popup->ends_at)
                            {{ $popup->starts_at->format('d/m/Y') }} &rarr; {{ $popup->ends_at->format('d/m/Y') }}
                        @else
                            <span style="color:var(--text-dim)">Selalu tampil</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $popup->is_active ? 'badge-success' : 'badge-neutral' }}">
                            {{ $popup->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-muted)">{{ $popup->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" class="btn btn-ghost btn-xs"
                                data-popup='{{ json_encode(array_merge(
                                    $popup->only(['id','title','description','orientation','image_fit','image_position','link','sort_order','is_active']),
                                    ['starts_at' => $popup->starts_at?->format('Y-m-d\TH:i'),
                                     'ends_at' => $popup->ends_at?->format('Y-m-d\TH:i'),
                                     'image_url' => $popup->image_url]
                                )) }}'
                                onclick="openEditModal(this)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.popup-banners.toggle', $popup) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-xs" title="{{ $popup->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="fas {{ $popup->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger btn-xs" onclick="confirmDelete('{{ route('admin.popup-banners.destroy', $popup) }}', 'Hapus popup banner {{ $popup->title ?? 'ini' }}?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-bullhorn"></i>
                            <p>Belum ada popup banner. Tambahkan banner promo event sekarang.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $popupBanners->links('vendor.pagination.admin') }}</div>

<!-- MODAL POPUP BANNER -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="popupModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closePopupModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:560px;margin:0 1rem;max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold" id="popupModalTitle">Tambah Popup Banner</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closePopupModal()">&times;</button>
        </div>
        <form id="popupForm" class="p-5" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" id="popupFormMethod" name="_method" value="POST">
            <input type="hidden" id="popupId" name="popup_id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Judul</label>
                    <input type="text" name="title" id="f_title" class="input-field" placeholder="Contoh: Event Lebaran Promo 50%">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_title"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Orientasi Banner</label>
                    <select name="orientation" id="f_orientation" required class="input-field">
                        <option value="portrait">Portrait (memanjang)</option>
                        <option value="landscape">Landscape (melebar)</option>
                    </select>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_orientation"></p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Deskripsi <span style="color:var(--text-dim);font-weight:400">(opsional)</span></label>
                <textarea name="description" id="f_description" rows="2" class="input-field" placeholder="Teks singkat promosi"></textarea>
                <p class="text-red-400 text-xs mt-1 hidden" id="err_description"></p>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Gambar Banner <span style="color:var(--text-dim);font-weight:400">(wajib, JPG / PNG / WebP)</span></label>
                <div class="flex items-center gap-3">
                    <div id="popupImagePreview" style="width:96px;height:72px;border-radius:12px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        <span id="popupImagePlaceholder" style="font-size:0.7rem;color:var(--text-dim);text-align:center">Preview</span>
                        <img id="popupImage" class="hidden" style="width:100%;height:100%;object-fit:cover" src="" alt="preview">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" id="popupImageInput" accept="image/jpeg,image/png,image/jpg,image/webp"
                               class="w-full text-sm" style="color:var(--text-muted)">
                        <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem" id="popupImageHint">Maksimal 5MB. Gambar otomatis dioptimalkan (WebP) agar jernih, tajam, dan berukuran kecil. Untuk hasil terbaik gunakan orientasi sesuai pilihan di atas.</p>
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_image"></p>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Cara Gambar Ditampilkan</label>
                        <select name="image_fit" id="f_image_fit" class="input-field">
                            <option value="contain">Sesuaikan penuh (tampil utuh)</option>
                            <option value="cover">Penuhi frame (potong pinggir)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Posisi Gambar</label>
                        <select name="image_position" id="f_image_position" class="input-field">
                            <option value="top">Atas</option>
                            <option value="center">Tengah</option>
                            <option value="bottom">Bawah</option>
                        </select>
                    </div>
                </div>
                <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.4rem">
                    Mode <b>"Penuhi frame"</b> akan menjadikan gambar penuh memenuhi area popup (mirip Background Detail Game).
                    Posisi menentukan bagian gambar yang tampil saat ukurannya kurang pas.
                </p>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Link Tujuan <span style="color:var(--text-dim);font-weight:400">(opsional)</span></label>
                <input type="url" name="link" id="f_link" class="input-field" placeholder="Contoh: https://johengaming.com/games/mobile-legends">
                <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem">Banner akan diklik menuju link ini (buka di tab baru).</p>
                <p class="text-red-400 text-xs mt-1 hidden" id="err_link"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Mulai Tampil <span style="color:var(--text-dim);font-weight:400">(opsional)</span></label>
                    <input type="datetime-local" name="starts_at" id="f_starts_at" class="input-field">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_starts_at"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Selesai Tampil <span style="color:var(--text-dim);font-weight:400">(opsional)</span></label>
                    <input type="datetime-local" name="ends_at" id="f_ends_at" class="input-field">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_ends_at"></p>
                </div>
            </div>
            <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.4rem">Kosongkan keduanya untuk selalu tampil. Isi untuk banner promo event berjangka.</p>

            <div class="flex gap-6 mt-4">
                <label class="flex items-center gap-2" style="cursor:pointer">
                    <input type="checkbox" name="is_active" id="f_is_active" value="1" checked
                           style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer">
                    <span class="text-sm font-medium">Aktif</span>
                </label>
                <label class="flex items-center gap-2" style="cursor:pointer">
                    <span class="text-sm font-medium">Urutan
                        <input type="number" name="sort_order" id="f_sort_order" value="0" min="0"
                               class="input-field" style="width:80px;padding:0.3rem 0.5rem;display:inline-block;margin-left:0.4rem">
                    </span>
                </label>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" class="btn btn-ghost" onclick="closePopupModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="popupSubmitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let editPopupId = null;

document.getElementById('popupImageInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('popupImage').src = ev.target.result;
            document.getElementById('popupImage').classList.remove('hidden');
            document.getElementById('popupImagePlaceholder').classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
});

// Live preview of orientation on preview box
document.getElementById('f_orientation')?.addEventListener('change', function() {
    const isPortrait = this.value === 'portrait';
    const preview = document.getElementById('popupImagePreview');
    if (isPortrait) {
        preview.style.width = '72px';
        preview.style.height = '96px';
    } else {
        preview.style.width = '96px';
        preview.style.height = '72px';
    }
});

function setPreviewByOrientation() {
    const isPortrait = document.getElementById('f_orientation').value === 'portrait';
    const preview = document.getElementById('popupImagePreview');
    preview.style.width = isPortrait ? '72px' : '96px';
    preview.style.height = isPortrait ? '96px' : '72px';
    applyFitToPreview();
}

function applyFitToPreview() {
    const img = document.getElementById('popupImage');
    const fit = document.getElementById('f_image_fit')?.value || 'contain';
    const pos = document.getElementById('f_image_position')?.value || 'center';
    img.style.objectFit = fit;
    img.style.objectPosition = pos;
}

document.getElementById('f_image_fit')?.addEventListener('change', applyFitToPreview);
document.getElementById('f_image_position')?.addEventListener('change', applyFitToPreview);

function openCreateModal() {
    editPopupId = null;
    document.getElementById('popupModalTitle').textContent = 'Tambah Popup Banner';
    document.getElementById('popupSubmitBtn').textContent = 'Simpan';
    document.getElementById('popupFormMethod').value = 'POST';
    document.getElementById('popupId').value = '';
    document.getElementById('popupForm').reset();
    document.getElementById('popupImage').classList.add('hidden');
    document.getElementById('popupImagePlaceholder').classList.remove('hidden');
    document.getElementById('popupImageHint').textContent = 'Maksimal 5MB. Gambar otomatis dioptimalkan (WebP) agar jernih dan berukuran kecil. Untuk hasil terbaik gunakan orientasi sesuai pilihan di atas.';
    document.getElementById('f_orientation').value = 'portrait';
    document.getElementById('f_image_fit').value = 'contain';
    document.getElementById('f_image_position').value = 'center';
    document.getElementById('f_is_active').checked = true;
    document.getElementById('f_sort_order').value = 0;
    setPreviewByOrientation();
    clearPopupErrors();
    document.getElementById('popupModal').style.display = 'flex';
}

function openEditModal(btn) {
    const p = JSON.parse(btn.dataset.popup);
    editPopupId = p.id;
    document.getElementById('popupModalTitle').textContent = 'Edit Popup Banner';
    document.getElementById('popupSubmitBtn').textContent = 'Simpan Perubahan';
    document.getElementById('popupFormMethod').value = 'PUT';
    document.getElementById('popupId').value = p.id;
    document.getElementById('f_title').value = p.title || '';
    document.getElementById('f_description').value = p.description || '';
    document.getElementById('f_orientation').value = p.orientation || 'portrait';
    document.getElementById('f_image_fit').value = p.image_fit || 'contain';
    document.getElementById('f_image_position').value = p.image_position || 'center';
    document.getElementById('f_link').value = p.link || '';
    document.getElementById('f_starts_at').value = p.starts_at || '';
    document.getElementById('f_ends_at').value = p.ends_at || '';
    document.getElementById('f_is_active').checked = p.is_active;
    document.getElementById('f_sort_order').value = p.sort_order || 0;
    document.getElementById('popupImage').classList.add('hidden');
    document.getElementById('popupImagePlaceholder').classList.remove('hidden');
    document.getElementById('popupImageHint').textContent = 'Kosongkan jika tidak ingin mengubah. Maks 5MB. Gambar baru otomatis dioptimalkan (WebP).';
    if (p.image_url) {
        document.getElementById('popupImage').src = p.image_url;
        document.getElementById('popupImage').classList.remove('hidden');
        document.getElementById('popupImagePlaceholder').classList.add('hidden');
    }
    setPreviewByOrientation();
    clearPopupErrors();
    document.getElementById('popupModal').style.display = 'flex';
}

function closePopupModal() {
    document.getElementById('popupModal').style.display = 'none';
}

function clearPopupErrors() {
    document.querySelectorAll('#popupForm [id^="err_"]').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

document.getElementById('popupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('popupSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
    const isEdit = editPopupId !== null;
    const btnText = isEdit ? 'Simpan Perubahan' : 'Simpan';

    const imageInput = document.getElementById('popupImageInput');
    const imageFile = imageInput && imageInput.files && imageInput.files[0];
    if (imageFile && imageFile.size > 5 * 1024 * 1024) {
        showModal('error', 'Ukuran file melebihi 5MB. Pilih gambar yang lebih kecil lalu coba lagi.');
        btn.disabled = false;
        btn.textContent = btnText;
        return;
    }
    if (imageFile && !/^image\/(jpeg|png|webp)$/.test(imageFile.type)) {
        showModal('error', 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP.');
        btn.disabled = false;
        btn.textContent = btnText;
        return;
    }

    const formData = new FormData(this);
    if (isEdit) formData.set('_method', 'PUT');

    const url = isEdit
        ? '{{ route('admin.popup-banners.update', '__ID__') }}'.replace('__ID__', editPopupId)
        : '{{ route('admin.popup-banners.store') }}';

    let res;
    try {
        res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        });
    } catch (err) {
        showModal('error', 'Koneksi gagal. Periksa internet Anda lalu coba lagi.');
        btn.disabled = false;
        btn.textContent = btnText;
        return;
    }

    let data = {};
    try { data = await res.json(); } catch (err) { /* respons non-JSON */ }

    if (res.ok) {
        closePopupModal();
        showModal('success', data.message || (isEdit ? 'Popup banner berhasil diperbarui' : 'Popup banner berhasil ditambahkan'));
        setTimeout(() => location.reload(), 800);
        return;
    }

    const errors = data.errors || {};
    clearPopupErrors();
    let hasFieldErrors = false;
    for (const field in errors) {
        const el = document.getElementById('err_' + field);
        if (el) { el.textContent = errors[field][0]; el.classList.remove('hidden'); hasFieldErrors = true; }
    }

    if (!hasFieldErrors) {
        const msg = data.message
            || (res.status === 413 ? 'Ukuran file terlalu besar. Maksimal 5MB per gambar.'
            : (res.status === 419 ? 'Sesi Anda kedaluwarsa. Muat ulang halaman lalu coba lagi.'
            : (res.status === 422 ? 'Data tidak valid. Periksa kembali isian form, terutama gambar.'
            : (res.status === 500 ? 'Server gagal memproses gambar. Gunakan file JPG/PNG/WebP maksimal 5MB dengan resolusi sedikit lebih rendah, atau hubungi admin.'
            : 'Terjadi kesalahan (HTTP ' + res.status + '). Silakan coba lagi.'))));
        showModal('error', msg);
    }

    btn.disabled = false;
    btn.textContent = btnText;
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePopupModal();
});
</script>
@endpush
@endsection
