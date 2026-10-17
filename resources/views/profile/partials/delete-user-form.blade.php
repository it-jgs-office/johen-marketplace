<section id="hapus-akun" class="account-panel account-panel--danger">
    <header class="account-panel-head">
        <span class="account-panel-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v5M14 11v5"/></svg>
        </span>
        <div>
            <h2>Hapus Akun</h2>
            <p>Tindakan permanen untuk menghapus akun Johen Gaming.</p>
        </div>
    </header>

    <div class="account-panel-body">
        <p class="account-danger-copy">Setelah akun dihapus, seluruh data dan akses akun tidak dapat dipulihkan. Pastikan kamu sudah menyimpan informasi yang masih dibutuhkan.</p>
        <button type="button" id="openDeleteAccountDialog" class="account-danger-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6"/></svg>
            Hapus Akun Saya
        </button>
    </div>

    <dialog id="deleteAccountDialog" class="account-dialog">
        <form method="post" action="{{ route('profile.destroy') }}" class="account-dialog-inner">
            @csrf
            @method('delete')

            <div class="account-dialog-icon" aria-hidden="true">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.7 2.2 17.3A2 2 0 0 0 3.9 20h16.2a2 2 0 0 0 1.7-2.7L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <h3>Hapus akun secara permanen?</h3>
            <p>Masukkan password untuk mengonfirmasi penghapusan akun dan seluruh datanya.</p>

            <div class="account-field">
                <label class="account-label" for="delete_account_password">Password</label>
                <input id="delete_account_password" name="password" type="password" class="account-input" placeholder="Masukkan password" autocomplete="current-password">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="account-error" />
            </div>

            <div class="account-dialog-actions">
                <button type="button" id="closeDeleteAccountDialog" class="account-cancel-btn">Batal</button>
                <button type="submit" class="account-confirm-btn">Ya, Hapus Akun</button>
            </div>
        </form>
    </dialog>
</section>
