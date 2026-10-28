@php
    $currentStatus = old('status', $article->status ?: 'draft');
    $contentValue = old('content', $article->content);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-5">
    <div class="space-y-5">
        <section class="card-glass p-5 sm:p-6" style="border:1px solid var(--glass-border);border-radius:16px">
            <h2 class="font-bold text-base mb-5">Konten Artikel</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Judul Artikel *</label>
                    <input type="text" name="title" id="articleTitle" value="{{ old('title', $article->title) }}" class="input-field" maxlength="255" required placeholder="Contoh: Cara Top Up Mobile Legends Aman dan Cepat">
                    @error('title') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Slug URL</label>
                    <div class="flex items-center rounded-xl overflow-hidden" style="border:1px solid var(--border);background:var(--bg-input)">
                        <span class="px-3 text-xs whitespace-nowrap" style="color:var(--text-dim)">/artikel/</span>
                        <input type="text" name="slug" id="articleSlug" value="{{ old('slug', $article->slug) }}" class="flex-1 bg-transparent py-2.5 pr-3 outline-none text-sm" placeholder="otomatis-dari-judul">
                    </div>
                    @error('slug') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Ringkasan</label>
                    <textarea name="excerpt" rows="3" maxlength="500" class="input-field" placeholder="Ringkasan singkat untuk kartu artikel dan hasil pencarian.">{{ old('excerpt', $article->excerpt) }}</textarea>
                    @error('excerpt') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3 mb-1.5">
                        <label class="block text-sm font-medium">Isi Artikel *</label>
                        <span class="text-xs" style="color:var(--text-dim)">Gunakan heading agar struktur SEO rapi</span>
                    </div>
                    <div class="article-editor-shell">
                        <div class="article-editor-toolbar" role="toolbar" aria-label="Format artikel">
                            <button type="button" data-command="formatBlock" data-value="h2" title="Heading 2">H2</button>
                            <button type="button" data-command="formatBlock" data-value="h3" title="Heading 3">H3</button>
                            <button type="button" data-command="bold" title="Tebal"><i class="fas fa-bold"></i></button>
                            <button type="button" data-command="italic" title="Miring"><i class="fas fa-italic"></i></button>
                            <button type="button" data-command="insertUnorderedList" title="Daftar"><i class="fas fa-list-ul"></i></button>
                            <button type="button" data-command="insertOrderedList" title="Daftar nomor"><i class="fas fa-list-ol"></i></button>
                            <button type="button" data-command="formatBlock" data-value="blockquote" title="Kutipan"><i class="fas fa-quote-left"></i></button>
                            <button type="button" data-link title="Tautan"><i class="fas fa-link"></i></button>
                            <button type="button" data-command="removeFormat" title="Hapus format"><i class="fas fa-eraser"></i></button>
                        </div>
                        <div id="articleEditor" class="article-editor" contenteditable="true" data-placeholder="Tulis artikel lengkap di sini...">{!! $contentValue !!}</div>
                        <textarea name="content" id="articleContent" hidden>{{ $contentValue }}</textarea>
                    </div>
                    @error('content') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="card-glass p-5 sm:p-6" style="border:1px solid var(--glass-border);border-radius:16px">
            <div class="flex items-center gap-2 mb-1"><i class="fas fa-magnifying-glass-chart" style="color:var(--accent)"></i><h2 class="font-bold text-base">SEO Booster</h2></div>
            <p class="text-xs mb-5" style="color:var(--text-muted)">Optimalkan judul dan deskripsi yang tampil di hasil pencarian.</p>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between gap-3 mb-1.5"><label class="text-sm font-medium">Focus Keyword</label><span class="text-xs" style="color:var(--text-dim)">1 keyword utama</span></div>
                    <input type="text" name="focus_keyword" value="{{ old('focus_keyword', $article->focus_keyword) }}" class="input-field" maxlength="150" placeholder="top up mobile legends murah">
                    @error('focus_keyword') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex justify-between gap-3 mb-1.5"><label class="text-sm font-medium">Meta Title</label><span class="text-xs" id="metaTitleCount">0/70</span></div>
                    <input type="text" name="meta_title" id="metaTitle" value="{{ old('meta_title', $article->meta_title) }}" class="input-field" maxlength="70" placeholder="Kosongkan untuk memakai judul artikel">
                    @error('meta_title') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex justify-between gap-3 mb-1.5"><label class="text-sm font-medium">Meta Description</label><span class="text-xs" id="metaDescriptionCount">0/160</span></div>
                    <textarea name="meta_description" id="metaDescription" rows="3" class="input-field" maxlength="160" placeholder="Deskripsi singkat yang menarik untuk hasil pencarian.">{{ old('meta_description', $article->meta_description) }}</textarea>
                    @error('meta_description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="seo-preview">
                    <span>Pratinjau Google</span>
                    <strong id="seoPreviewTitle">{{ old('meta_title', $article->meta_title ?: $article->title ?: 'Judul artikel Anda') }}</strong>
                    <small>{{ url('/artikel') }}/<span id="seoPreviewSlug">{{ old('slug', $article->slug ?: 'slug-artikel') }}</span></small>
                    <p id="seoPreviewDescription">{{ old('meta_description', $article->meta_description ?: $article->excerpt ?: 'Deskripsi artikel akan tampil di bagian ini.') }}</p>
                </div>
            </div>
        </section>
    </div>

    <aside class="space-y-5">
        <section class="card-glass p-5" style="border:1px solid var(--glass-border);border-radius:16px">
            <h2 class="font-bold text-base mb-4">Publikasi</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Status *</label>
                    <select name="status" class="input-field" required>
                        <option value="draft" @selected($currentStatus === 'draft')>Draft</option>
                        <option value="published" @selected($currentStatus === 'published')>Publikasikan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Tanggal Terbit</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}" class="input-field">
                    <p class="text-xs mt-1" style="color:var(--text-dim)">Kosongkan untuk terbit sekarang.</p>
                </div>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="mt-1" style="accent-color:var(--accent)" @checked(old('is_featured', $article->is_featured))>
                    <span><strong class="block text-sm">Artikel unggulan</strong><small style="color:var(--text-dim)">Diprioritaskan di daftar artikel.</small></span>
                </label>
            </div>
        </section>

        <section class="card-glass p-5" style="border:1px solid var(--glass-border);border-radius:16px">
            <h2 class="font-bold text-base mb-4">Informasi</h2>
            <div>
                <label class="block text-sm font-medium mb-1.5">Kategori</label>
                <input type="text" name="category" value="{{ old('category', $article->category) }}" class="input-field" maxlength="100" placeholder="Tips Game">
                @error('category') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="card-glass p-5" style="border:1px solid var(--glass-border);border-radius:16px">
            <h2 class="font-bold text-base mb-4">Gambar Utama</h2>
            <div id="articleImagePreview" class="aspect-video rounded-xl overflow-hidden flex items-center justify-center mb-3" style="background:var(--bg-input);border:1px dashed var(--border);color:var(--text-dim)">
                @if($article->image_url)
                    <img src="{{ $article->image_url }}" alt="" class="w-full h-full object-cover">
                @else
                    <div class="text-center"><i class="fas fa-image text-2xl block mb-2"></i><span class="text-xs">Rasio 16:9 disarankan</span></div>
                @endif
            </div>
            <input type="file" name="featured_image" id="articleImageInput" accept="image/jpeg,image/png,image/webp" class="w-full text-xs" style="color:var(--text-muted)">
            <p class="text-xs mt-2" style="color:var(--text-dim)">JPG, PNG, atau WebP. Maksimal 5 MB.</p>
            @if($article->featured_image)
                <label class="flex items-center gap-2 mt-3 text-xs cursor-pointer"><input type="checkbox" name="remove_image" value="1" style="accent-color:var(--error)"> Hapus gambar saat ini</label>
            @endif
            @error('featured_image') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </section>

        <div class="flex gap-3 sticky bottom-4">
            <a href="{{ route('admin.articles.index') }}" class="btn btn-ghost flex-1 justify-center">Batal</a>
            <button type="submit" class="btn btn-primary flex-1 justify-center"><i class="fas fa-floppy-disk"></i> {{ $submitLabel }}</button>
        </div>
    </aside>
