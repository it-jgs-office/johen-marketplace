<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected CartService $cart;

    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    public function index()
    {
        $summary = $this->cart->summary(Auth::id());

        return view('cart.index', [
            'items' => $summary['items'],
            'subtotal' => $summary['subtotal'],
            'totalQty' => $summary['total_qty'],
            // Jumlah seluruh item di keranjang, termasuk yang stoknya habis,
            // supaya angkanya sama dengan badge di navbar.
            'count' => $this->cart->countItems(Auth::id()),
            'unavailable' => $summary['unavailable'],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:'.CartService::MAX_QTY,
        ], [], [
            'product_id' => 'produk',
        ]);

        [$ok, $message, $count] = $this->cart->add(
            Auth::id(),
            (int) $request->product_id,
            (int) ($request->quantity ?? 1)
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
                'count' => $count,
                'state' => $this->cart->state(Auth::id()),
            ], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    public function update(Request $request, int $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:'.CartService::MAX_QTY,
        ]);

        [$ok, $message, $count] = $this->cart->updateQuantity(
            Auth::id(),
            $item,
            (int) $request->quantity
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
                'count' => $count,
                'state' => $this->cart->state(Auth::id()),
            ], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    public function destroy(int $item)
    {
        [$ok, $message, $count] = $this->cart->remove(Auth::id(), $item);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
                'count' => $count,
                'state' => $this->cart->state(Auth::id()),
            ], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    public function clear()
    {
        $this->cart->clear(Auth::id());

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Keranjang dikosongkan.',
                'count' => 0,
                'state' => $this->cart->state(Auth::id()),
            ]);
        }

        return back()->with('success', 'Keranjang dikosongkan.');
    }

    /**
     * Jumlah item untuk badge di navbar.
     */
    public function count()
    {
        return response()->json([
            'count' => $this->cart->countItems(Auth::id()),
        ]);
    }

    /**
     * Ringkasan keranjang lengkap supaya frontend bisa menyegarkan semua angka
     * (badge, jumlah item, subtotal) tanpa reload, misalnya setelah user
     * mengubah keranjang lalu kembali ke halaman ini lewat tombol back.
     */
    public function state()
    {
        return response()->json([
            'state' => $this->cart->state(Auth::id()),
        ]);
    }
}