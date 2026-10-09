<x-guest-layout>
@section('title', 'Daftar — ' . config('app.name'))
@push('styles')
<style>.auth-container{width:min(540px,100%)!important}</style>
@endpush

<div class="auth-header">
    <h1>Buat Akun Baru</h1>
    <p>Daftar gratis dan nikmati promo spesial untuk member baru.</p>
</div>

<form method="POST" action="{{ route('register') }}" class="auth-form" id="registerForm">
    @csrf

    <div class="form-group">
        <label for="name">Nama Lengkap</label>
        <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Nama kamu" onfocus="this.closest('.input-wrap').classList.add('focused')" onblur="if(!this.value)this.closest('.input-wrap').classList.remove('focused')">
        </div>
        @error('name')
            <div class="error-text"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-wrap">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Username" onfocus="this.closest('.input-wrap').classList.add('focused')" onblur="if(!this.value)this.closest('.input-wrap').classList.remove('focused')">
            </div>
            @error('username')
                <div class="error-text"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <div class="input-wrap">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@email.com" onfocus="this.closest('.input-wrap').classList.add('focused')" onblur="if(!this.value)this.closest('.input-wrap').classList.remove('focused')">
            </div>
            @error('email')
                <div class="error-text"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="password">Kata Sandi</label>
            <div class="input-wrap">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" onfocus="this.closest('.input-wrap').classList.add('focused')" onblur="if(!this.value)this.closest('.input-wrap').classList.remove('focused')">
                <button type="button" class="toggle-pass" onclick="togglePass(this)" tabindex="-1" aria-label="Tampilkan sandi">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="error-text"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Konfirmasi Sandi</label>
            <div class="input-wrap">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi sandi" onfocus="this.closest('.input-wrap').classList.add('focused')" onblur="if(!this.value)this.closest('.input-wrap').classList.remove('focused')">
                <button type="button" class="toggle-pass" onclick="togglePass(this)" tabindex="-1" aria-label="Tampilkan sandi">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}" data-theme="dark"></div>
        @error('g-recaptcha-response')
            <div class="error-text"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn-primary" id="registerBtn">
        <span class="spinner"></span>
        <span class="btn-text"><i class="fas fa-user-plus"></i> Daftar</span>
    </button>
</form>

<div class="auth-divider"><span>atau</span></div>

<a href="{{ route('auth.google') }}" class="btn-google">
    <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
    Daftar dengan Google
</a>

<div class="auth-footer-text">
    Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
</div>

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
function togglePass(btn) {
    const input = btn.closest('.input-wrap').querySelector('input');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.classList.add('loading');
});

document.querySelectorAll('.input-wrap input').forEach(inp => {
    if (inp.value) inp.closest('.input-wrap').classList.add('focused');
});
</script>
@endpush
</x-guest-layout>
