<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkHourlyStat extends Model
{
    protected $table = 'link_hourly_stats';

    protected $fillable = [
        'short_link_id', 'datetime_hour', 'total_clicks', 'human_clicks', 'bot_clicks',
    ];

    protected $casts = ['datetime_hour' => 'datetime'];
}
