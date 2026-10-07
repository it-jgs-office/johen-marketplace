@extends('admin.layouts.app')

@section('title', 'Jual Beli Akun')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Jual Beli Akun</h2>
        <span class="badge badge-neutral">{{ $listings->total() }} total</span>
    </div>
    <div class="flex items-center gap-3">
        <form action="{{ route('admin.account-listings.sync') }}" method="POST" onsubmit="return confirm('Sinkronkan semua listing dari johengaming.id? Proses berjalan di latar belakang dan bisa memakan beberapa menit.')">
            @csrf
            <button type="submit" class="btn btn-ghost" @if(($syncStatus['state'] ?? null) === 'running') disabled @endif>
                <i class="fas fa-sync"></i>
                <span>Sinkron dari johengaming.id</span>
            </button>
        </form>
        <button onclick="openCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            <span>Tambah Listing</span>
        </button>
    </div>
</div>

<div id="johen-sync-status" role="status" aria-live="polite" class="mb-5 text-sm" @if(!isset($syncStatus['state']) || $syncStatus['state'] === 'idle') hidden @endif>
    @if(($syncStatus['state'] ?? null) === 'running')
        Mengambil produk terbaru dari johengaming.id. Halaman ini akan diperbarui saat selesai.
    @elseif(isset($syncStatus['result']))
        Sinkronisasi {{ $syncStatus['state'] === 'completed' ? 'selesai' : 'selesai dengan kendala' }}:
        {{ $syncStatus['result']['created'] }} dibuat, {{ $syncStatus['result']['updated'] }} diperbarui,
        {{ $syncStatus['result']['unchanged'] }} tidak berubah, {{ $syncStatus['result']['sold'] }} terjual,
        {{ $syncStatus['result']['errors'] }} gagal.
        @if($syncStatus['result']['errors'] > 0)
            {{ implode('; ', array_slice($syncStatus['result']['failed'], 0, 2)) }}
        @endif
    @elseif(($syncStatus['state'] ?? null) === 'failed')
        Sinkronisasi gagal: {{ $syncStatus['message'] ?? 'Lihat log aplikasi.' }}
    @endif
</div>

