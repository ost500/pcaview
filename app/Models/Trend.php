<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trend extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'title',
        'description',
        'link',
        'image_url',
        'traffic_count',
        'pub_date',
        'picture',
        'picture_source',
        'news_items',
    ];

    protected $casts = [
        'pub_date' => 'datetime',
        'traffic_count' => 'integer',
        'news_items' => 'array',
    ];

    /**
     * 최신 트렌드 순으로 정렬
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('pub_date', 'desc');
    }

    /**
     * 트래픽 순으로 정렬 (높은 순)
     */
    public function scopeByTraffic($query)
    {
        return $query->orderByDesc('traffic_count');
    }

    /**
     * 오늘의 트렌드만 가져오기
     */
    public function scopeToday($query)
    {
        return $query->whereDate('pub_date', today());
    }

    /**
     * 특정 기간의 트렌드 가져오기
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('pub_date', [$startDate, $endDate]);
    }

    /**
     * Department 관계 정의
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
