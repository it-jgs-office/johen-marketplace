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

        <form method="post" action="{{ route('profile.avatar.update') }}" class="account-photo-form" enctype="multipart/form-data" id="profileAvatarForm">
            @csrf

            <div class="account-photo-editor">
                <div class="account-photo-preview" aria-hidden="true">
                    <img id="profilePhotoPreview" src="{{ $profileAvatarUrl ?: '' }}" data-original-src="{{ $profileAvatarUrl ?: '' }}" alt="Foto profil {{ $user->name }}" onerror="this.hidden=true;document.getElementById('profilePhotoFallback').hidden=false" @if(!$profileAvatarUrl) hidden @endif>
                    <span id="profilePhotoFallback" @if($profileAvatarUrl) hidden @endif>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div class="account-photo-copy">
                    <strong>Foto profil</strong>
                    <p>Gunakan JPG, PNG, atau WebP maksimal 5 MB.</p>
                    <div class="account-photo-actions">
                        <label for="avatar" class="account-photo-button">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
                            Pilih Foto
                        </label>
                        <input id="avatar" name="avatar" class="account-photo-input" type="file" accept="image/jpeg,image/png,image/webp">
                        <input id="remove_avatar" name="remove_avatar" type="hidden" value="0">
                        @if($profileAvatarUrl)
                            <button id="removeProfilePhoto" class="account-photo-remove" type="button">Hapus</button>
                        @endif
                    </div>
                    <x-input-error class="account-error" :messages="$errors->get('avatar')" />
                    @if (session('status') === 'avatar-updated')
                        <span class="account-saved" style="margin-top:.55rem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>
                            Foto profil tersimpan
                        </span>
                    @endif
                </div>
            </div>
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
