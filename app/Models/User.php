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

#[Fillable(['name', 'username', 'email', 'password', 'is_admin', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
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
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
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

    public function isMainAdmin(): bool
    {
        return $this->isAdmin()
            && hash_equals((string) config('admin.main_username'), (string) $this->username);
    }

    public function hasTwoFactorAuthentication(): bool
    {
        return $this->isAdmin()
            && filled($this->two_factor_secret)
            && $this->two_factor_confirmed_at !== null;
    }

}
