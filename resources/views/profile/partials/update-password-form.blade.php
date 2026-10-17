<section id="keamanan-akun" class="account-panel">
    <header class="account-panel-head">
        <span class="account-panel-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
        <div>
            <h2>Keamanan Akun</h2>
            <p>Gunakan password yang kuat dan berbeda dari akun lainnya.</p>
        </div>
    </header>

    <div class="account-panel-body">
        <form method="post" action="{{ route('password.update') }}" class="account-form">
            @csrf
            @method('put')

            <div class="account-field">
                <label class="account-label" for="update_password_current_password">Password saat ini</label>
                <input id="update_password_current_password" name="current_password" type="password" class="account-input" autocomplete="current-password" placeholder="Masukkan password saat ini">
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="account-error" />
            </div>

            <div class="account-form-grid">
                <div class="account-field">
                    <label class="account-label" for="update_password_password">Password baru</label>
                    <input id="update_password_password" name="password" type="password" class="account-input" autocomplete="new-password" placeholder="Minimal 8 karakter">
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="account-error" />
                </div>

                <div class="account-field">
                    <label class="account-label" for="update_password_password_confirmation">Konfirmasi password baru</label>
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="account-input" autocomplete="new-password" placeholder="Ulangi password baru">
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="account-error" />
                </div>
            </div>

            <div class="account-actions">
                <button type="submit" class="btn btn-solid account-save">Perbarui Password</button>

                @if (session('status') === 'password-updated')
                    <span class="account-saved">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>
                        Password diperbarui
                    </span>
                @endif
            </div>
        </form>
    </div>
</section>
