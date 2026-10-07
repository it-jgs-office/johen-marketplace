@extends('layouts.topup')

@section('title', $listing->product_name . ' - Jual Beli Akun - ' . config('app.name'))

@section('content')
<div class="jba-detail-page">
  <div class="jba-detail-wrap">
    <div class="jba-detail-gallery {{ $listing->is_sold ? 'jba-detail-gallery--sold' : '' }}">
      <a href="{{ $gameSlug ? route('jual-beli-akun.game', $gameSlug) : route('jual-beli-akun') }}" class="jba-back-btn" title="Kembali">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Kembali
      </a>
      <div class="jba-detail-main-photo" id="jbaZoomContainer">
        @if($listing->photo_url)
          <img id="jbaMainPhoto" src="{{ $listing->photo_url }}" alt="{{ $listing->product_name }}" class="jba-zoom-img {{ $listing->is_sold ? 'jba-img-sold' : '' }}">
          <div class="jba-zoom-hint">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>
            Klik untuk memperbesar
          </div>
        @else
          <div class="jba-detail-photo-fallback">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          </div>
        @endif
        @if($listing->youtube_video_id)
        <div class="jba-detail-video" id="jbaVideoContainer" style="display:none">
          <div class="jba-detail-video-wrap">
            <iframe src="https://www.youtube.com/embed/{{ $listing->youtube_video_id }}" frameborder="0" allowfullscreen></iframe>
          </div>
        </div>
        @endif
        @if($listing->is_sold)
          <div class="jba-ribbon jba-ribbon--sold" style="font-size:.85rem;padding:4px 44px;top:20px;left:-36px">SOLD</div>
        @endif
      </div>
      @if($listing->photo_url || count($listing->detail_photo_urls) > 0 || $listing->youtube_video_id)
      <div class="jba-detail-thumbs">
        @if($listing->youtube_video_id)
          <button class="jba-thumb jba-thumb--video active-video" onclick="showVideo(this)" title="Video preview">
            <img src="https://img.youtube.com/vi/{{ $listing->youtube_video_id }}/hqdefault.jpg" alt="Video">
            <span class="jba-thumb-play">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </span>
          </button>
        @endif
        @if($listing->photo_url)
          <button class="jba-thumb{{ $listing->youtube_video_id ? '' : ' active' }}" onclick="changeMainPhoto(this, '{{ $listing->photo_url }}')" title="Foto utama">
            <img src="{{ $listing->photo_url }}" alt="main">
          </button>
        @endif
        @foreach($listing->detail_photo_urls as $i => $url)
          <button class="jba-thumb" onclick="changeMainPhoto(this, '{{ $url }}')" title="Foto {{ $i+1 }}">
            <img src="{{ $url }}" alt="detail {{ $i+1 }}">
          </button>
        @endforeach
      </div>
      @endif
    </div>

    <div class="jba-lightbox" id="jbaLightbox">
      <button class="jba-lightbox-close" id="jbaLightboxClose">&times;</button>
      <div class="jba-lightbox-content">
        <img id="jbaLightboxImg" src="" alt="">
      </div>
      <div class="jba-lightbox-zoom-level" id="jbaZoomLevel">1x</div>
    </div>

    <div class="jba-detail-info">
      <div class="jba-detail-game">{{ $listing->game }}</div>
      <h1 class="jba-detail-title">{{ $listing->product_name }}</h1>
      @if($listing->owner_name)
        <div class="jba-detail-owner">{{ $listing->owner_name }}</div>
      @endif
      <div class="jba-detail-prices">
        @if($listing->original_price)
          <span class="jba-detail-original">Rp {{ number_format($listing->original_price, 0, ',', '.') }}</span>
        @endif
        <span class="jba-detail-price">Rp {{ number_format($listing->price, 0, ',', '.') }}</span>
      </div>

      <div class="jba-detail-specs">
        <h3>Spesifikasi Produk</h3>
        <div class="jba-detail-specs-content">
          {!! $listing->specifications !!}
        </div>
      </div>

      <div class="jba-detail-actions">
        @if($listing->is_sold)
          <div class="jba-sold-notice">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            Produk ini sudah terjual
          </div>
        @else
          <a href="{{ route('jual-beli-akun.checkout', $listing) }}" class="jba-btn jba-btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            Pesan Sekarang
          </a>
          @if($listing->whatsapp)
          <a href="https://wa.me/{{ $listing->whatsapp }}?text=Halo%20saya%20tertarik%20dengan%20{{ urlencode($listing->product_name) }}" target="_blank" class="jba-btn jba-btn-wa">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Hubungi Admin
          </a>
          @else
          <button class="jba-btn jba-btn-wa" onclick="alert('Nomor WhatsApp tidak tersedia untuk listing ini.')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Hubungi Admin
          </button>
          @endif
        @endif
      </div>
    </div>
  </div>

  <div class="jba-transfer-info">
    <div class="jba-transfer-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 9v6"/><path d="M9 12h6"/></svg>
    </div>
    <div class="jba-transfer-text">
      <strong>Informasi Transfer</strong>
      <span>Pembayaran hanya melalui transfer ke rekening <strong>BRI 114101707070565 a/n JOSIA HENDRICO SIMANUNGKALIT</strong>. Kami tidak pernah meminta transfer ke rekening lain.</span>
    </div>
  </div>

  @if($related->count() > 0)
  <section class="jba-related">
    <h2>Akun {{ $listing->game }} Lainnya</h2>
    <div class="jba-grid">
      @foreach($related as $item)
      <a href="{{ $item->is_sold ? '#' : route('jual-beli-akun.detail', $item) }}" class="jba-card {{ $item->is_sold ? 'jba-card--sold' : '' }}" {{ $item->is_sold ? 'tabindex=-1 aria-disabled=true' : '' }}>
        <div class="jba-card-thumb">
          @if($item->photo_url)
            <img src="{{ $item->photo_url }}" alt="{{ $item->product_name }}" class="{{ $item->is_sold ? 'jba-img-sold' : '' }}">
          @else
            <div class="jba-card-thumb-fallback">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </div>
          @endif
          @if($item->is_sold)
            <div class="jba-sold-badge">SOLD</div>
          @elseif($item->promo_type && $item->promo_type !== 'none')
            <div class="jba-ribbon">
              @if($item->promo_type === 'diskon' && $item->discount_percent)
                -{{ $item->discount_percent }}%
              @elseif($item->promo_type === 'promo')
                Promo
              @elseif($item->promo_type === 'flash_sale')
                Flash Sale
              @elseif($item->promo_type === 'best_seller')
                Best Seller
              @elseif($item->promo_type === 'hot')
                Hot
              @elseif($item->promo_type === 'new')
                New
              @elseif($item->promo_type === 'limited')
                Limited
              @endif
            </div>
          @endif
        </div>
        <div class="jba-card-body">
          <div class="jba-card-game">{{ $item->game }}</div>
          <h3 class="jba-card-title">{{ $item->product_name }}</h3>
          @if($item->owner_name)
            <div class="jba-card-owner">{{ $item->owner_name }}</div>
          @endif
          <div class="jba-card-prices">
            @if($item->original_price)
              <span class="jba-card-original">Rp {{ number_format($item->original_price, 0, ',', '.') }}</span>
            @endif
            <div class="jba-card-price-row">
              <span class="jba-card-price">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
              @if($item->original_price > $item->price)
                <?php $save = $item->original_price - $item->price; ?>
                <span class="jba-card-save">Hemat @if($save >= 1000000000) {{ rtrim(rtrim(number_format($save/1000000000, 1, ',', '.'), '0'), ',') }}M @elseif($save >= 1000000) {{ rtrim(rtrim(number_format($save/1000000, 1, ',', '.'), '0'), ',') }}jt @elseif($save >= 1000) {{ rtrim(rtrim(number_format($save/1000, 1, ',', '.'), '0'), ',') }}rb @else {{ number_format($save, 0, ',', '.') }} @endif</span>
              @endif
            </div>
          </div>
        </div>
      </a>
      @endforeach
    </div>
  </section>
  @endif
