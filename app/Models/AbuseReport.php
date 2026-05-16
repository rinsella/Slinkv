<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbuseReport extends Model
{
    protected $fillable = ['reporter_email', 'short_link_id', 'short_url', 'reason', 'status', 'admin_action'];
}
