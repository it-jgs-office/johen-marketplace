@extends('layouts.topup')

@section('title', 'Syarat & Ketentuan - ' . config('app.name'))

@section('content')
@php
  $biz = business_info();
@endphp
<div class="simple-page">
  <div class="simple-hero">
    <h1>Syarat & Ketentuan</h1>
    <p>Aturan layanan {{ $biz['name'] }}</p>
  </div>
  <div class="simple-content prose">
    <h3>Identitas Penyedia</h3>
    <p>
      Situs ini dikelola oleh <strong>{{ $biz['name'] }}</strong> ({{ config('app.name') }}).<br>
      Alamat: {{ $biz['address'] }}
      @if($biz['npwp']), NPWP: {{ $biz['npwp'] }}@endif
    </p>
    <p>
      Email: <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a><br>
      Telepon / WhatsApp: {{ $biz['phone'] }}<br>
      Customer Service: {{ $biz['cs_email'] }} &middot; {{ $biz['cs_hours'] }}
    </p>

    <p>Dengan menggunakan layanan kami, anda menyetujui syarat dan ketentuan berikut:</p>

    <h3>Layanan</h3>
    <p>Kami menyediakan layanan top up game, jasa joki, dan jual beli akun game. Seluruh layanan dilakukan sesuai dengan ketentuan yang berlaku dari masing-masing platform game. Harga setiap produk/jasa tercantum pada halaman produk sebelum Anda melakukan checkout.</p>

    <h3>Kewajiban Pengguna</h3>
    <p>Pengguna wajib memberikan data yang benar dan valid saat melakukan transaksi. Kesalahan data menjadi tanggung jawab pengguna sepenuhnya.</p>

    <h3>Produk &amp; Harga</h3>
    <p>Harga yang ditampilkan pada halaman produk merupakan harga akhir yang berlaku saat transaksi. Harga dapat berubah sewaktu-waktu mengikuti nilai tukar dan ketersediaan stok; harga yang berlaku adalah harga yang tampil pada saat Anda menekan tombol checkout.</p>

    <h3>Checkout &amp; Pembayaran</h3>
    <p>Pembayaran dilakukan melalui kanal yang tampil pada halaman checkout (QRIS, e-wallet, virtual account, atau minimarket). Pesanan dianggap sah setelah pembayaran diterima dan dikonfirmasi oleh sistem. Mohon Storakan bukti pembayaran yang diperlukan agar proses verifikasi lebih cepat.</p>

    <h3>Pengembalian Dana</h3>
    <p>Pengembalian dana dapat dilakukan jika pesanan tidak diproses dalam waktu 2&times;24 jam. Pengembalian tidak berlaku untuk pesanan yang sudah masuk ke akun game atau voucher yang sudah dikirim ke pembeli.</p>

    <h3>Penolakan Pesanan</h3>
    <p>Kami berhak menolak atau membatalkan pesanan yang melanggar ketentuan platform game, suspected fraud, atau pembayaran yang tidak terverifikasi. Dana akan dikembalikan ke metode pembayaran asal maksimal 7 hari kerja.</p>

    <h3>Perubahan Kebijakan</h3>
    <p>Kami berhak mengubah syarat dan ketentuan ini sewaktu-waktu. Perubahan akan diinformasikan melalui website kami.</p>
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
</style>
@endsection
