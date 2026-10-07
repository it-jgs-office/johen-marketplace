<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Peringatan Stok Menipis - {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#ffffff !important;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;font-family:'Inter',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="background-color:#ffffff !important;">
        <tr>
            <td align="center" style="padding:40px 16px;background-color:#ffffff !important;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff !important;">
                    <tr>
                        <td align="center" style="padding:32px 40px 0;background-color:#ffffff !important;">
                            <img src="{{ $message->embed(public_path('logo.png')) }}" alt="Johen Gaming" width="56" style="display:block;width:56px;height:auto;margin:0 auto 16px;border-radius:12px;">
                            <h1 style="margin:0;font-family:'Sora',Arial,sans-serif;font-size:20px;font-weight:800;color:#111827;letter-spacing:-0.02em;"><span style="color:#2563eb;">JOHENGAMING</span></h1>
                            <p style="margin:8px 0 0;font-size:13px;color:#6B7280;">Peringatan Stok Menipis</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:28px 40px;">
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#6B7280;">Stok produk di bawah ini sudah hampir habis. Mohon segera lakukan pengisian ulang agar transaksi tetap dapat diproses.</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FFF7ED !important;border:1px solid #FED7AA;border-radius:12px;margin-bottom:24px;">
                                <tr>
                                    <td align="left" style="padding:20px 24px;">
                                        <p style="margin:0 0 8px;font-size:12px;color:#6B7280;">{{ $product->buyer_sku_code }}</p>
                                        <p style="margin:0 0 12px;font-size:17px;font-weight:700;color:#111827;">{{ $product->product_name }}</p>
                                        <p style="margin:0;font-size:16px;font-weight:800;color:#d97706;">Sisa Stok: {{ $product->stock }}</p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center" style="background-color:#f59e0b;border-radius:10px;">
                                        <a href="{{ route('admin.products', ['brand' => $product->brand]) }}" style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:700;color:#111827;text-decoration:none;border-radius:10px;background-color:#f59e0b;">Segera Tambah Stok</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#6B7280;">Mohon isi ulang stok produk ini agar transaksi tetap dapat diproses tanpa hambatan.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding:20px 40px;border-top:1px solid #E5E7EB;">
                            <p style="margin:0;font-size:12px;color:#9CA3AF;">© {{ date('Y') }} Johen Gaming. All Rights Reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
