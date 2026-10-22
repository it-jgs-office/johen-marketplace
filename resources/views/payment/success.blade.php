@extends('layouts.topup')

@section('content')
<div class="max-w-lg mx-auto text-center invoice-theme" data-invoice-state="success">
    <div class="text-6xl mb-4">&#10004;&#65039;</div>
    <h1 class="text-2xl font-bold text-green-400 mb-2">Pembayaran Berhasil!</h1>
    <p class="text-gray-400 mb-6">Pesanan sedang diproses. Top up akan masuk secara otomatis.</p>

    <div class="bg-emerald-950/30 rounded-xl p-6 border border-emerald-500/30 mb-6 text-left shadow-lg shadow-emerald-950/20">
        <div class="flex justify-between mb-3">
            <span class="text-gray-400">ID Pesanan</span>
            <span>{{ $order->order_id }}</span>
        </div>
        <div class="flex justify-between mb-3">
            <span class="text-gray-400">Produk</span>
            <span>{{ $order->product_name }}</span>
        </div>
        <div class="flex justify-between mb-3">
            <span class="text-gray-400">ID Game</span>
            <span>{{ $order->customer_number }}</span>
        </div>
        <div class="flex justify-between mb-3">
            <span class="text-gray-400">Status</span>
            <span class="text-green-400 font-semibold">{{ ucfirst($order->status) }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-400">Total</span>
            <span class="text-green-400 font-bold">Rp {{ number_format($order->price, 0, ',', '.') }}</span>
        </div>
    </div>

    <a href="{{ route('home') }}" class="inline-block bg-emerald-600 px-6 py-3 rounded-xl hover:bg-emerald-700 transition">
        Kembali ke Beranda
    </a>
</div>
@endsection
