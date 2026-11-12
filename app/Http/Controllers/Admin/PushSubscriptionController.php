<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveChatConversation;
use App\Models\LiveChatMessage;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    protected function storeSubscription(Request $request, $userId, string $guard): JsonResponse
    {
        $valid = $request->validate([
            'endpoint' => 'required|url',
            'public_key' => 'required|string|max:2048',
            'auth_token' => 'required|string|max:2048',
        ]);

        logger()->info('PushSubscription: subscribe', [
            'user_id' => $userId,
            'guard' => $guard,
            'endpoint' => substr($valid['endpoint'], 0, 80),
        ]);

        PushSubscription::updateOrCreate(
            ['user_id' => $userId, 'guard' => $guard, 'endpoint' => $valid['endpoint']],
            [
                'public_key' => $valid['public_key'],
                'auth_token' => $valid['auth_token'],
                'user_agent' => substr($request->header('User-Agent', ''), 0, 2048),
            ],
        );

        PushSubscription::query()
            ->where('user_id', $userId)
            ->where('guard', $guard)
            ->where(function ($q) {
                $q->whereNull('public_key')->orWhereNull('auth_token');
            })
            ->where('endpoint', '!=', $valid['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function statusWeb(Request $request): JsonResponse
    {
        $userId = Auth::guard('web')->id();
        if (! $userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $count = PushSubscription::query()
            ->where('user_id', $userId)
            ->where('guard', 'web')
            ->count();

        return response()->json(['ok' => true, 'registered' => $count > 0, 'count' => $count]);
    }

    public function subscribeWeb(Request $request): JsonResponse
    {
        $userId = Auth::guard('web')->id();
        if (! $userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->storeSubscription($request, $userId, 'web');
    }

    public function unsubscribeWeb(Request $request): JsonResponse
    {
        $userId = Auth::guard('web')->id();

        if ($userId) {
            $endpoint = $request->input('endpoint');
            PushSubscription::query()
                ->where('user_id', $userId)
                ->where('guard', 'web')
                ->when($endpoint, fn ($q) => $q->where('endpoint', $endpoint))
                ->delete();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Kirim notifikasi uji ke perangkat admin yang terdaftar.
     */
    public function test(Request $request, string $guard): JsonResponse
    {
        $userId = Auth::guard('admin')->id();
        if (! $userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($guard !== 'admin') {
            return response()->json(['error' => 'Invalid guard'], 422);
        }

        $url = '/johen-admin-secret';

        $sent = app(\App\Services\PushService::class)->sendToTargets(
            [
                ['guard' => $guard, 'url' => $url],
            ],
            $request->input('title', 'Johen'),
            $request->input('body', 'Ini notifikasi uji — push berfungsi!'),
            [
                'icon' => asset('logo-96.png'),
                'badge' => asset('logo-96.png'),
                'tag' => 'push-test-'.time().'-'.$userId,
                'target_guard' => $guard,
            ],
            $userId,
        );

        return response()->json(['ok' => true, 'delivered' => $sent]);
    }

    /**
     * Kirim notifikasi uji ke perangkat user (guard web) yang terdaftar.
     */
    public function testWeb(Request $request): JsonResponse
    {
        $userId = Auth::guard('web')->id();
        if (! $userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sent = app(\App\Services\PushService::class)->sendToTargets(
            [
                ['guard' => 'web', 'url' => '/'],
            ],
            $request->input('title', 'Johen'),
            $request->input('body', 'Ini notifikasi uji — push berfungsi!'),
            [
                'icon' => asset('logo-96.png'),
                'badge' => asset('logo-96.png'),
                'tag' => 'push-test-'.time().'-'.$userId,
                'target_guard' => 'web',
            ],
            $userId,
        );

        return response()->json(['ok' => true, 'delivered' => $sent]);
    }

    /**
     * Fallback payload untuk push yang datang tanpa data terenkripsi.
     * Service worker memanggil endpoint ini untuk menampilkan chat terbaru.
     */
    public function latestNotification(): JsonResponse
    {
        return response()->json(['error' => 'Live chat masih dalam mode dummy'], 404);

        if (Auth::guard('web')->check()) {
            return $this->latestForWeb();
        }

        if (Auth::guard('admin')->check()) {
            return $this->latestForAdmin();
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    protected function latestForWeb(): JsonResponse
    {
        $conversation = LiveChatConversation::query()
            ->with(['channel', 'lastMessage.sender'])
            ->where('user_id', Auth::guard('web')->id())
            ->where('user_unread_count', '>', 0)
            ->orderByDesc('last_message_at')
            ->first();

        if (! $conversation || ! $conversation->lastMessage) {
            return response()->json(['error' => 'Tidak ada pesan baru'], 404);
        }

        $channel = $conversation->channel;
        $shown = 'Admin';

        return response()->json([
            'title' => $shown,
            'body' => $this->messagePreview($conversation->lastMessage),
            'url' => '/?chat=1&channel='.urlencode((string) ($channel?->slug ?? '')),
            'app_name' => config('services.vapid.notif_brand', 'Johen Gaming'),
            'conversation_id' => $conversation->id,
            'channel_slug' => $channel?->slug,
            'target_guard' => 'web',
            'icon' => asset('logo-96.png'),
        ]);
    }

    protected function latestForAdmin(): JsonResponse
    {
        return response()->json(['error' => 'Live chat admin tidak aktif'], 404);
    }

    protected function messagePreview(LiveChatMessage $message): string
    {
        return match ($message->message_type) {
            'image' => $message->attachments()->count() > 0 ? '📷 '.$message->attachments()->count().' Foto' : '📷 Foto',
            'video' => $message->attachments()->count() > 0 ? '🎬 '.$message->attachments()->count().' Video' : '🎬 Video',
            'audio' => '🎤 Pesan suara',
            default => $message->message && mb_strlen((string) $message->message) > 150
                ? mb_substr((string) $message->message, 0, 150).'…'
                : (string) $message->message,
        };
    }
}
