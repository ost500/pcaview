<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contents extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'is_hide' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ContentsImage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    // Keep for backward compatibility with existing code
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Many-to-many relationship with departments
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'content_department', 'content_id', 'department_id')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'content_id');
    }

    public function platformComments(): HasMany
    {
        return $this->hasMany(ContentsPlatformComment::class, 'content_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'content_tag', 'content_id', 'tag_id')
            ->withTimestamps();
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
