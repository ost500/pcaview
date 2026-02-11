<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'course_name',
        'hole_count',
        'hole_pars',
        'status',
        'memo',
        'played_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'hole_pars'    => 'array',
        'played_at'    => 'date',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ParkGolfCourse::class, 'course_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(RoundPlayer::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RoundScore::class);
    }

    public function getTotalParAttribute(): int
    {
        return array_sum($this->hole_pars);
    }
}