@if(($syncStatus['state'] ?? null) === 'running')
<script>
    (() => {
        const timer = setInterval(async () => {
            try {
                const response = await fetch(@json(route('admin.account-listings.sync-status')), { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const status = await response.json();
                if (status.state !== 'running') {
                    clearInterval(timer);
                    window.location.reload();
                }
            } catch (_) {
                // Kegagalan jaringan sementara tidak menghentikan pemantauan.
            }
        }, 5000);
    })();
</script>
@endif

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Nama Produk</th>
                    <th class="text-right">Harga</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($listings as $listing)
                <tr>
                    <td>{{ $listing->game }}</td>
                    <td style="font-size:0.88rem">{{ $listing->product_name }}</td>
                    <td class="text-right font-semibold" style="color:var(--accent)">Rp {{ number_format($listing->price, 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $listing->is_sold ? 'badge-danger' : ($listing->is_active ? 'badge-success' : 'badge-neutral') }}">
                            {{ $listing->is_sold ? 'Terjual' : ($listing->is_active ? 'Aktif' : 'Nonaktif') }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('admin.account-listings.edit', $listing) }}" class="btn btn-ghost btn-xs">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.account-listings.toggle', $listing) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-xs" title="{{ $listing->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="fas {{ $listing->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.account-listings.destroy', $listing) }}" method="POST" class="inline" onsubmit="return confirm('Hapus listing {{ $listing->product_name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Belum ada listing akun.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="pagination-wrap">
    {{ $listings->links('vendor.pagination.admin') }}
</div>

<!-- ===== MODAL LISTING AKUN ===== -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="createModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeCreateModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:720px;margin:0 1rem;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold">Tambah Listing Akun</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeCreateModal()">&times;</button>
        </div>
        <form id="createListingForm" class="p-5" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Game</label>
                    <input type="text" name="game" list="gameList" id="f_game" required class="input-field" placeholder="Nama game">
                    <datalist id="gameList">
                        @foreach($brands as $brand)
                            <option value="{{ $brand->name }}">
                        @endforeach
                    </datalist>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_game"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Nama Produk</label>
                    <input type="text" name="product_name" id="f_product_name" required class="input-field" placeholder="cth: Akun Mythic Glory">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_product_name"></p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Spesifikasi Produk</label>
                <textarea name="specifications" id="f_specifications" rows="4" required class="input-field" placeholder="Detail akun: rank, skin, emblem, dll"></textarea>
                <p class="text-red-400 text-xs mt-1 hidden" id="err_specifications"></p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Harga Coret <span style="color:#ef4444;font-weight:400;font-size:.75rem">(original)</span></label>
                    <input type="number" name="original_price" id="f_original_price" step="0.01" min="0" class="input-field" placeholder="cth: 500000">
                    <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem">Harga sebelum diskon (dicoret)</p>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_original_price"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Harga Jual</label>
                    <input type="number" name="price" id="f_price" required step="0.01" min="0" class="input-field">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_price"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Nama Owner</label>
                    <select name="owner_name" id="f_owner_name" class="input-field">
                        <option value="">Pilih Owner</option>
                        <option value="Johen PUBG">Johen PUBG</option>
                        <option value="Monkey PUBG">Monkey PUBG</option>
                        <option value="Johen MLBB">Johen MLBB</option>
                        <option value="Johen FF">Johen FF</option>
                        <option value="Johen Roblox">Johen Roblox</option>
                        <option value="Johen E-Football">Johen E-Football</option>
                        <option value="Johen FCM">Johen FCM</option>
                        <option value="Johen Valorant">Johen Valorant</option>
                    </select>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_owner_name"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">No. WhatsApp</label>
                    <input type="text" name="whatsapp" id="f_whatsapp" class="input-field" placeholder="6281234567890">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_whatsapp"></p>
                </div>
            </div>

            <div class="mt-4" style="border-top:1px solid var(--glass-border);padding-top:1.25rem">
                <h3 class="text-sm font-semibold mb-3" style="color:var(--jba-accent, #9d5cf5)">Promo</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Status Promo</label>
                        <select name="promo_type" id="modal_promo_type" class="input-field">
                            <option value="none">Tidak Ada</option>
                            <option value="promo">Promo</option>
                            <option value="flash_sale">Flash Sale</option>
                            <option value="diskon">Diskon</option>
                            <option value="best_seller">Best Seller</option>
                            <option value="hot">Hot</option>
                            <option value="new">New</option>
                            <option value="limited">Limited</option>
                        </select>
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_promo_type"></p>
                    </div>
                    <div id="modal_discount_field" style="display:none">
                        <label class="block text-sm font-medium mb-1.5">Persentase Diskon (%)</label>
                        <input type="number" name="discount_percent" id="f_discount_percent" min="1" max="100" class="input-field" placeholder="cth: 25">
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_discount_percent"></p>
                    </div>
                </div>
            </div>

            <div class="mt-4" style="border-top:1px solid var(--glass-border);padding-top:1.25rem">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="is_sold" value="1" id="f_is_sold"
                           style="width:18px;height:18px;border-radius:6px;accent-color:var(--error);background:var(--bg-input);border:1.5px solid var(--border)">
                    <span class="text-sm font-medium">Tandai sebagai <span style="color:#ef4444;font-weight:600">TERJUAL</span></span>
                </label>
                <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem;margin-left:2rem">Produk akan tetap ditampilkan tetapi dengan overlay gelap dan badge SOLD</p>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Foto Produk</label>
                <div class="flex items-center gap-4">
                    <div id="photoPreview" style="width:80px;height:80px;border-radius:12px;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        <span id="photoPlaceholder" style="font-size:0.72rem;color:var(--text-dim)">Preview</span>
                        <img id="photoImage" class="hidden" style="width:100%;height:100%;object-fit:cover" src="" alt="preview">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/jpg,image/webp"
                               class="w-full text-sm" style="color:var(--text-muted)">
                        <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem" id="photoHint">Maksimal 2MB. Format: JPG, PNG, WebP.</p>
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_photo"></p>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Foto Detail Produk (maksimal 4)</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @for($i = 1; $i <= 4; $i++)
                    <div>
                        <label class="block text-xs font-medium mb-1" style="color:var(--text-dim)">Foto Detail {{ $i }}</label>
                        <input type="file" name="detail_photo_{{ $i }}" accept="image/jpeg,image/png,image/jpg,image/webp"
                               class="w-full text-sm" style="color:var(--text-muted)">
                        <p class="text-red-400 text-xs mt-1 hidden" id="err_detail_photo_{{ $i }}"></p>
                    </div>
                    @endfor
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium mb-1.5">Video YouTube (opsional)</label>
                <input type="url" name="video_url" id="f_video_url" class="input-field" placeholder="https://www.youtube.com/watch?v=...">
                <p style="color:var(--text-dim);font-size:0.72rem;margin-top:0.25rem">Link YouTube untuk preview produk</p>
                <p class="text-red-400 text-xs mt-1 hidden" id="err_video_url"></p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" class="btn btn-ghost" onclick="closeCreateModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('createModal').style.display = 'flex';
    document.getElementById('photoImage').classList.add('hidden');
    document.getElementById('photoPlaceholder').classList.remove('hidden');
    document.getElementById('createListingForm').reset();
    clearErrors();
}

function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
}

function clearErrors() {
    document.querySelectorAll('#createListingForm [id^="err_"]').forEach(function (el) {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Close on escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('createModal').style.display === 'flex') {
            closeCreateModal();
        }
    });

    // Photo preview
    document.getElementById('photoInput')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (ev) {
                const img = document.getElementById('photoImage');
                const placeholder = document.getElementById('photoPlaceholder');
                img.src = ev.target.result;
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    // Promo type toggle
    document.getElementById('modal_promo_type')?.addEventListener('change', function () {
        const field = document.getElementById('modal_discount_field');
        field.style.display = this.value === 'diskon' ? '' : 'none';
        if (this.value !== 'diskon') {
            document.getElementById('f_discount_percent').value = '';
        }
    });

    // Form submission
    document.getElementById('createListingForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';

        const formData = new FormData(this);

        try {
            const res = await fetch('{{ route('admin.account-listings.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const data = await res.json();

            if (res.ok) {
                closeCreateModal();
                showModal('success', data.message || 'Listing akun berhasil ditambahkan');
                setTimeout(function () { location.reload(); }, 800);
            } else {
                const errors = data.errors || {};
                clearErrors();
                for (const field in errors) {
                    const el = document.getElementById('err_' + field);
                    if (el) {
                        el.textContent = errors[field][0];
                        el.classList.remove('hidden');
                    }
                }
                btn.disabled = false;
                btn.textContent = 'Simpan';
            }
        } catch (err) {
            showModal('error', 'Terjadi kesalahan. Silakan coba lagi.');
            btn.disabled = false;
            btn.textContent = 'Simpan';
        }
    });
});
</script>
@endpush
