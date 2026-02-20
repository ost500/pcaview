<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 리워드 사용(교환) 내역 모델
 *
 * @property int $id
 * @property int $user_reward_id UserReward ID
 * @property int $reward_id Reward ID
 * @property int $points_spent 사용한 포인트
 * @property string $status 상태 (pending, completed, cancelled)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RewardUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_reward_id',
        'reward_id',
        'points_spent',
        'status',
    ];

    protected $casts = [
        'user_reward_id' => 'integer',
        'reward_id' => 'integer',
        'points_spent' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * 사용자 리워드
     */
    public function userReward(): BelongsTo
    {
        return $this->belongsTo(UserReward::class);
    }

    /**
     * 교환한 리워드
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}
