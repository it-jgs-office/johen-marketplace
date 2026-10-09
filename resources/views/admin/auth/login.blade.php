<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — Masuk — {{ config('app.name', 'Johen Gaming Marketplace') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        :root{
            --bg-0:#020D2E;
            --bg-1:#061536;
            --card:#071A46;
            --card-edge:rgba(56,189,248,.14);
            --card-glow:0 0 0 1px rgba(56,189,248,.06),0 24px 70px -24px rgba(2,10,40,.9),0 0 60px -24px rgba(0,207,255,.18);
            --line:#18356B;
            --input-bg:rgba(2,13,46,.6);
            --text:#F8FAFC;
            --muted:#94A3B8;
            --label:#E6EDFB;
            --icon:#8FA8D9;
            --blue:#2563EB;
            --cyan:#00CFFF;
            --purple:#7C3AED;
            --error:#F87171;
        }

        *{box-sizing:border-box;margin:0;padding:0;}
        html{scroll-behavior:smooth;}
        html,body{overflow-x:hidden;}

        body{
            min-height:100vh;
            font-family:'Poppins',sans-serif;
            color:var(--text);
            background:linear-gradient(160deg,var(--bg-0),var(--bg-1));
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            position:relative;
        }

        /* ---------- Background ---------- */
        .bg{position:fixed;inset:0;z-index:0;pointer-events:none;overflow:hidden;}
        .bg .glow{position:absolute;border-radius:50%;filter:blur(120px);}
        .bg .glow-tl{
            width:460px;height:460px;left:-140px;top:-160px;
            background:rgba(37,99,235,.32);
        }
        .bg .glow-tc{
            width:520px;height:520px;left:50%;top:0;transform:translateX(-50%);
            background:rgba(0,207,255,.2);
        }
        .bg .glow-br{
            width:480px;height:480px;right:-160px;bottom:-180px;
            background:rgba(124,58,237,.3);
        }

        /* ---------- Corner settings button ---------- */
        .corner-btn{
            position:fixed;top:20px;right:20px;z-index:20;
            width:40px;height:40px;border-radius:12px;
            display:flex;align-items:center;justify-content:center;
            background:rgba(7,26,70,.5);
            border:1px solid rgba(56,189,248,.28);
            color:#AFC6EE;
            cursor:pointer;
            backdrop-filter:blur(10px);
            transition:border-color .25s,color .25s,transform .25s,box-shadow .25s;
        }
        .corner-btn svg{width:18px;height:18px;}
        .corner-btn:hover{
            color:#fff;
            border-color:rgba(0,207,255,.55);
            box-shadow:0 0 20px -6px rgba(0,207,255,.35);
            transform:translateY(-1px);
        }
        .corner-btn:focus-visible{
            outline:2px solid var(--cyan);outline-offset:2px;
        }

        /* ---------- Card ---------- */
        .page{
            position:relative;z-index:1;
            width:100%;
            display:flex;align-items:center;justify-content:center;
            animation:cardIn .55s ease-out;
        }
        @keyframes cardIn{
            from{opacity:0;transform:translateY(22px) scale(.985);}
            to{opacity:1;transform:translateY(0) scale(1);}
        }

        .card{
            width:min(400px,100%);
            background:linear-gradient(180deg,var(--card),rgba(8,28,72,.95));
            border:1px solid var(--card-edge);
            border-radius:20px;
            padding:38px 36px 34px;
            box-shadow:var(--card-glow);
        }

        /* ---------- Logo ---------- */
        .brand{display:flex;justify-content:center;margin-bottom:26px;}
        .brand img{
            width:58px;height:58px;object-fit:contain;
            filter:drop-shadow(0 8px 22px rgba(0,207,255,.22));
        }

        /* ---------- Title ---------- */
        .head{text-align:center;margin-bottom:30px;}
        .head h1{
            font-family:'Poppins',sans-serif;
            font-size:23px;font-weight:800;
            letter-spacing:.01em;
            background:linear-gradient(92deg,#4FB8FF 10%,#A78BFA 90%);
            -webkit-background-clip:text;
            background-clip:text;
            -webkit-text-fill-color:transparent;
            color:#EAF2FF;
            margin-bottom:9px;
        }
        .head p{
            color:var(--muted);
            font-size:12.5px;
            line-height:1.55;
        }

        /* ---------- Fields ---------- */
        .field{margin-bottom:18px;}
        .field label{
            display:block;
            font-size:13px;font-weight:600;
            color:var(--label);
            margin-bottom:8px;
        }

        .input{
            position:relative;
            height:46px;
        }
        .input > svg{
            position:absolute;left:14px;top:50%;transform:translateY(-50%);
            width:18px;height:18px;
            color:var(--icon);
            pointer-events:none;
            transition:color .25s;
        }
        .input input{
            width:100%;height:46px;
            padding:0 46px 0 42px;
            background:var(--input-bg);
            border:1px solid var(--line);
            border-radius:12px;
            color:var(--text);
            font-family:'Poppins',sans-serif;
            font-size:14px;
            outline:none;
            transition:border-color .25s,box-shadow .25s,background .25s;
        }
        .input input::placeholder{color:var(--muted);opacity:.75;}
        .input:focus-within > svg{color:var(--cyan);}
        .input input:focus{
            border-color:var(--cyan);
            box-shadow:0 0 0 3px rgba(0,207,255,.14),0 0 22px -8px rgba(0,207,255,.4);
        }
        .input input.error{
            border-color:rgba(248,113,113,.7);
            box-shadow:0 0 0 3px rgba(248,113,113,.12);
        }

        .toggle-pass{
            position:absolute;right:10px;top:50%;transform:translateY(-50%);
            width:34px;height:34px;
            display:flex;align-items:center;justify-content:center;
            background:none;border:none;border-radius:9px;
            color:var(--icon);cursor:pointer;
            transition:color .2s,background .2s;
        }
        .toggle-pass:hover{color:var(--text);background:rgba(0,207,255,.08);}
        .toggle-pass svg{width:18px;height:18px;position:static;transform:none;color:inherit;}
        .toggle-pass .ico-slash{display:none;}
        .toggle-pass.visible .ico-eye{display:none;}
        .toggle-pass.visible .ico-slash{display:block;}

        .error-text{
            display:flex;align-items:center;gap:6px;
            font-size:12px;color:var(--error);
            margin-top:7px;
        }
        .error-text svg{width:13px;height:13px;flex-shrink:0;}

        /* ---------- Remember ---------- */
        .row{
            display:flex;align-items:center;justify-content:space-between;
            margin:22px 0 18px;
        }
        .check{
            display:flex;align-items:center;gap:8px;
            font-size:12.5px;color:var(--muted);
            cursor:pointer;user-select:none;
        }
        .check input{
            width:15px;height:15px;cursor:pointer;
            accent-color:var(--blue);
        }

        /* ---------- Button ---------- */
        .btn-login{
            position:relative;
            width:100%;height:47px;border:none;border-radius:11px;
            background:linear-gradient(92deg,#2563EB,#0EA5E9);
            color:#fff;
            font-family:'Poppins',sans-serif;
            font-size:14.5px;font-weight:700;
            display:flex;align-items:center;justify-content:center;gap:9px;
            cursor:pointer;
            box-shadow:0 10px 26px -12px rgba(37,99,235,.55),0 6px 18px -12px rgba(124,58,237,.5);
            transition:box-shadow .25s,filter .25s,transform .15s;
        }
        .btn-login svg{width:17px;height:17px;}
        .btn-login:hover{
            filter:brightness(1.1);
            box-shadow:0 14px 34px -12px rgba(37,99,235,.7),0 8px 24px -12px rgba(124,58,237,.6),0 0 26px -8px rgba(124,58,237,.4);
        }
        .btn-login:active{transform:scale(.98);}
        .btn-login:focus-visible{outline:2px solid var(--cyan);outline-offset:2px;}
        .btn-login:disabled{opacity:.65;cursor:not-allowed;transform:none;filter:none;}
        .btn-login .spinner{
            width:17px;height:17px;display:none;
            border:2px solid rgba(255,255,255,.35);
            border-top-color:#fff;border-radius:50%;
            animation:spin .6s linear infinite;
        }
        .btn-login.loading .spinner{display:block;}
        .btn-login.loading .btn-text{opacity:.75;}
        @keyframes spin{to{transform:rotate(360deg);}}

        /* ---------- Back link ---------- */
        .back{
            display:block;text-align:center;
            margin-top:20px;
            font-size:12.5px;font-weight:600;
            color:var(--cyan);
            text-decoration:none;
            transition:color .2s;
        }
        .back:hover{color:#7DD3FC;}

        /* ---------- Alerts ---------- */
        .alert{
            display:flex;align-items:center;gap:9px;
            padding:11px 13px;border-radius:11px;
            font-size:12.5px;margin-bottom:20px;
            animation:alertIn .3s ease-out;
        }
        @keyframes alertIn{from{opacity:0;transform:translateY(-5px);}to{opacity:1;transform:translateY(0);}}
        .alert svg{width:14px;height:14px;flex-shrink:0;}
        .alert-error{
            background:rgba(248,113,113,.08);
            border:1px solid rgba(248,113,113,.25);
            color:#FCA5A5;
        }
        .alert-success{
            background:rgba(52,211,153,.08);
            border:1px solid rgba(52,211,153,.25);
            color:#6EE7B7;
        }

        @media (prefers-reduced-motion: reduce){
            .page,.alert{animation:none;}
            *{transition:none !important;}
        }

        @media(max-width:500px){
            body{padding:16px;}
            .card{padding:32px 24px 30px;border-radius:18px;}
            .corner-btn{top:14px;right:14px;}
            .head h1{font-size:21px;}
        }
    </style>
</head>
<body>
    <div class="bg">
        <div class="glow glow-tl"></div>
        <div class="glow glow-tc"></div>
        <div class="glow glow-br"></div>
    </div>

    <button class="corner-btn" aria-label="Settings" title="Settings">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    </button>

    <main class="page">
        <section class="card" aria-label="Login Admin">
            <div class="brand">
                <img src="{{ asset('logo.png') }}" alt="Logo Johen">
            </div>

            <div class="head">
                <h1>Admin Panel</h1>
                <p>PT. Johen Sukses Abadi — Masuk untuk melanjutkan</p>
            </div>

            @if (session('error'))
                <div class="alert alert-error" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ session('error') }}
                </div>
            @endif
            @if (session('status'))
                <div class="alert alert-success" role="status">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" id="adminLoginForm" novalidate>
                @csrf

                <div class="field">
                    <label for="username">Username atau Email</label>
                    <div class="input">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Username atau email admin" class="{{ $errors->has('username') ? 'error' : '' }}">
                    </div>
                    @error('username')
                        <div class="error-text">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="kata sandi" class="{{ $errors->has('password') ? 'error' : '' }}">
                        <button type="button" class="toggle-pass" onclick="togglePass(this)" tabindex="-1" aria-label="Tampilkan atau sembunyikan sandi">
                            <svg class="ico-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="ico-slash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="error-text">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="row">
                    <label class="check">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        Ingat saya
                    </label>
                </div>

                <div class="field">
                    <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}" data-theme="dark"></div>
                    @error('g-recaptcha-response')
                        <div class="error-text">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn-login" id="adminLoginBtn">
                    <span class="spinner" aria-hidden="true"></span>
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <span class="btn-text">Masuk</span>
                </button>
            </form>

            <a href="{{ route('home') }}" class="back">&larr; Kembali ke Website</a>
        </section>
    </main>

    <script>
    function togglePass(btn) {
        const input = btn.closest('.input').querySelector('input');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        btn.classList.toggle('visible', !visible);
    }

    document.getElementById('adminLoginForm')?.addEventListener('submit', function(e) {
        const btn = document.getElementById('adminLoginBtn');
        btn.disabled = true;
        btn.classList.add('loading');
    });
    </script>
</body>
</html>
