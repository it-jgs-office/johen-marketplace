@extends('admin.layouts.app')
@section('title', 'Provider Pengecekan Akun')
@section('content')
<div class="flex items-center gap-3 mb-5">
    <i class="fas fa-user-check" style="color:var(--accent);font-size:1.1rem"></i>
    <h2 class="text-lg font-semibold">Provider Pengecekan Akun</h2>
</div>

<div class="alert mb-5" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.25);color:var(--text-dim);font-size:0.74rem">
    <i class="fas fa-info-circle" style="margin-right:0.25rem"></i>
    Pengecekan akun gratis untuk pembeli. Urutan: provider gratis utama &rarr; provider gratis cadangan &rarr;
    fallback Digiflazz (hanya saat checkout, jika diaktifkan). Kredensial API dibaca dari <code>.env</code>.
</div>

@if(session('success'))
    <div class="alert mb-4" style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#10b981;font-size:0.8rem">
        <i class="fas fa-check-circle" style="margin-right:0.25rem"></i> {{ session('success') }}
    </div>
@endif

@forelse($providers as $provider)
    <div class="card-glass p-4 mb-4">
        <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
            <div class="flex items-center gap-2">
                <i class="fas fa-gamepad" style="color:var(--accent);font-size:0.9rem"></i>
                <span class="font-semibold" style="font-size:0.9rem">{{ $provider->label }}</span>
                <code style="font-size:0.7rem;color:var(--text-dim)">{{ $provider->brand_pattern }}</code>
            </div>
            <form action="{{ route('admin.check-providers.toggle', $provider) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn {{ $provider->enabled ? 'btn-primary' : '' }}" style="font-size:0.72rem;padding:0.3rem 0.7rem">
                    <i class="fas fa-{{ $provider->enabled ? 'toggle-on' : 'toggle-off' }}"></i>
                    {{ $provider->enabled ? 'Aktif' : 'Nonaktif' }}
                </button>
            </form>
        </div>

        <form action="{{ route('admin.check-providers.update', $provider) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1">Label</label>
                    <input type="text" name="label" value="{{ old('label', $provider->label) }}" class="input-field text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Prioritas</label>
                    <input type="number" name="priority" value="{{ old('priority', $provider->priority) }}" class="input-field text-sm" min="0" max="999">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Provider Gratis Utama</label>
                    <input type="text" name="free_type" value="{{ old('free_type', $provider->free_type) }}" class="input-field text-sm" placeholder="alu / isan / gopay / enka">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Config Gratis Utama (JSON)</label>
                    <input type="text" name="free_config_json" value="{{ old('free_config_json', $provider->free_config ? json_encode($provider->free_config) : '') }}" class="input-field text-sm" placeholder='{"code":"PUBG_ID"}'>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Provider Gratis Cadangan</label>
                    <input type="text" name="fallback_free_type" value="{{ old('fallback_free_type', $provider->fallback_free_type) }}" class="input-field text-sm" placeholder="isan">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Config Cadangan (JSON)</label>
                    <input type="text" name="fallback_free_config_json" value="{{ old('fallback_free_config_json', $provider->fallback_free_config ? json_encode($provider->fallback_free_config) : '') }}" class="input-field text-sm" placeholder='{"game":"ml"}'>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">SKU Digiflazz</label>
                    <input type="text" name="digiflazz_sku" value="{{ old('digiflazz_sku', $provider->digiflazz_sku) }}" class="input-field text-sm" placeholder="usrnameml-johen">
                </div>
                <div class="flex items-end">
                    <input type="hidden" name="digiflazz_fallback_enabled" value="0">
                    <label class="flex items-center gap-2 cursor-pointer" for="dfx-{{ $provider->id }}">
                        <input id="dfx-{{ $provider->id }}" type="checkbox" name="digiflazz_fallback_enabled" value="1" {{ $provider->digiflazz_fallback_enabled ? 'checked' : '' }} style="width:1.1rem;height:1.1rem;accent-color:var(--accent)">
                        <span class="text-sm">Fallback Digiflazz saat checkout</span>
                    </label>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" style="font-size:0.75rem;padding:0.4rem 1rem">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
@empty
    <div class="card-glass p-6 text-center" style="color:var(--text-dim);font-size:0.85rem">
        Belum ada provider. Jalankan <code>php artisan db:seed --class=CheckProviderSeeder</code>.
    </div>
@endforelse
@endsection
