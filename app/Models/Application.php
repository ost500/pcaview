<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 애플리케이션 모델
 *
 * @property int                             $id
 * @property string                          $name        앱 이름
 * @property string                          $code        앱 코드
 * @property string|null                     $description 앱 설명
 * @property bool                            $is_active   활성화 여부
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 앱에 속한 리워드들
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }
}
