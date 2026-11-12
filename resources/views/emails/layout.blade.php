@php
    $emailBusiness = $business ?? business_info();
    $emailLogo = 'https://johengaming.store/logo.png';
    $emailBadgeColor = $emailBadgeColor ?? '#dbeafe';
    $emailBadgeBackground = $emailBadgeBackground ?? 'rgba(255,255,255,.12)';
    $emailWebsiteUrl = 'https://johengaming.store';
    $showStockAccountSupport = $showStockAccountSupport ?? false;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title') - Johen Gaming</title>
    <style>
        @media only screen and (max-width: 620px) {
            .jg-shell { padding: 16px 8px !important; }
            .jg-card { border-radius: 12px !important; }
            .jg-header, .jg-content, .jg-footer { padding-left: 20px !important; padding-right: 20px !important; }
            .jg-brand-name { font-size: 15px !important; }
            .jg-badge { font-size: 8px !important; padding: 6px 7px !important; }
            .jg-title { font-size: 22px !important; }
            .jg-detail-label, .jg-detail-value { display: block !important; width: 100% !important; text-align: left !important; box-sizing: border-box !important; }
            .jg-detail-value { padding-top: 0 !important; }
            .jg-button { display: block !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background:#f2f5f9;font-family:Arial,'Helvetica Neue',sans-serif;color:#10243e;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">@yield('preheader')</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#f2f5f9;">
    <tr>
        <td class="jg-shell" align="center" style="padding:32px 14px;">
            <table class="jg-card" role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #dce5ef;border-radius:16px;overflow:hidden;box-shadow:0 10px 32px rgba(15,43,76,.08);">
                <tr>
                    <td class="jg-header" style="padding:20px 30px;background:#0b3765;border-bottom:4px solid #2388df;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="vertical-align:middle;white-space:nowrap;">
                                    <img src="{{ $emailLogo }}" alt="Johen Gaming" width="38" style="display:inline-block;width:38px;height:38px;object-fit:contain;vertical-align:middle;border:0;">
                                    <span class="jg-brand-name" style="display:inline-block;margin-left:9px;color:#ffffff;font-size:17px;font-weight:800;letter-spacing:.035em;vertical-align:middle;">JOHEN GAMING</span>
                                </td>
                                <td align="right" style="vertical-align:middle;">
                                    @hasSection('badge')
                                    <span class="jg-badge" style="display:inline-block;padding:7px 9px;border-radius:6px;background:{{ $emailBadgeBackground }};color:{{ $emailBadgeColor }};font-size:9px;font-weight:800;letter-spacing:.08em;white-space:nowrap;">@yield('badge')</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="jg-content" style="padding:30px;">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td class="jg-footer" style="padding:20px 30px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0 0 10px;color:#64748b;font-size:11px;line-height:1.65;text-align:center;">Email ini dikirim otomatis oleh sistem Johen Gaming. Jangan membagikan kode, password, atau data transaksi kepada pihak lain.</p>
                        @if($showStockAccountSupport && (!empty($emailBusiness['wa_href']) || !empty($emailBusiness['phone'])))
                            <p style="margin:0 0 10px;color:#64748b;font-size:11px;line-height:1.6;text-align:center;">
                                Bantuan pembelian stock akun:
                                @if(!empty($emailBusiness['wa_href']))
                                    <a href="{{ $emailBusiness['wa_href'] }}" style="color:#1267b0;font-weight:700;text-decoration:none;">{{ $emailBusiness['phone'] }}</a>
                                @else
                                    <strong style="color:#1267b0;">{{ $emailBusiness['phone'] }}</strong>
                                @endif
                            </p>
                        @endif
                        <p style="margin:0 0 10px;color:#64748b;font-size:11px;line-height:1.6;text-align:center;">
                            Website: <a href="{{ $emailWebsiteUrl }}" style="color:#1267b0;font-weight:700;text-decoration:none;">johengaming.store</a>
                        </p>
                        <p style="margin:0;color:#94a3b8;font-size:10px;text-align:center;">© {{ date('Y') }} Johen Gaming. Seluruh hak dilindungi.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
