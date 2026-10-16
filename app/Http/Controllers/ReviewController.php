<?php

namespace App\Http\Controllers;

use App\Models\AccountOrder;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('web');

        $reviews = Review::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereRaw('LOWER(email) = ?', [mb_strtolower($user->email)]);
            })
            ->latest()
            ->paginate(10);

        return view('reviews.index', compact('reviews'));
    }

    /**
     * Simpan ulasan untuk satu order yang sudah sukses.
     * Satu order hanya boleh direview sekali (dibedakan per order_id).
     */
    public function store(Request $request, Order $order)
    {
        $this->ensureOrderOwner($request, $order->user_id, $order->email);

        if (!in_array($order->status, ['success', 'processing'])) {
            throw ValidationException::withMessages(['order' => 'Pesanan belum berhasil diproses.']);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::firstOrCreate([
            'order_id' => $order->order_id,
            'service_type' => 'topup',
        ], [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'game' => $order->brand,
            'rating' => (int) $validated['rating'],
            'comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
            'status' => 'approved',
        ]);

        if (! $review->wasRecentlyCreated) {
            throw ValidationException::withMessages(['rating' => 'Ulasan untuk pesanan ini sudah pernah dikirim.']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas ulasan Anda!',
            'review' => $review,
        ]);
    }

    /**
     * Simpan ulasan untuk satu pesanan akun yang sudah berhasil.
     */
    public function storeAccount(Request $request, AccountOrder $accountOrder)
    {
        $this->ensureOrderOwner($request, $accountOrder->user_id, $accountOrder->customer_email);

        if (! in_array($accountOrder->status, ['success', 'processing'], true)) {
            throw ValidationException::withMessages(['order' => 'Pesanan akun belum berhasil diproses.']);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $accountOrder->loadMissing('listing');

        $review = Review::firstOrCreate([
            'order_id' => $accountOrder->order_ref,
            'service_type' => 'jual-beli-akun',
        ], [
            'user_id' => Auth::id(),
            'email' => Auth::user()->email,
            'game' => $accountOrder->listing?->game ?: 'Akun Game',
            'rating' => (int) $validated['rating'],
            'comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
            'status' => 'approved',
        ]);

        if (! $review->wasRecentlyCreated) {
            throw ValidationException::withMessages(['rating' => 'Ulasan untuk pesanan akun ini sudah pernah dikirim.']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas ulasan Anda!',
            'review' => $review,
        ]);
    }

    private function ensureOrderOwner(Request $request, ?int $ownerId, ?string $orderEmail): void
    {
        $user = $request->user('web');
        abort_unless($user, 401);

        $ownsOrder = $ownerId
            ? (int) $ownerId === (int) $user->id
            : ($orderEmail && mb_strtolower(trim($orderEmail)) === mb_strtolower(trim($user->email)));

        abort_unless($ownsOrder, 403, 'Pesanan ini bukan milik akun Anda.');
    }
}
