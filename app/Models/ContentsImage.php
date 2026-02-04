<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentsImage extends Model
{
    protected $guarded = [];

    /**
     * Contents 관계 (외래키: contents_id)
     */
    public function contents(): BelongsTo
    {
        return $this->belongsTo(Contents::class, 'contents_id');
    }

    /**
     * Get the file URL attribute.
     * Convert HTTP to HTTPS for security and SEO.
     */
    public function getFileUrlAttribute($value): ?string
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
