<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClickLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'short_link_id', 'user_id', 'ip_hash', 'ip_address_encrypted',
        'user_agent', 'referer', 'country_code', 'country_name', 'city',
        'device_type', 'browser', 'os', 'source_platform', 'source_id',
        'is_bot', 'bot_score', 'bot_reasons', 'action', 'redirected_to',
        'clicked_at', 'created_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'bot_reasons' => 'array',
        'clicked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function shortLink(): BelongsTo { return $this->belongsTo(ShortLink::class); }
}
