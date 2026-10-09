@extends('layouts.topup')

@section('title', 'FAQ - ' . config('app.name'))

@section('content')
<div class="simple-page">
  <div class="simple-hero">
    <h1>FAQ</h1>
    <p>Pertanyaan yang sering diajukan</p>
  </div>
  <div class="simple-content">
    <div class="faq-item">
      <h3>Cara top up?</h3>
      <p>Pilih game, masukkan User ID, pilih nominal, lalu selesaikan pembayaran. Proses otomatis dalam hitungan detik.</p>
    </div>
    <div class="faq-item">
      <h3>Pembayaran apa saja yang tersedia?</h3>
      <p>Kami menerima berbagai metode pembayaran seperti QRIS, GoPay, OVO, Dana, transfer bank, dan lainnya.</p>
    </div>
    <div class="faq-item">
      <h3>Apakah aman bertransaksi di Johen Gaming?</h3>
      <p>100% aman dan legal. Kami sudah berpengalaman dan dipercaya ribuan pelanggan.</p>
    </div>
    <div class="faq-item">
      <h3>Bagaimana jika pesanan tidak masuk?</h3>
      <p>Hubungi CS kami melalui halaman Contact Us dengan menyertakan bukti transaksi.</p>
    </div>
    <div class="faq-item">
      <h3>Berapa lama proses top up?</h3>
      <p>Proses otomatis biasanya selesai dalam 1-60 detik setelah pembayaran berhasil.</p>
    </div>
  </div>
</div>

<style>
.simple-page{width:100%;max-width:var(--layout-max);margin:0 auto;padding:3rem var(--layout-gutter) 4rem;min-height:calc(100vh - 140px);}
.simple-hero{text-align:center;margin-bottom:2.5rem;}
.simple-hero h1{font-size:1.8rem;font-weight:800;margin-bottom:.3rem;}
.simple-hero p{color:var(--text-dim);font-size:.95rem;}
.simple-content{width:100%;max-width:960px;margin:0 auto;display:flex;flex-direction:column;gap:1.2rem;}
.faq-item{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.3rem 1.5rem;}
.faq-item h3{font-size:1rem;font-weight:700;margin-bottom:.4rem;}
.faq-item p{color:var(--text-dim);font-size:.9rem;line-height:1.6;margin:0;}
</style>
@endsection
