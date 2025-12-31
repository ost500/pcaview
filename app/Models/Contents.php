<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contents extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ContentsImage::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'content_id');
    }

    public function platformComments(): HasMany
    {
        return $this->hasMany(ContentsPlatformComment::class, 'content_id');
    }

    /**
     * Get the thumbnail URL attribute.
     * Convert HTTP to HTTPS for security and SEO.
     */
    public function getThumbnailUrlAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        // Convert HTTP to HTTPS for Kakao CDN and other image URLs
        if (str_starts_with($value, 'http://')) {
            return str_replace('http://', 'https://', $value);
        }

        return $value;
    }
}
