<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAdminAction extends Model
{
    protected $fillable = ['order_id', 'admin_id', 'action', 'reason', 'amount', 'payload', 'idempotency_key'];
    protected $casts = ['amount' => 'decimal:2', 'payload' => 'array'];

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
