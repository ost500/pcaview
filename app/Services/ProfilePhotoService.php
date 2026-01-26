<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoService
{
    /**
     * 프로필 사진 업데이트
     *
     * @param  User  $user  프로필 사진을 업데이트할 사용자
     * @param  UploadedFile  $photo  업로드된 사진 파일
     * @return string 새로운 프로필 사진 URL
     */
    public function updateProfilePhoto(User $user, UploadedFile $photo): string
    {
        // 기존 프로필 사진 삭제
        $this->deleteExistingPhoto($user);

        // 새 프로필 사진 업로드
        $path = $photo->store('profile-photos', 's3');
        $url  = Storage::disk('s3')->url($path);

        // 사용자 프로필 사진 업데이트
        $user->update([
            'profile_photo_url' => $url,
        ]);

        return $url;
    }

    /**
     * 기존 프로필 사진 삭제 (S3에 있고 카카오 사진이 아닌 경우)
     *
     * @param  User  $user
     * @return bool 삭제 성공 여부
     */
    public function deleteExistingPhoto(User $user): bool
    {
        if (! $user->profile_photo_url) {
            return false;
        }

        // 카카오 CDN 사진은 삭제하지 않음
        if (str_contains($user->profile_photo_url, 'kakaocdn.net')) {
            return false;
        }

        // S3에서 파일 경로 추출
        $path = parse_url($user->profile_photo_url, PHP_URL_PATH);

        if (! $path) {
            return false;
        }

        $path = ltrim($path, '/');

        // S3에 파일이 존재하면 삭제
        if (Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->delete($path);
        }

        return false;
    }

    /**
     * 사용자 계정 삭제 시 프로필 사진 정리
     *
     * @param  User  $user
     * @return bool
     */
    public function cleanupOnAccountDeletion(User $user): bool
    {
        return $this->deleteExistingPhoto($user);
    }
}
