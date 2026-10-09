@extends('admin.layouts.app')

@section('title', 'Edit ' . $brand->name)

@section('content')
<div class="w-full max-w-none">
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
        <input type="hidden" name="category" value="{{ old('category', $brand->category ?: 'Games') }}">
        <input type="hidden" name="service_type" value="{{ old('service_type', $brand->service_type ?: 'topup') }}">
        <input type="hidden" name="catalog_group" value="{{ old('catalog_group', $brand->catalog_group ?: 'game') }}">
        <input type="hidden" name="is_active" value="{{ old('is_active', $brand->is_active ? '1' : '0') }}">
        <input type="hidden" name="is_popular" value="{{ old('is_popular', $brand->is_popular ? '1' : '0') }}">
        <input type="hidden" name="is_topup_popular" id="isTopupPopularValue" value="{{ old('is_topup_popular', $brand->is_topup_popular ? '1' : '0') }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1.5" for="name">Nama Game</label>
                <input id="name" type="text" name="name" value="{{ old('name', $brand->name) }}" required class="input-field w-full">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
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

        <section class="detail-cover-editor mt-6" aria-labelledby="detailCoverTitle">
            <div class="detail-cover-heading">
                <div>
                    <h3 id="detailCoverTitle" class="font-semibold text-sm">Sampul halaman detail Top Up</h3>
                    <p class="detail-cover-help">Gambar ini mengisi area background header di atas pilihan nominal. Gunakan gambar landscape agar kartu game tetap menjadi fokus utama.</p>
                </div>
                @if($brand->detail_bg_url)
                    <span class="detail-cover-status"><i class="fas fa-circle-check" aria-hidden="true"></i> Sampul aktif</span>
                @endif
            </div>

            <div class="detail-cover-controls">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="detail_bg">Upload sampul detail</label>
                    <input id="detail_bg" type="file" name="detail_bg" accept="image/jpeg,image/png,image/webp" class="input-field w-full" style="padding:.45rem .65rem">
                    <p class="detail-cover-help">JPG, PNG, atau WebP, maksimal 10 MB. Gambar akan dioptimalkan ke rasio 21:9.</p>
                    @error('detail_bg') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

                    @if($brand->detail_bg_url)
                        <label class="detail-cover-remove" for="remove_detail_bg">
                            <input id="remove_detail_bg" type="checkbox" name="remove_detail_bg" value="1" @checked(old('remove_detail_bg'))>
                            <span>Hapus sampul saat perubahan disimpan</span>
                        </label>
                    @endif
                </div>

                <div id="detailCoverPreviewWrap" class="detail-cover-preview-wrap" @if(!$brand->detail_bg_url) hidden @endif>
                    <p class="detail-cover-preview-title">Preview area header</p>
                    <div id="detailCoverPreview" class="detail-cover-preview" role="img" aria-label="Preview sampul halaman detail {{ $brand->name }}">
                        <div id="detailCoverPreviewImage" class="detail-cover-preview-image"
                             @if($brand->detail_bg_url) style="background-image:url('{{ $brand->detail_bg_url }}');background-position:{{ old('detail_bg_position', $brand->detail_bg_position ?: 'center') }}" @endif></div>
                        <span class="detail-cover-preview-overlay" aria-hidden="true"></span>
                        <span class="detail-cover-preview-card" aria-hidden="true">
                            @if($brand->thumbnail_url)
                                <img src="{{ $brand->thumbnail_url }}" alt="">
                            @else
                                <i class="fas fa-gamepad"></i>
                            @endif
                        </span>
                    </div>
                    <p class="detail-cover-drag-hint"><i class="fas fa-arrows-up-down-left-right" aria-hidden="true"></i> Seret preview untuk mengatur titik fokus gambar.</p>
                </div>
            </div>
            <input type="hidden" name="detail_bg_position" id="detailBgPosition" value="{{ old('detail_bg_position', $brand->detail_bg_position ?: 'center') }}">
        </section>

        <section class="character-3d-editor mt-6" aria-labelledby="character3dTitle">
            <div>
                <h3 id="character3dTitle" class="font-semibold text-sm">Karakter 3D untuk kartu Top Up</h3>
                <p class="character-3d-help">Tampilkan karakter sebagai foreground berlapis pada kartu game di halaman utama.</p>
            </div>
            <div class="character-3d-assets">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="topup_character_image">Gambar karakter 3D</label>
                    <input id="topup_character_image" type="file" name="topup_character_image" accept="image/png,image/webp" class="input-field w-full" style="padding:.45rem .65rem">
                    <p class="character-3d-help">PNG atau WebP transparan, maksimal 4 MB. Gunakan karakter berdiri/portrait dengan rasio sekitar 3:4 agar hasilnya proporsional.</p>
                    @error('topup_character_image') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div id="character3dPreviewWrap" class="character-3d-preview" @if(!$brand->topup_character_image_url) hidden @endif>
                    <img id="character3dPreview" src="{{ $brand->topup_character_image_url ?? '' }}" alt="Preview karakter 3D {{ $brand->name }}">
                    <span class="character-3d-preview-label">{{ $brand->topup_character_image_url ? 'Karakter saat ini' : 'Preview karakter' }}</span>
                </div>
            </div>
        </section>

        <section class="topup-popular-editor mt-6" aria-labelledby="topupPopularTitle">
            <div class="topup-popular-toggle-row">
                <div>
                    <h3 id="topupPopularTitle" class="font-semibold text-sm">Top Up Game Populer</h3>
                    <p class="topup-popular-help">Tampilkan game ini pada section populer di halaman utama.</p>
                </div>
                <label class="topup-popular-switch" for="topupPopularToggle">
                    <input type="checkbox" id="topupPopularToggle" @checked(old('is_topup_popular', $brand->is_topup_popular ? '1' : '0') === '1') aria-describedby="topupPopularTitle">
                    <span class="topup-popular-switch-track" aria-hidden="true"></span>
                    <span class="sr-only">Tampilkan sebagai Top Up Game Populer</span>
                </label>
            </div>

            <div id="topupPopularImageField" class="topup-popular-image-field" @if(!old('is_topup_popular', $brand->is_topup_popular ? '1' : '0')) hidden @endif>
                <div class="topup-popular-assets">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="topup_popular_image">Gambar card populer <span class="text-red-400">*</span></label>
                        <input id="topup_popular_image" type="file" name="topup_popular_image" accept="image/jpeg,image/png,image/webp" class="input-field w-full" style="padding:.45rem .65rem">
                        <p class="topup-popular-help">JPG, PNG, atau WebP, maksimal 4 MB. Disarankan gambar landscape (16:9); bagian kiri menampilkan artwork.</p>
                        @error('topup_popular_image') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <div id="topupPopularImagePreviewWrap" class="topup-popular-preview-wrap" @if(!$brand->topup_popular_image_url) hidden @endif>
                            <img id="topupPopularPreview" src="{{ $brand->topup_popular_image_url ?? '' }}" alt="Preview gambar card populer {{ $brand->name }}">
                            <span class="topup-popular-preview-label">{{ $brand->topup_popular_image_url ? 'Gambar card saat ini' : 'Preview gambar card' }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="topup_popular_logo">Logo di tengah card</label>
                        <input id="topup_popular_logo" type="file" name="topup_popular_logo" accept="image/png,image/webp" class="input-field w-full" style="padding:.45rem .65rem">
                        <p class="topup-popular-help">Opsional. Gunakan PNG atau WebP transparan, maksimal 2 MB. Logo akan muncul di tengah card.</p>
                        @error('topup_popular_logo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        <div id="topupPopularLogoPreviewWrap" class="topup-popular-preview-wrap topup-popular-logo-preview-wrap" @if(!$brand->topup_popular_logo_url) hidden @endif>
                            <img id="topupPopularLogoPreview" src="{{ $brand->topup_popular_logo_url ?? '' }}" alt="Preview logo card populer {{ $brand->name }}">
                            <span class="topup-popular-preview-label">{{ $brand->topup_popular_logo_url ? 'Logo saat ini' : 'Preview logo' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

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
.detail-cover-editor{padding:1rem;border:1px solid color-mix(in srgb,#0ea5e9 30%,var(--glass-border));border-radius:14px;background:linear-gradient(135deg,rgba(14,165,233,.08),rgba(37,99,235,.035))}.detail-cover-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}.detail-cover-help{color:var(--text-dim);font-size:.74rem;line-height:1.5;margin-top:.25rem}.detail-cover-status{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .55rem;border:1px solid rgba(52,211,153,.25);border-radius:999px;background:rgba(16,185,129,.1);color:#6ee7b7;font-size:.68rem;font-weight:700;white-space:nowrap}.detail-cover-controls{display:grid;grid-template-columns:minmax(240px,.7fr) minmax(360px,1.3fr);gap:1rem;align-items:start;margin-top:1rem}.detail-cover-preview-wrap[hidden]{display:none}.detail-cover-preview-title{margin-bottom:.4rem;color:var(--text-dim);font-size:.72rem;font-weight:600}.detail-cover-preview{position:relative;aspect-ratio:21/5;overflow:hidden;border:1px solid rgba(125,211,252,.38);border-radius:11px;background:#0b1327;cursor:grab;touch-action:none;user-select:none}.detail-cover-preview.is-dragging{cursor:grabbing}.detail-cover-preview-image{position:absolute;inset:-7%;background-repeat:no-repeat;background-size:cover;background-position:center;transition:filter .2s ease}.detail-cover-preview-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(10,8,24,.6),rgba(10,8,24,.18));pointer-events:none}.detail-cover-preview-card{position:absolute;bottom:-18px;left:50%;display:grid;place-items:center;width:68px;aspect-ratio:1;overflow:hidden;border:2px solid rgba(125,211,252,.7);border-radius:14px;background:#13203b;box-shadow:0 10px 22px rgba(0,0,0,.42);transform:translateX(-50%);pointer-events:none}.detail-cover-preview-card img{width:100%;height:100%;object-fit:cover}.detail-cover-preview-card i{color:#7dd3fc;font-size:1.25rem}.detail-cover-drag-hint{display:flex;align-items:center;gap:.35rem;margin-top:.45rem;color:var(--text-dim);font-size:.7rem}.detail-cover-remove{display:flex;align-items:center;gap:.5rem;width:max-content;margin-top:.85rem;color:#fca5a5;font-size:.75rem;cursor:pointer}.detail-cover-remove input{width:15px;height:15px;accent-color:#ef4444}.detail-cover-preview-wrap.is-marked-for-removal{opacity:.4;filter:grayscale(.8)}
.character-3d-editor{padding:1rem;border:1px solid color-mix(in srgb,#7c3aed 28%,var(--glass-border));border-radius:14px;background:linear-gradient(135deg,rgba(124,58,237,.09),rgba(14,165,233,.04))}.character-3d-help{color:var(--text-dim);font-size:.74rem;line-height:1.5;margin-top:.25rem}.character-3d-assets{display:grid;grid-template-columns:minmax(0,1fr) 170px;gap:1rem;align-items:center;margin-top:1rem}.character-3d-preview{position:relative;display:grid;place-items:center;min-height:190px;overflow:hidden;border:1px solid var(--glass-border);border-radius:12px;background:radial-gradient(circle at 50% 32%,rgba(118,91,255,.38),transparent 46%),linear-gradient(160deg,#18243c,#090f1e)}.character-3d-preview::after{content:"";position:absolute;inset:auto 0 0;height:42%;background:linear-gradient(transparent,rgba(4,8,18,.82));pointer-events:none}.character-3d-preview img{position:relative;z-index:1;width:100%;height:184px;object-fit:contain;object-position:center bottom;filter:drop-shadow(0 12px 12px rgba(0,0,0,.55))}.character-3d-preview-label{position:absolute;z-index:2;right:.5rem;bottom:.45rem;left:.5rem;color:#e7edff;font-size:.68rem;font-weight:700;text-align:center}.character-3d-preview[hidden]{display:none}
.topup-popular-editor{padding:1rem;border:1px solid var(--glass-border);border-radius:14px;background:rgba(255,255,255,.025)}.topup-popular-toggle-row{display:flex;align-items:center;justify-content:space-between;gap:1rem}.topup-popular-help{color:var(--text-dim);font-size:.74rem;line-height:1.5;margin-top:.25rem}.topup-popular-image-field{margin-top:1rem}.topup-popular-image-field[hidden],.topup-popular-preview-wrap[hidden]{display:none}.topup-popular-assets{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.topup-popular-switch{display:inline-flex;position:relative;flex:0 0 auto;cursor:pointer}.topup-popular-switch input{position:absolute;opacity:0;width:1px;height:1px}.topup-popular-switch-track{width:44px;height:25px;border-radius:999px;background:#475569;transition:background .2s;position:relative}.topup-popular-switch-track:after{content:"";position:absolute;width:19px;height:19px;left:3px;top:3px;border-radius:50%;background:#fff;transition:transform .2s}.topup-popular-switch input:checked+.topup-popular-switch-track{background:#7c3aed}.topup-popular-switch input:checked+.topup-popular-switch-track:after{transform:translateX(19px)}.topup-popular-switch input:focus-visible+.topup-popular-switch-track{outline:2px solid #c4b5fd;outline-offset:3px}.topup-popular-preview-wrap{display:flex;align-items:center;gap:.75rem;margin-top:.8rem}.topup-popular-preview-wrap img{width:min(100%,280px);aspect-ratio:16/9;object-fit:cover;border:1px solid var(--glass-border);border-radius:10px}.topup-popular-logo-preview-wrap img{width:120px;height:68px;aspect-ratio:auto;object-fit:contain;background:rgba(15,23,42,.5)}.topup-popular-preview-label{font-size:.75rem;color:var(--text-dim)}@media(max-width:640px){.topup-popular-assets{grid-template-columns:1fr}}
@media(max-width:800px){.detail-cover-controls{grid-template-columns:1fr}.detail-cover-preview{aspect-ratio:16/5}}
@media(max-width:640px){.detail-cover-heading{display:block}.detail-cover-status{margin-top:.65rem}.character-3d-assets{grid-template-columns:1fr}.character-3d-preview{max-width:190px}}
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

const detailCoverInput = document.getElementById('detail_bg');
const detailCoverWrap = document.getElementById('detailCoverPreviewWrap');
const detailCoverPreview = document.getElementById('detailCoverPreview');
const detailCoverImage = document.getElementById('detailCoverPreviewImage');
const detailCoverPosition = document.getElementById('detailBgPosition');
const detailCoverRemove = document.getElementById('remove_detail_bg');
let detailCoverObjectUrl = null;
let detailCoverX = 50;
let detailCoverY = 50;
let detailCoverDrag = null;

function parseDetailCoverPosition(value) {
    const positions = { center:[50,50], top:[50,0], bottom:[50,100], left:[0,50], right:[100,50] };
    if (positions[value]) return positions[value];
    const match = String(value || '').match(/^([\d.]+)%\s+([\d.]+)%$/);
    return match ? [Number(match[1]), Number(match[2])] : [50,50];
}

function renderDetailCoverPosition() {
    detailCoverX = Math.max(0, Math.min(100, detailCoverX));
    detailCoverY = Math.max(0, Math.min(100, detailCoverY));
    const value = `${detailCoverX.toFixed(1)}% ${detailCoverY.toFixed(1)}%`;
    detailCoverImage.style.backgroundPosition = value;
    detailCoverPosition.value = value;
}

if (detailCoverPosition) {
    [detailCoverX, detailCoverY] = parseDetailCoverPosition(detailCoverPosition.value);
    renderDetailCoverPosition();
}

detailCoverInput?.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    if (detailCoverObjectUrl) URL.revokeObjectURL(detailCoverObjectUrl);
    detailCoverObjectUrl = URL.createObjectURL(file);
    detailCoverImage.style.backgroundImage = `url("${detailCoverObjectUrl}")`;
    detailCoverX = 50;
    detailCoverY = 50;
    renderDetailCoverPosition();
    detailCoverWrap.hidden = false;
    detailCoverWrap.classList.remove('is-marked-for-removal');
    if (detailCoverRemove) detailCoverRemove.checked = false;
});

detailCoverRemove?.addEventListener('change', function () {
    detailCoverWrap.classList.toggle('is-marked-for-removal', this.checked);
});

detailCoverPreview?.addEventListener('pointerdown', function (event) {
    if (detailCoverRemove?.checked) return;
    detailCoverDrag = { pointerId:event.pointerId, x:event.clientX, y:event.clientY, coverX:detailCoverX, coverY:detailCoverY };
    this.setPointerCapture(event.pointerId);
    this.classList.add('is-dragging');
});

detailCoverPreview?.addEventListener('pointermove', function (event) {
    if (!detailCoverDrag || detailCoverDrag.pointerId !== event.pointerId) return;
    const rect = this.getBoundingClientRect();
    detailCoverX = detailCoverDrag.coverX + ((event.clientX - detailCoverDrag.x) / rect.width) * 100;
    detailCoverY = detailCoverDrag.coverY + ((event.clientY - detailCoverDrag.y) / rect.height) * 100;
    renderDetailCoverPosition();
});

function stopDetailCoverDrag(event) {
    if (!detailCoverDrag || detailCoverDrag.pointerId !== event.pointerId) return;
    detailCoverPreview.classList.remove('is-dragging');
    detailCoverDrag = null;
}

detailCoverPreview?.addEventListener('pointerup', stopDetailCoverDrag);
detailCoverPreview?.addEventListener('pointercancel', stopDetailCoverDrag);

document.getElementById('topup_character_image')?.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    const preview = document.getElementById('character3dPreview');
    const wrap = document.getElementById('character3dPreviewWrap');
    preview.src = URL.createObjectURL(file);
    wrap.hidden = false;
    wrap.querySelector('.character-3d-preview-label').textContent = 'Preview karakter baru';
});

const popularToggle = document.getElementById('topupPopularToggle');
const popularValue = document.getElementById('isTopupPopularValue');
const popularImageField = document.getElementById('topupPopularImageField');
const popularImageInput = document.getElementById('topup_popular_image');
const popularImagePreviewWrap = document.getElementById('topupPopularImagePreviewWrap');
const popularPreview = document.getElementById('topupPopularPreview');
const hasPopularImage = Boolean(popularPreview?.getAttribute('src'));
const popularLogoInput = document.getElementById('topup_popular_logo');
const popularLogoPreviewWrap = document.getElementById('topupPopularLogoPreviewWrap');
const popularLogoPreview = document.getElementById('topupPopularLogoPreview');

function syncPopularImageField() {
    const enabled = popularToggle.checked;
    popularValue.value = enabled ? '1' : '0';
    popularImageField.hidden = !enabled;
    popularImageInput.required = enabled && !hasPopularImage;
}

popularToggle?.addEventListener('change', syncPopularImageField);
syncPopularImageField();

popularImageInput?.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    popularPreview.src = URL.createObjectURL(file);
    popularImagePreviewWrap.hidden = false;
    popularImagePreviewWrap.querySelector('.topup-popular-preview-label').textContent = 'Preview gambar baru';
});

popularLogoInput?.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    popularLogoPreview.src = URL.createObjectURL(file);
    popularLogoPreviewWrap.hidden = false;
    popularLogoPreviewWrap.querySelector('.topup-popular-preview-label').textContent = 'Preview logo baru';
});
</script>
@endpush
