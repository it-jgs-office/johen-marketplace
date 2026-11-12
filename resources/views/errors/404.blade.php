@extends('layouts.topup')

@section('title', 'Halaman Tidak Ditemukan - ' . config('app.name'))
@section('meta_description', 'Halaman yang Anda cari tidak tersedia.')

@push('styles')
<style>
  .not-found-page{
    min-height:clamp(440px,68dvh,680px);
    display:flex;
    align-items:center;
    padding:clamp(2.5rem,8vw,6.5rem) var(--layout-gutter,1.5rem);
  }
  .not-found-shell{
    width:min(100%,860px);
    margin:0 auto;
    display:grid;
    grid-template-columns:minmax(150px,210px) minmax(0,1fr);
    align-items:center;
    gap:clamp(2rem,7vw,5.5rem);
    padding:clamp(2rem,5vw,3.5rem) 0;
    border-block:1px solid var(--border,rgba(255,255,255,.12));
  }
  .not-found-status{
    min-width:0;
    padding-right:clamp(2rem,5vw,4rem);
    border-right:1px solid var(--border,rgba(255,255,255,.12));
  }
  .not-found-code{
    display:block;
    margin:0;
    color:var(--text,#fff);
    font-family:var(--font-display,inherit);
    font-size:clamp(4.75rem,10vw,7rem);
    font-weight:800;
    letter-spacing:-.075em;
    line-height:.85;
  }
  .not-found-label{
    display:block;
    margin-top:1rem;
    color:var(--text-mute,var(--text-dim,#a8a3b8));
    font-size:.68rem;
    font-weight:700;
    letter-spacing:.15em;
    text-transform:uppercase;
  }
  .not-found-content{min-width:0}
  .not-found-content h1{
    max-width:520px;
    margin:0;
    color:var(--text,#fff);
    font-family:var(--font-display,inherit);
    font-size:clamp(1.65rem,4vw,2.4rem);
    font-weight:700;
    letter-spacing:-.035em;
    line-height:1.18;
  }
  .not-found-content p{
    max-width:520px;
    margin:.85rem 0 0;
    color:var(--text-dim,#a8a3b8);
    font-size:.92rem;
    line-height:1.7;
  }
  .not-found-actions{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:.7rem;
    margin-top:1.75rem;
  }
  .not-found-action{
    min-height:44px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.5rem;
    padding:.68rem 1rem;
    border-radius:10px;
    font:700 .8rem/1 var(--font-body,inherit);
    text-decoration:none;
    cursor:pointer;
    transition:background-color .18s ease,border-color .18s ease,color .18s ease;
  }
  .not-found-action svg{width:17px;height:17px;flex:0 0 auto}
  .not-found-home{
    border:1px solid #2563eb;
    background:#2563eb;
    color:#fff;
  }
  .not-found-home:hover,.not-found-home:focus-visible{background:#1d4ed8;border-color:#1d4ed8}
  .not-found-back{
    border:1px solid var(--border-strong,rgba(255,255,255,.2));
    background:transparent;
    color:var(--text-dim,#a8a3b8);
  }
  .not-found-back:hover,.not-found-back:focus-visible{
    border-color:var(--text-dim,#a8a3b8);
    color:var(--text,#fff);
  }
  .not-found-action:focus-visible{outline:3px solid rgba(37,99,235,.28);outline-offset:3px}
  @media(max-width:640px){
    .not-found-page{min-height:calc(100dvh - 136px);align-items:flex-start;padding-top:clamp(3.5rem,14vw,5.5rem);padding-bottom:calc(var(--mobile-nav-height,68px) + 2rem)}
    .not-found-shell{grid-template-columns:1fr;gap:1.75rem;padding:2rem 0}
    .not-found-status{display:flex;align-items:flex-end;gap:1rem;padding:0 0 1.6rem;border-right:0;border-bottom:1px solid var(--border,rgba(255,255,255,.12))}
    .not-found-code{font-size:clamp(4.2rem,22vw,5.75rem)}
    .not-found-label{margin:0 0 .15rem}
    .not-found-content h1{font-size:clamp(1.55rem,7vw,2rem)}
    .not-found-actions{align-items:stretch}
  }
  @media(max-width:420px){
    .not-found-actions{display:grid;grid-template-columns:1fr}
    .not-found-action{width:100%}
  }
  @media(prefers-reduced-motion:reduce){.not-found-action{transition:none}}
</style>
@endpush

@section('content')
<section class="not-found-page" aria-labelledby="not-found-title">
  <div class="not-found-shell">
    <div class="not-found-status" aria-hidden="true">
      <span class="not-found-code">404</span>
      <span class="not-found-label">Tidak ditemukan</span>
    </div>
    <div class="not-found-content">
      <h1 id="not-found-title">Tautan ini tidak tersedia.</h1>
      <p>Alamatnya mungkin sudah berubah atau tidak lagi digunakan. Periksa kembali tautannya, atau lanjutkan dari beranda.</p>
      <div class="not-found-actions">
        <a class="not-found-action not-found-home" href="{{ route('home') }}">
          Ke beranda
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <button class="not-found-action not-found-back" type="button" onclick="history.back()">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Kembali
        </button>
      </div>
    </div>
  </div>
</section>
@endsection
