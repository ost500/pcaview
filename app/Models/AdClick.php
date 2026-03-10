<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdClick extends Model
{
    protected $fillable = [
        'ad_id',
        'click_count',
        'redirect_url',
    ];

    protected $casts = [
        'click_count' => 'integer',
    ];

    public function incrementClickCount(): void
    {
        $this->increment('click_count');
    }
}
