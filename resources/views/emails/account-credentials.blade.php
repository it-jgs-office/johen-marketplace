@php
    $listing = $accountOrder->listing;
    $emailBadgeColor = '#d1fae5';
    $emailBadgeBackground = 'rgba(16,185,129,.2)';
    $showStockAccountSupport = true;
@endphp
@extends('emails.layout')

@section('title', 'Data Login Akun Game & Zimbra')
@section('preheader', 'Pembayaran berhasil. Data login akun game dan Zimbra kamu sudah tersedia.')
@section('badge', 'PESANAN BERHASIL')

@section('content')
<p style="margin:0 0 8px;color:#52677f;font-size:14px;">Halo <strong style="color:#10243e;">{{ $accountOrder->customer_name }}</strong>,</p>
<h1 class="jg-title" style="margin:0 0 10px;color:#102a43;font-size:25px;line-height:1.3;letter-spacing:-.02em;">Data login akun game &amp; Zimbra kamu</h1>
<p style="margin:0 0 24px;color:#64748b;font-size:13px;line-height:1.7;">Pembayaran telah diterima dan pesanan selesai diproses. Simpan email ini sebagai referensi transaksi.</p>

<p style="margin:0 0 10px;color:#1267b0;font-size:10px;font-weight:800;letter-spacing:.1em;">RINCIAN PESANAN</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:22px;border:1px solid #dfe7ef;border-collapse:collapse;">
    <tr><td class="jg-detail-label" width="38%" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">Nomor Pesanan</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-family:monospace;font-size:12px;font-weight:700;">{{ $accountOrder->order_ref }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">Tanggal</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-size:12px;font-weight:700;">{{ $accountOrder->created_at?->format('d M Y, H:i') }} WIB</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">Game</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-size:12px;font-weight:700;">{{ $listing?->game ?: '-' }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;background:#f8fafc;color:#64748b;font-size:12px;">Produk</td><td class="jg-detail-value" style="padding:11px 13px;color:#183b56;font-size:12px;font-weight:700;">{{ $listing?->product_name ?: '-' }}</td></tr>
</table>

<p style="margin:0 0 10px;color:#1267b0;font-size:10px;font-weight:800;letter-spacing:.1em;">DATA LOGIN AKUN GAME &amp; ZIMBRA</p>
<div style="margin:0 0 20px;padding:16px 18px;border:1px solid #b8d7f0;border-left:4px solid #2388df;background:#f3f9ff;color:#102a43;font-family:'Courier New',monospace;font-size:14px;line-height:1.75;white-space:pre-wrap;word-break:break-word;">{{ $credentials }}</div>

<div style="padding:14px 16px;border:1px solid #f3d38a;background:#fffbeb;color:#7c4a03;font-size:12px;line-height:1.65;">
    Segera masuk ke akun game dan Zimbra, lalu ganti kedua password serta aktifkan fitur keamanan yang tersedia. Johen Gaming tidak pernah meminta password akun melalui chat atau telepon.
</div>

<p style="margin:24px 0 0;color:#52677f;font-size:13px;line-height:1.65;">Salam,<br><strong style="color:#1267b0;">Tim Johen Gaming</strong></p>
@endsection
