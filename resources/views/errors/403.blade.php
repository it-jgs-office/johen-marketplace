<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="text-6xl mb-4">🚫</div>
        <h1 class="text-3xl font-bold mb-2">Akses Ditolak</h1>
        <p class="text-gray-400 mb-6">Kamu tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="{{ route('home') }}" class="bg-purple-600 px-6 py-3 rounded-lg hover:bg-purple-700 transition inline-block">
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>
