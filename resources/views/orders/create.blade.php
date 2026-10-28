@extends('layouts.topup')

@section('content')
@php($isValorant = str_starts_with(mb_strtolower($product->brand), 'valorant'))
<div class="max-w-lg mx-auto">
    <a href="{{ route('games.show', $product->brand) }}" class="text-purple-400 hover:text-purple-300">&larr; Kembali</a>
    <h1 class="text-2xl font-bold mt-2 mb-6">Beli {{ $product->product_name }}</h1>

    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <p class="text-sm text-gray-400">{{ $product->brand }}</p>
                <p class="font-semibold text-lg">{{ $product->product_name }}</p>
            </div>
            <p class="text-xl font-bold text-purple-400">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</p>
        </div>
    </div>

    <form action="{{ route('orders.store') }}" method="POST" class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">{{ $isValorant ? 'Riot ID' : 'ID Game / Nomor HP' }}</label>
            <input type="text" name="customer_number" inputmode="{{ $isValorant ? 'text' : 'numeric' }}" placeholder="{{ $isValorant ? 'NamaPemain#TAG' : 'Masukkan ID game atau nomor HP' }}" required
                   value="{{ old('customer_number', request('customer_number')) }}"
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-purple-500">
            @if($isValorant)
                <p class="text-gray-400 text-xs mt-1">Contoh: JohenGaming#ID1</p>
            @endif
            @error('customer_number')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        @if($requiresZoneId)
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2" for="zone_id">{{ $usesServerId ? 'Server ID' : 'Zone ID' }}</label>
            <input type="text" id="zone_id" name="zone_id" placeholder="Masukkan {{ $usesServerId ? 'Server ID' : 'Zone ID' }}" required
                   value="{{ old('zone_id', request('zone_id')) }}"
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-purple-500">
            @error('zone_id')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        @endif

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Nickname (opsional)</label>
            <input type="text" name="customer_name" placeholder="Nickname game"
                   value="{{ old('customer_name', request('customer_name')) }}"
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-purple-500">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Email <span class="text-gray-400 text-xs">(untuk cek transaksi)</span></label>
            <input type="email" name="email" placeholder="contoh@email.com"
                   value="{{ old('email', request('email')) }}"
                   class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-purple-500">
            @error('email')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            @auth
            <label class="flex items-center justify-between gap-3 p-4 mb-4 rounded-xl border border-amber-400/30 bg-amber-400/10 cursor-pointer">
                <span><strong class="flex items-center gap-2 text-amber-300"><x-johen-point-icon size="22" /> Gunakan Johen Point</strong><small class="text-gray-300">Tersedia {{ number_format($pointBalance, 0, ',', '.') }} JP · maksimal sebesar total belanja</small><input type="number" name="points_amount" value="{{ old('points_amount', (int) $pointBalance) }}" min="1" max="{{ (int) $pointBalance }}" class="mt-2 w-40 bg-gray-800 border border-amber-400/30 rounded-lg px-3 py-2 text-white" aria-label="Jumlah Johen Point" {{ $pointBalance < 1 ? 'disabled' : '' }}></span>
                <input type="checkbox" name="use_points" value="1" {{ old('use_points') ? 'checked' : '' }} class="accent-amber-400 h-5 w-5" {{ $pointBalance < 1 ? 'disabled' : '' }}>
            </label>
            @endauth
            <label class="block text-sm font-medium mb-1">Metode Pembayaran</label>
            <p class="text-xs text-gray-400 mb-3">Diproses aman melalui {{ $gatewayName }}.</p>
            @php
                $categories = [
                    'qris' => 'QRIS',
                    'ewallet' => 'E-Wallet',
                    'paylater' => 'PayLater',
                    'va' => 'Virtual Account',
                    'convenience_store' => 'Convenience Store',
                ];
                $groupedPay = $paymentMethods->groupBy('category');
            @endphp
            @foreach($categories as $catKey => $catLabel)
                @php $catMethods = $groupedPay->get($catKey, collect()); @endphp
                @if($catMethods->isNotEmpty())
                <div class="mb-3">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ $catLabel }}</div>
                    <div class="space-y-2">
                        @foreach($catMethods as $pm)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-700 bg-gray-700/40 cursor-pointer hover:border-purple-500 transition payment-method-option" data-provider="{{ $pm->gateway_provider }}" data-gateway-code="{{ $pm->gateway_code }}">
                            <input type="radio" name="payment_method" value="{{ $pm->code }}"
                                   @if(old('payment_method', $paymentMethods->first()?->code) === $pm->code) checked @endif
                                   class="accent-purple-500">
                            @if(payment_logo_asset($pm->code))
                                <img src="{{ payment_logo_asset($pm->code) }}" alt="{{ $pm->name }}" class="h-7 object-contain">
                            @endif
                            <span class="text-sm text-gray-200">{{ $pm->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
            @error('payment_method')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-purple-600 py-3 rounded-xl font-semibold hover:bg-purple-700 transition">
            Lanjut ke Pembayaran
        </button>
    </form>
</div>
@endsection
