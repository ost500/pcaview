<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentsPlatformComment extends Model
{
    protected $fillable = [
        'content_id',
        'comment_id',
        'source',
        'author',
        'content',
        'likes',
        'dislikes',
        'created_date',
        'is_best',
        'is_mobile',
        'reply_count',
    ];

    protected $casts = [
        'likes' => 'integer',
        'dislikes' => 'integer',
        'is_best' => 'boolean',
        'is_mobile' => 'boolean',
        'reply_count' => 'integer',
    ];

    public function contentPost(): BelongsTo
    {
        return $this->belongsTo(Contents::class, 'content_id');
    }

    /**
     * 댓글 ID로 조회 (중복 방지용)
     */
    public function scopeByCommentId($query, string $commentId)
    {
        return $query->where('comment_id', $commentId);
    }

    /**
     * 특정 소스의 댓글만 조회
     */
    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * 베스트 댓글만 조회
     */
    public function scopeBest($query)
    {
        return $query->where('is_best', true);
    }

    /**
     * 좋아요 순 정렬
     */
    public function scopeOrderByLikes($query, string $direction = 'desc')
    {
        return $query->orderBy('likes', $direction);
    }
}
