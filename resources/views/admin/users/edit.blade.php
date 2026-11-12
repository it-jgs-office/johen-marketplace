@extends('admin.layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
<div style="max-width:500px;margin:0 auto">
    <a href="{{ route('admin.users') }}" class="btn btn-ghost mb-4" style="padding-left:0">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
    </a>

    <div class="card-glass p-6">
        <h2 class="text-lg font-semibold mb-6">
            <i class="fas fa-user-edit" style="color:var(--accent);margin-right:0.5rem"></i>
            Edit Pengguna
        </h2>

        <div class="flex items-center gap-3 mb-6 pb-4" style="border-bottom:1px solid var(--glass-border)">
            <x-user-avatar :user="$user" :size="44" radius="12px" />
            <div>
                <p class="font-semibold">{{ $user->name }}</p>
                <p style="font-size:0.82rem;color:var(--text-muted)">{{ $user->email }}</p>
            </div>
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="input-field">
                @error('name') <p style="color:var(--error);font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input-field">
                @error('email') <p style="color:var(--error);font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1.5">Password <span style="color:var(--text-dim);font-weight:400">(kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" class="input-field" placeholder="Minimal 6 karakter">
                @error('password') <p style="color:var(--error);font-size:0.78rem;margin-top:0.3rem">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.users') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection
