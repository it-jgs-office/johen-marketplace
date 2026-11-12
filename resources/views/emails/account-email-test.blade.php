@php
    $emailBadgeColor = '#dbeafe';
    $emailBadgeBackground = 'rgba(96,165,250,.2)';
    $showStockAccountSupport = true;
@endphp
@extends('emails.layout')

@section('title', 'Kode Konfirmasi Pembelian')
@section('preheader', 'Gunakan kode ini untuk mengonfirmasi email pembelian akun game.')
@section('badge', 'VERIFIKASI EMAIL')

@section('content')
<p style="margin:0 0 8px;color:#52677f;font-size:14px;">Halo,</p>
<h1 class="jg-title" style="margin:0 0 10px;color:#102a43;font-size:25px;line-height:1.3;letter-spacing:-.02em;">Konfirmasi email pembelian</h1>
<p style="margin:0 0 22px;color:#64748b;font-size:13px;line-height:1.7;">Masukkan kode berikut pada halaman checkout untuk memastikan data login akun dikirim ke <strong style="color:#183b56;">{{ $recipientEmail }}</strong>.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:18px;background:#0b3765;border:1px solid #0b3765;">
    <tr>
        <td align="center" style="padding:20px 16px;">
            <p style="margin:0 0 7px;color:#b9d8f3;font-size:10px;font-weight:800;letter-spacing:.12em;">KODE KONFIRMASI</p>
            <span style="color:#ffffff;font-family:'Courier New',monospace;font-size:31px;font-weight:800;letter-spacing:9px;">{{ $confirmationCode }}</span>
        </td>
    </tr>
</table>

<div style="margin-bottom:20px;padding:13px 15px;border-left:4px solid #2388df;background:#f1f7fd;color:#33546f;font-size:12px;line-height:1.65;">Kode berlaku selama <strong>15 menit</strong>. Jangan berikan kode ini kepada siapa pun.</div>
<p style="margin:0;color:#64748b;font-size:12px;line-height:1.65;">Setelah pembayaran berhasil, email yang sama akan menerima data login akun game secara otomatis.</p>
<p style="margin:24px 0 0;color:#52677f;font-size:13px;line-height:1.65;">Salam,<br><strong style="color:#1267b0;">Tim Johen Gaming</strong></p>
@endsection
