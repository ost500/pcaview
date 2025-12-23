<?php

namespace App\Domain\news;

use App\Models\Contents;
use App\Models\Department;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Nate 뉴스를 Contents로 변환하여 저장하는 서비스
 */
class NateNewsContentService
{
    /**
     * Nate 뉴스 배열을 Contents로 변환하여 저장
     *
     * @param array $newsItems Nate 뉴스 아이템 배열
     * @param Department $department 연결할 Department
     * @return int 저장된 개수
     */
    public function saveNewsAsContents(array $newsItems, Department $department): int
    {
        $savedCount = 0;

        foreach ($newsItems as $newsItem) {
            try {
                // 필수 필드 확인
                if (empty($newsItem['title']) || empty($newsItem['url'])) {
                    continue;
                }

                // URL 기반으로 중복 체크
                $existingContent = Contents::where('file_url', $newsItem['url'])
                    ->where('department_id', $department->id)
                    ->first();

                if ($existingContent) {
                    continue; // 이미 존재하면 스킵
                }

                // 이미지가 있으면 S3에 업로드
                $thumbnailUrl = null;
                if (!empty($newsItem['picture'])) {
                    $thumbnailUrl = $this->uploadImageToS3($newsItem['picture'], $department->id);
                }

                // Contents 생성 (이미 NateNewsService에서 UTF-8 변환됨)
                Contents::create([
                    'department_id' => $department->id,
                    'type' => 'news', // Nate 뉴스 타입
                    'title' => $newsItem['title'],
                    'body' => $newsItem['snippet'] ?? null,
                    'file_url' => $newsItem['url'],
                    'thumbnail_url' => $thumbnailUrl,
                    'published_at' => $newsItem['published_at'] ?? now(), // 실제 발행일시 사용
                ]);

                $savedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to save Nate news as Contents', [
                    'error' => $e->getMessage(),
                    'news_item' => $newsItem,
                ]);
            }
        }

        return $savedCount;
    }

    /**
     * 외부 이미지 URL을 다운로드하여 S3에 업로드
     *
     * @param string $imageUrl 원본 이미지 URL
     * @param int $departmentId Department ID
     * @return string|null S3 URL 또는 null (실패 시)
     */
    private function uploadImageToS3(string $imageUrl, int $departmentId): ?string
    {
        try {
            // 외부 이미지 다운로드
            $response = Http::timeout(10)->get($imageUrl);

            if (!$response->successful()) {
                Log::warning('Failed to download image', ['url' => $imageUrl]);
                return null;
            }

            // 파일 확장자 추출 (기본값: jpg)
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'jpg';
            }

            // S3 저장 경로 생성
            $fileName = Str::uuid() . '.' . $extension;
            $s3Path = "news/thumbnails/{$departmentId}/{$fileName}";

            // S3에 업로드
            Storage::put($s3Path, $response->body());

            // S3 URL 반환
            return Storage::url($s3Path);
        } catch (\Exception $e) {
            Log::error('Failed to upload image to S3', [
                'error' => $e->getMessage(),
                'image_url' => $imageUrl,
            ]);
            return null;
        }
    }
}
