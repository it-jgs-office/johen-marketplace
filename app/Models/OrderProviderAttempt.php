<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProviderAttempt extends Model
{
    protected $fillable = ['order_id', 'admin_id', 'action', 'provider_ref', 'status', 'message', 'response'];
    protected $casts = ['response' => 'array'];

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
