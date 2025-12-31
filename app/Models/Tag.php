<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // 자동으로 slug 생성 (한글 지원)
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                // 한글인 경우 그대로 사용, 그 외에는 slug 변환
                $slug = Str::slug($tag->name);

                // slug가 비어있거나, 원본 이름과 너무 차이가 나면 원본 사용
                // 예: "시즌2 기대" → "2" (너무 짧음) → "시즌2 기대" 사용
                if (empty($slug) || mb_strlen($slug) < mb_strlen($tag->name) / 2) {
                    $baseSlug = $tag->name;
                } else {
                    $baseSlug = $slug;
                }

                // Unique slug 생성
                $uniqueSlug = $baseSlug;
                $counter = 1;

                // slug가 이미 존재하면 suffix 추가
                while (static::where('slug', $uniqueSlug)->exists()) {
                    $counter++;
                    $uniqueSlug = $baseSlug . '-' . $counter;
                }

                $tag->slug = $uniqueSlug;
            }
        });
    }

    /**
     * Contents와의 다대다 관계
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Contents::class, 'content_tag', 'tag_id', 'content_id')
            ->withTimestamps();
    }

    /**
     * Trends와의 다대다 관계
     */
    public function trends(): BelongsToMany
    {
        return $this->belongsToMany(Trend::class, 'trend_tag')
            ->withTimestamps();
    }

    /**
     * 사용 횟수 증가
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * 사용 횟수 감소
     */
    public function decrementUsage(): void
    {
        $this->decrement('usage_count');
    }

    /**
     * 인기 태그 조회 (사용 횟수 기준)
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * 이름으로 검색
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where('name', 'like', "%{$keyword}%");
    }
}
