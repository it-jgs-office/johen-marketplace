@php
    $emailBadgeColor = '#dbeafe';
    $emailBadgeBackground = 'rgba(96,165,250,.2)';
@endphp
@extends('emails.layout')

@section('title', 'Balasan Customer Service')
@section('preheader', 'Tim Johen Gaming telah membalas pesan kamu.')
@section('badge', 'BALASAN CS')

@section('content')
<p style="margin:0 0 8px;color:#52677f;font-size:14px;">Halo <strong style="color:#10243e;">{{ $inquiry->name }}</strong>,</p>
<h1 class="jg-title" style="margin:0 0 10px;color:#102a43;font-size:25px;line-height:1.3;letter-spacing:-.02em;">Balasan dari Customer Service</h1>
<p style="margin:0 0 22px;color:#64748b;font-size:13px;line-height:1.7;">Terima kasih telah menghubungi Johen Gaming. Berikut jawaban dari tim kami:</p>

<div style="margin-bottom:20px;padding:18px;border:1px solid #d9e4ef;border-left:4px solid #2388df;background:#f8fbff;color:#183b56;font-size:13px;line-height:1.75;white-space:pre-wrap;word-break:break-word;">{{ $reply }}</div>

<p style="margin:0;color:#64748b;font-size:12px;line-height:1.65;">Jika masih memerlukan bantuan, hubungi kami kembali melalui kanal Customer Service resmi Johen Gaming.</p>
<p style="margin:24px 0 0;color:#52677f;font-size:13px;line-height:1.65;">Salam,<br><strong style="color:#1267b0;">Tim Customer Service Johen Gaming</strong></p>
@endsection
