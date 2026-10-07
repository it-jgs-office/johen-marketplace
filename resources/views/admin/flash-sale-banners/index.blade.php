@extends('admin.layouts.app')
@section('title', 'Banner Flash Sale')
@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Banner Flash Sale</h2>
        <span class="badge badge-neutral">{{ $flashSaleBanners->total() }} total</span>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-fire"></i><span>Tambah Banner</span>
    </button>
</div>

<div class="card-glass p-4 mb-5" style="display:flex;align-items:flex-start;gap:.75rem">
    <i class="fas fa-info-circle" style="color:var(--accent);margin-top:0.2rem"></i>
    <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.6">
        Banner ini tampil di halaman <b>Jual Beli Akun</b> tepat di atas teks "Jual Beli Akun Game".
        Banner hanya tampil jika berstatus <b>Aktif</b>. Gunakan jadwal (mulai/selesai) untuk promo berjangka.
        Jika lebih dari satu banner aktif, semuanya ditampilkan berurutan sesuai kolom <b>Urutan</b>.
    </div>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Judul</th>
                    <th class="text-center">Urutan</th>
                    <th>Jadwal</th>
                    <th class="text-center">Status</th>
                    <th>Dibuat</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($flashSaleBanners as $banner)
                <tr>
                    <td>
                        @if($banner->image_url)
                            <div style="width:80px;height:40px;border-radius:8px;background:var(--bg-input);border:1px solid var(--glass-border);display:flex;align-items:center;justify-content:center;overflow:hidden">
                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? 'Banner' }}"
                                     style="width:100%;height:100%;object-fit:cover">
                            </div>
                        @else
                            <span style="font-size:1.2rem">🖼️</span>
                        @endif
                    </td>
                    <td class="font-semibold">{{ $banner->title ?? 'Tanpa judul' }}</td>
                    <td class="text-center text-sm" style="color:var(--text-muted)">{{ $banner->sort_order }}</td>
                    <td style="font-size:0.78rem;color:var(--text-muted)">
                        @if($banner->starts_at && $banner->ends_at)
                            {{ $banner->starts_at->format('d/m/Y') }} &rarr; {{ $banner->ends_at->format('d/m/Y') }}
                        @else
                            <span style="color:var(--text-dim)">Selalu tampil</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $banner->is_active ? 'badge-success' : 'badge-neutral' }}">
                            {{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-muted)">{{ $banner->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" class="btn btn-ghost btn-xs"
                                data-banner='{{ json_encode(array_merge(
                                    $banner->only(['id','title','link','sort_order','is_active']),
                                    ['starts_at' => $banner->starts_at?->format('Y-m-d\TH:i'),
                                     'ends_at' => $banner->ends_at?->format('Y-m-d\TH:i'),
                                     'image_url' => $banner->image_url]
                                )) }}'
                                onclick="openEditModal(this)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.flash-sale-banners.toggle', $banner) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-xs" title="{{ $banner->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="fas {{ $banner->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger btn-xs" onclick="confirmDelete('{{ route('admin.flash-sale-banners.destroy', $banner) }}', 'Hapus banner flash sale {{ $banner->title ?? 'ini' }}?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-fire"></i>
                            <p>Belum ada banner flash sale. Tambahkan banner untuk halaman Jual Beli Akun.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $flashSaleBanners->links('vendor.pagination.admin') }}</div>

<!-- MODAL BANNER FLASH SALE -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="bannerModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeBannerModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:560px;margin:0 1rem;max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold" id="bannerModalTitle">Tambah Banner Flash Sale</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeBannerModal()">&times;</button>
        </div>
        <form id="bannerForm" class="p-5" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" id="bannerFormMethod" name="_method" value="POST">
            <input type="hidden" id="bannerId" name="banner_id" value="">

            <div>
                <label class="block text-sm font-medium mb-1.5">Judul <span style="color:var(--text-dim);font-weight:400">(opsional)</span></label>
                <input type="text" name="title" id="f_title" class="input-field" placeholder="Contoh: Flash Sale Akun Mobile Legends">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_title"></p>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Gambar Banner <span style="color:var(--text-dim);font-weight:400">(wajib, JPG / PNG / WebP, disarankan lebar)</span></label>
                <div class="flex items-center gap-3">
                    <div id="bannerImagePreview" style="width:112px;height:56px;border-radius:12px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        <span id="bannerImagePlaceholder" style="font-size:0.7rem;color:var(--text-dim);text-align:center">Preview</span>
                        <img id="bannerImage" class="hidden" style="width:100%;height:100%;object-fit:cover" src="" alt="preview">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" id="bannerImageInput" accept="image/jpeg,image/png,image/jpg,image/webp"
                               class="w-full text-sm" style="color:var(--text-muted)">
                        <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem" id="bannerImageHint">Maksimal 5MB. Gambar otomatis dioptimalkan (WebP). Untuk hasil terbaik gunakan banner lebar.</p>
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_image"></p>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Link Tujuan <span style="color:var(--text-dim);font-weight:400">(opsional)</span></label>
                <input type="url" name="link" id="f_link" class="input-field" placeholder="Contoh: https://johengaming.id/games/mobile-legends">
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
            <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.4rem">Kosongkan keduanya untuk selalu tampil. Isi untuk promo event berjangka.</p>

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
                <button type="button" class="btn btn-ghost" onclick="closeBannerModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="bannerSubmitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let editBannerId = null;

document.getElementById('bannerImageInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('bannerImage').src = ev.target.result;
            document.getElementById('bannerImage').classList.remove('hidden');
            document.getElementById('bannerImagePlaceholder').classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
});

