@extends('admin.layouts.app')

@section('title', 'Jual Beli Akun')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div class="flex items-center space-x-3">
        <h2 class="text-lg font-semibold">Jual Beli Akun</h2>
        <span class="badge badge-neutral">{{ $listings->total() }} hasil</span>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.jba-game-cards') }}" class="btn btn-ghost">
            <i class="fas fa-images"></i>
            <span>Gambar Card Game</span>
        </a>
        <button onclick="openCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            <span>Tambah Listing</span>
        </button>
    </div>
</div>

<form action="{{ route('admin.account-listings') }}" method="GET" class="listing-filter mb-5">
    <div class="listing-filter-search">
        <i class="fas fa-search"></i>
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama akun, ID game, atau penjual">
    </div>
    <select name="game" class="input-field">
        <option value="">Semua game</option>
        @foreach($games as $game)
            <option value="{{ $game }}" @selected(($filters['game'] ?? '') === $game)>{{ $game }}</option>
        @endforeach
    </select>
    <select name="status" class="input-field">
        <option value="">Semua status</option>
        <option value="tersedia" @selected(($filters['status'] ?? '') === 'tersedia')>Tersedia</option>
        <option value="habis" @selected(($filters['status'] ?? '') === 'habis')>Terjual</option>
    </select>
    <select name="sort" class="input-field">
        <option value="urutan_asc" @selected(($filters['sort'] ?? 'urutan_asc') === 'urutan_asc')>Urutkan: urutan</option>
        <option value="terbaru" @selected(($filters['sort'] ?? '') === 'terbaru')>Urutkan: terbaru</option>
        <option value="harga_asc" @selected(($filters['sort'] ?? '') === 'harga_asc')>Harga terendah</option>
        <option value="harga_desc" @selected(($filters['sort'] ?? '') === 'harga_desc')>Harga tertinggi</option>
    </select>
    <div class="listing-filter-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('admin.account-listings') }}" class="btn btn-ghost" title="Reset filter"><i class="fas fa-rotate-left"></i></a>
    </div>
</form>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-center">Urutan</th>
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
                    <td class="text-center">{{ data_get($listing->getAttribute('jba_raw'), 'urutan', '-') }}</td>
                    <td>{{ $listing->game }}</td>
                    <td style="font-size:0.88rem">{{ $listing->product_name }}</td>
                    <td class="text-right font-semibold" style="color:var(--accent)">Rp {{ number_format($listing->price, 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $listing->is_sold ? 'badge-danger' : 'badge-success' }}">
                            {{ $listing->is_sold ? 'Terjual' : 'Tersedia' }}
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
                                <button type="submit" class="btn btn-ghost btn-xs" title="{{ $listing->is_sold ? 'Tandai tersedia' : 'Tandai terjual' }}">
                                    <i class="fas {{ $listing->is_sold ? 'fa-eye' : 'fa-eye-slash' }}"></i>
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
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Tidak ada listing yang sesuai filter.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($listings->hasPages())
    <div class="listing-pagination">
        <span>Menampilkan {{ $listings->firstItem() }}–{{ $listings->lastItem() }} dari {{ $listings->total() }} listing</span>
        {{ $listings->onEachSide(1)->links() }}
    </div>
@endif

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
                <div>
                    <label class="block text-sm font-medium mb-1.5">ID Game</label>
                    <input type="text" name="id_game" id="f_id_game" required class="input-field" placeholder="cth: 123456789">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_id_game"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Urutan</label>
                    <input type="number" name="urutan" id="f_urutan" min="0" value="0" required class="input-field">
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_urutan"></p>
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
                    <label class="block text-sm font-medium mb-1.5">Penjual</label>
                    <select name="penjual" id="f_penjual" required class="input-field">
                        <option value="">Pilih Penjual</option>
                        <option value="Johen PUBG">Johen PUBG</option>
                        <option value="Monkey PUBG">Monkey PUBG</option>
                        <option value="Johen MLBB">Johen MLBB</option>
                        <option value="Johen FF">Johen FF</option>
                        <option value="Johen Roblox">Johen Roblox</option>
                        <option value="Johen E-Football">Johen E-Football</option>
                        <option value="Johen FCM">Johen FCM</option>
                        <option value="Johen Valorant">Johen Valorant</option>
                    </select>
                    <p class="text-red-400 text-xs mt-1 hidden" id="err_penjual"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">No. WhatsApp</label>
                    <input type="text" id="f_whatsapp" class="input-field" placeholder="Diatur dari company profile" disabled>
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

@push('styles')
<style>
    .listing-filter {
        display: grid;
        grid-template-columns: minmax(240px, 1.7fr) repeat(3, minmax(150px, 1fr)) auto;
        gap: .75rem;
        padding: .875rem;
        border: 1px solid var(--glass-border);
        border-radius: 14px;
        background: var(--bg-card);
    }
    .listing-filter-search { position: relative; }
    .listing-filter-search i {
        position: absolute;
        top: 50%; left: .9rem;
        transform: translateY(-50%);
        color: var(--text-dim);
        pointer-events: none;
    }
    .listing-filter-search input {
        width: 100%; height: 100%; min-height: 42px;
        padding: .65rem .8rem .65rem 2.5rem;
        border: 1px solid var(--border);
        border-radius: .5rem;
        background: var(--bg-input);
        color: var(--text-primary);
        outline: none;
    }
    .listing-filter-actions { display: flex; gap: .5rem; align-items: center; }
    .listing-filter-actions .btn { white-space: nowrap; }
    .listing-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        color: var(--text-muted);
        font-size: .82rem;
    }
    .listing-pagination nav { margin-left: auto; }
    @media (max-width: 1024px) {
        .listing-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .listing-filter-search { grid-column: span 2; }
    }
    @media (max-width: 640px) {
        .listing-filter, .listing-filter-search { grid-template-columns: 1fr; grid-column: auto; }
        .listing-filter-actions .btn:first-child { flex: 1; }
        .listing-pagination { flex-direction: column; align-items: flex-start; }
        .listing-pagination nav { margin-left: 0; width: 100%; overflow-x: auto; }
    }
</style>
@endpush

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
