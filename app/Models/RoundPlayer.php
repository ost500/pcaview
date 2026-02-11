<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoundPlayer extends Model
{
    protected $fillable = [
        'round_id',
        'user_id',
        'player_name',
        'player_order',
        'is_me',
        'total_score',
        'score_vs_par',
        'rank',
        'is_winner',
    ];

    protected $casts = [
        'is_me'     => 'boolean',
        'is_winner' => 'boolean',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RoundScore::class);
    }
}
