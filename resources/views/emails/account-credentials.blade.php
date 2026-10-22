@php($listing = $accountOrder->listing)
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Data Login Akun Game</title></head>
<body style="margin:0;padding:0;background:#eef4ff;font-family:Arial,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef4ff"><tr><td align="center" style="padding:36px 14px">
  <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="width:100%;max-width:620px;background:#fff;border:1px solid #dbe7ff;border-radius:18px;overflow:hidden">
    <tr><td style="padding:24px 30px;background:#071a3b;color:#fff"><strong style="font-size:19px">JOHEN GAMING</strong><span style="float:right;padding:7px 10px;border-radius:999px;background:#16a34a;font-size:11px;font-weight:700">PESANAN BERHASIL</span></td></tr>
    <tr><td style="padding:30px 30px 16px">
      <p style="margin:0 0 8px;color:#475569">Halo <strong>{{ $accountOrder->customer_name }}</strong>,</p>
      <h1 style="margin:0 0 10px;font-size:25px">Data login akun game kamu</h1>
      <p style="margin:0;color:#64748b;line-height:1.7">Pembayaran telah diterima. Simpan email ini dan segera amankan akun setelah berhasil masuk.</p>
    </td></tr>
    <tr><td style="padding:16px 30px">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
        <tr><td style="padding:12px 15px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px">Nomor Pesanan</td><td align="right" style="padding:12px 15px;border-bottom:1px solid #e2e8f0;font-family:monospace;font-weight:700">{{ $accountOrder->order_ref }}</td></tr>
        <tr><td style="padding:12px 15px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px">Game</td><td align="right" style="padding:12px 15px;border-bottom:1px solid #e2e8f0;font-weight:700">{{ $listing?->game }}</td></tr>
        <tr><td style="padding:12px 15px;color:#64748b;font-size:12px">Produk</td><td align="right" style="padding:12px 15px;font-weight:700">{{ $listing?->product_name }}</td></tr>
      </table>
    </td></tr>
    <tr><td style="padding:4px 30px 18px">
      <p style="margin:0 0 9px;font-size:12px;font-weight:800;color:#2563eb;letter-spacing:.07em">DATA LOGIN AKUN</p>
      <div style="padding:18px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#172554;font-family:monospace;font-size:14px;line-height:1.75;white-space:pre-wrap;word-break:break-word">{{ $credentials }}</div>
    </td></tr>
    <tr><td style="padding:0 30px 26px">
      <div style="padding:16px;border:1px solid #fde68a;border-radius:12px;background:#fffbeb;color:#78350f;font-size:13px;line-height:1.65">Segera login, ganti password, dan aktifkan keamanan akun. Jika mengalami kendala login, hubungi CS Johen di <strong>{{ $business['phone'] }}</strong>@if($business['wa_href']) melalui <a href="{{ $business['wa_href'] }}" style="color:#b45309;font-weight:700">WhatsApp CS</a>@endif.</div>
    </td></tr>
  </table>
</td></tr></table>
</body>
</html>
