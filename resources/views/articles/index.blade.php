@extends('layouts.topup')

@section('title', 'Artikel & Panduan Game - ' . config('app.name'))
@section('meta_description', 'Temukan panduan top up, tips game, keamanan akun, dan informasi terbaru seputar dunia gaming dari Johen Gaming.')
@section('canonical', route('articles.index'))

@push('styles')
<link rel="stylesheet" href="{{ pwa_asset('css/articles.css') }}&f={{ filemtime(public_path('css/articles.css')) }}">
@endpush

@section('content')
<div class="article-index-page">
    <header class="article-index-hero article-news-banner">
        <div class="article-index-hero__copy">
            <h1>Berita</h1>
            <p>Berita terkini seputar dunia games dan update dari PT. Johen Sukses Abadi (Johen Gaming)</p>
        </div>
    </header>

    @if($articles->count())
        <section class="article-card-grid" aria-label="Daftar artikel">
            @foreach($articles as $article)
                <article class="article-card {{ $loop->first && $articles->currentPage() === 1 ? 'article-card--lead' : '' }}">
                    <a href="{{ route('articles.show', $article->slug) }}" class="article-card__media" tabindex="-1" aria-hidden="true">
                        @if($article->image_url)
                            <img src="{{ $article->image_url }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                        @else
                            <span class="article-card__fallback"><i>J</i>OHEN JOURNAL</span>
                        @endif
                        @if($article->is_featured)<span class="article-card__featured">Pilihan Editor</span>@endif
                    </a>
                    <div class="article-card__body">
                        <div class="article-card__meta">
                            <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
                        </div>
                        <h2><a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a></h2>
                        <p>{{ $article->excerpt ?: Illuminate\Support\Str::limit(strip_tags($article->content), 150) }}</p>
                        <a class="article-card__read" href="{{ route('articles.show', $article->slug) }}">Baca {{ $article->reading_time }} menit <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="article-pagination">{{ $articles->onEachSide(1)->links() }}</div>
    @else
        <div class="article-empty">
            <span>J</span>
            <h2>Belum ada artikel</h2>
            <p>Artikel terbaru akan tampil di halaman ini.</p>
        </div>
    @endif
</div>
@endsection