</div>

<style>
:root {
  --jba-accent: #2563eb;
}
.jba-detail-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem 4rem;
}
.jba-detail-wrap {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  align-items: start;
}
.jba-detail-gallery {
  position: sticky;
  top: 2rem;
}
.jba-back-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .5rem 1rem;
  margin-bottom: .75rem;
  border-radius: 10px;
  background: var(--bg-card);
  border: 1.5px solid var(--glass-border);
  color: var(--text);
  font-size: .85rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: background .2s, border-color .2s, transform .15s;
}
.jba-back-btn:hover {
  background: var(--bg-soft);
  border-color: var(--jba-accent);
  transform: translateX(-3px);
}
@media (max-width: 768px) {
  .jba-detail-wrap { grid-template-columns: 1fr; }
  .jba-detail-gallery { position: static; }
  .jba-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .jba-grid { grid-template-columns: repeat(2, 1fr); }
  .jba-card-body { padding: .6rem .7rem; }
  .jba-card-title { font-size: .78rem; }
  .jba-card-price { font-size: .82rem; }
  .jba-card-original { font-size: .68rem; }
}
.jba-detail-main-photo {
  width: 100%;
  border-radius: 16px;
  overflow: hidden;
  background: var(--bg-soft);
  border: 1px solid var(--glass-border);
  position: relative;
  cursor: zoom-in;
  display: flex;
  align-items: center;
  justify-content: center;
}
.jba-detail-main-photo img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: contain;
  transition: transform .3s ease;
}
.jba-detail-main-photo:hover img {
  transform: scale(1.03);
}
.jba-zoom-hint {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: rgba(0,0,0,.65);
  color: #fff;
  font-size: .72rem;
  padding: 4px 10px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 4px;
  pointer-events: none;
  backdrop-filter: blur(4px);
}
.jba-lightbox {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0,0,0,.92);
  backdrop-filter: blur(8px);
  align-items: center;
  justify-content: center;
}
.jba-lightbox.active {
  display: flex;
}
.jba-lightbox-close {
  position: absolute;
  top: 20px;
  right: 28px;
  background: none;
  border: none;
  color: #fff;
  font-size: 2.4rem;
  cursor: pointer;
  z-index: 10;
  line-height: 1;
  opacity: .7;
  transition: opacity .2s;
}
.jba-lightbox-close:hover {
  opacity: 1;
}
.jba-lightbox-content {
  max-width: 90vw;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}
