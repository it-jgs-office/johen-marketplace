@extends('admin.layouts.app')

@section('title', 'Edit ' . $brand->name)

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.products') }}" style="display:inline-flex;align-items:center;gap:.35rem;color:var(--text-dim);font-size:.8rem;margin-bottom:1rem">
        <i class="fas fa-arrow-left"></i> Kembali ke Top Up
    </a>

    <div class="flex items-center gap-3 mb-5">
        <h2 class="text-lg font-semibold">Edit Game</h2>
        <span class="badge badge-neutral">{{ $brand->name }}</span>
    </div>

    <form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data" class="card-glass p-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="return_to" value="topup">
        <input type="hidden" name="service_type" value="{{ old('service_type', $brand->service_type ?: 'topup') }}">
        <input type="hidden" name="catalog_group" value="{{ old('catalog_group', $brand->catalog_group ?: 'game') }}">
        <input type="hidden" name="detail_bg_position" value="{{ old('detail_bg_position', $brand->detail_bg_position ?: 'center') }}">
        <input type="hidden" name="is_active" value="{{ old('is_active', $brand->is_active ? '1' : '0') }}">
        <input type="hidden" name="is_popular" value="{{ old('is_popular', $brand->is_popular ? '1' : '0') }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1.5" for="name">Nama Game</label>
                <input id="name" type="text" name="name" value="{{ old('name', $brand->name) }}" required class="input-field w-full">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5" for="category">Kategori / Developer</label>
                <input id="category" type="text" name="category" value="{{ old('category', $brand->category ?: 'Games') }}" required class="input-field w-full">
                @error('category') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5" for="sort_order">Urutan</label>
                <input id="sort_order" type="number" name="sort_order" min="0" value="{{ old('sort_order', $brand->sort_order) }}" class="input-field w-full">
                @error('sort_order') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5" for="thumbnail">Gambar Game</label>
                <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/jpg" class="input-field w-full" style="padding:.45rem .65rem">
                <p style="color:var(--text-dim);font-size:.72rem;margin-top:.35rem">JPG atau PNG, maksimal 2 MB.</p>
                @error('thumbnail') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-5 flex items-center gap-4">
            <div class="brand-edit-preview">
                @if($brand->thumbnail_url)
                    <img id="thumbnailPreview" src="{{ $brand->thumbnail_url }}" alt="Thumbnail {{ $brand->name }}">
                @else
                    <i id="thumbnailFallback" class="fas fa-gamepad"></i>
                    <img id="thumbnailPreview" class="hidden" src="" alt="Preview gambar game">
                @endif
            </div>
            <div>
                <p class="font-medium text-sm">Thumbnail saat ini</p>
                <p style="color:var(--text-dim);font-size:.76rem;margin-top:.2rem">Gambar ini digunakan pada daftar game dan halaman top up.</p>
            </div>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium mb-1.5" for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3" class="input-field w-full" placeholder="Deskripsi singkat game">{{ old('description', $brand->description) }}</textarea>
            @error('description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.products') }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Edit</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.brand-edit-preview{display:grid;place-items:center;width:78px;height:78px;overflow:hidden;flex:0 0 78px;border:1px solid var(--glass-border);border-radius:15px;background:linear-gradient(135deg,rgba(124,58,237,.35),rgba(14,165,233,.18));color:#c4b5fd;font-size:1.5rem}.brand-edit-preview img{width:100%;height:100%;object-fit:cover}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('thumbnail')?.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    const preview = document.getElementById('thumbnailPreview');
    const fallback = document.getElementById('thumbnailFallback');
    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
    fallback?.classList.add('hidden');
});
</script>
@endpush
