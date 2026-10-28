@php
    $isSuccess = $status === 'success';
    $accent = $isSuccess ? '#159669' : '#dc4c4c';
    $accentSoft = $isSuccess ? '#eaf8f2' : '#fff0f0';
    $customerTarget = $order->customer_number.($order->effective_zone_id ? ' · Server '.$order->effective_zone_id : '');
    $recipientName = $order->user?->name ?: 'Pelanggan Johen Gaming';
    $paymentMethod = strtoupper(str_replace('_', ' ', $order->payment_method ?: ($order->transaction?->payment_type ?: '-')));
    $orderDate = $order->created_at?->format('d M Y, H:i').' WIB';
    $logoSource = isset($message) && is_object($message) && method_exists($message, 'embed')
        ? $message->embed(public_path('logo.png'))
        : asset('logo.png');
    $detailUrl = !empty($isPreview) ? '#' : route('orders.show', $order);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $isSuccess ? 'Bukti Top Up' : 'Status Top Up' }} - Johen Gaming</title>
    <style>
        @media only screen and (max-width:620px) {
            .email-shell { padding:18px 10px !important; }
            .email-card { border-radius:14px !important; }
            .email-pad { padding-left:20px !important; padding-right:20px !important; }
            .receipt-label,.receipt-value { display:block !important; width:100% !important; text-align:left !important; }
            .receipt-value { padding-top:4px !important; }
            .email-button { display:block !important; text-align:center !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background:#f1f5f9;font-family:Arial,'Helvetica Neue',sans-serif;color:#14213d;-webkit-text-size-adjust:100%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#f1f5f9;">
    <tr>
        <td class="email-shell" align="center" style="padding:36px 16px;">
            <table class="email-card" role="presentation" width="620" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;background:#ffffff;border:1px solid #dbe4ef;border-radius:18px;overflow:hidden;">
                <tr>
                    <td class="email-pad" style="padding:22px 34px;background:#082447;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <img src="{{ $logoSource }}" alt="Johen Gaming" width="44" style="display:inline-block;vertical-align:middle;width:44px;height:auto;border:0;">
                                    <span style="display:inline-block;vertical-align:middle;margin-left:10px;color:#ffffff;font-size:16px;font-weight:800;letter-spacing:.04em;">JOHEN GAMING</span>
                                </td>
                                <td align="right" style="vertical-align:middle;">
                                    <span style="display:inline-block;color:#b9d5ef;font-size:10px;font-weight:700;letter-spacing:.12em;">BUKTI TRANSAKSI</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:30px 34px 18px;">
                        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Halo, {{ $recipientName }}.</p>
                        <h1 style="margin:0 0 10px;font-size:25px;line-height:1.28;color:#102a43;letter-spacing:-.02em;">{{ $isSuccess ? 'Top up berhasil diproses' : 'Top up belum dapat diproses' }}</h1>
                        <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">{{ $isSuccess ? 'Produk dikirim ke akun tujuan. Simpan email ini sebagai bukti transaksi.' : 'Pesanan tidak dapat dilanjutkan. Periksa rincian dan alasan di bawah ini.' }}</p>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:0 34px 20px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $accentSoft }};border-left:4px solid {{ $accent }};">
                            <tr>
                                <td style="padding:14px 16px;">
                                    <p style="margin:0 0 3px;color:{{ $accent }};font-size:10px;font-weight:800;letter-spacing:.1em;">{{ $isSuccess ? 'STATUS: BERHASIL' : 'STATUS: PERLU PERHATIAN' }}</p>
                                    <p style="margin:0;color:#334155;font-size:13px;line-height:1.55;">{{ $isSuccess ? 'Transaksi telah diterima dan produk diproses oleh provider.' : ($order->note ?: 'Provider tidak dapat memproses pesanan ini.') }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:0 34px;">
                        <p style="margin:0 0 12px;color:#0f4c81;font-size:11px;font-weight:800;letter-spacing:.1em;">RINCIAN PESANAN</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #dbe4ef;border-bottom:1px solid #dbe4ef;">
                            <tr><td class="receipt-label" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#64748b;font-size:12px;">ID Pesanan</td><td class="receipt-value" align="right" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#183b56;font-family:monospace;font-size:12px;font-weight:700;">{{ $order->order_id }}</td></tr>
                            <tr><td class="receipt-label" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#64748b;font-size:12px;">Tanggal</td><td class="receipt-value" align="right" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#183b56;font-size:12px;font-weight:700;">{{ $orderDate }}</td></tr>
                            <tr><td class="receipt-label" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#64748b;font-size:12px;">Produk</td><td class="receipt-value" align="right" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#183b56;font-size:12px;font-weight:700;">{{ $order->product_name }}</td></tr>
                            <tr><td class="receipt-label" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#64748b;font-size:12px;">Game</td><td class="receipt-value" align="right" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#183b56;font-size:12px;font-weight:700;">{{ $order->brand }}</td></tr>
                            <tr><td class="receipt-label" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#64748b;font-size:12px;">User ID / Server</td><td class="receipt-value" align="right" style="padding:12px 0;border-bottom:1px solid #e8eef5;color:#183b56;font-size:12px;font-weight:700;">{{ $customerTarget }}</td></tr>
                            <tr><td class="receipt-label" style="padding:12px 0;color:#64748b;font-size:12px;">Metode Pembayaran</td><td class="receipt-value" align="right" style="padding:12px 0;color:#183b56;font-size:12px;font-weight:700;">{{ $paymentMethod }}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:18px 34px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fbff;border:1px dashed #b9cbe0;">
                            <tr>
                                <td style="padding:16px 18px;color:#33546f;font-size:13px;font-weight:700;">Total Pembayaran</td>
                                <td align="right" style="padding:16px 18px;color:#0f4c81;font-size:19px;font-weight:800;">Rp {{ number_format($order->price, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @if(!$isSuccess)
                <tr>
                    <td class="email-pad" style="padding:16px 34px 0;">
                        <p style="margin:0;font-size:12px;line-height:1.65;color:#64748b;">Apabila pembayaran telah diterima, dana dikembalikan ke Johen Point sesuai status transaksi dan dapat digunakan kembali untuk pembelian berikutnya.</p>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="email-pad" style="padding:24px 34px 30px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="background:#1267b0;border-radius:8px;"><a class="email-button" href="{{ $detailUrl }}" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-size:13px;font-weight:800;">Lihat Detail Pesanan</a></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:18px 34px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0 0 4px;font-size:11px;line-height:1.55;color:#64748b;">Email ini dikirim otomatis oleh Johen Gaming. Mohon tidak membalas email ini.</p>
                        <p style="margin:0;font-size:11px;color:#94a3b8;">© {{ date('Y') }} Johen Gaming.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
