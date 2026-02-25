<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 리워드 모델
 *
 * @property int                             $id
 * @property int|null                        $application_id  애플리케이션 ID
 * @property string                          $name            리워드 이름
 * @property string|null                     $description     설명
 * @property float                           $points_required 필요한 포인트
 * @property int|null                        $duration        리워드 지속 시간 (초)
 * @property string|null                     $image_url       이미지 URL
 * @property bool                            $is_active       활성화 여부
 * @property \Illuminate\Support\Carbon|null $expires_at      만료일
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'code',
        'type',
        'name',
        'description',
        'points_required',
        'duration',
        'image_url',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'points_required' => 'decimal:9',
        'duration'        => 'integer',
        'is_active'       => 'boolean',
        'expires_at'      => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * 애플리케이션 관계
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
