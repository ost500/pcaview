<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 사용자별 리워드 포인트 잔액 모델
 *
 * @property int $id
 * @property string $encrypted 암호화된 사용자 식별 정보 (unique)
 * @property int $balance 현재 포인트 잔액
 * @property int $total_earned 총 적립 포인트
 * @property int $total_spent 총 사용 포인트
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'encrypted',
        'balance',
        'total_earned',
        'total_spent',
    ];

    protected $casts = [
        'balance' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
    ];

    protected $attributes = [
        'balance' => 0,
        'total_earned' => 0,
        'total_spent' => 0,
    ];

    /**
     * 리워드 적립 내역
     */
    public function rewardLogs(): HasMany
    {
        return $this->hasMany(RewardLog::class, 'encrypted', 'encrypted');
    }

    /**
     * 리워드 사용 내역
     */
    public function usages(): HasMany
    {
        return $this->hasMany(RewardUsage::class);
    }
}
