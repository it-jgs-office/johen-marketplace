<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'is_admin', 'is_live_chat_admin', 'is_live_chat_cs', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_live_chat_admin' => 'boolean',
            'is_live_chat_cs' => 'boolean',
        ];
    }

    public function balance()
    {
        return $this->hasOne(UserBalance::class);
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class)->latest('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest('id');
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isLiveChatAdmin(): bool
    {
        return (bool) ($this->is_live_chat_admin && ! $this->is_admin);
    }

    public function isLiveChatCs(): bool
    {
        return (bool) ($this->is_live_chat_cs && ! $this->is_admin);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function liveChatConversations(): HasMany
    {
        return $this->hasMany(LiveChatConversation::class, 'user_id');
    }

    public function liveChatMessages(): HasMany
    {
        return $this->hasMany(LiveChatMessage::class, 'sender_id');
    }

    public function assignedOperators(): HasMany
    {
        return $this->hasMany(LiveChatOperator::class, 'user_id');
    }

    public function assignedChannels()
    {
        return $this->belongsToMany(LiveChatChannel::class, 'live_chat_operators', 'user_id', 'channel_id');
    }

    public function liveChatAdmin()
    {
        return $this->hasOne(LiveChatAdmin::class, 'user_id');
    }

    public function getAssignedChannelNames(): string
    {
        if ($this->is_admin) {
            return 'Semua Channel';
        }

        return $this->assignedChannels()->pluck('name')->implode(', ') ?: 'Belum ditugaskan';
    }
}
