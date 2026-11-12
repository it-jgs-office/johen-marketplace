@php
    $emailBadgeColor = '#ffedd5';
    $emailBadgeBackground = 'rgba(245,158,11,.22)';
@endphp
@extends('emails.layout')

@section('title', 'Peringatan Stok Menipis')
@section('preheader', 'Stok produk perlu segera ditambahkan.')
@section('badge', 'STOK MENIPIS')

@section('content')
<p style="margin:0 0 8px;color:#52677f;font-size:14px;">Halo Admin,</p>
<h1 class="jg-title" style="margin:0 0 10px;color:#102a43;font-size:25px;line-height:1.3;letter-spacing:-.02em;">Stok produk perlu ditambahkan</h1>
<p style="margin:0 0 22px;color:#64748b;font-size:13px;line-height:1.7;">Stok produk berikut sudah mendekati batas minimum. Tambahkan stok agar transaksi pelanggan tetap dapat diproses.</p>

<p style="margin:0 0 10px;color:#b45309;font-size:10px;font-weight:800;letter-spacing:.1em;">RINCIAN PRODUK</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:22px;border:1px solid #eadfc9;border-collapse:collapse;">
    <tr><td class="jg-detail-label" width="38%" style="padding:11px 13px;border-bottom:1px solid #eee5d7;background:#fffbeb;color:#785f3d;font-size:12px;">Kode Produk</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #eee5d7;color:#183b56;font-family:monospace;font-size:12px;font-weight:700;">{{ $product->buyer_sku_code }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;border-bottom:1px solid #eee5d7;background:#fffbeb;color:#785f3d;font-size:12px;">Produk</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #eee5d7;color:#183b56;font-size:12px;font-weight:700;">{{ $product->product_name }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;background:#fffbeb;color:#785f3d;font-size:12px;">Sisa Stok</td><td class="jg-detail-value" style="padding:11px 13px;color:#b45309;font-size:16px;font-weight:800;">{{ $product->stock }}</td></tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
    <tr><td style="background:#1267b0;border-radius:7px;"><a class="jg-button" href="{{ route('admin.products', ['brand' => $product->brand]) }}" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-size:13px;font-weight:800;">Kelola Stok Produk</a></td></tr>
</table>
<p style="margin:0;color:#64748b;font-size:12px;line-height:1.65;">Notifikasi ini dikirim otomatis ketika stok mencapai batas peringatan.</p>
@endsection
