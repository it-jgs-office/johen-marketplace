@extends('admin.layouts.app')

@section('title', 'Kelola Akun')

@section('content')
<div class="mb-6">
    <h2 class="text-lg font-semibold">Kelola Akun</h2>
    <p style="margin-top:.25rem;font-size:.82rem;color:var(--text-muted)">Daftar akun pengguna marketplace.</p>
</div>

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th class="text-center">Bergabung</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-user-avatar :user="$user" :size="34" radius="10px" />
                            <span class="font-semibold">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td style="font-size:.85rem;color:var(--text-muted)">{{ $user->username ?? '—' }}</td>
                    <td style="font-size:.85rem;color:var(--text-muted)">{{ $user->email }}</td>
                    <td class="text-center" style="font-size:.82rem;color:var(--text-muted)">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-ghost btn-xs"
                            data-user="{{ json_encode($user->only(['id','name','email','username'])) }}"
                            onclick="openEditModal(this)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-user"></i>
                            <p>Belum ada akun pengguna</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center" id="userModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeUserModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:480px;margin:0 1rem;box-shadow:0 24px 64px -16px rgba(0,0,0,.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold">Edit User</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeUserModal()">&times;</button>
        </div>
        <form id="userForm" class="p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" id="userId" value="">

            <div class="flex items-center gap-3 mb-5 pb-4" style="border-bottom:1px solid var(--glass-border)">
                <div id="userAvatar" style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;flex-shrink:0"></div>
                <div>
                    <p class="font-semibold" id="userNameDisplay"></p>
                    <p style="font-size:.82rem;color:var(--text-muted)" id="userEmailDisplay"></p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Nama</label>
                <input type="text" name="name" id="f_name" required class="input-field">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_name"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Email</label>
                <input type="email" name="email" id="f_email" required class="input-field">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_email"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Password <span style="color:var(--text-dim);font-weight:400">(kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" class="input-field" placeholder="Minimal 6 karakter">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_password"></p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="btn btn-ghost" onclick="closeUserModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditModal(button) {
    const user = JSON.parse(button.dataset.user);
    document.getElementById('userId').value = user.id;
    document.getElementById('f_name').value = user.name;
    document.getElementById('f_email').value = user.email;
    document.getElementById('userAvatar').textContent = user.name.charAt(0);
    document.getElementById('userNameDisplay').textContent = user.name;
    document.getElementById('userEmailDisplay').textContent = user.email;

    clearEditErrors();
    document.getElementById('userModal').style.display = 'flex';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

function clearEditErrors() {
    document.querySelectorAll('#userForm [id^="err_"]').forEach(function (element) {
        element.classList.add('hidden');
        element.textContent = '';
    });
}

document.getElementById('userForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const button = document.getElementById('submitBtn');
    button.disabled = true;
    button.textContent = 'Menyimpan...';

    const formData = new FormData(this);
    const userId = document.getElementById('userId').value;
    const url = '{{ route('admin.users.update', '__ID__') }}'.replace('__ID__', userId);

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        const data = await response.json();

        if (response.ok) {
            closeUserModal();
            showModal('success', data.message || 'Pengguna berhasil diperbarui');
            setTimeout(function () { location.reload(); }, 800);
            return;
        }

        clearEditErrors();
        for (const field in data.errors || {}) {
            const element = document.getElementById('err_' + field);
            if (element) {
                element.textContent = data.errors[field][0];
                element.classList.remove('hidden');
            }
        }
    } catch (error) {
        showModal('error', 'Terjadi kesalahan. Silakan coba lagi.');
    }

    button.disabled = false;
    button.textContent = 'Simpan';
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && document.getElementById('userModal').style.display === 'flex') {
        closeUserModal();
    }
});
</script>
@endpush
