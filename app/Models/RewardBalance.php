<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 사용자별 리워드 포인트 잔액 모델
 *
 * @property int                             $id
 * @property int|null                        $user_id      사용자 ID (nullable)
 * @property string                          $encrypted    암호화된 사용자 식별 정보 (unique)
 * @property float                           $balance      현재 포인트 잔액
 * @property float                           $total_earned 총 적립 포인트
 * @property float                           $total_spent  총 사용 포인트
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RewardBalance extends Model
{
    use HasFactory;

    protected $table = 'reward_balances';

    protected $fillable = [
        'user_id',
        'encrypted',
        'balance',
        'total_earned',
        'total_spent',
    ];

    protected $casts = [
        'balance'      => 'decimal:9',
        'total_earned' => 'decimal:9',
        'total_spent'  => 'decimal:9',
    ];

    protected $attributes = [
        'balance'      => 0.0,
        'total_earned' => 0.0,
        'total_spent'  => 0.0,
    ];

    /**
     * 사용자 관계
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
