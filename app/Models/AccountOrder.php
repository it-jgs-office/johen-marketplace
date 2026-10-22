<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountOrder extends Model
{
    protected $fillable = [
        'account_listing_id',
        'user_id',
        'order_ref',
        'customer_name',
        'customer_email',
        'customer_phone',
        'payment_method',
        'gateway_invoice_id',
        'gateway_invoice_url',
        'gateway_type',
        'qr_string',
        'va_number',
        'payment_code',
        'checkout_url',
        'gateway_extra',
        'status',
        'credentials_email_attempted_at',
        'credentials_email_sent_at',
        'credentials_email_error',
        'total_price',
        'points_used',
        'points_status',
        'cash_amount',
        'saldo_amount',
        'saldo_status',
        'saldo_released_at',
        'notes',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'points_used' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'saldo_amount' => 'decimal:2',
        'saldo_released_at' => 'datetime',
        'gateway_extra' => 'array',
        'credentials_email_attempted_at' => 'datetime',
        'credentials_email_sent_at' => 'datetime',
    ];

    public function listing()
    {
        return $this->belongsTo(AccountListing::class, 'account_listing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
