@extends('admin.layouts.app')

@section('title', 'Gambar Card Jual Beli Akun')

@push('styles')
<style>
    .jba-card-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 310px), 1fr)); gap: 1rem; }
    .jba-card-admin-item { padding: 1rem; border: 1px solid var(--glass-border); border-radius: 16px; background: var(--bg-card); }
    .jba-card-admin-head { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; margin-bottom: .85rem; }
    .jba-card-admin-preview { position: relative; width: min(100%, 190px); aspect-ratio: 3 / 4; margin: 0 auto 1rem; overflow: hidden; border: 1px solid var(--glass-border); border-radius: 13px; background: var(--bg-input); }
    .jba-card-admin-preview img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .jba-card-admin-preview [hidden] { display: none; }
    .jba-card-admin-preview-fallback { position: absolute; inset: 0; display: grid; place-content: center; gap: .4rem; text-align: center; color: var(--text-dim); font-size: .75rem; }
    .jba-card-admin-preview-fallback i { font-size: 1.5rem; }
    .jba-card-admin-preview::after { content: ''; position: absolute; inset: 45% 0 0; background: linear-gradient(transparent, rgba(0,0,0,.8)); pointer-events: none; }
    .jba-card-admin-preview-name { position: absolute; z-index: 1; right: .7rem; bottom: .7rem; left: .7rem; color: #fff; font-size: .85rem; font-weight: 700; }
    .jba-card-admin-actions { display: flex; align-items: center; gap: .5rem; margin-top: .8rem; }
    .jba-card-admin-actions .btn-primary { flex: 1; justify-content: center; }
    .jba-card-admin-item .admin-image-dropzone { width: 100%; }
</style>
@endpush

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-lg font-semibold">Gambar Card Jual Beli Akun</h2>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Atur foto kartu game di halaman Jual Beli Akun. Gambar Top Up tidak ikut berubah.</p>
    </div>
    <a href="{{ route('admin.account-listings') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Kembali ke Listing</a>
</div>

@error('jba_card_image')
    <p class="text-red-400 text-sm mb-4" role="alert">{{ $message }}</p>
@enderror

@if($brands->isEmpty())
    <div class="card-glass p-6">
        <p style="color:var(--text-muted)">Belum ada game populer. Aktifkan game populer terlebih dahulu agar kartu Jual Beli Akun muncul di sini.</p>
    </div>
@else
    <div class="jba-card-admin-grid">
        @foreach($brands as $brand)
            <article class="jba-card-admin-item">
                <div class="jba-card-admin-head">
                    <h3 class="font-semibold">{{ $brand->name }}</h3>
                    <span class="badge {{ $brand->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $brand->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>

                <div class="jba-card-admin-preview">
                    <img src="{{ $brand->jba_card_image_url ?? '' }}" alt="Preview kartu {{ $brand->name }}" @if(!$brand->jba_card_image_url) hidden @endif>
                    <div class="jba-card-admin-preview-fallback" @if($brand->jba_card_image_url) hidden @endif>
                        <i class="fas fa-image" aria-hidden="true"></i>
                        <span>Belum ada gambar</span>
                    </div>
                    <span class="jba-card-admin-preview-name">{{ $brand->name }}</span>
                </div>

                <form action="{{ route('admin.jba-game-cards.update', $brand) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-medium mb-1.5" for="jba-card-image-{{ $brand->id }}">{{ $brand->jba_card_image ? 'Ganti gambar kartu' : 'Tambah gambar kartu' }}</label>
                    <input id="jba-card-image-{{ $brand->id }}" type="file" name="jba_card_image" accept="image/jpeg,image/png,image/webp" required class="input-field w-full" style="padding:.45rem .65rem">
                    <p class="text-xs mt-2" style="color:var(--text-dim)">JPG, PNG, atau WebP, maksimal 5 MB. Disarankan foto potret rasio 3:4.</p>
                    <div class="jba-card-admin-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Simpan Gambar</button>
                    </div>
                </form>

                @if($brand->jba_card_image)
                    <form action="{{ route('admin.jba-game-cards.destroy', $brand) }}" method="POST" class="mt-2" onsubmit="return confirm('Hapus gambar kartu Jual Beli Akun untuk {{ $brand->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost w-full" style="justify-content:center;color:#ef4444"><i class="fas fa-trash"></i> Hapus Gambar</button>
                    </form>
                @endif
            </article>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.jba-card-admin-item input[type="file"]').forEach(input => {
    let previewUrl;
    input.addEventListener('change', () => {
        const frame = input.closest('.jba-card-admin-item').querySelector('.jba-card-admin-preview');
        const image = frame.querySelector('img');
        const fallback = frame.querySelector('.jba-card-admin-preview-fallback');
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        if (!input.files.length) return;
        previewUrl = URL.createObjectURL(input.files[0]);
        image.src = previewUrl;
        image.hidden = false;
        fallback.hidden = true;
    });
});
</script>
@endpush
