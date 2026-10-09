@php
    $adminUser = Auth::guard('admin')->user();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin CS</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: #071D35;
            color: #F5F7FB;
            display: flex;
            flex-direction: column;
        }
        .lc-topbar {
            height: 68px;
            background: #102E4D;
            border-bottom: 1px solid #1A4168;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            flex-shrink: 0;
            z-index: 100;
        }
        .lc-topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .lc-topbar-logo {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        .lc-topbar-title {
            font-weight: 700;
            font-size: 16px;
            color: #F5F7FB;
        }
        .lc-topbar-title span { color: #3F6DF5; }
        .lc-topbar-right {
            display: flex;
            align-items: center;
        }
        .lc-topbar-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px 6px 6px;
            border-radius: 12px;
            border: 1px solid #1A4168;
            background: #102A47;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .lc-topbar-profile:hover { border-color: #3F6DF5; }
        .lc-topbar-avatar {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: linear-gradient(135deg, #14b8a6, #3F6DF5);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
            object-fit: cover;
        }
        .lc-topbar-name {
            font-size: 14px;
            font-weight: 600;
            color: #F5F7FB;
        }
        .lc-topbar-chevron {
            font-size: 10px;
            color: #6F89A7;
            transition: transform .2s;
        }
        .lc-topbar-profile.open .lc-topbar-chevron { transform: rotate(180deg); }
        .lc-topbar-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: #102E4D;
            border: 1px solid #1A4168;
            border-radius: 12px;
            padding: 6px;
            min-width: 160px;
            box-shadow: 0 12px 40px -8px rgba(0,0,0,0.4);
            opacity: 0;
            pointer-events: none;
            transform: translateY(-6px);
            transition: all .2s;
            z-index: 200;
        }
        .lc-topbar-profile.open .lc-topbar-dropdown {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .lc-topbar-dropdown button {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-radius: 8px;
            background: none;
            color: #8FA8C4;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all .15s;
        }
        .lc-topbar-dropdown button:hover {
            background: rgba(239,68,68,0.1);
            color: #ef4444;
        }
        .lc-main {
            flex: 1;
            overflow: hidden;
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #214D78; border-radius: 3px; }

        @media (max-width: 768px) {
            .lc-topbar { height: 52px; padding: 0 14px; }
            .lc-topbar-left { gap: 8px; }
            .lc-topbar-logo { width: 28px; height: 28px; border-radius: 50%; }
            .lc-topbar-title { font-size: 13px; }
            .lc-topbar-profile { padding: 4px 8px 4px 4px; gap: 6px; }
            .lc-topbar-avatar { width: 26px; height: 26px; font-size: 11px; border-radius: 6px; }
            .lc-topbar-name { font-size: 12px; }
            .lc-topbar-chevron { font-size: 9px; }
            .lc-topbar-dropdown { min-width: 140px; }
            .lc-topbar-dropdown button { padding: 8px 10px; font-size: 12px; }
            .lc-topbar-name { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="lc-topbar">
        <div class="lc-topbar-left">
            <img src="{{ asset('logo.png') }}" alt="Johen" class="lc-topbar-logo">
            <div class="lc-topbar-title">Admin <span>CS</span></div>
        </div>
        <div class="lc-topbar-right">
            <div class="lc-topbar-profile" id="lcProfile" onclick="this.classList.toggle('open')">
                <div class="lc-topbar-avatar">{{ substr($adminUser->name, 0, 1) }}</div>
                <span class="lc-topbar-name">{{ $adminUser->name }}</span>
                <i class="fas fa-chevron-down lc-topbar-chevron"></i>
                <div class="lc-topbar-dropdown">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="lc-main">
        @yield('content')
    </div>
    <script>
        document.addEventListener('click', function(e) {
            const profile = document.getElementById('lcProfile');
            if (profile && !profile.contains(e.target)) profile.classList.remove('open');
        });
    </script>
    @stack('scripts')
    @include('partials.push-subscribe', ['pushGuard' => 'csadmin'])
</body>
</html>
