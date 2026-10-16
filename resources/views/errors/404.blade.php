@extends('layouts.topup')

@section('title', 'Halaman Tidak Ditemukan - ' . config('app.name'))

@push('styles')
<style>
  .not-found-page{min-height:clamp(480px,68vh,720px);display:grid;place-items:center;padding:3rem var(--layout-gutter,1.5rem);position:relative;overflow:hidden;isolation:isolate}
  .not-found-page::before{content:"";position:absolute;z-index:-2;width:min(78vw,800px);aspect-ratio:1;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,.28),rgba(124,58,237,.12) 38%,transparent 70%);filter:blur(10px)}
  .not-found-panel{width:min(100%,640px);text-align:center;padding:clamp(2rem,6vw,4.5rem) clamp(1.25rem,6vw,4rem);border:1px solid color-mix(in srgb,var(--purple-light,#9d5cf5) 25%,transparent);border-radius:28px;background:color-mix(in srgb,var(--surface,#17112b) 82%,transparent);box-shadow:0 30px 80px -35px rgba(0,0,0,.8);backdrop-filter:blur(12px)}
  .not-found-signal{display:grid;place-items:center;width:76px;height:76px;margin-bottom:1.15rem;border:1px solid rgba(96,165,250,.55);border-radius:22px;background:linear-gradient(135deg,rgba(37,99,235,.35),rgba(124,58,237,.25));box-shadow:0 0 0 9px rgba(59,130,246,.06),0 0 36px -8px rgba(59,130,246,.65);overflow:hidden}
  .not-found-signal img{width:54px;height:54px;object-fit:contain}
  .not-found-code{margin:0;font-family:var(--font-display,inherit);font-size:clamp(5rem,18vw,9.5rem);font-weight:800;letter-spacing:-.09em;line-height:.82;color:transparent;background:linear-gradient(135deg,#fff 12%,#93c5fd 48%,#a78bfa);background-clip:text;-webkit-background-clip:text}
  .not-found-panel h1{margin:1.25rem 0 .65rem;font-family:var(--font-display,inherit);font-size:clamp(1.4rem,4vw,2rem);color:var(--text,#fff)}
  .not-found-panel p{max-width:420px;margin:0 auto;color:var(--text-dim,#a8a3b8);line-height:1.7}
  .not-found-actions{display:flex;justify-content:center;flex-wrap:wrap;gap:.7rem;margin-top:1.65rem}
  .not-found-actions a{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;min-height:44px;padding:.7rem 1.1rem;border-radius:11px;font-weight:700;text-decoration:none}
  .not-found-home{color:#fff;background:linear-gradient(135deg,#2563eb,#7c3aed);box-shadow:0 12px 28px -13px rgba(59,130,246,.75)}
  .not-found-back{color:var(--text,#fff);border:1px solid var(--border-strong,rgba(255,255,255,.2));background:rgba(255,255,255,.04)}
  .not-found-actions a:hover{transform:translateY(-2px)}
  @media (prefers-reduced-motion:reduce){.not-found-actions a{transition:none}}
</style>
@endpush

@section('content')
<section class="not-found-page" aria-labelledby="not-found-title">
  <div class="not-found-panel">
    <div class="not-found-signal" aria-hidden="true">
      <img src="{{ $themeLogo }}" alt="">
    </div>
    <p class="not-found-code" aria-hidden="true">404</p>
    <h1 id="not-found-title">Halaman tidak ditemukan</h1>
    <p>Sepertinya tautan ini sudah berpindah, tidak tersedia, atau alamatnya tidak tepat.</p>
    <div class="not-found-actions">
      <a class="not-found-home" href="{{ route('home') }}">Kembali ke beranda</a>
      <a class="not-found-back" href="javascript:history.back()">Halaman sebelumnya</a>
    </div>
  </div>
</section>
@endsection
