@php
    $isSuccess = $status === 'success';
    $accent = $isSuccess ? '#16a34a' : '#dc2626';
    $accentSoft = $isSuccess ? '#f0fdf4' : '#fef2f2';
    $accentBorder = $isSuccess ? '#bbf7d0' : '#fecaca';
    $customerTarget = $order->customer_number.($order->effective_zone_id ? ' · Zone '.$order->effective_zone_id : '');
    $recipientName = $order->user?->name ?: 'Pelanggan Johen Gaming';
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
    <title>{{ $isSuccess ? 'Pesanan Berhasil' : 'Pesanan Gagal' }} - Johen Gaming</title>
    <style>
        @media only screen and (max-width:620px) {
            .email-shell { padding:20px 10px !important; }
            .email-card { border-radius:16px !important; }
            .email-pad { padding-left:22px !important; padding-right:22px !important; }
            .detail-label,.detail-value { display:block !important; width:100% !important; text-align:left !important; }
            .detail-value { padding-top:4px !important; }
            .email-button { display:block !important; text-align:center !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background:#eef4ff;font-family:Arial,'Helvetica Neue',sans-serif;color:#0f172a;-webkit-text-size-adjust:100%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#eef4ff;">
    <tr>
        <td class="email-shell" align="center" style="padding:38px 16px;">
            <table class="email-card" role="presentation" width="620" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;background:#ffffff;border:1px solid #dbe7ff;border-radius:22px;overflow:hidden;box-shadow:0 18px 50px rgba(15,37,85,.10);">
                <tr>
                    <td class="email-pad" style="padding:28px 38px;background:#071a3b;border-bottom:4px solid {{ $accent }};">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <img src="{{ $logoSource }}" alt="Johen Gaming" width="58" style="display:block;width:58px;height:auto;border:0;">
                                </td>
                                <td align="right" style="vertical-align:middle;">
                                    <span style="display:inline-block;padding:8px 12px;border-radius:999px;background:{{ $accent }};color:#ffffff;font-size:11px;font-weight:800;letter-spacing:.08em;">{{ $isSuccess ? 'PESANAN BERHASIL' : 'PESANAN GAGAL' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:34px 38px 12px;">
                        <p style="margin:0 0 10px;font-size:15px;color:#475569;">Halo <strong style="color:#0f172a;">{{ $recipientName }}</strong>,</p>
                        <h1 style="margin:0 0 12px;font-size:27px;line-height:1.25;color:#0f172a;letter-spacing:-.025em;">{{ $isSuccess ? 'Top up berhasil diproses' : 'Top up belum berhasil diproses' }}</h1>
                        <p style="margin:0;font-size:14px;line-height:1.7;color:#64748b;">{{ $isSuccess ? 'Produk telah dikirim ke akun tujuan. Simpan email ini sebagai bukti transaksi.' : 'Pesanan dihentikan karena tidak dapat diproses. Detail alasan tersedia di bawah ini.' }}</p>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:20px 38px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $accentSoft }};border:1px solid {{ $accentBorder }};border-radius:14px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <p style="margin:0 0 5px;font-size:11px;font-weight:800;letter-spacing:.08em;color:{{ $accent }};">{{ $isSuccess ? 'STATUS TERKONFIRMASI' : 'ALASAN PESANAN GAGAL' }}</p>
                                    <p style="margin:0;font-size:14px;line-height:1.65;color:#334155;">{{ $isSuccess ? 'Transaksi selesai dan produk telah diproses oleh provider.' : ($order->note ?: 'Provider tidak dapat memproses pesanan.') }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:24px 38px 0;">
                        <p style="margin:0 0 12px;font-size:12px;font-weight:800;letter-spacing:.08em;color:#2563eb;">RINCIAN PESANAN</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                            <tr><td class="detail-label" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">ID Pesanan</td><td class="detail-value" align="right" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#0f172a;font-family:monospace;">{{ $order->order_id }}</td></tr>
                            <tr><td class="detail-label" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Produk</td><td class="detail-value" align="right" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#0f172a;">{{ $order->product_name }}</td></tr>
                            <tr><td class="detail-label" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Game</td><td class="detail-value" align="right" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#0f172a;">{{ $order->brand }}</td></tr>
                            <tr><td class="detail-label" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Tujuan</td><td class="detail-value" align="right" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#0f172a;">{{ $customerTarget }}</td></tr>
                            <tr><td class="detail-label" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;">Pembayaran</td><td class="detail-value" align="right" style="padding:13px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#0f172a;">{{ $order->payment_method ?: ($order->transaction?->payment_type ?: '-') }}</td></tr>
                            <tr><td class="detail-label" style="padding:13px 16px;font-size:12px;color:#64748b;">Total Pesanan</td><td class="detail-value" align="right" style="padding:13px 16px;font-size:15px;font-weight:800;color:#0f172a;">Rp {{ number_format($order->price, 0, ',', '.') }}</td></tr>
                        </table>
                    </td>
                </tr>
                @if(!$isSuccess)
                <tr>
                    <td class="email-pad" style="padding:18px 38px 0;">
                        <p style="margin:0;font-size:12px;line-height:1.65;color:#64748b;">Jika pembayaran telah diterima, pengembalian dana diproses ke Johen Point sesuai status transaksi. Saldo dapat digunakan kembali untuk transaksi berikutnya dan tidak dapat dicairkan.</p>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="email-pad" style="padding:26px 38px 34px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="background:#2563eb;border-radius:11px;"><a class="email-button" href="{{ $detailUrl }}" style="display:inline-block;padding:13px 22px;color:#ffffff;text-decoration:none;font-size:13px;font-weight:800;">Lihat Detail Pesanan</a></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:20px 38px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0 0 5px;font-size:12px;line-height:1.6;color:#64748b;">Email ini dikirim otomatis oleh sistem Johen Gaming. Jangan membalas email ini.</p>
                        <p style="margin:0;font-size:11px;color:#94a3b8;">© {{ date('Y') }} Johen Gaming. Semua hak dilindungi.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
