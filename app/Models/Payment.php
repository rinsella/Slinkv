<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'subscription_id', 'invoice_number',
        'amount', 'currency', 'status', 'gateway', 'gateway_reference',
        'payment_url', 'paid_at', 'expired_at', 'raw_response',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
}
