@extends('admin.layouts.app')

@section('title', 'Artikel SEO')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-lg font-semibold">Artikel SEO</h2>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Kelola konten organik yang tampil di website.</p>
    </div>
    <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Artikel
    </a>
</div>

<form method="GET" action="{{ route('admin.articles.index') }}" class="grid grid-cols-1 sm:grid-cols-[1fr_180px_auto] gap-3 mb-5">
    <div class="relative">
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2" style="color:var(--text-dim)"></i>
        <input type="search" name="q" value="{{ $search }}" class="input-field" style="padding-left:2.6rem" placeholder="Cari judul, kategori, atau keyword">
    </div>
    <select name="status" class="input-field">
        <option value="">Semua status</option>
        <option value="published" @selected($status === 'published')>Dipublikasikan</option>
        <option value="draft" @selected($status === 'draft')>Draft</option>
    </select>
    <div class="flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('admin.articles.index') }}" class="btn btn-ghost" title="Reset"><i class="fas fa-rotate-left"></i></a>
    </div>
</form>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Artikel</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Terbit</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td>
                        <div class="flex items-center gap-3 min-w-[280px]">
                            <div class="w-16 h-12 rounded-lg overflow-hidden flex-shrink-0" style="background:var(--bg-input)">
                                @if($article->image_url)
                                    <img src="{{ $article->image_url }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center" style="color:var(--text-dim)"><i class="fas fa-newspaper"></i></div>
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold">{{ $article->title }}</div>
                                <div class="text-xs mt-1" style="color:var(--text-dim)">/{{ $article->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $article->category ?: '-' }}</td>
                    <td>
                        @if($article->status === 'published')
                            <span class="badge badge-success">Published</span>
                        @else
                            <span class="badge badge-neutral">Draft</span>
                        @endif
                        @if($article->is_featured)
                            <span class="badge badge-warning ml-1">Unggulan</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap">{{ $article->published_at?->format('d M Y, H:i') ?: '-' }}</td>
                    <td>
                        <div class="flex items-center justify-center gap-2">
                            @if($article->status === 'published')
                                <a href="{{ route('articles.show', $article->slug) }}" target="_blank" class="btn btn-ghost" title="Lihat artikel"><i class="fas fa-arrow-up-right-from-square"></i></a>
                            @endif
                            <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-ghost" title="Edit"><i class="fas fa-pen"></i></a>
                            <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Hapus artikel ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding:3rem;color:var(--text-muted)">
                        <i class="fas fa-newspaper text-3xl mb-3 block" style="color:var(--text-dim)"></i>
                        Belum ada artikel. Tambahkan artikel pertama Anda.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-5">{{ $articles->links('vendor.pagination.admin') }}</div>
@endsection
