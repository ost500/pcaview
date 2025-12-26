<?php

namespace App\Listeners;

use App\Events\TrendFetched;
use App\Domain\news\NaverNewsContentService;
use App\Domain\news\NaverNewsService;
use App\Models\Department;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FetchNaverNewsForTrend implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        private NaverNewsService $naverNewsService,
        private NaverNewsContentService $naverNewsContentService
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

            // Trend의 news_items에서 title로 Naver 뉴스 검색
            $naverNews = [];
            $newsItems = $trend->news_items ?? [];

            foreach ($newsItems as $newsItem) {
                if (!empty($newsItem['title'])) {
                    $naverResult = $this->naverNewsService->searchNews($newsItem['title']);
                    if (!empty($naverResult)) {
                        $naverNews = array_merge($naverNews, $naverResult);
                    }
                }
            }

            if (empty($naverNews)) {
                Log::info('No Naver news found for trend', ['trend_title' => $trend->title]);
                return;
            }

            // Naver 뉴스를 Contents로 저장
            $savedCount = $this->naverNewsContentService->saveNewsAsContents($naverNews, $department);

            // Trend의 news_items 업데이트
            $allNewsItems = array_merge($newsItems, $naverNews);
            $trend->update(['news_items' => $allNewsItems]);

            Log::info('Naver news fetched for trend', [
                'trend_id' => $trend->id,
                'trend_title' => $trend->title,
                'saved_count' => $savedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Naver news for trend', [
                'trend_id' => $event->trend->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
