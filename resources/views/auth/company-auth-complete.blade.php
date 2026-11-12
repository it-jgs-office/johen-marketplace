<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Berhasil</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#061a38;color:#fff;font-family:Poppins,Arial,sans-serif;text-align:center}
        .box{padding:32px}.icon{width:68px;height:68px;border-radius:50%;display:grid;place-items:center;margin:0 auto 16px;background:#16a34a;font-size:34px;font-weight:800}
        h1{font-size:20px;margin:0 0 8px}p{font-size:13px;color:#a9bad5;margin:0}
    </style>
</head>
<body>
    <div class="box"><div class="icon">✓</div><h1>Login berhasil</h1><p>Menghubungkan akun ke live chat…</p></div>
    <script>
        window.parent.postMessage({type:'johen-marketplace-auth',code:@json($code)}, @json($origin));
    </script>
</body>
</html>