.jba-lightbox-content img {
  max-width: 90vw;
  max-height: 90vh;
  object-fit: contain;
  cursor: zoom-in;
  transition: transform .25s ease;
  user-select: none;
  -webkit-user-drag: none;
}
.jba-lightbox-content img.zoomed {
  cursor: grab;
}
.jba-lightbox-content img.zoomed:active {
  cursor: grabbing;
}
.jba-lightbox-zoom-level {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255,255,255,.15);
  color: #fff;
  font-size: .82rem;
  padding: 4px 14px;
  border-radius: 20px;
  backdrop-filter: blur(4px);
  pointer-events: none;
}
.jba-thumb--video {
  position: relative;
  background: #111;
}
.jba-thumb--video img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: .75;
}
.jba-thumb--video.active-video {
  border-color: #ff0000;
}
.jba-thumb--video.active-video img {
  opacity: 1;
}
.jba-thumb-play {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}
.jba-thumb-play svg {
  filter: drop-shadow(0 2px 6px rgba(0,0,0,.5));
}
.jba-thumb--video::after {
  content: 'VIDEO';
  position: absolute;
  bottom: 3px;
  right: 3px;
  background: rgba(0,0,0,.75);
  color: #fff;
  font-size: .55rem;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 3px;
  letter-spacing: .04em;
  pointer-events: none;
}
.jba-detail-video {
  position: relative;
  z-index: 2;
  width: 100%;
}
.jba-detail-video-wrap {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 16px;
  overflow: hidden;
  background: #000;
}
.jba-detail-video-wrap iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
.jba-detail-photo-fallback {
  width: 100%;
  min-height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #555;
}
.jba-detail-thumbs {
  display: flex;
  gap: .5rem;
  margin-top: .75rem;
  flex-wrap: wrap;
}
.jba-thumb {
  width: 88px;
  height: 64px;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid transparent;
  background: #111;
  cursor: pointer;
  padding: 0;
  transition: border-color .2s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.jba-thumb.active {
  border-color: var(--jba-accent);
}
.jba-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.jba-detail-info {}
.jba-detail-game {
  font-size: .8rem;
  color: var(--jba-accent);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .03em;
  margin-bottom: .3rem;
}
.jba-detail-title {
  font-size: 1.5rem;
  font-weight: 800;
  margin-bottom: .2rem;
  line-height: 1.2;
}
.jba-detail-owner {
  font-size: .92rem;
  color: var(--text-dim);
  font-weight: 500;
  margin-bottom: .6rem;
}
.jba-detail-prices {
  display: flex;
  align-items: center;
  gap: .65rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.jba-detail-original {
  font-size: 1.2rem;
  color: #ef4444;
  text-decoration: line-through;
  opacity: .65;
}
.jba-detail-price {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--jba-accent);
}
.jba-detail-specs {
  margin-bottom: 1.5rem;
}
.jba-detail-specs h3 {
  font-size: .95rem;
  font-weight: 700;
  margin-bottom: -1rem;
}
.jba-detail-specs-content {
  font-size: .88rem;
  color: var(--text-dim);
  line-height: 1.7;
  white-space: pre-line;
  text-align: justify;
}
.jba-detail-actions {
  display: flex;
  gap: .75rem;
  flex-wrap: wrap;
}
@media (max-width: 640px) {
  .jba-detail-actions {
    flex-wrap: nowrap;
  }
  .jba-detail-actions .jba-btn,
  .jba-detail-actions .jba-sold-notice {
    flex: 1;
    min-width: 0;
    justify-content: center;
    font-size: .82rem;
    padding: .65rem .8rem;
  }
}
.jba-btn {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: .92rem;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: opacity .2s, transform .15s;
}
.jba-btn:hover {
  opacity: .9;
  transform: translateY(-1px);
}
.jba-btn-primary {
  background: var(--jba-accent);
  color: #fff;
}
.jba-btn-wa {
  background: #25d366;
  color: #fff;
}
.jba-sold-notice {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: .92rem;
  background: rgba(239,68,68,.1);
  border: 1.5px solid rgba(239,68,68,.35);
  color: #ef4444;
  width: 100%;
}
.jba-detail-gallery--sold {
  opacity: .85;
}
.jba-detail-gallery--sold .jba-detail-main-photo {
  position: relative;
}
.jba-detail-gallery--sold .jba-detail-main-photo::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,.4);
  z-index: 1;
  border-radius: 16px;
}
.jba-detail-gallery--sold .jba-zoom-hint {
  display: none;
}
.jba-transfer-info {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .85rem 1rem;
  margin: 2rem 0 .5rem;
  border-radius: 12px;
  background: rgba(37, 99, 235, .08);
  border: 1px solid rgba(37, 99, 235, .25);
  animation: jbaGlowPulse 2s ease-in-out infinite;
}
@keyframes jbaGlowPulse {
  0%, 100% { border-color: rgba(37, 99, 235, .25); box-shadow: 0 0 6px rgba(37, 99, 235, .08); }
  50% { border-color: rgba(37, 99, 235, .7); box-shadow: 0 0 14px rgba(37, 99, 235, .25); }
}
.jba-transfer-icon {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(37, 99, 235, .15);
  color: var(--jba-accent);
}
.jba-transfer-text {
  font-size: .8rem;
  color: var(--text-dim);
  line-height: 1.5;
}
.jba-transfer-text strong {
  color: var(--text);
}
.jba-related {
  margin-top: .75rem;
  padding-top: 1rem;
  border-top: 1px solid var(--glass-border);
}
.jba-related h2 {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 1rem;
}
.jba-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  align-items: start;
}
@media (max-width: 768px) {
  .jba-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .jba-grid { grid-template-columns: repeat(2, 1fr); gap: .7rem; }
}
.jba-card {
  background: var(--bg-card);
  border: 1.5px solid rgba(157, 92, 245, .25);
  border-radius: 10px;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s, border-color .2s;
  text-decoration: none;
  color: inherit;
}
.jba-card:hover {
  transform: translateY(-3px);
  border-color: var(--jba-accent);
  box-shadow: 0 0 20px -4px rgba(157, 92, 245, .5), 0 8px 24px -6px rgba(0,0,0,.35);
}
.jba-card-thumb {
  width: 100%;
  overflow: hidden;
  background: var(--bg-soft);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}
