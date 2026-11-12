@extends('admin.layouts.app')

@section('title', 'Keamanan Admin')

@section('content')
<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h2 class="text-lg font-semibold">Google Authenticator</h2>
        <p style="margin-top:.25rem;font-size:.82rem;color:var(--text-muted)">Admin utama menautkan dan mengatur 2FA untuk akun admin.</p>
    </div>
    <span class="badge badge-success"><i class="fas fa-shield-halved"></i> Admin utama</span>
</div>

@if($errors->any())
    <div class="alert alert-error mb-5" role="alert">
        <i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}
    </div>
@endif

@if($setupUser && $setupSecret && $qrCode)
<div class="card-glass p-5 mb-6" style="border-color:rgba(96,165,250,.38)">
    <div class="flex items-center gap-3 mb-5">
        <div style="width:40px;height:40px;border-radius:12px;background:rgba(96,165,250,.16);color:#60a5fa;display:flex;align-items:center;justify-content:center">
            <i class="fas fa-qrcode"></i>
        </div>
        <div>
            <h3 class="font-semibold">Tautkan {{ $setupUser->name }}</h3>
            <p style="font-size:.76rem;color:var(--text-muted)">{{ $setupUser->email }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-5 items-start">
        <div style="background:#fff;padding:10px;border-radius:14px;width:240px;max-width:100%">
            <img src="{{ $qrCode }}" alt="QR Google Authenticator untuk {{ $setupUser->name }}" style="display:block;width:100%;height:auto">
        </div>
        <div>
            <ol class="space-y-2" style="font-size:.84rem;color:var(--text-muted);list-style:decimal;padding-left:1.2rem">
                <li>Buka Google Authenticator di perangkat admin.</li>
                <li>Pilih tambah akun, lalu pindai QR di samping.</li>
                <li>Masukkan kode 6 digit yang muncul untuk menyelesaikan penautan.</li>
            </ol>

            <div class="mt-4">
                <label class="block text-xs font-medium mb-1.5">Kunci manual</label>
                <code style="display:block;padding:.75rem;border-radius:10px;background:var(--bg-input);border:1px solid var(--border);color:var(--brand-cyan);font-size:.78rem;word-break:break-all;letter-spacing:.08em">{{ $setupSecret }}</code>
            </div>

            <form action="{{ route('admin.two-factor.confirm', $setupUser) }}" method="POST" class="mt-4 flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="text" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="input-field" placeholder="Kode 6 digit" style="max-width:220px;letter-spacing:.18em;font-weight:700">
                <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Tautkan 2FA</button>
            </form>
        </div>
    </div>
</div>
@endif

<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Username</th>
                    <th class="text-center">Status 2FA</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-user-avatar :user="$admin" :size="36" radius="10px" />
                            <div>
                                <div class="font-semibold">
                                    {{ $admin->name }}
                                    @if($admin->isMainAdmin())
                                        <span class="badge" style="font-size:.62rem;margin-left:.35rem">Utama</span>
                                    @endif
                                </div>
                                <div style="font-size:.76rem;color:var(--text-muted)">{{ $admin->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.84rem;color:var(--text-muted)">{{ $admin->username ?: '—' }}</td>
                    <td class="text-center">
                        @if($admin->hasTwoFactorAuthentication())
                            <span class="badge badge-success"><i class="fas fa-circle-check"></i> Aktif</span>
                        @else
                            <span class="badge badge-error"><i class="fas fa-circle-xmark"></i> Belum aktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex justify-center gap-2 flex-wrap">
                            <form action="{{ route('admin.two-factor.setup', $admin) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-xs">
                                    <i class="fas fa-qrcode"></i> {{ $admin->hasTwoFactorAuthentication() ? 'Atur ulang' : 'Tautkan' }}
                                </button>
                            </form>
                            @if($admin->hasTwoFactorAuthentication())
                            <form action="{{ route('admin.two-factor.destroy', $admin) }}" method="POST" onsubmit="return confirm('Nonaktifkan 2FA untuk {{ addslashes($admin->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-unlink"></i> Nonaktifkan</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state"><i class="fas fa-user-shield"></i><p>Belum ada akun admin</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
