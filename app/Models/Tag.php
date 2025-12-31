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
                $tag->slug = empty($slug) ? $tag->name : $slug;
            }
        });
    }

    /**
     * Contents와의 다대다 관계
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Contents::class, 'content_tag')
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
