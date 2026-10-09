@extends('layouts.topup')

@section('title', 'Kebijakan Privasi - ' . config('app.name'))

@section('content')
@php
  $biz = business_info();
@endphp
<div class="simple-page">
  <div class="simple-hero">
    <h1>Kebijakan Privasi</h1>
    <p>Bagaimana kami melindungi data anda</p>
  </div>
  <div class="simple-content prose">
    <h3>Pengelola Data</h3>
    <p>
      Kebijakan ini berlaku untuk data yang diproses oleh <strong>{{ $biz['name'] }}</strong> ({{ config('app.name') }}),<br>
      Alamat: {{ $biz['address'] }}
      @if($biz['npwp']), NPWP: {{ $biz['npwp'] }}@endif
    </p>
    <p>
      Email: <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a> &middot; Telepon/WhatsApp: {{ $biz['phone'] }}
    </p>

    <p>Kami berkomitmen untuk melindungi privasi pengguna. Kebijakan ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi pribadi anda.</p>

    <h3>Data yang Kami Kumpulkan</h3>
    <p>Kami mengumpulkan data seperti nama, alamat email, nomor telepon/WhatsApp, dan User ID game yang anda berikan saat melakukan transaksi atau menghubungi CS.</p>

    <h3>Penggunaan Data</h3>
    <p>Data anda digunakan untuk memproses transaksi, memberikan dukungan pelanggan, dan meningkatkan layanan kami. Kami tidak akan menjual atau menyewakan data anda kepada pihak ketiga.</p>

    <h3>Berbagi Data dengan Penyedia Pembayaran</h3>
    <p>Untuk memproses pembayaran, data seperlunya (nama, email, nomor telepon, dan nominal transaksi) diteruskan kepada penyedia payment gateway yang kami gunakan. Data tersebut tidak digunakan oleh penyedia untuk keperluan lain.</p>

    <h3>Keamanan Data</h3>
    <p>Kami menerapkan langkah-langkah keamanan teknis untuk melindungi data anda dari akses tidak sah, perubahan, atau pengungkapan.</p>

    <h3>Retensi Data</h3>
    <p>Data transaksi disimpan selama maksimal 2 (dua) tahun sebagai bukti penyelesaian transaksi dan untuk keperluan pelaporan, kemudian dihapus atau dianonimkan.</p>

    <h3>Hak Anda</h3>
    <p>Anda berhak mengakses, mengoreksi, atau menghapus data pribadi anda dengan menghubungi customer service kami melalui {{ $biz['email'] }} atau {{ $biz['phone'] }}.</p>

    <p class="prose-update">Terakhir diperbarui: {{ date('F Y') }}</p>
  </div>
</div>

<style>
.simple-page{width:100%;max-width:var(--layout-max);margin:0 auto;padding:3rem var(--layout-gutter) 4rem;min-height:calc(100vh - 140px);}
.simple-hero{text-align:center;margin-bottom:2.5rem;}
.simple-hero h1{font-size:1.8rem;font-weight:800;margin-bottom:.3rem;}
.simple-hero p{color:var(--text-dim);font-size:.95rem;}
.simple-content.prose{width:100%;max-width:960px;margin:0 auto;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:2rem;font-size:.9rem;line-height:1.7;color:var(--text-dim);}
.simple-content.prose h3{font-size:1.05rem;font-weight:700;margin:1.5rem 0 .5rem;color:var(--text);}
.simple-content.prose h3:first-child{margin-top:0;}
.simple-content.prose p{margin:0 0 .8rem;}
.prose-update{font-size:.82rem;color:var(--text-dim);margin-top:2rem !important;opacity:.7;}
</style>
@endsection
