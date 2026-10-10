<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#01203c">
    <title>Admin - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ pwa_build() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --sidebar: #01203c;
            --sidebar-hover: #052a48;
            --sidebar-active: #2563eb;
            --bg-main: #020D2E;
            --bg-card: #0A1E50;
            --bg-input: #0d2448;
            --border: #0a2e5c;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --brand-cyan: #00d4ff;
            --brand-purple: #0ea5e9;
            --brand-gradient: linear-gradient(135deg, var(--accent), var(--brand-purple));
            --text: #f0f4ff;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --card-shadow: 0 4px 24px -8px rgba(0,0,0,0.3);
            --glass-bg: rgba(10,30,80,0.68);
            --glass-border: rgba(255,255,255,0.06);
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
        }

        [data-theme="light"] {
            --sidebar: #f1f5f9;
            --sidebar-hover: #e2e8f0;
            --sidebar-active: #2563eb;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --bg-input: #f1f5f9;
            --border: #e2e8f0;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --text: #0f172a;
            --text-muted: #475569;
            --text-dim: #94a3b8;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --glass-bg: rgba(255,255,255,0.7);
            --glass-border: rgba(0,0,0,0.06);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: var(--bg-main);
            color: var(--text);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            animation: orbFloat 14s ease-in-out infinite;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: rgba(37,99,235,0.10);
            top: -15%; right: -10%;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: rgba(0,212,255,0.06);
            bottom: -20%; left: -8%;
            animation-delay: -5s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: rgba(124,58,237,0.05);
            top: 40%; left: 50%;
            animation-delay: -9s;
        }
        @keyframes orbFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            25% { transform: translate(30px,-40px) scale(1.05); }
            50% { transform: translate(-20px,20px) scale(0.95); }
            75% { transform: translate(40px,30px) scale(1.02); }
        }



        .app-layout {
            position: relative; z-index: 1;
            display: flex; min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #01203c 0%, #0A1E50 100%);
            border-right: 1px solid rgba(255,255,255,0.08);
            display: flex;
            flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform 0.3s cubic-bezier(.22,1,.36,1);
            overflow: hidden;
        }
        .sidebar-brand {
            padding: 18px 20px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
        }
        .sidebar-brand .brand-logo-box {
            width: 42px; height: 42px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.20);
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-brand .brand-logo-box img {
            width: 28px; height: 28px;
            border-radius: 6px;
            object-fit: contain;
        }
        .sidebar-brand .brand-text { display: flex; flex-direction: column; }
        .sidebar-brand .brand-name {
            font-family: 'Poppins', sans-serif;
            font-size: 15px; font-weight: 700; color: #FFFFFF;
            line-height: 1.2;
        }
        .sidebar-brand .brand-sub {
            font-family: 'Poppins', sans-serif;
            font-size: 11px; font-weight: 400;
            color: rgba(255,255,255,0.55);
            margin-top: 1px;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 10px 12px;
        }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }

        .sidebar-nav .nav-section {
            font-size: 10px; text-transform: uppercase;
            letter-spacing: 0.08em; font-weight: 700;
            color: rgba(255,255,255,0.35);
            padding: 14px 12px 5px;
        }
        .sidebar-nav .nav-section:first-child { padding-top: 2px; }

        .sidebar-nav a, .sidebar-nav form button {
            display: flex; align-items: center; gap: 10px;
            padding: 0 12px; height: 38px;
            border-radius: 10px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 13px; font-weight: 500;
            transition: background 150ms ease;
            width: 100%; text-align: left;
            background: none; border: none; cursor: pointer;
            font-family: 'Poppins', sans-serif;
            position: relative;
            margin-bottom: 2px;
        }
        .sidebar-nav a i, .sidebar-nav form button i {
            width: 18px; text-align: center;
            font-size: 15px; opacity: 0.8;
        }
        .sidebar-nav a:hover, .sidebar-nav form button:hover {
            background: rgba(255,255,255,0.06);
        }
        .sidebar-nav a.active {
            background: var(--brand-gradient);
            color: #fff; font-weight: 700;
            box-shadow: 0 4px 12px -2px rgba(38,63,152,0.3);
        }
        .sidebar-nav a.active i { color: #fff; opacity: 1; }
        .sidebar-nav a.active::before {
            content: '';
            position: absolute; left: -12px; top: 50%; transform: translateY(-50%);
            width: 3px; height: 22px;
            background: var(--brand-cyan);
            border-radius: 0 4px 4px 0;
        }

        .sidebar-footer {
            flex-shrink: 0;
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 14px 12px 16px;
        }
        .sidebar-footer .logout-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 0 12px; height: 38px;
            border-radius: 10px;
            background: rgba(72, 69, 160, 0.45);
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 13px; font-weight: 500;
            transition: background 150ms ease;
            width: 100%; text-align: left;
            border: none; cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        .sidebar-footer .logout-btn i {
            width: 18px; text-align: center; font-size: 15px; opacity: 0.8;
        }
        .sidebar-footer .logout-btn:hover {
            background: rgba(72, 69, 160, 0.65);
        }

        /* HEADER */
        .main-area {
            flex: 1; margin-left: 260px;
            display: flex; flex-direction: column; min-height: 100vh;
            transition: margin-left 0.3s cubic-bezier(.22,1,.36,1);
        }
        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 0.75rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            transition: background 0.3s ease;
        }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .header-left .page-title { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1rem; }
        .hamburger {
            display: none; background: none; border: none;
            color: var(--text-muted); cursor: pointer;
            font-size: 1rem; padding: 0.25rem;
        }
        .header-right { display: flex; align-items: center; gap: 0.75rem; }

        .theme-toggle {
            width: 38px; height: 38px;
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            background: var(--bg-input);
            color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1rem;
            transition: all 0.2s ease;
        }
        .theme-toggle:hover { color: var(--accent); border-color: var(--accent); }

        .user-menu {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.4rem 0.8rem 0.4rem 0.4rem;
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            background: var(--bg-input);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .user-menu:hover { border-color: var(--accent); }
        .user-avatar {
            width: 30px; height: 30px;
            border-radius: 8px;
            background: var(--brand-gradient);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 700; color: #fff;
        }
        .user-name { font-size: 0.82rem; font-weight: 600; color: var(--text); }
        .user-dropdown {
            position: absolute; top: calc(100% + 6px); right: 0;
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.4rem;
            min-width: 160px;
            box-shadow: 0 12px 40px -8px rgba(0,0,0,0.3);
            opacity: 0; pointer-events: none;
            transform: translateY(-6px);
            transition: all 0.2s ease;
        }
        .user-dropdown.show { opacity: 1; pointer-events: auto; transform: translateY(0); }
        .user-dropdown a, .user-dropdown button {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.55rem 0.75rem; border-radius: 8px;
            color: var(--text-muted); text-decoration: none;
            font-size: 0.82rem; font-weight: 500;
            transition: all 0.15s ease;
            width: 100%; text-align: left;
            background: none; border: none; cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }
        .user-dropdown a:hover, .user-dropdown button:hover { background: var(--sidebar-hover); color: var(--text); }
        .user-dropdown .dropdown-divider { border: none; border-top: 1px solid var(--glass-border); margin: 0.3rem 0; }

        /* MAIN CONTENT */
        .main-content {
            flex: 1; padding: 1.5rem;
            min-width: 0;
            animation: fadeUp 0.4s ease-out;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* SIDEBAR OVERLAY MOBILE */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 99;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            opacity: 0; pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.show { opacity: 1; pointer-events: auto; }

        @media (max-width: 1023px) {
            .hamburger { display: flex; align-items: center; justify-content: center; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-area { margin-left: 0 !important; }
            .sidebar-overlay { display: block; }
        }

        @media (max-width: 479px) {
            .header { padding: 0.6rem 0.8rem; }
            .main-content { padding: 1rem 0.85rem 1.4rem; }
            .header-left { min-width: 0; gap: 0.5rem; }
            .header-left .page-title {
                font-size: 0.9rem; min-width: 0;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .user-name { display: none; }
            .user-menu { padding: 0.3rem 0.5rem 0.3rem 0.3rem; }
        }

        /* SUCCESS/ERROR MODAL */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 9998;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; pointer-events: auto; }
        .modal-box {
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2.5rem 2rem 2rem;
            width: 90%; max-width: 380px;
            text-align: center;
            box-shadow: 0 24px 64px -16px rgba(0,0,0,0.5);
            transform: scale(0.9) translateY(20px);
            transition: transform 0.35s cubic-bezier(.22,1,.36,1), background 0.3s ease;
        }
        .modal-overlay.show .modal-box { transform: scale(1) translateY(0); }
        .modal-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin: 0 auto 1rem;
        }
        .modal-icon.success { background: rgba(16,185,129,0.15); color: var(--success); }
        .modal-icon.error { background: rgba(239,68,68,0.15); color: var(--error); }
        .modal-title { font-weight: 800; font-size: 1.05rem; margin-bottom: 0.4rem; }
        .modal-title.success { color: var(--success); }
        .modal-title.error { color: var(--error); }
        .modal-message { color: var(--text-muted); font-size: 0.88rem; line-height: 1.5; margin-bottom: 1.5rem; }
        .modal-btn {
            padding: 0.7rem 2rem; border: none; border-radius: 12px;
            font-weight: 700; font-size: 0.85rem; cursor: pointer;
            transition: all 0.2s; color: #fff;
        }
        .modal-btn.success { background: var(--success); }
        .modal-btn.error { background: var(--error); }
        .modal-btn:hover { transform: translateY(-1px); filter: brightness(1.1); }

        /* TABLE MODERN */
        .table-wrap {
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            transition: background 0.3s ease;
        }
        .table-wrap table { width: 100%; }
        .table-wrap thead th {
            text-align: left; padding: 0.85rem 1rem;
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;
            color: var(--text-dim); font-weight: 600;
            border-bottom: 1px solid var(--glass-border);
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
        }
        .table-wrap tbody tr {
            border-bottom: 1px solid var(--glass-border);
            transition: all 0.15s ease;
        }
        .table-wrap tbody tr:last-child { border-bottom: none; }
        .table-wrap tbody tr:hover {
            background: rgba(9,135,245,0.03);
            transform: scale(1.001);
        }
        .table-wrap tbody td { padding: 0.75rem 1rem; font-size: 0.88rem; }

        /* CARD GLASS */
        .card-glass {
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            transition: all 0.25s ease, background 0.3s ease;
        }
        .card-glass:hover {
            box-shadow: 0 8px 32px -12px rgba(9,135,245,0.15);
            transform: translateY(-2px);
        }

        /* PAGE HEADER */
        .page-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 12px; flex-wrap: wrap; margin-bottom: 1.25rem;
        }
        .page-header .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem; font-weight: 700; color: var(--text); margin: 0;
        }
        .page-header .page-subtitle {
            font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0;
        }

        /* CARD HEADER */
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--glass-border);
        }

        @media (max-width: 479px) {
            .page-header { margin-bottom: 1rem; }
            .page-header .page-title { font-size: 1.1rem; }
            .card-header { padding: 0.85rem 0.9rem; }
        }

        /* INPUT STYLING */
        .input-field {
            width: 100%;
            background: var(--bg-input);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
            color: var(--text);
            font-size: 0.88rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }
        .input-field:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(9,135,245,0.1);
        }
        .input-field::placeholder { color: var(--text-dim); }

        /* BADGE */
        .badge {
            display: inline-flex; align-items: center;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.75rem; font-weight: 600;
        }
        .badge-success { background: rgba(16,185,129,0.12); color: var(--success); }
        .badge-warning { background: rgba(245,158,11,0.12); color: var(--warning); }
        .badge-error { background: rgba(239,68,68,0.12); color: var(--error); }
        .badge-info { background: rgba(59,130,246,0.12); color: var(--info); }
        .badge-neutral { background: rgba(148,163,184,0.12); color: var(--text-muted); }

        .badge-pulse { animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.3); }
            50% { box-shadow: 0 0 0 6px rgba(245,158,11,0); }
        }

        @keyframes stockFlash {
            0% { background: rgba(245,158,11,0.35); }
            100% { background: rgba(16,185,129,0.12); }
        }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
            padding: 0.55rem 1.1rem; border-radius: 10px;
            font-size: 0.82rem; font-weight: 600;
            border: none; cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn-primary { background: var(--brand-gradient); color: #fff; box-shadow: 0 4px 14px -4px rgba(37,99,235,0.4); }
        .btn-primary:hover { box-shadow: 0 6px 20px -4px rgba(37,99,235,0.5); }
        .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--glass-border); }
        .btn-ghost:hover { background: var(--sidebar-hover); color: var(--text); }
        .btn-danger { background: var(--error); color: #fff; }
        .btn-danger:hover { filter: brightness(1.1); }
        .btn-sm { padding: 0.35rem 0.7rem; font-size: 0.75rem; border-radius: 8px; }
        .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.7rem; border-radius: 6px; }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.25rem;
            transition: all 0.25s ease, background 0.3s ease;
            position: relative; overflow: hidden;
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            border-radius: 16px 16px 0 0;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 40px -12px rgba(9,135,245,0.15); }
        .stat-card .stat-icon {
            width: 42px; height: 42px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }

        /* FILTER BAR */
        .admin-filter-bar {
            display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;
            width: 100%; margin-bottom: 1rem;
        }
        .admin-filter-bar .search-wrap { flex: 1 1 240px; min-width: 0; }
        .admin-filter-bar .input-field {
            flex: 0 1 190px; width: auto; min-width: 150px; height: 42px;
            padding: 0.55rem 0.75rem; font-size: 0.82rem;
        }
        .admin-filter-bar .search-wrap .input-field { width: 100%; min-width: 0; padding-left: 2.4rem; }
        .admin-filter-bar .btn { min-height: 42px; }

        /* PAGINATION */
        .pagination-wrap { margin-top: 1.5rem; }
        .admin-pagination {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; width: 100%; margin-top: 1.5rem;
        }
        .pagination-wrap .admin-pagination { margin-top: 0; }
        .admin-pagination__summary {
            margin: 0; color: var(--text-dim); font-size: 0.8rem; white-space: nowrap;
        }
        .admin-pagination__summary strong { color: var(--text); font-weight: 700; }
        .admin-pagination__links {
            display: flex; align-items: center; justify-content: flex-end;
            flex-wrap: wrap; gap: 0.35rem;
        }
        .admin-pagination__control {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 0.5rem;
            border: 1px solid var(--glass-border); border-radius: 10px;
            background: var(--bg-input); color: var(--text-muted);
            font-size: 0.8rem; font-weight: 600; line-height: 1;
            text-decoration: none; transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .admin-pagination__control:hover { background: var(--sidebar-hover); border-color: var(--accent); color: var(--text); }
        .admin-pagination__control:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .admin-pagination__control.is-current { background: var(--brand-gradient); border-color: transparent; color: #fff; }
        .admin-pagination__control.is-disabled { cursor: not-allowed; opacity: 0.4; }
        .admin-pagination__control.is-dots { min-width: 22px; border-color: transparent; background: transparent; cursor: default; }
        .admin-pagination__control.is-dots:hover { border-color: transparent; background: transparent; color: var(--text-muted); }

        @media (max-width: 640px) {
            .admin-filter-bar { align-items: stretch; }
            .admin-filter-bar .search-wrap,
            .admin-filter-bar .input-field,
            .admin-filter-bar .btn { flex: 1 1 100%; width: 100%; min-width: 0; }
            .admin-pagination { flex-direction: column; align-items: stretch; gap: 0.75rem; }
            .admin-pagination__summary { text-align: center; white-space: normal; }
            .admin-pagination__links { justify-content: center; gap: 0.25rem; }
            .admin-pagination__control { min-width: 34px; height: 34px; }
        }

        /* SEARCH INPUT */
        .search-wrap { position: relative; }
        .search-wrap .search-icon {
            position: absolute; left: 0.85rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            pointer-events: none;
            font-size: 0.85rem;
        }
        .search-wrap input { padding-left: 2.4rem; }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 3rem 1rem; }
        .empty-state i { font-size: 2.2rem; color: var(--text-dim); margin-bottom: 1rem; display: block; }
        .empty-state p { color: var(--text-muted); font-size: 0.82rem; }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-dim); }

        /* IMAGE DROPZONE */
        .admin-image-dropzone {
            display: flex; align-items: center; gap: 0.7rem; width: 100%; min-height: 58px;
            padding: 0.65rem 0.75rem; border: 1px dashed color-mix(in srgb, var(--accent) 52%, var(--border));
            border-radius: 10px; background: color-mix(in srgb, var(--accent) 5%, transparent);
            color: var(--text); cursor: pointer; flex: 1 1 12rem;
            transition: border-color .18s ease, background .18s ease, transform .18s ease;
        }
        .admin-image-dropzone:hover, .admin-image-dropzone:focus-visible, .admin-image-dropzone.is-dragging {
            border-color: var(--accent); background: color-mix(in srgb, var(--accent) 12%, transparent); outline: none;
        }
        .admin-image-dropzone.is-dragging { transform: scale(1.01); }
        .admin-image-dropzone.is-disabled { cursor: not-allowed; opacity: .55; }
        .admin-image-dropzone > input[type="file"] { position: absolute !important; width: 1px !important; height: 1px !important; overflow: hidden !important; clip: rect(0 0 0 0) !important; clip-path: inset(50%) !important; white-space: nowrap !important; }
        .admin-image-dropzone__icon { display: grid; place-items: center; width: 32px; height: 32px; flex: 0 0 32px; border-radius: 9px; background: color-mix(in srgb, var(--accent) 16%, transparent); color: var(--accent); font-size: .9rem; }
        .admin-image-dropzone__copy { display: grid; gap: .1rem; min-width: 0; color: var(--text-dim); font-size: .68rem; line-height: 1.35; }
        .admin-image-dropzone__title { overflow: hidden; color: var(--text); font-size: .75rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .admin-image-dropzone__action { margin-left: auto; padding: .3rem .45rem; border: 1px solid var(--glass-border); border-radius: 6px; color: var(--text-muted); font-size: .63rem; font-weight: 600; white-space: nowrap; }
        .admin-image-dropzone.has-file .admin-image-dropzone__icon { background: rgba(16,185,129,.16); color: #6ee7b7; }
        @media (max-width: 480px) { .admin-image-dropzone__action { display: none; } }
    </style>
    @stack('styles')
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-layout">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-logo-box">
                    <img src="{{ asset('logo.png') }}" alt="Johen Gaming">
                </div>
                <div class="brand-text">
                    <div class="brand-name">Johen Marketplace</div>
                    <div class="brand-sub">Admin Panel</div>
                </div>
            </div>
            <nav class="sidebar-nav" id="adminSidebarNav">
                <div class="nav-section">Beranda</div>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>

                <div class="nav-section">Produk</div>
                <a href="{{ route('admin.products') }}" class="{{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                    <i class="fas fa-gem"></i> Top Up
                </a>
                <a href="{{ route('admin.account-listings') }}" class="{{ request()->routeIs('admin.account-listings*') ? 'active' : '' }}">
                    <i class="fas fa-store"></i> Jual Beli Akun
                </a>
                <a href="{{ route('admin.jba-game-cards') }}" class="{{ request()->routeIs('admin.jba-game-cards*') ? 'active' : '' }}">
                    <i class="fas fa-images"></i> Gambar Card Jual Beli
                </a>
                <div class="nav-section">Event</div>
                <a href="{{ route('admin.popup-banners') }}" class="{{ request()->routeIs('admin.popup-banners*') ? 'active' : '' }}">
                    <i class="fas fa-bullhorn"></i> Popup Banner
                </a>
                <a href="{{ route('admin.flash-sale-banners') }}" class="{{ request()->routeIs('admin.flash-sale-banners*') ? 'active' : '' }}">
                    <i class="fas fa-fire"></i> Banner Flash Sale
                </a>
                <a href="{{ route('admin.flash-deals') }}" class="{{ request()->routeIs('admin.flash-deals*') ? 'active' : '' }}">
                    <i class="fas fa-bolt"></i> Flash Deal
                </a>
                <a href="{{ route('admin.event-themes') }}" class="{{ request()->routeIs('admin.event-themes*') ? 'active' : '' }}">
                    <i class="fas fa-palette"></i> Tema Event
                </a>
                <a href="{{ route('admin.gacha-prizes') }}" class="{{ request()->routeIs('admin.gacha-prizes*') ? 'active' : '' }}">
                    <i class="fas fa-dice"></i> Gacha Voucher
                </a>
                <a href="{{ route('admin.vouchers.index') }}" class="{{ request()->routeIs('admin.vouchers*') ? 'active' : '' }}">
                    <i class="fas fa-ticket"></i> Voucher
                </a>

                <div class="nav-section">Pesanan</div>
                <a href="{{ route('admin.orders') }}" class="{{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i> Pesanan
                </a>
                <a href="{{ route('admin.account-orders') }}" class="{{ request()->routeIs('admin.account-orders*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i> Pesanan Akun
                </a>

                <div class="nav-section">Live Chat</div>
                <a href="{{ route('admin.live-chat.dashboard') }}" class="{{ request()->routeIs('admin.live-chat.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-comments"></i> Live Chat
                </a>
                <a href="{{ route('admin.live-chat.conversations') }}" class="{{ request()->routeIs('admin.live-chat.conversations*') ? 'active' : '' }}">
                    <i class="fas fa-envelope-open-text"></i> Percakapan
                </a>
                <a href="{{ route('admin.live-chat.channels') }}" class="{{ request()->routeIs('admin.live-chat.channels*') ? 'active' : '' }}">
                    <i class="fas fa-satellite-dish"></i> Channel
                </a>
                <a href="{{ route('admin.live-chat.operators') }}" class="{{ request()->routeIs('admin.live-chat.operators*') ? 'active' : '' }}">
                    <i class="fas fa-user-clock"></i> Admin
                </a>
                <a href="{{ route('admin.live-chat.admins') }}" class="{{ request()->routeIs('admin.live-chat.admins*') ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i> Admin Chat
                </a>

                <div class="nav-section">Pengguna</div>
                <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Kelola Akun
                </a>
                <a href="{{ route('admin.contact-inquiries') }}" class="{{ request()->routeIs('admin.contact-inquiries*') ? 'active' : '' }}">
                    <i class="fas fa-inbox"></i> Pesan Masuk
                </a>

                <div class="nav-section">Sistem</div>
                <a href="{{ route('admin.gateway-status') }}" class="{{ request()->routeIs('admin.gateway-status*') ? 'active' : '' }}">
                    <i class="fas fa-plug"></i> Status Gateway
                </a>
                <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Pengaturan
                </a>
            </nav>
            <div class="sidebar-footer">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN -->
        <div class="main-area">
            <header class="header">
                <div class="header-left">
                    <button class="hamburger" id="hamburgerBtn" aria-label="Buka menu sidebar" aria-expanded="false" aria-controls="sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="header-right">
                    <button class="theme-toggle" id="themeToggle" title="Ganti tema" aria-label="Ganti tema gelap/terang">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    <div class="user-menu" id="userMenu" role="button" aria-haspopup="true" aria-expanded="false" tabindex="0">
                        <div class="user-avatar">{{ substr(Auth::user()->name, 0, 1) }}</div>
                        <span class="user-name">{{ Auth::user()->name }}</span>
                        <div class="user-dropdown" id="userDropdown">
                            <a href="{{ route('home') }}"><i class="fas fa-store"></i> Lihat Toko</a>
                            <hr class="dropdown-divider">
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="main-content">
                @if (session('success'))
                    <div id="flash-success" data-message="{{ session('success') }}" style="display:none"></div>
                @endif
                @if (session('error'))
                    <div id="flash-error" data-message="{{ session('error') }}" style="display:none"></div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box">
            <div class="modal-icon" id="modalIcon"></div>
            <div class="modal-title" id="modalTitle"></div>
            <div class="modal-message" id="modalMessage"></div>
            <button class="modal-btn" id="modalBtn">OK</button>
        </div>
    </div>

    <script>
        // ===== THEME TOGGLE =====
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('admin-theme', theme);
            const icon = document.getElementById('themeIcon');
            if (theme === 'light') {
                icon.className = 'fas fa-sun';
                document.querySelector('meta[name="theme-color"]')?.setAttribute('content', '#f8fafc');
            } else {
                icon.className = 'fas fa-moon';
                document.querySelector('meta[name="theme-color"]')?.setAttribute('content', '#01203c');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const saved = localStorage.getItem('admin-theme') || 'dark';
            applyTheme(saved);

            document.getElementById('themeToggle')?.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-theme');
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        });

        // ===== SIDEBAR =====
        (function preserveSidebarScrollPosition() {
            const sidebarNav = document.getElementById('adminSidebarNav');
            const storageKey = 'johen-admin-sidebar-scroll-top';

            if (!sidebarNav) return;

            const savedPosition = sessionStorage.getItem(storageKey);
            if (savedPosition !== null) {
                requestAnimationFrame(function () {
                    sidebarNav.scrollTop = Number(savedPosition);
                });
            }

            const savePosition = function () {
                sessionStorage.setItem(storageKey, String(sidebarNav.scrollTop));
            };

            sidebarNav.addEventListener('scroll', savePosition, { passive: true });
            window.addEventListener('pagehide', savePosition);

            document.querySelectorAll('#adminSidebarNav a').forEach(function (link) {
                link.addEventListener('click', savePosition);
            });
        })();

        document.getElementById('hamburgerBtn')?.addEventListener('click', function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isOpen = sidebar.classList.toggle('open');
            overlay.classList.toggle('show', isOpen);
            this.setAttribute('aria-expanded', String(isOpen));
            this.setAttribute('aria-label', isOpen ? 'Tutup menu sidebar' : 'Buka menu sidebar');
        });

        function isDrawerMode() {
            return window.matchMedia('(max-width: 1023px)').matches;
        }

        function closeSidebar() {
            document.getElementById('sidebar')?.classList.remove('open');
            document.getElementById('sidebarOverlay')?.classList.remove('show');
            document.getElementById('hamburgerBtn')?.setAttribute('aria-expanded', 'false');
            document.getElementById('hamburgerBtn')?.setAttribute('aria-label', 'Buka menu sidebar');
        }

        document.querySelectorAll('#sidebar a, #sidebar form button').forEach(function (el) {
            el.addEventListener('click', function () {
                if (isDrawerMode()) closeSidebar();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !document.getElementById('modalOverlay')?.classList.contains('show')) {
                closeSidebar();
            }
        });

        document.getElementById('sidebarOverlay')?.addEventListener('click', function () {
            document.getElementById('sidebar').classList.remove('open');
            this.classList.remove('show');
            document.getElementById('hamburgerBtn')?.setAttribute('aria-expanded', 'false');
        });

        // ===== USER DROPDOWN =====
        document.getElementById('userMenu')?.addEventListener('click', function (e) {
            e.stopPropagation();
            const dd = document.getElementById('userDropdown');
            const open = dd.classList.toggle('show');
            this.setAttribute('aria-expanded', String(open));
        });
        document.getElementById('userMenu')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
        document.addEventListener('click', function () {
            const dd = document.getElementById('userDropdown');
            if (dd?.classList.contains('show')) {
                dd.classList.remove('show');
                document.getElementById('userMenu')?.setAttribute('aria-expanded', 'false');
            }
        });

        // ===== FLASH MODAL =====
        function showModal(type, message) {
            const overlay = document.getElementById('modalOverlay');
            const icon = document.getElementById('modalIcon');
            const title = document.getElementById('modalTitle');
            const msg = document.getElementById('modalMessage');
            const btn = document.getElementById('modalBtn');

            const titles = { success: 'Berhasil', error: 'Gagal' };
            const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle' };

            icon.className = 'modal-icon ' + type + ' fas ' + icons[type];
            title.className = 'modal-title ' + type;
            title.textContent = titles[type];
            msg.textContent = message;
            btn.className = 'modal-btn ' + type;
            btn.textContent = 'OK';
            overlay.classList.add('show');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const flashSuccess = document.getElementById('flash-success');
            const flashError = document.getElementById('flash-error');
            if (flashSuccess) showModal('success', flashSuccess.dataset.message);
            if (flashError) showModal('error', flashError.dataset.message);
        });

        document.getElementById('modalBtn')?.addEventListener('click', function () {
            document.getElementById('modalOverlay').classList.remove('show');
        });
        document.getElementById('modalOverlay')?.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('show');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.getElementById('modalOverlay')?.classList.remove('show');
        });

        // ===== IMAGE DROPZONE =====
        // Terapkan ke field gambar murni saja. Lampiran chat yang dapat berisi
        // video/dokumen tetap memakai alur lampiran khususnya masing-masing.
        function enhanceAdminImageUpload(input) {
            if (input.dataset.adminDropzoneReady === 'true') return;

            const acceptValues = (input.getAttribute('accept') || '')
                .split(',')
                .map((value) => value.trim())
                .filter(Boolean);
            const isImageOnly = acceptValues.length > 0 && acceptValues.every((value) => value.startsWith('image/'));

            if (!isImageOnly || input.closest('.thumbnail-dropzone') || input.closest('.chat-composer') || input.hasAttribute('capture')) {
                return;
            }

            const label = input.id ? document.querySelector(`label[for="${input.id}"]`) : null;
            const fieldName = label?.textContent?.trim() || input.name?.replace(/[_\[\]]/g, ' ').trim() || 'gambar';
            const dropzone = document.createElement('div');
            const icon = document.createElement('span');
            const copy = document.createElement('span');
            const title = document.createElement('strong');
            const subtitle = document.createElement('span');
            const action = document.createElement('span');
            let dragDepth = 0;

            dropzone.className = 'admin-image-dropzone';
            dropzone.setAttribute('role', 'button');
            dropzone.setAttribute('tabindex', input.disabled ? '-1' : '0');
            dropzone.setAttribute('aria-label', `Pilih atau seret gambar untuk ${fieldName}`);
            dropzone.classList.toggle('is-disabled', input.disabled);

            icon.className = 'admin-image-dropzone__icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<i class="fas fa-cloud-arrow-up"></i>';
            copy.className = 'admin-image-dropzone__copy';
            title.className = 'admin-image-dropzone__title';
            subtitle.textContent = input.multiple ? 'Seret satu atau beberapa gambar ke sini' : 'Seret gambar ke sini atau klik untuk memilih';
            action.className = 'admin-image-dropzone__action';
            action.setAttribute('aria-hidden', 'true');
            action.textContent = 'Pilih gambar';
            copy.append(title, subtitle);

            input.parentNode.insertBefore(dropzone, input);
            dropzone.append(input, icon, copy, action);
            input.dataset.adminDropzoneReady = 'true';
            input.setAttribute('tabindex', '-1');

            const updateFileLabel = function () {
                const files = Array.from(input.files || []);
                if (!files.length) {
                    title.textContent = input.multiple ? 'Belum ada gambar dipilih' : 'Belum ada gambar dipilih';
                    dropzone.classList.remove('has-file');
                    return;
                }

                title.textContent = files.length === 1 ? files[0].name : `${files.length} gambar dipilih`;
                dropzone.classList.add('has-file');
            };

            const openPicker = function () {
                if (!input.disabled) input.click();
            };

            dropzone.addEventListener('click', function (event) {
                if (event.target !== input) openPicker();
            });
            dropzone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openPicker();
                }
            });
            input.addEventListener('change', updateFileLabel);

            dropzone.addEventListener('dragenter', function (event) {
                event.preventDefault();
                dragDepth += 1;
                if (!input.disabled) dropzone.classList.add('is-dragging');
            });
            dropzone.addEventListener('dragover', function (event) {
                event.preventDefault();
            });
            dropzone.addEventListener('dragleave', function (event) {
                event.preventDefault();
                dragDepth = Math.max(0, dragDepth - 1);
                if (!dragDepth) dropzone.classList.remove('is-dragging');
            });
            dropzone.addEventListener('drop', function (event) {
                event.preventDefault();
                dragDepth = 0;
                dropzone.classList.remove('is-dragging');
                if (input.disabled) return;

                const files = Array.from(event.dataTransfer?.files || []).filter((file) => file.type.startsWith('image/'));
                if (!files.length) return;

                const transfer = new DataTransfer();
                (input.multiple ? files : files.slice(0, 1)).forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            input.form?.addEventListener('reset', function () {
                requestAnimationFrame(updateFileLabel);
            });

            updateFileLabel();
        }

        function enhanceAdminImageUploads(root = document) {
            root.querySelectorAll('input[type="file"][accept*="image"]').forEach(enhanceAdminImageUpload);
        }

        enhanceAdminImageUploads();

        // ===== CONFIRM DELETE =====
        let deleteForm = null;
        function confirmDelete(action, message) {
            const overlay = document.getElementById('modalOverlay');
            const icon = document.getElementById('modalIcon');
            const title = document.getElementById('modalTitle');
            const msg = document.getElementById('modalMessage');
            const btn = document.getElementById('modalBtn');

            icon.className = 'modal-icon error fas fa-exclamation-triangle';
            title.className = 'modal-title error';
            title.textContent = 'Konfirmasi Hapus';
            msg.textContent = message || 'Yakin ingin menghapus?';
            btn.className = 'modal-btn error';
            btn.textContent = 'Ya, Hapus';
            document.getElementById('modalOverlay').classList.add('show');
            deleteForm = action;
        }
        document.getElementById('modalBtn')?.addEventListener('click', function () {
            if (deleteForm) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = deleteForm;
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrf);
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
            document.getElementById('modalOverlay').classList.remove('show');
            deleteForm = null;
        });

    </script>
    @stack('scripts')
    @include('partials.push-subscribe', ['pushGuard' => 'admin'])
</body>
</html>
