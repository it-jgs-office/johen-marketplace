@extends('layouts.topup')

@php
    $seoTitle = $article->meta_title ?: $article->title;
    $seoDescription = $article->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 155));
    $articleUrl = route('articles.show', $article->slug);
    $documentation = collect($article->documentation ?? [])->filter(fn ($item) => is_array($item) && !empty($item['url']))->values();
@endphp

@section('title', $seoTitle . ' - ' . config('app.name'))
@section('meta_description', $seoDescription)
@section('canonical', $articleUrl)
@section('og_type', 'article')
@section('og_image', $article->image_url ?: asset('logo.png'))

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{ pwa_asset('css/articles.css') }}&f={{ filemtime(public_path('css/articles.css')) }}">
@endpush

@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->title,
    'description' => $seoDescription,
    'image' => $article->image_url ? [$article->image_url] : [],
    'datePublished' => $article->published_at?->toIso8601String(),
    'dateModified' => $article->updated_at?->toIso8601String(),
    'author' => ['@type' => 'Organization', 'name' => business_info()['name']],
    'publisher' => ['@type' => 'Organization', 'name' => business_info()['name'], 'logo' => ['@type' => 'ImageObject', 'url' => asset('logo.png')]],
    'mainEntityOfPage' => $articleUrl,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
<section class="hero-article" style="--article-hero-image:url('{{ $article->image_url ?: asset('logo.png') }}')">
    <div class="article-reference-container hero-article-container">
        <div class="hero-article-content">
            <span class="label-kategori">Johen News</span>
            <h1>{{ $article->title }}</h1>
            <div class="hero-article-meta">
                <span><i class="fas fa-calendar-alt" aria-hidden="true"></i> {{ $article->published_at?->translatedFormat('d M Y') }}</span>
                <span><i class="fas fa-pencil-alt" aria-hidden="true"></i> Johen Gaming Team</span>
                <span><i class="fas fa-clock" aria-hidden="true"></i> {{ $article->reading_time }} Menit Membaca</span>
            </div>
        </div>
    </div>
</section>

<section class="article-main-section">
    <div class="article-reference-container">
        <a href="{{ route('articles.index') }}" class="back-link">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali ke Berita
        </a>

        <div class="article-layout">
            <div class="article-body-wrap">
                @if($article->image_url)
                    <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="featured-img" fetchpriority="high">
                @else
                    <div class="featured-img-placeholder" aria-hidden="true">J</div>
                @endif

                <div class="article-body">{!! $article->content !!}</div>

                @if($article->quote)
                    <div class="blockquote-card">
                        <div class="quote-icon"><i class="fas fa-quote-left" aria-hidden="true"></i></div>
                        <blockquote>{{ trim($article->quote) }}</blockquote>
                    </div>
                @endif

                @if($article->source)
                    <div class="article-source">
                        <i class="fas fa-link" aria-hidden="true"></i>
                        <span>Sumber:
                            @if($article->source_url)
                                <a href="{{ $article->source_url }}" target="_blank" rel="noopener noreferrer">{{ trim($article->source) }}</a>
                            @else
                                {{ trim($article->source) }}
                            @endif
                        </span>
                    </div>
                @endif

                <div class="share-section">
                    <div class="share-label">Bagikan Artikel</div>
                    <div class="share-buttons">
                        <a href="https://wa.me/?text={{ urlencode($article->title.' '.$articleUrl) }}" target="_blank" rel="noopener" class="share-btn wa" aria-label="Bagikan ke WhatsApp"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
                        <a href="https://facebook.com/sharer/sharer.php?u={{ urlencode($articleUrl) }}" target="_blank" rel="noopener" class="share-btn fb" aria-label="Bagikan ke Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                        <a href="https://www.tiktok.com/" target="_blank" rel="noopener" class="share-btn tt" aria-label="Bagikan ke TikTok"><i class="fab fa-tiktok" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>

            <aside>
                <div class="sidebar-card">
                    <div class="sidebar-title">Berita Terbaru</div>
                    @forelse($latestArticles as $related)
                        <a href="{{ route('articles.show', $related->slug) }}" class="sidebar-item">
                            @if($related->image_url)
                                <img src="{{ $related->image_url }}" alt="{{ $related->title }}" class="thumb" loading="lazy">
                            @else
                                <div class="thumb" aria-hidden="true">J</div>
                            @endif
                            <div class="info">
                                <h4>{{ $related->title }}</h4>
                                <div class="date"><i class="fas fa-calendar-alt" aria-hidden="true"></i> {{ $related->published_at?->translatedFormat('d M Y') }}</div>
                            </div>
                        </a>
                    @empty
                        <p class="article-reference-empty">Belum ada berita lain.</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</section>

@if($documentation->isNotEmpty())
<section class="carousel-section">
    <div class="article-reference-container">
        <div class="carousel-header">
            <h2>Dokumentasi Kegiatan</h2>
            <div class="carousel-nav">
                <button type="button" data-carousel-target="documentationTrack" data-carousel-direction="-1" aria-label="Sebelumnya"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                <button type="button" data-carousel-target="documentationTrack" data-carousel-direction="1" aria-label="Selanjutnya"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="carousel-track-wrap">
            <div class="carousel-track" id="documentationTrack">
                @foreach($documentation as $item)
                    <div class="doku-card"><img src="{{ $item['url'] }}" alt="Dokumentasi {{ $loop->iteration }}" loading="lazy"></div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<section class="carousel-section article-more-section">
    <div class="article-reference-container">
        <div class="carousel-header">
            <h2>Berita Lainnya</h2>
            @if($relatedArticles->count() > 1)
                <div class="carousel-nav">
                    <button type="button" data-carousel-target="relatedArticleTrack" data-carousel-direction="-1" aria-label="Sebelumnya"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                    <button type="button" data-carousel-target="relatedArticleTrack" data-carousel-direction="1" aria-label="Selanjutnya"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                </div>
            @endif
        </div>
        <div class="carousel-track-wrap">
            <div class="carousel-track" id="relatedArticleTrack">
                @forelse($relatedArticles as $related)
                    <a href="{{ route('articles.show', $related->slug) }}" class="berita-carousel-card">
                        @if($related->image_url)<img src="{{ $related->image_url }}" alt="{{ $related->title }}" loading="lazy">@endif
                        <div class="overlay"></div>
                        <div class="info">
                            <h4>{{ $related->title }}</h4>
                            <div class="date"><i class="fas fa-calendar-alt" aria-hidden="true"></i> {{ $related->published_at?->translatedFormat('d M Y') }}</div>
                        </div>
                    </a>
                @empty
                    <p class="article-reference-empty">Belum ada berita lain.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-carousel-target]').forEach(button => {
    button.addEventListener('click', () => {
        const track = document.getElementById(button.dataset.carouselTarget);
        const firstItem = track?.firstElementChild;
        if (!track || !firstItem) return;
        const direction = Number(button.dataset.carouselDirection) || 1;
        track.scrollBy({ left: (firstItem.getBoundingClientRect().width + 20) * direction, behavior: 'smooth' });
    });
});
</script>
@endpush
