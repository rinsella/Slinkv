<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShortLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'slug', 'destination_url', 'fallback_url',
        'bot_protection_enabled', 'geo_filter_enabled',
        'allowed_countries', 'blocked_countries', 'device_filter',
        'password', 'is_active', 'is_flagged', 'expires_at',
        'total_clicks', 'human_clicks', 'bot_clicks', 'last_clicked_at',
    ];

    protected $casts = [
        'allowed_countries' => 'array',
        'blocked_countries' => 'array',
        'bot_protection_enabled' => 'boolean',
        'geo_filter_enabled' => 'boolean',
        'is_active' => 'boolean',
        'is_flagged' => 'boolean',
        'expires_at' => 'datetime',
        'last_clicked_at' => 'datetime',
    ];

    protected $hidden = ['password'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function clicks(): HasMany { return $this->hasMany(ClickLog::class); }
    public function dailyStats(): HasMany { return $this->hasMany(LinkDailyStat::class); }
    public function hourlyStats(): HasMany { return $this->hasMany(LinkHourlyStat::class); }

    public function shortUrl(): string
    {
        return rtrim(config('app.url'), '/') . '/' . $this->slug;
    }

    public function botRate(): float
    {
        if ($this->total_clicks <= 0) return 0;
        return round(($this->bot_clicks / $this->total_clicks) * 100, 2);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
