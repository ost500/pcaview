<?php

namespace App\Listeners;

use App\Events\TrendFetched;
use App\Domain\news\NateNewsContentService;
use App\Domain\news\NateNewsService;
use App\Models\Department;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FetchNateNewsForTrend implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private NateNewsService $nateNewsService,
        private NateNewsContentService $nateNewsContentService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TrendFetched $event): void
    {
        try {
            $trend = $event->trend;

            // Department 찾기
            $department = Department::find($trend->department_id);
            if (!$department) {
                Log::warning('Department not found for trend', ['trend_id' => $trend->id]);
                return;
            }

            // Nate 뉴스 검색
            $nateNews = $this->nateNewsService->searchNews($trend->title);

            if (empty($nateNews)) {
                Log::info('No Nate news found for trend', ['trend_title' => $trend->title]);
                return;
            }

            // Nate 뉴스를 Contents로 저장
            $savedCount = $this->nateNewsContentService->saveNewsAsContents($nateNews, $department);

            // Trend의 news_items 업데이트
            $allNewsItems = array_merge($trend->news_items ?? [], $nateNews);

            // news_items의 title들을 모아서 description 생성
            $newsTitles = array_slice(
                array_map(fn($item) => $item['title'] ?? '', $allNewsItems),
                0,
                5 // 최대 5개까지만 사용
            );
            $description = implode(' | ', array_filter($newsTitles));

            $trend->update([
                'news_items' => $allNewsItems,
                'description' => $description ?: $trend->description, // 빈 경우 기존 description 유지
            ]);

            Log::info('Nate news fetched for trend', [
                'trend_id' => $trend->id,
                'trend_title' => $trend->title,
                'saved_count' => $savedCount,
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Nate news for trend', [
                'trend_id' => $event->trend->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
