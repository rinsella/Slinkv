<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkDailyStat extends Model
{
    protected $table = 'link_daily_stats';

    protected $fillable = [
        'short_link_id', 'date', 'total_clicks', 'human_clicks', 'bot_clicks', 'unique_visitors',
    ];

    protected $casts = ['date' => 'date'];
}