function openCreateModal() {
    editBannerId = null;
    document.getElementById('bannerModalTitle').textContent = 'Tambah Banner Flash Sale';
    document.getElementById('bannerSubmitBtn').textContent = 'Simpan';
    document.getElementById('bannerFormMethod').value = 'POST';
    document.getElementById('bannerId').value = '';
    document.getElementById('bannerForm').reset();
    document.getElementById('bannerImage').classList.add('hidden');
    document.getElementById('bannerImagePlaceholder').classList.remove('hidden');
    document.getElementById('bannerImageHint').textContent = 'Maksimal 5MB. Gambar otomatis dioptimalkan (WebP). Untuk hasil terbaik gunakan banner lebar.';
    document.getElementById('f_is_active').checked = true;
    document.getElementById('f_sort_order').value = 0;
    clearBannerErrors();
    document.getElementById('bannerModal').style.display = 'flex';
}

function openEditModal(btn) {
    const b = JSON.parse(btn.dataset.banner);
    editBannerId = b.id;
    document.getElementById('bannerModalTitle').textContent = 'Edit Banner Flash Sale';
    document.getElementById('bannerSubmitBtn').textContent = 'Simpan Perubahan';
    document.getElementById('bannerFormMethod').value = 'PUT';
    document.getElementById('bannerId').value = b.id;
    document.getElementById('f_title').value = b.title || '';
    document.getElementById('f_link').value = b.link || '';
    document.getElementById('f_starts_at').value = b.starts_at || '';
    document.getElementById('f_ends_at').value = b.ends_at || '';
    document.getElementById('f_is_active').checked = !!b.is_active;
    document.getElementById('f_sort_order').value = b.sort_order || 0;
    document.getElementById('bannerImage').classList.add('hidden');
    document.getElementById('bannerImagePlaceholder').classList.remove('hidden');
    document.getElementById('bannerImageHint').textContent = 'Kosongkan jika tidak ingin mengubah. Maks 5MB. Gambar baru otomatis dioptimalkan (WebP).';
    if (b.image_url) {
        document.getElementById('bannerImage').src = b.image_url;
        document.getElementById('bannerImage').classList.remove('hidden');
        document.getElementById('bannerImagePlaceholder').classList.add('hidden');
    }
    clearBannerErrors();
    document.getElementById('bannerModal').style.display = 'flex';
}

function closeBannerModal() {
    document.getElementById('bannerModal').style.display = 'none';
}

function clearBannerErrors() {
    document.querySelectorAll('#bannerForm [id^="err_"]').forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

document.getElementById('bannerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('bannerSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const formData = new FormData(this);
    const isEdit = editBannerId !== null;
    if (isEdit) formData.set('_method', 'PUT');

    const url = isEdit
        ? '{{ route('admin.flash-sale-banners.update', '__ID__') }}'.replace('__ID__', editBannerId)
        : '{{ route('admin.flash-sale-banners.store') }}';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        });
        const data = await res.json();
        if (res.ok) {
            closeBannerModal();
            showModal('success', data.message || (isEdit ? 'Banner flash sale berhasil diperbarui' : 'Banner flash sale berhasil ditambahkan'));
            setTimeout(() => location.reload(), 800);
        } else {
            const errors = data.errors || {};
            clearBannerErrors();
            for (const field in errors) {
                const el = document.getElementById('err_' + field);
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
    if (e.key === 'Escape') closeBannerModal();
});
</script>
@endpush
@endsection
