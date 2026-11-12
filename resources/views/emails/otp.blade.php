@php
    $isPasswordReset = $type === 'password_reset';
    $emailBadgeColor = '#dbeafe';
    $emailBadgeBackground = 'rgba(96,165,250,.2)';
@endphp
@extends('emails.layout')

@section('title', $isPasswordReset ? 'Kode Reset Password' : 'Kode Verifikasi Registrasi')
@section('preheader', 'Kode keamanan akun Johen Gaming berlaku selama 5 menit.')
@section('badge', 'KEAMANAN AKUN')

@section('content')
<p style="margin:0 0 8px;color:#52677f;font-size:14px;">Halo <strong style="color:#10243e;">{{ $name }}</strong>,</p>
<h1 class="jg-title" style="margin:0 0 10px;color:#102a43;font-size:25px;line-height:1.3;letter-spacing:-.02em;">{{ $isPasswordReset ? 'Reset password akun' : 'Verifikasi akun baru' }}</h1>
<p style="margin:0 0 22px;color:#64748b;font-size:13px;line-height:1.7;">Gunakan kode berikut untuk {{ $isPasswordReset ? 'melanjutkan perubahan password' : 'menyelesaikan pendaftaran' }} akun Johen Gaming.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:18px;background:#0b3765;border:1px solid #0b3765;">
    <tr>
        <td align="center" style="padding:20px 16px;">
            <p style="margin:0 0 7px;color:#b9d8f3;font-size:10px;font-weight:800;letter-spacing:.12em;">KODE OTP</p>
            <span style="color:#ffffff;font-family:'Courier New',monospace;font-size:31px;font-weight:800;letter-spacing:9px;">{{ $otp }}</span>
        </td>
    </tr>
</table>

<div style="padding:13px 15px;border-left:4px solid #f59e0b;background:#fffbeb;color:#7c4a03;font-size:12px;line-height:1.65;">Kode berlaku selama <strong>5 menit</strong> dan hanya dapat digunakan sekali. Jangan membagikan kode kepada siapa pun, termasuk pihak yang mengaku sebagai Johen Gaming.</div>
<p style="margin:24px 0 0;color:#52677f;font-size:13px;line-height:1.65;">Salam,<br><strong style="color:#1267b0;">Tim Johen Gaming</strong></p>
@endsection
