<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ai extends Model
{
    use HasFactory;

    protected $table = 'ais';

    protected $fillable = [
        'user_id',
        'ai_id',
        'provider',
        'model',
        'created',
        'question_role',
        'question',
        'answer_role',
        'answer',
        'is_public',
    ];

    protected $casts = [
        'created' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
