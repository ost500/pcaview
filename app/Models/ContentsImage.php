<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentsImage extends Model
{
    protected $guarded = [];

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
