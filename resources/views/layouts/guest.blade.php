<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Johen Gaming Marketplace'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @stack('styles')
    <style>
        :root{
            --auth-primary:#2563eb;
            --auth-accent:#00d4ff;
            --auth-purple:#0ea5e9;
            --auth-gradient:linear-gradient(92deg,var(--auth-primary),var(--auth-purple));
            --auth-bg:linear-gradient(160deg,#01203c,#020D2E);
            --auth-text:#F0F4FF;
            --auth-card-bg:linear-gradient(180deg,#0A1E50,rgba(10,30,80,.95));
            --auth-card-border:rgba(0,212,255,.14);
            --auth-card-shadow:0 0 0 1px rgba(0,212,255,.06),0 24px 70px -24px rgba(2,13,46,.9),0 0 60px -24px rgba(37,99,235,.22);
            --auth-label:#DBEAFE;
            --auth-muted:#94A3B8;
            --auth-input-bg:rgba(1,32,60,.65);
            --auth-input-border:#0A2E5C;
            --auth-input-placeholder:#6F89A7;
            --auth-input-color:#F0F4FF;
            --auth-icon-color:#8FA8C4;
        }
        [data-theme="light"]{
            --auth-bg:linear-gradient(160deg,#eef2f7,#f2f6fa);
            --auth-text:#1a1a2e;
            --auth-card-bg:rgba(255,255,255,.85);
            --auth-card-border:rgba(37,99,235,.12);
            --auth-card-shadow:0 0 0 1px rgba(37,99,235,.06),0 10px 40px -12px rgba(0,0,0,.12);
            --auth-label:#334155;
            --auth-muted:#64748b;
            --auth-input-bg:rgba(0,0,0,.03);
            --auth-input-border:rgba(37,99,235,.15);
            --auth-input-placeholder:#94a3b8;
            --auth-input-color:#1a1a2e;
            --auth-icon-color:#64748b;
        }

        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html{scroll-behavior:smooth;}
        body{
            font-family:'Poppins',sans-serif;
            background:var(--auth-bg);
            color:var(--auth-text);
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            position:relative;
            overflow-x:hidden;
        }

        .auth-theme-btn{
            position:fixed;top:1.2rem;right:1.2rem;z-index:10;
            width:40px;height:40px;border-radius:12px;
            background:rgba(10,30,80,.55);
            border:1px solid rgba(0,212,255,.28);
            color:#8FA8C4;
            cursor:pointer;display:flex;align-items:center;justify-content:center;
            backdrop-filter:blur(10px);
            transition:all .25s ease;
        }
        .auth-theme-btn:hover{
            color:#fff;
            border-color:rgba(0,212,255,.55);
            box-shadow:0 0 20px -6px rgba(0,212,255,.35);
            transform:translateY(-1px);
        }
        .auth-theme-btn svg{width:18px;height:18px;}
        .auth-theme-btn .icon-sun,
        .auth-theme-btn .icon-moon{display:none;}
        [data-theme="dark"] .auth-theme-btn .icon-moon,
        html:not([data-theme="light"]) .auth-theme-btn .icon-moon{display:block;}
        [data-theme="light"] .auth-theme-btn .icon-sun{display:block;}

        .auth-bg-glow{
            position:fixed;inset:0;pointer-events:none;z-index:0;
            overflow:hidden;
        }
        .auth-bg-glow .glow{
            position:absolute;border-radius:50%;filter:blur(100px);
            animation:glowFloat 14s ease-in-out infinite;
        }
        .auth-bg-glow .glow-1{
            width:550px;height:550px;
            background:rgba(37,99,235,.32);
            top:-20%;right:-10%;
        }
        .auth-bg-glow .glow-2{
            width:400px;height:400px;
            background:rgba(0,212,255,.18);
            bottom:-25%;left:-8%;
            animation-delay:-5s;
        }
        .auth-bg-glow .glow-3{
            width:300px;height:300px;
            background:rgba(124,58,237,.18);
            top:50%;left:60%;
            animation-delay:-10s;
        }
        [data-theme="light"] .auth-bg-glow .glow-1{background:rgba(37,99,235,.12);}
        [data-theme="light"] .auth-bg-glow .glow-2{background:rgba(0,212,255,.08);}
        [data-theme="light"] .auth-bg-glow .glow-3{background:rgba(124,58,237,.06);}
        @keyframes glowFloat{
            0%,100%{transform:translate(0,0) scale(1);}
            25%{transform:translate(40px,-50px) scale(1.08);}
            50%{transform:translate(-30px,30px) scale(.92);}
            75%{transform:translate(50px,40px) scale(1.05);}
        }

        .auth-page{
            position:relative;z-index:1;
            width:100%;min-height:100vh;
            display:flex;align-items:center;justify-content:center;
            padding:1.5rem;
        }

        .auth-container{
            width:min(400px,100%);
            background:var(--auth-card-bg);
            backdrop-filter:blur(30px);
            border:1px solid var(--auth-card-border);
            border-radius:18px;
            padding:28px 28px 24px;
            box-shadow:var(--auth-card-shadow);
            animation:containerIn .55s ease-out;
        }
        @keyframes containerIn{
            from{opacity:0;transform:translateY(24px) scale(.97);}
            to{opacity:1;transform:translateY(0) scale(1);}
        }

        .auth-brand{
            display:flex;justify-content:center;
            margin-bottom:10px;
            animation:fadeUp .5s ease-out .1s both;
        }
        .auth-logo-img{
            width:46px;height:46px;object-fit:contain;
            filter:drop-shadow(0 8px 22px rgba(37,99,235,.25));
            transition:transform .25s;
        }
        .auth-logo-img:hover{transform:scale(1.05);}
        .auth-header{
            text-align:center;
            margin-bottom:22px;
            animation:fadeUp .5s ease-out .15s both;
        }
        .auth-header h1{
            font-family:'Poppins',sans-serif;
            font-size:18px;font-weight:800;
            margin-bottom:4px;
        }
        .auth-header p{
            color:var(--auth-muted);
            font-size:11.5px;
            line-height:1.5;
        }

        .auth-form{animation:fadeUp .5s ease-out .25s both;}

        .form-group{margin-bottom:16px;}
        .form-row{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:18px;
        }
        @media(max-width:400px){
            .form-row{grid-template-columns:1fr;}
        }

        .form-group label{
            display:block;
            font-size:12px;font-weight:600;
            color:var(--auth-label);
            margin-bottom:4px;
        }

        .input-wrap{position:relative;height:40px;}
        .input-wrap .input-icon{
            position:absolute;left:12px;top:50%;
            transform:translateY(-50%);
            width:16px;height:16px;
            color:var(--auth-icon-color);
            transition:color .25s;
            pointer-events:none;
        }
        .input-wrap.focused .input-icon{color:var(--auth-accent);}

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"]{
            width:100%;height:40px;
            padding:0 42px 0 38px;
            background:var(--auth-input-bg);
            border:1px solid var(--auth-input-border);
            border-radius:10px;
            color:var(--auth-input-color);
            font-size:13px;
            font-family:'Poppins',sans-serif;
            transition:all .25s ease;
            outline:none;
        }
        .form-group input::placeholder{color:var(--auth-input-placeholder);}
        .form-group input:hover{border-color:rgba(0,212,255,.30);}
        .form-group input:focus{
            border-color:var(--auth-primary);
            background:rgba(37,99,235,.04);
            box-shadow:0 0 0 3px rgba(37,99,235,.14),0 0 22px -8px rgba(0,212,255,.35);
        }
        .form-group input.error{
            border-color:#ef4444;
            box-shadow:0 0 0 3px rgba(239,68,68,.1);
        }

        .toggle-pass{
            position:absolute;right:8px;top:50%;transform:translateY(-50%);
            width:30px;height:30px;
            display:flex;align-items:center;justify-content:center;
            background:none;border:none;border-radius:8px;
            color:var(--auth-icon-color);cursor:pointer;
            font-size:.8rem;
            transition:color .2s,background .2s;
        }
        .toggle-pass:hover{color:var(--auth-label);background:rgba(37,99,235,.08);}

        .form-group .error-text{
            font-size:11px;color:#f87171;
            margin-top:5px;
            display:flex;align-items:center;gap:5px;
        }

        .form-check{
            display:flex;align-items:center;justify-content:space-between;
            margin:16px 0 10px;
        }
        .form-check label{
            display:flex;align-items:center;gap:6px;
            font-size:11.5px;color:var(--auth-muted);
            cursor:pointer;
            text-transform:none;letter-spacing:0;
        }
        .form-check input[type="checkbox"]{
            width:14px;height:14px;
            accent-color:var(--auth-primary);
            cursor:pointer;
        }
        .form-check a{
            color:var(--auth-accent);
            font-size:11.5px;text-decoration:none;
            font-weight:600;
            transition:opacity .2s;
        }
        .form-check a:hover{opacity:.8;text-decoration:underline;}

        .btn-primary{
            width:100%;height:42px;
            background:var(--auth-gradient);
            color:#fff;
            border:none;
            border-radius:10px;
            font-size:13.5px;
            font-weight:700;
            font-family:'Poppins',sans-serif;
            cursor:pointer;
            transition:all .25s ease;
            position:relative;
            display:flex;align-items:center;justify-content:center;
            gap:8px;
            box-shadow:0 10px 26px -12px rgba(37,99,235,.55),0 6px 18px -12px rgba(124,58,237,.5);
        }
        .btn-primary:hover{
            filter:brightness(1.1);
            box-shadow:0 14px 34px -12px rgba(37,99,235,.7),0 8px 24px -12px rgba(124,58,237,.6),0 0 26px -8px rgba(0,212,255,.3);
        }
        .btn-primary:active{transform:translateY(0);}
        .btn-primary:disabled{
            opacity:.6;cursor:not-allowed;
            transform:none;box-shadow:none;
        }
        .btn-primary .spinner{
            width:17px;height:17px;
            border:2px solid rgba(255,255,255,.35);
            border-top-color:#fff;
            border-radius:50%;
            animation:spin .6s linear infinite;
            display:none;
        }
        .btn-primary.loading .spinner{display:block;}
        .btn-primary.loading .btn-text{opacity:.7;}
        @keyframes spin{to{transform:rotate(360deg);}}

        .auth-footer-text{
            text-align:center;
            margin-top:14px;
            font-size:11.5px;color:var(--auth-muted);
            animation:fadeUp .4s ease-out .4s both;
        }
        .auth-footer-text a{
            color:var(--auth-accent);
            text-decoration:none;font-weight:700;
            transition:opacity .2s;
        }
        .auth-footer-text a:hover{opacity:.8;text-decoration:underline;}

        .alert{
            padding:9px 11px;border-radius:9px;
            font-size:11.5px;margin-bottom:14px;
            display:flex;align-items:center;gap:8px;
            animation:fadeUp .3s ease-out;
        }
        .alert svg{width:12px;height:12px;flex-shrink:0;}
        .alert-success{
            background:rgba(52,211,153,.08);
            border:1px solid rgba(52,211,153,.15);
            color:#34d399;
        }
        .alert-error{
            background:rgba(239,68,68,.08);
            border:1px solid rgba(239,68,68,.15);
            color:#f87171;
        }

        .auth-divider{
            display:flex;align-items:center;gap:.5rem;
            margin:14px 0 10px;
            animation:fadeUp .4s ease-out .3s both;
        }
        .auth-divider::before,
        .auth-divider::after{
            content:'';flex:1;height:1px;
            background:rgba(37,99,235,.15);
        }
        .auth-divider span{
            font-size:11.5px;color:var(--auth-muted);
            text-transform:uppercase;letter-spacing:.04em;
            white-space:nowrap;
        }

        .btn-google{
            display:flex;align-items:center;justify-content:center;gap:.5rem;
            width:100%;height:42px;
            background:var(--auth-input-bg);
            border:1px solid var(--auth-input-border);
            border-radius:10px;
            color:var(--auth-text);
            font-size:13px;font-weight:600;
            font-family:'Poppins',sans-serif;
            text-decoration:none;
            cursor:pointer;
            transition:all .25s ease;
            animation:fadeUp .5s ease-out .35s both;
        }
        .btn-google:hover{
            border-color:rgba(37,99,235,.45);
            background:rgba(37,99,235,.08);
            transform:translateY(-1px);
        }

        @keyframes fadeUp{
            from{opacity:0;transform:translateY(10px);}
            to{opacity:1;transform:translateY(0);}
        }

        @media (prefers-reduced-motion: reduce){
            .auth-page,.auth-container,.alert{animation:none;}
            *{transition:none !important;}
        }

        @media(max-width:500px){
            .auth-page{padding:12px;}
            .auth-container{padding:22px 20px 20px;border-radius:16px;}
            .auth-header h1{font-size:16px;}
        }
    </style>
</head>
<body>
    <button class="auth-theme-btn" id="authThemeToggle" onclick="toggleAuthTheme()" aria-label="Ganti tema">
        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
    </button>

    <div class="auth-bg-glow">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
        <div class="glow glow-3"></div>
    </div>

    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-brand">
                <a href="{{ route('home') }}">
                    <img src="{{ pwa_asset('logo.png') }}" alt="Johen Gaming" class="auth-logo-img">
                </a>
            </div>

            @if (session('error'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if (session('status'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>

    <script>
    (function() {
        var theme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', theme);
    })();
    function toggleAuthTheme() {
        var html = document.documentElement;
        var current = html.getAttribute('data-theme');
        var next = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: next } }));
    }
    </script>

    @stack('scripts')
</body>
</html>
