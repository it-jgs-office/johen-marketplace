<?php

namespace App\Policies;

use App\Models\LiveChatConversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiveChatConversationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, LiveChatConversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $conversation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function reply(User $user, LiveChatConversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $conversation->user_id === $user->id;
    }

    public function close(User $user, LiveChatConversation $conversation): bool
    {
        return $user->isAdmin();
    }
}
