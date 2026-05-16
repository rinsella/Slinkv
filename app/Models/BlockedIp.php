<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $fillable = ['ip_hash', 'reason', 'expires_at', 'is_active'];
    protected $casts = ['expires_at' => 'datetime', 'is_active' => 'boolean'];
}
