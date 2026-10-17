<section id="informasi-profil" class="account-panel">
    <header class="account-panel-head">
        <span class="account-panel-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
        </span>
        <div>
            <h2>Informasi Profil</h2>
            <p>Perbarui nama dan alamat email yang terhubung ke akunmu.</p>
        </div>
    </header>

    <div class="account-panel-body">
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}" class="account-form">
            @csrf
            @method('patch')

            <div class="account-form-grid">
                <div class="account-field">
                    <label class="account-label" for="name">Nama lengkap</label>
                    <input id="name" name="name" type="text" class="account-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    <x-input-error class="account-error" :messages="$errors->get('name')" />
                </div>

                <div class="account-field">
                    <label class="account-label" for="email">Alamat email</label>
                    <input id="email" name="email" type="email" class="account-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
                    <x-input-error class="account-error" :messages="$errors->get('email')" />

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <p class="account-note">
                            Email belum diverifikasi.
                            <button form="send-verification">Kirim ulang email verifikasi</button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="account-saved" style="margin-top:.5rem">Tautan verifikasi baru telah dikirim.</p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="account-actions">
                <button type="submit" class="btn btn-solid account-save">Simpan Perubahan</button>

                @if (session('status') === 'profile-updated')
                    <span class="account-saved">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>
                        Profil tersimpan
                    </span>
                @endif
            </div>
        </form>
    </div>
</section>
