@php
    $isSuccess = $status === 'success';
    $accent = $isSuccess ? '#159669' : '#dc4c4c';
    $accentSoft = $isSuccess ? '#edf9f4' : '#fff2f2';
    $emailBadgeColor = $isSuccess ? '#d1fae5' : '#fee2e2';
    $emailBadgeBackground = $isSuccess ? 'rgba(16,185,129,.2)' : 'rgba(239,68,68,.2)';
    $customerTarget = $order->customer_number.($order->effective_zone_id ? ' · Server '.$order->effective_zone_id : '');
    $recipientName = $order->user?->name ?: 'Pelanggan Johen Gaming';
    $paymentMethod = strtoupper(str_replace('_', ' ', $order->payment_method ?: ($order->transaction?->payment_type ?: '-')));
    $orderDate = $order->created_at?->format('d M Y, H:i').' WIB';
    $detailUrl = !empty($isPreview) ? '#' : route('orders.show', $order);
@endphp
@extends('emails.layout')

@section('title', $isSuccess ? 'Pesanan Berhasil' : 'Pesanan Gagal')
@section('preheader', $isSuccess ? 'Top up berhasil diproses.' : 'Top up belum dapat diproses.')
@section('badge', $isSuccess ? 'TRANSAKSI BERHASIL' : 'PERLU PERHATIAN')

@section('content')
<p style="margin:0 0 8px;color:#52677f;font-size:14px;">Halo <strong style="color:#10243e;">{{ $recipientName }}</strong>,</p>
<h1 class="jg-title" style="margin:0 0 10px;color:#102a43;font-size:25px;line-height:1.3;letter-spacing:-.02em;">{{ $isSuccess ? 'Top up berhasil diproses' : 'Top up belum dapat diproses' }}</h1>
<p style="margin:0 0 20px;color:#64748b;font-size:13px;line-height:1.7;">{{ $isSuccess ? 'Produk telah dikirim ke akun tujuan. Simpan email ini sebagai bukti transaksi.' : 'Pesanan tidak dapat dilanjutkan. Periksa status dan rincian transaksi di bawah ini.' }}</p>

<div style="margin-bottom:22px;padding:13px 15px;border-left:4px solid {{ $accent }};background:{{ $accentSoft }};color:#334155;font-size:12px;line-height:1.65;">
    <strong style="display:block;margin-bottom:3px;color:{{ $accent }};font-size:10px;letter-spacing:.09em;">{{ $isSuccess ? 'STATUS: BERHASIL' : 'STATUS: GAGAL' }}</strong>
    {{ $isSuccess ? 'Transaksi telah diterima dan diproses oleh provider.' : ($order->note ?: 'Provider tidak dapat memproses pesanan ini.') }}
</div>

<p style="margin:0 0 10px;color:#1267b0;font-size:10px;font-weight:800;letter-spacing:.1em;">RINCIAN TRANSAKSI</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:18px;border:1px solid #dfe7ef;border-collapse:collapse;">
    <tr><td class="jg-detail-label" width="38%" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">ID Pesanan</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-family:monospace;font-size:12px;font-weight:700;">{{ $order->order_id }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">Tanggal</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-size:12px;font-weight:700;">{{ $orderDate }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">Produk</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-size:12px;font-weight:700;">{{ $order->product_name }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">Game</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-size:12px;font-weight:700;">{{ $order->brand }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;border-bottom:1px solid #e7edf3;background:#f8fafc;color:#64748b;font-size:12px;">User ID / Server</td><td class="jg-detail-value" style="padding:11px 13px;border-bottom:1px solid #e7edf3;color:#183b56;font-size:12px;font-weight:700;">{{ $customerTarget }}</td></tr>
    <tr><td class="jg-detail-label" style="padding:11px 13px;background:#f8fafc;color:#64748b;font-size:12px;">Metode Pembayaran</td><td class="jg-detail-value" style="padding:11px 13px;color:#183b56;font-size:12px;font-weight:700;">{{ $paymentMethod }}</td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:20px;border:1px dashed #b8c9da;background:#f8fbff;">
    <tr><td style="padding:14px 16px;color:#52677f;font-size:12px;font-weight:700;">Total Pembayaran</td><td align="right" style="padding:14px 16px;color:#0f4c81;font-size:19px;font-weight:800;">Rp {{ number_format($order->price, 0, ',', '.') }}</td></tr>
</table>

@if(!$isSuccess)
<p style="margin:0 0 20px;color:#64748b;font-size:12px;line-height:1.65;">Jika pembayaran sudah diterima, dana dikembalikan sesuai status transaksi dan dapat digunakan kembali setelah proses pengembalian selesai.</p>
@endif

<table role="presentation" cellpadding="0" cellspacing="0" border="0">
    <tr><td style="background:#1267b0;border-radius:7px;"><a class="jg-button" href="{{ $detailUrl }}" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-size:13px;font-weight:800;">Lihat Detail Pesanan</a></td></tr>
</table>
@endsection
