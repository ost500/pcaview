<?php

namespace App\Services;

use App\Enums\ContentsType;
use App\Models\Contents;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ContentsService
{
    /**
     * 콘텐츠 삭제
     *
     * @param  Contents  $contents  삭제할 콘텐츠
     * @param  User  $user  삭제를 요청한 사용자
     * @return bool 삭제 성공 여부
     *
     * @throws \Exception 권한이 없는 경우
     */
    public function deleteContents(Contents $contents, User $user): bool
    {
        // 권한 확인: 콘텐츠 작성자만 삭제 가능
        if ($contents->user_id !== $user->id) {
            throw new \Exception('삭제 권한이 없습니다.');
        }

        // 관련 이미지 삭제
        $this->deleteContentsImages($contents);

        // 콘텐츠 삭제 (연관된 댓글, 태그 등은 모델의 cascade 설정에 따라 처리)
        return $contents->delete();
    }

    /**
     * 콘텐츠의 모든 이미지 삭제
     *
     * @param  Contents  $contents
     * @return void
     */
    protected function deleteContentsImages(Contents $contents): void
    {
        if (! $contents->images) {
            return;
        }

        foreach ($contents->images as $image) {
            // S3에서 이미지 삭제
            if ($image->file_url) {
                $path = parse_url($image->file_url, PHP_URL_PATH);
                if ($path && Storage::disk('s3')->exists(ltrim($path, '/'))) {
                    Storage::disk('s3')->delete(ltrim($path, '/'));
                }
            }

            // 이미지 레코드 삭제
            $image->delete();
        }
    }

    /**
     * 콘텐츠 삭제 후 리다이렉트 URL 가져오기
     *
     * @param  Contents  $contents
     * @param  string|null  $fallbackUrl
     * @return string
     */
    public function getRedirectUrlAfterDelete(Contents $contents, ?string $fallbackUrl = null): string
    {
        // church 페이지로 리다이렉트
        if ($contents->church && $contents->church->slug) {
            return '/c/'.$contents->church->slug;
        }

        return $fallbackUrl ?? '/';
    }

    /**
     * news 타입 콘텐츠의 body 텍스트를 1/3만 표시
     * 저작권 보호를 위해 news 타입은 본문의 일부만 표시
     *
     * @param  Collection  $contents
     * @return Collection
     */
    public function filterNewsContents(Collection $contents): Collection
    {
        return $contents->map(function ($content) {
            // news 관련 타입 필터링 (news, nate_news, naver_news)
            if (ContentsType::isNews($content->type) && $content->body) {
                // body 텍스트를 1/3로 줄임
                $originalLength = mb_strlen($content->body);
                $truncatedLength = (int) ($originalLength / 3);

                if ($truncatedLength > 0) {
                    $content->body = mb_substr($content->body, 0, $truncatedLength).'...';
                }
            }

            return $content;
        });
    }
}
