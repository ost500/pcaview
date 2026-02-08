<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'content_id',
        'user_id',
        'guest_name',
        'ip_address',
        'body',
        'created_at',
    ];

    protected $appends = [
        'display_name',
        'ip_last_digits',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Contents::class, 'content_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }

        return $this->guest_name ?? '익명';
    }

    public function getIpLastDigitsAttribute(): string
    {
        if (! $this->ip_address) {
            return '';
        }
        $parts = explode('.', $this->ip_address);
        if (count($parts) >= 4) {
            return $parts[2].'.'.$parts[3];
        }

        return end($parts);
    }
}
