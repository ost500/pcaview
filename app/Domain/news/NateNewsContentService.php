<?php

namespace App\Domain\news;

use App\Models\Contents;
use App\Models\Department;
use Illuminate\Support\Facades\Log;

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

                // Contents 생성
                Contents::create([
                    'department_id' => $department->id,
                    'type' => 'news', // Nate 뉴스 타입
                    'title' => $newsItem['title'],
                    'body' => $newsItem['snippet'] ?? null,
                    'file_url' => $newsItem['url'],
                    'thumbnail_url' => $newsItem['picture'] ?? null,
                    'published_at' => now(), // Nate 뉴스는 현재 시각 사용
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
}
