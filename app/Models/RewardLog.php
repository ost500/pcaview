<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 리워드 적립 로그 모델
 *
 * @property int                             $id
 * @property string                          $encrypted        암호화된 사용자 식별 정보
 * @property string                          $reward_type      리워드 종류 (watch, ad 등)
 * @property string|null                     $where            리워드 발생 위치/출처
 * @property string|null                     $video_url        시청한 비디오 URL
 * @property int|null                        $video_time       비디오 시청 시간 (초)
 * @property string|null                     $video_stringtime 비디오 시청 시간 (문자열)
 * @property float|null                      $points_earned             적립된 포인트
 * @property float|null                      $points_value              적립 포인트의 원화 가치
 * @property float|null                      $before_balance            적립 전 잔액
 * @property float|null                      $after_balance             적립 후 잔액
 * @property float|null                      $after_balance_value       적립 후 잔액의 원화 가치
 * @property int|null                        $metal_domestic_price_id   적립 당시 금 시세 ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RewardLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'encrypted',
        'reward_type',
        'where',
        'video_url',
        'video_time',
        'video_stringtime',
        'points_earned',
        'points_value',
        'before_balance',
        'after_balance',
        'after_balance_value',
        'metal_domestic_price_id',
    ];

    protected $casts = [
        'video_time'          => 'integer',
        'points_earned'       => 'decimal:9',
        'points_value'        => 'decimal:9',
        'before_balance'      => 'decimal:9',
        'after_balance'       => 'decimal:9',
        'after_balance_value' => 'decimal:9',
    ];
}
