<?php

namespace App\Http\Controllers\LCAdmin;

use App\Http\Controllers\Controller;
use App\Models\LiveChatConversation;
use App\Models\LiveChatMessage;
use App\Models\LiveChatMessageHiddenUser;
use App\Services\LiveChatMedia;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LCAdminConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        $query = LiveChatConversation::with(['user', 'channel', 'lastMessage.sender'])
            ->whereIn('channel_id', $channelIds);

        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->channel_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (Schema::hasColumn('live_chat_conversations', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        $pinnedColumn = Schema::hasColumn('live_chat_conversations', 'is_pinned');
        $conversations = $query
            ->when($pinnedColumn, fn ($q) => $q->orderByDesc('is_pinned'))
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        $channels = $user->assignedOperators()->with('channel')->get()->pluck('channel')->unique('id');
        $archivedUnreadCount = $this->archivedUnreadCount($channelIds);

        return view('admin.lcadmin.conversations.index', compact('conversations', 'channels', 'archivedUnreadCount'));
    }

    public function show(LiveChatConversation $conversation)
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            abort(403);
        }

        

        $conversation->load(['user', 'channel', 'lastMessage.sender']);
        $conversation->markAdminRead();

        $adminId = Auth::guard('admin')->id();

        $messages = LiveChatMessage::where('conversation_id', $conversation->id)
            ->whereDoesntHave('hiddenUsers', fn ($hidden) => $hidden->where('user_id', $adminId))
            ->with(['sender', 'replyTo', 'attachments'])
            ->ordered()
            ->get();

        $activeOperator = $conversation->channel->getActiveOperator();

        return view('admin.lcadmin.conversations.show', compact('conversation', 'messages', 'activeOperator'));
    }

    public function loadMessages(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        $conversation->load(['user', 'channel']);
        if ($conversation->user) {
            $conversation->user->setAttribute(
                'avatar_url',
                $conversation->user->avatar ? media_url($conversation->user->avatar) : null
            );
        }
        $conversation->markAdminRead();

        $adminId = Auth::guard('admin')->id();

        $messages = LiveChatMessage::where('conversation_id', $conversation->id)
            ->whereDoesntHave('hiddenUsers', fn ($hidden) => $hidden->where('user_id', $adminId))
            ->with(['sender', 'replyTo', 'attachments'])
            ->ordered()
            ->get();

        $activeOperator = $conversation->channel->getActiveOperator();

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
            'activeOperator' => $activeOperator,
        ]);
    }

    public function poll(LiveChatConversation $conversation, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        $afterId = $request->input('after', 0);

        $adminId = Auth::guard('admin')->id();

        $messages = LiveChatMessage::where('conversation_id', $conversation->id)
            ->where('id', '>', $afterId)
            ->whereDoesntHave('hiddenUsers', fn ($hidden) => $hidden->where('user_id', $adminId))
            ->with(['sender', 'replyTo', 'attachments'])
            ->ordered()
            ->get();

        $conversation->markAdminRead();

        return response()->json($messages);
    }

    public function reply(Request $request, LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        $request->validate([
            'message_type' => 'required|in:text,image,video,audio',
            'message' => 'nullable|string|max:5000',
            'reply_to_message_id' => 'nullable|exists:live_chat_messages,id',
            'media' => 'nullable|array',
            'media.*' => 'file|max:1048576|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv,webm,flv,3gp,wav,oga,ogg,opus,mp3,m4a,aac,weba',
            'media_duration' => 'nullable|integer|min:0|max:1800',
            'thumbnail' => 'nullable|array',
            'thumbnail.*' => 'file|max:5120|mimes:jpg,jpeg,png,webp',
            'thumbnail_indexes' => 'nullable|array',
            'thumbnail_indexes.*' => 'integer|min:0',
        ]);

        $data = [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'admin',
            'message_type' => $request->message_type,
            'message' => $request->message,
            'reply_to_message_id' => $request->reply_to_message_id,
        ];

        $message = LiveChatMessage::create($data);

        $files = $request->file('media', []);
        if (!empty($files)) {
            $posterFiles = [];
            foreach ((array) $request->input('thumbnail_indexes', []) as $key => $index) {
                $thumb = $request->file('thumbnail')[$key] ?? null;
                if ($thumb) {
                    $posterFiles[(int) $index] = $thumb;
                }
            }
            $durations = $request->message_type === 'audio' ? [0 => (int) $request->media_duration] : [];
            $rows = LiveChatMedia::storeBatch($files, $posterFiles, $durations, $request->message_type !== 'audio');
            $message->attachments()->createMany($rows);
            LiveChatMedia::applyCompatColumns($message, $rows);
        }

        $conversation->update([
            'last_message_at' => now(),
            'user_unread_count' => $conversation->user_unread_count + 1,
        ]);

        app(PushService::class)->sendUserReplyNotification($conversation, $message, $user->name ?? 'Admin');

        $message->load('sender', 'replyTo', 'attachments');

        return response()->json($message);
    }

    public function close(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        $conversation->update(['status' => 'closed']);

        return response()->json(['success' => true, 'message' => 'Conversation ditutup']);
    }

    public function reopen(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        $conversation->update(['status' => 'open']);

        return response()->json(['success' => true, 'message' => 'Conversation dibuka kembali']);
    }

    public function toggleFavorite(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->update(['is_favorited' => !$conversation->is_favorited]);

        return response()->json(['is_favorited' => $conversation->is_favorited]);
    }

    public function togglePin(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!Schema::hasColumn('live_chat_conversations', 'is_pinned')) {
            return response()->json(['error' => 'Kolom is_pinned belum tersedia. Jalankan php artisan migrate.'], 503);
        }

        $conversation->update(['is_pinned' => !$conversation->is_pinned]);

        return response()->json(['is_pinned' => $conversation->is_pinned]);
    }

    public function archived()
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!Schema::hasColumn('live_chat_conversations', 'archived_at')) {
            return response()->json(['html' => '', 'count' => 0]);
        }

        $conversations = LiveChatConversation::with(['user', 'channel', 'lastMessage.sender'])
            ->whereIn('channel_id', $channelIds)
            ->whereNotNull('archived_at')
            ->orderBy('last_message_at', 'desc')
            ->limit(100)
            ->get();

        $html = '';
        foreach ($conversations as $conv) {
            $html .= view('admin.lcadmin.conversations._list-item', ['conv' => $conv])->render();
        }

        return response()->json(['html' => $html, 'count' => $conversations->count(), 'archivedUnreadCount' => $this->archivedUnreadCount($channelIds)]);
    }

    public function archivedUnread(): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        return response()->json(['count' => $this->archivedUnreadCount($channelIds)]);
    }

    private function archivedUnreadCount($channelIds): int
    {
        if (!Schema::hasColumn('live_chat_conversations', 'archived_at')) {
            return 0;
        }

        return LiveChatConversation::whereIn('channel_id', $channelIds)
            ->whereNotNull('archived_at')
            ->where('admin_unread_count', '>', 0)
            ->count();
    }

    public function archive(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!Schema::hasColumn('live_chat_conversations', 'archived_at')) {
            return response()->json(['error' => 'Kolom archived_at belum tersedia. Jalankan php artisan migrate.'], 503);
        }

        $conversation->update(['archived_at' => now()]);

        return response()->json(['success' => true, 'archived_at' => $conversation->archived_at]);
    }

    public function restore(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!Schema::hasColumn('live_chat_conversations', 'archived_at')) {
            return response()->json(['error' => 'Kolom archived_at belum tersedia. Jalankan php artisan migrate.'], 503);
        }

        $conversation->update(['archived_at' => null]);

        return response()->json(['success' => true]);
    }

    public function deleteMessage(LiveChatMessage $message): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        $conversation = $message->conversation;
        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        if ($message->sender_type !== 'admin' || $message->sender_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }

    public function hideMessage(LiveChatMessage $message): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($message->conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        

        LiveChatMessageHiddenUser::firstOrCreate([
            'live_chat_message_id' => $message->id,
            'user_id' => $user->id,
        ]);

        return response()->json(['success' => true]);
    }

    public function deleteConversation(LiveChatConversation $conversation): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $channelIds = $user->assignedOperators()->pluck('channel_id');

        if (!$channelIds->contains($conversation->channel_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['success' => true]);
    }
}