.jba-ribbon {
  position: absolute;
  top: 14px;
  left: -30px;
  background: linear-gradient(135deg, #E53935 0%, #c62828 100%);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 2px 36px;
  z-index: 2;
  text-align: center;
  text-transform: uppercase;
  white-space: nowrap;
  letter-spacing: 0.04em;
  line-height: 1.6;
  transform: rotate(-45deg);
  box-shadow: 0 3px 10px rgba(0,0,0,0.3);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  pointer-events: none;
}
.jba-card:hover .jba-ribbon {
  transform: rotate(-45deg) scale(1.06);
  box-shadow: 0 4px 16px rgba(229, 57, 53, 0.5);
}
.jba-ribbon::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  border: 5px solid #a82121;
  border-left-color: transparent;
  border-bottom-color: transparent;
}
.jba-ribbon::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  border: 5px solid #a82121;
  border-right-color: transparent;
  border-top-color: transparent;
}
.jba-card-thumb img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: contain;
}
.jba-card--sold {
  pointer-events: none;
  cursor: default;
  opacity: .75;
  position: relative;
}
.jba-card--sold .jba-card-game,
.jba-card--sold .jba-card-title,
.jba-card--sold .jba-card-owner {
  color: var(--text-dim) !important;
  opacity: .5;
}
.jba-card--sold .jba-card-prices * {
  filter: grayscale(1);
  opacity: .4;
}
.jba-card--sold .jba-card-thumb::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 1;
}
.jba-img-sold {
  filter: grayscale(1) brightness(.55);
}
.jba-ribbon--sold {
  background: linear-gradient(135deg, #424242 0%, #212121 100%);
}
.jba-ribbon--sold::before {
  border-color: transparent transparent #111 #111;
}
.jba-ribbon--sold::after {
  border-color: #111 #111 transparent transparent;
}
.jba-sold-badge {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-14deg);
  z-index: 3;
  width: 116px;
  height: 116px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ff6b6b;
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 1.12rem;
  letter-spacing: .16em;
  text-transform: uppercase;
  text-indent: .16em;
  text-shadow: 0 1px 3px rgba(0,0,0,.25);
  background: rgba(239,68,68,.13);
  border: 2px solid rgba(239,68,68,.55);
  backdrop-filter: blur(3px);
  box-shadow: 0 0 0 5px rgba(239,68,68,.1), inset 0 0 0 2px rgba(239,68,68,.18), 0 12px 28px -12px rgba(0,0,0,.35);
  white-space: nowrap;
  pointer-events: none;
}
.jba-sold-badge::before {
  content: '';
  position: absolute;
  inset: -7px;
  border-radius: 50%;
  border: 2px dashed rgba(239,68,68,.4);
}
.jba-card-thumb-fallback {
  width: 100%;
  min-height: 170px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #555;
}
.jba-card-body {
  padding: .75rem 1rem;
}
.jba-card-game {
  font-size: .6rem;
  color: var(--jba-accent);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: .04em;
  margin-bottom: .15rem;
  opacity: .7;
}
.jba-card-title {
  font-size: .85rem;
  font-weight: 600;
  margin-bottom: .2rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  color: var(--text);
}
.jba-card-owner {
  font-size: .75rem;
  color: var(--text-dim);
  font-weight: 400;
  margin-bottom: .2rem;
}
.jba-card-prices {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: .25rem;
}
.jba-card-original {
  font-size: .75rem;
  color: #f87171;
  text-decoration: line-through;
  opacity: .75;
  line-height: 1.2;
}
.jba-card-price-row {
  display: flex;
  align-items: center;
  gap: .45rem;
  flex-wrap: wrap;
}
.jba-card-price {
  font-size: .92rem;
  font-weight: 700;
  color: var(--jba-accent);
}
.jba-card-save {
  display: inline-flex;
  align-items: center;
  font-size: .64rem;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, #22c55e, #15803d);
  padding: .18rem .5rem;
  border-radius: 999px;
  letter-spacing: .02em;
  white-space: nowrap;
  box-shadow: 0 4px 10px -4px rgba(34, 197, 94, .6);
}
</style>