</div>

@push('styles')
<style>
.article-editor-shell{border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--bg-input)}
.article-editor-toolbar{display:flex;flex-wrap:wrap;gap:4px;padding:8px;border-bottom:1px solid var(--border);background:var(--glass-bg)}
.article-editor-toolbar button{height:32px;min-width:34px;padding:0 9px;border:1px solid transparent;border-radius:7px;background:transparent;color:var(--text-muted);font-size:12px;font-weight:700;cursor:pointer}
.article-editor-toolbar button:hover,.article-editor-toolbar button:focus{border-color:var(--accent);color:var(--text);outline:none}
.article-editor{min-height:430px;padding:18px;color:var(--text);font-size:14px;line-height:1.8;outline:none;overflow-wrap:anywhere}
.article-editor:empty:before{content:attr(data-placeholder);color:var(--text-dim)}
.article-editor h2{font-size:1.35rem;font-weight:800;margin:1.4rem 0 .55rem}.article-editor h3{font-size:1.1rem;font-weight:700;margin:1.2rem 0 .45rem}
.article-editor p{margin:.6rem 0}.article-editor ul,.article-editor ol{padding-left:1.5rem;margin:.7rem 0}.article-editor blockquote{border-left:3px solid var(--accent);padding-left:1rem;color:var(--text-muted)}
.seo-preview{padding:16px;border:1px solid var(--border);border-radius:12px;background:var(--bg-input)}
.seo-preview>span{display:block;color:var(--text-dim);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px}
.seo-preview strong{display:block;color:#8ab4f8;font-family:Arial,sans-serif;font-size:18px;font-weight:400;line-height:1.25}.seo-preview small{display:block;color:#7ac68c;margin:5px 0;font-size:12px}.seo-preview p{color:var(--text-muted);font-family:Arial,sans-serif;font-size:13px;line-height:1.45;margin:0}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const title = document.getElementById('articleTitle');
    const slug = document.getElementById('articleSlug');
    const editor = document.getElementById('articleEditor');
    const content = document.getElementById('articleContent');
    const metaTitle = document.getElementById('metaTitle');
    const metaDescription = document.getElementById('metaDescription');
    let slugEdited = Boolean(slug.value);

    const slugify = value => value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-|-$/g, '');
    const syncContent = () => { content.value = editor.innerHTML.trim(); };
    const refreshSeo = () => {
        document.getElementById('metaTitleCount').textContent = `${metaTitle.value.length}/70`;
        document.getElementById('metaDescriptionCount').textContent = `${metaDescription.value.length}/160`;
        document.getElementById('seoPreviewTitle').textContent = metaTitle.value || title.value || 'Judul artikel Anda';
        document.getElementById('seoPreviewSlug').textContent = slug.value || 'slug-artikel';
        document.getElementById('seoPreviewDescription').textContent = metaDescription.value || 'Deskripsi artikel akan tampil di bagian ini.';
    };

    title.addEventListener('input', () => {
        if (!slugEdited) slug.value = slugify(title.value);
        refreshSeo();
    });
    slug.addEventListener('input', () => { slugEdited = true; slug.value = slugify(slug.value); refreshSeo(); });
    metaTitle.addEventListener('input', refreshSeo);
    metaDescription.addEventListener('input', refreshSeo);
    editor.addEventListener('input', syncContent);
    editor.closest('form').addEventListener('submit', syncContent);

    document.querySelectorAll('.article-editor-toolbar [data-command]').forEach(button => {
        button.addEventListener('click', () => {
            editor.focus();
            document.execCommand(button.dataset.command, false, button.dataset.value || null);
            syncContent();
        });
    });
    document.querySelector('[data-link]').addEventListener('click', () => {
        const url = window.prompt('Masukkan URL tautan:');
        if (!url) return;
        editor.focus();
        document.execCommand('createLink', false, url);
        syncContent();
    });

    document.getElementById('articleImageInput').addEventListener('change', event => {
        const file = event.target.files[0];
        if (!file) return;
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.className = 'w-full h-full object-cover';
        const preview = document.getElementById('articleImagePreview');
        preview.replaceChildren(image);
    });

    syncContent();
    refreshSeo();
})();
</script>
@endpush
