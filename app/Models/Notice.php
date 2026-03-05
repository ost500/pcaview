<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 앱 공지사항 모델
 *
 * @property int $id
 * @property string $title 제목
 * @property string $content 내용
 * @property int $priority 우선순위 (높을수록 상단 표시)
 * @property bool $is_active 활성화 여부
 * @property \Illuminate\Support\Carbon|null $start_at 노출 시작일
 * @property \Illuminate\Support\Carbon|null $end_at 노출 종료일
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'priority',
        'is_active',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected $attributes = [
        'priority' => 0,
        'is_active' => true,
    ];
}