@push('scripts')
<script>
function changeMainPhoto(btn, url) {
  document.querySelectorAll('.jba-thumb').forEach(t => t.classList.remove('active', 'active-video'));
  btn.classList.add('active');
  document.getElementById('jbaMainPhoto').src = url;
  document.getElementById('jbaMainPhoto').style.display = '';
  document.getElementById('jbaZoomContainer').style.display = '';
  const video = document.getElementById('jbaVideoContainer');
  if (video) video.style.display = 'none';
  const hint = document.querySelector('.jba-zoom-hint');
  if (hint) hint.style.display = '';
}

function showVideo(btn) {
  document.querySelectorAll('.jba-thumb').forEach(t => t.classList.remove('active', 'active-video'));
  btn.classList.add('active-video');
  document.getElementById('jbaMainPhoto').style.display = 'none';
  const video = document.getElementById('jbaVideoContainer');
  if (video) video.style.display = '';
  const hint = document.querySelector('.jba-zoom-hint');
  if (hint) hint.style.display = 'none';
}

(function(){
  const container = document.getElementById('jbaZoomContainer');
  const mainImg = document.getElementById('jbaMainPhoto');
  const lightbox = document.getElementById('jbaLightbox');
  const lbImg = document.getElementById('jbaLightboxImg');
  const lbClose = document.getElementById('jbaLightboxClose');
  const zoomLevel = document.getElementById('jbaZoomLevel');

  if (!container || !mainImg) return;

  const zooms = [1, 2, 3, 5];
  let zoomIndex = 0;
  let isOpen = false;

  container.addEventListener('click', function() {
    if (!mainImg.src || mainImg.style.display === 'none') return;
    lbImg.src = mainImg.src;
    zoomIndex = 0;
    lbImg.style.transform = 'scale(1)';
    lbImg.style.cursor = 'zoom-in';
    lbImg.classList.remove('zoomed');
    zoomLevel.textContent = '1x';
    lightbox.classList.add('active');
    isOpen = true;
    document.body.style.overflow = 'hidden';
  });

  lbImg.addEventListener('click', function(e) {
    zoomIndex = (zoomIndex + 1) % zooms.length;
    const scale = zooms[zoomIndex];
    lbImg.style.transform = 'scale(' + scale + ')';
    lbImg.style.cursor = scale > 1 ? 'grab' : 'zoom-in';
    lbImg.classList.toggle('zoomed', scale > 1);
    zoomLevel.textContent = scale + 'x';
  });

  function closeLightbox() {
    lightbox.classList.remove('active');
    isOpen = false;
    document.body.style.overflow = '';
    lbImg.style.transform = 'scale(1)';
    lbImg.style.cursor = 'zoom-in';
    lbImg.classList.remove('zoomed');
  }

  lbClose.addEventListener('click', closeLightbox);
  lightbox.addEventListener('click', function(e) {
    if (e.target === lightbox) closeLightbox();
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && isOpen) closeLightbox();
  });

  /* auto-show video if it's the first/primary media */
  var firstThumb = document.querySelector('.jba-thumb--video.active-video');
  if (firstThumb) showVideo(firstThumb);
})();
</script>
@endpush
@endsection
