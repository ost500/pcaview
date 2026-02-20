<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 실시간 활동 추적 모델
 *
 * @property int $id
 * @property string|null $referrer 레퍼러 정보
 * @property string|null $user_agent 사용자 에이전트
 * @property string|null $ip_address IP 주소
 * @property string|null $session_id 세션 ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LiveCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer',
        'user_agent',
        'ip_address',
        'session_id',
    ];
}
