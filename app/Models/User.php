<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'kakao_id',
        'profile_photo_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 사용자가 구독한 부서들
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
    }

    /**
     * Convert HTTP to HTTPS for profile_photo_url.
     */
    public function getProfilePhotoUrlAttribute($value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http://')) {
            return str_replace('http://', 'https://', $value);
        }

        return $value;
    }

    /**
     * 프로필 사진 URL을 가져옵니다. 없으면 Gravatar를 반환합니다.
     */
    public function getProfilePhotoAttribute(): string
    {
        if ($this->profile_photo_url) {
            return $this->profile_photo_url;
        }

        // Gravatar 생성
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s=200&d=mp";
    }
}
