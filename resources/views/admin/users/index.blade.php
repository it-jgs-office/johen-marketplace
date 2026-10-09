@extends('admin.layouts.app')

@section('title', 'Kelola Akun')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-lg font-semibold">Kelola Akun</h2>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> <span id="addBtnText">Tambah Admin</span>
    </button>
</div>

<!-- Tabs Card -->
<div style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:14px;padding:6px;display:flex;gap:6px;margin:0 auto 24px;max-width:none">
    <button class="user-tab active" data-tab="admin" onclick="switchTab('admin')">
        <i class="fas fa-shield-alt"></i> Akun Admin
    </button>
    <button class="user-tab" data-tab="user" onclick="switchTab('user')">
        <i class="fas fa-user"></i> Akun User
    </button>
</div>

<!-- Admin Table -->
<div class="table-wrap" id="tab-admin">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Bergabung</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $admins = $users->filter(fn($u) => $u->is_admin || $u->is_live_chat_admin); @endphp
                @forelse($admins as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#0987F5,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#fff;flex-shrink:0">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <span class="font-semibold">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td style="font-size:0.85rem;color:var(--text-muted)">{{ $user->username ?? '—' }}</td>
                    <td style="font-size:0.85rem;color:var(--text-muted)">{{ $user->email }}</td>
                    <td class="text-center">
                        @if($user->is_admin)
                            <span class="badge badge-info">
                                <i class="fas fa-crown" style="font-size:0.65rem;margin-right:0.25rem"></i> Super Admin
                            </span>
                        @elseif($user->is_live_chat_admin)
                            @php $channels = $user->assignedChannels->pluck('name')->implode(', ') @endphp
                            <span class="badge badge-info">
                                <i class="fas fa-headset" style="font-size:0.65rem;margin-right:0.25rem"></i> {{ $channels ?: 'Live Chat Admin' }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center" style="font-size:0.82rem;color:var(--text-muted)">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-ghost btn-xs"
                            data-user="{{ json_encode($user->only(['id','name','email','username','is_admin','is_live_chat_admin','is_live_chat_cs'])) }}"
                            data-channels="{{ json_encode($user->assignedChannels->pluck('id')->toArray()) }}"
                            onclick="openEditModal(this)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-shield-alt"></i>
                            <p>Belum ada akun admin</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- User Table -->
<div class="table-wrap" id="tab-user" style="display:none">
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
                @php $regularUsers = $users->filter(fn($u) => !$u->is_admin && !$u->is_live_chat_admin); @endphp
                @forelse($regularUsers as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#0987F5,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#fff;flex-shrink:0">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <span class="font-semibold">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td style="font-size:0.85rem;color:var(--text-muted)">{{ $user->username ?? '—' }}</td>
                    <td style="font-size:0.85rem;color:var(--text-muted)">{{ $user->email }}</td>
                    <td class="text-center" style="font-size:0.82rem;color:var(--text-muted)">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-ghost btn-xs"
                            data-user="{{ json_encode($user->only(['id','name','email','username','is_admin','is_live_chat_admin','is_live_chat_cs'])) }}"
                            data-channels="{{ json_encode($user->assignedChannels->pluck('id')->toArray()) }}"
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
                            <p>Belum ada akun user</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL TAMBAH PENGGUNA ===== -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="createModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeCreateModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:480px;margin:0 1rem;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold">Tambah User</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeCreateModal()">&times;</button>
        </div>
        <form id="createForm" class="p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Nama</label>
                <input type="text" name="name" required class="input-field" placeholder="Nama lengkap">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_create_name"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Email</label>
                <input type="email" name="email" required class="input-field" placeholder="email@contoh.com">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_create_email"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Username</label>
                <input type="text" name="username" required class="input-field" placeholder="username untuk login">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_create_username"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Password</label>
                <input type="password" name="password" required class="input-field" placeholder="Minimal 6 karakter">
                <p class="text-red-400 text-xs mt-1 hidden" id="err_create_password"></p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1.5">Role</label>
                <select name="role" class="input-field" id="createRoleSelect">
                    <option value="user" selected>User Biasa</option>
                    @foreach(\App\Models\LiveChatChannel::active()->ordered()->get() as $ch)
                    <option value="lc_{{ $ch->id }}">Admin Live Chat - {{ $ch->name }}</option>
                    @endforeach
                    <option value="super_admin">Super Admin</option>
                    <option value="cs">Admin CS</option>
                </select>
                <input type="hidden" name="is_admin" id="f_is_admin_create" value="0">
                <input type="hidden" name="is_live_chat_admin" id="f_is_live_chat_admin_create" value="0">
                <input type="hidden" name="is_live_chat_cs" id="f_is_live_chat_cs_create" value="0">
                <input type="hidden" name="channel_id" id="f_channel_id_create" value="">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="btn btn-ghost" onclick="closeCreateModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="createSubmitBtn">Buat User</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL EDIT PENGGUNA ===== -->
<div class="fixed inset-0 z-50 flex items-center justify-center" id="userModal" style="display:none">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeUserModal()"></div>
    <div class="relative" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;width:100%;max-width:480px;margin:0 1rem;box-shadow:0 24px 64px -16px rgba(0,0,0,0.5)">
        <div class="flex items-center justify-between p-5" style="border-bottom:1px solid var(--glass-border)">
            <h3 class="text-lg font-bold">Edit User</h3>
            <button type="button" style="background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1" onclick="closeUserModal()">&times;</button>
        </div>
        <form id="userForm" class="p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" id="userId" value="">

            <div class="flex items-center gap-3 mb-5 pb-4" style="border-bottom:1px solid var(--glass-border)">
                <div id="userAvatar" style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;color:#fff;flex-shrink:0"></div>
                <div>
                    <p class="font-semibold" id="userNameDisplay"></p>
                    <p style="font-size:0.82rem;color:var(--text-muted)" id="userEmailDisplay"></p>
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

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1.5">Role</label>
                <select name="role" class="input-field" id="editRoleSelect" onchange="updateEditRole(this)">
                    <option value="user" id="opt_role_user">User Biasa</option>
                    @foreach(\App\Models\LiveChatChannel::active()->ordered()->get() as $ch)
                    <option value="lc_{{ $ch->id }}" id="opt_role_lc_{{ $ch->id }}">Admin Live Chat - {{ $ch->name }}</option>
                    @endforeach
                    <option value="super_admin" id="opt_role_super_admin">Super Admin</option>
                    <option value="cs" id="opt_role_cs">Admin CS</option>
                </select>
                <input type="hidden" name="is_admin" id="f_is_admin" value="0">
                <input type="hidden" name="is_live_chat_admin" id="f_is_live_chat_admin" value="0">
                <input type="hidden" name="is_live_chat_cs" id="f_is_live_chat_cs" value="0">
                <input type="hidden" name="channel_id" id="f_channel_id" value="">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="btn btn-ghost" onclick="closeUserModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.user-tab {
    flex: 1;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    color: var(--text-dim);
    background: transparent;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.user-tab:hover {
    color: var(--text);
    background: rgba(255,255,255,0.03);
}
.user-tab.active {
    color: #fff;
    background: linear-gradient(135deg, var(--accent), #0ea5e9);
    box-shadow: 0 4px 14px -4px rgba(63,109,245,0.35);
}
.user-tab i {
    font-size: 12px;
}
</style>
@endpush

@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.user-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById('tab-admin').style.display = tab === 'admin' ? '' : 'none';
    document.getElementById('tab-user').style.display = tab === 'user' ? '' : 'none';
    document.getElementById('addBtnText').textContent = tab === 'admin' ? 'Tambah Admin' : 'Tambah User';
}

function setRoleFields(selectEl, hiddenIsAdmin, hiddenIsLcAdmin, hiddenChannelId, hiddenIsLcCs) {
    const val = selectEl.value;
    document.getElementById(hiddenIsLcCs).value = '0';
    if (val === 'super_admin') {
        document.getElementById(hiddenIsAdmin).value = '1';
        document.getElementById(hiddenIsLcAdmin).value = '0';
        document.getElementById(hiddenChannelId).value = '';
    } else if (val.startsWith('lc_')) {
        document.getElementById(hiddenIsAdmin).value = '0';
        document.getElementById(hiddenIsLcAdmin).value = '1';
        document.getElementById(hiddenChannelId).value = val.replace('lc_', '');
    } else if (val === 'cs') {
        document.getElementById(hiddenIsAdmin).value = '0';
        document.getElementById(hiddenIsLcAdmin).value = '0';
        document.getElementById(hiddenChannelId).value = '';
        document.getElementById(hiddenIsLcCs).value = '1';
    } else {
        document.getElementById(hiddenIsAdmin).value = '0';
        document.getElementById(hiddenIsLcAdmin).value = '0';
        document.getElementById(hiddenChannelId).value = '';
    }
}

// ===== CREATE MODAL =====
function openCreateModal() {
    document.getElementById('createForm').reset();
    clearCreateErrors();
    document.getElementById('createModal').style.display = 'flex';
}

function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
}

function clearCreateErrors() {
    document.querySelectorAll('#createForm [id^="err_create_"]').forEach(function (el) {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

document.getElementById('createForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('createSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const select = document.getElementById('createRoleSelect');
    const formData = new FormData(this);
    setRoleFields(select, 'f_is_admin_create', 'f_is_live_chat_admin_create', 'f_channel_id_create', 'f_is_live_chat_cs_create');
    formData.set('is_admin', document.getElementById('f_is_admin_create').value);
    formData.set('is_live_chat_admin', document.getElementById('f_is_live_chat_admin_create').value);
    formData.set('is_live_chat_cs', document.getElementById('f_is_live_chat_cs_create').value);
    formData.set('channel_id', document.getElementById('f_channel_id_create').value);
    formData.delete('role');

    try {
        const res = await fetch('{{ route("admin.users.store") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const data = await res.json();

        if (res.ok) {
            closeCreateModal();
            showModal('success', data.message || 'User berhasil dibuat');
            setTimeout(function () { location.reload(); }, 800);
        } else {
            const errors = data.errors || {};
            clearCreateErrors();
            for (const field in errors) {
                const el = document.getElementById('err_create_' + field);
                if (el) {
                    el.textContent = errors[field][0];
                    el.classList.remove('hidden');
                }
            }
            btn.disabled = false;
            btn.textContent = 'Buat User';
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan. Silakan coba lagi.');
        btn.disabled = false;
        btn.textContent = 'Buat User';
    }
});

// ===== EDIT MODAL =====
function openEditModal(btn) {
    const u = JSON.parse(btn.dataset.user);
    const channels = JSON.parse(btn.dataset.channels || '[]');
    document.getElementById('userId').value = u.id;
    document.getElementById('f_name').value = u.name;
    document.getElementById('f_email').value = u.email;
    document.getElementById('userAvatar').textContent = u.name.charAt(0);
    document.getElementById('userNameDisplay').textContent = u.name;
    document.getElementById('userEmailDisplay').textContent = u.email;

    const select = document.getElementById('editRoleSelect');
    if (u.is_admin) {
        select.value = 'super_admin';
    } else if (u.is_live_chat_admin && channels.length > 0) {
        select.value = 'lc_' + channels[0];
    } else if (u.is_live_chat_cs) {
        select.value = 'cs';
    } else {
        select.value = 'user';
    }

    setRoleFields(select, 'f_is_admin', 'f_is_live_chat_admin', 'f_channel_id', 'f_is_live_chat_cs');

    clearEditErrors();
    document.getElementById('userModal').style.display = 'flex';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

function clearEditErrors() {
    document.querySelectorAll('#userForm [id^="err_"]').forEach(function (el) {
        el.classList.add('hidden');
        el.textContent = '';
    });
}

function updateEditRole(selectEl) {
    setRoleFields(selectEl, 'f_is_admin', 'f_is_live_chat_admin', 'f_channel_id', 'f_is_live_chat_cs');
}

document.getElementById('userForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const formData = new FormData(this);
    const userId = document.getElementById('userId').value;
    const url = '{{ route('admin.users.update', '__ID__') }}'.replace('__ID__', userId);

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const data = await res.json();

        if (res.ok) {
            closeUserModal();
            showModal('success', data.message || 'User berhasil diperbarui');
            setTimeout(function () { location.reload(); }, 800);
        } else {
            const errors = data.errors || {};
            clearEditErrors();
            for (const field in errors) {
                const el = document.getElementById('err_' + field);
                if (el) {
                    el.textContent = errors[field][0];
                    el.classList.remove('hidden');
                }
            }
            btn.disabled = false;
            btn.textContent = 'Simpan';
        }
    } catch (err) {
        showModal('error', 'Terjadi kesalahan. Silakan coba lagi.');
        btn.disabled = false;
        btn.textContent = 'Simpan';
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        if (document.getElementById('userModal').style.display === 'flex') closeUserModal();
        if (document.getElementById('createModal').style.display === 'flex') closeCreateModal();
    }
});
</script>
@endpush
