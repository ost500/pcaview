<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 앱 버전 관리 모델
 *
 * @property int $id
 * @property string $version 버전 번호 (예: "1.0.0")
 * @property string $platform 플랫폼 (ios, android, all)
 * @property bool $is_force_update 강제 업데이트 여부
 * @property bool $is_active 활성화 여부
 * @property string|null $update_url 업데이트 URL (앱스토어/플레이스토어 링크)
 * @property string|null $update_message 업데이트 안내 메시지
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'platform',
        'is_force_update',
        'is_active',
        'update_url',
        'update_message',
    ];

    protected $casts = [
        'is_force_update' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'platform' => 'all',
        'is_force_update' => false,
        'is_active' => true,
    ];
}
