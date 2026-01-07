<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SymlinkVisit extends Model
{
    protected $fillable = [
        'ad_id',
        'ip',
        'user_agent',
        'referer',
    ];
}
