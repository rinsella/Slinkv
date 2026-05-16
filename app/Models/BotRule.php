<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotRule extends Model
{
    protected $fillable = ['name', 'type', 'pattern', 'score', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
