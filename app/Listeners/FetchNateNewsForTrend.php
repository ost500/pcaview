<?php

namespace App\Listeners;

use App\Events\TrendFetched;
use App\Domain\news\NateNewsContentService;
use App\Domain\news\NateNewsService;
use App\Domain\news\NaverNewsContentService;
use App\Domain\news\NaverNewsService;
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
        private NateNewsContentService $nateNewsContentService,
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

            // Nate 뉴스 검색
            $nateNews = $this->nateNewsService->searchNews($trend->title);

            if (empty($nateNews)) {
                Log::info('No Nate news found for trend', ['trend_title' => $trend->title]);
                return;
            }

            // Nate 뉴스를 Contents로 저장
            $nateSavedCount = $this->nateNewsContentService->saveNewsAsContents($nateNews, $department);

            // Trend의 news_items 업데이트
            $allNewsItems = array_merge($trend->news_items ?? [], $nateNews);

            // Naver 뉴스 검색 (news_items의 title로 검색)
            $naverNews = [];
            foreach ($allNewsItems as $newsItem) {
                if (!empty($newsItem['title'])) {
                    $naverResult = $this->naverNewsService->searchNews($newsItem['title']);
                    if (!empty($naverResult)) {
                        $naverNews = array_merge($naverNews, $naverResult);
                    }
                }
            }

            // Naver 뉴스를 Contents로 저장
            $naverSavedCount = 0;
            if (!empty($naverNews)) {
                $naverSavedCount = $this->naverNewsContentService->saveNewsAsContents($naverNews, $department);
                $allNewsItems = array_merge($allNewsItems, $naverNews);
            }

            $trend->update(['news_items' => $allNewsItems]);

            Log::info('News fetched for trend', [
                'trend_id' => $trend->id,
                'trend_title' => $trend->title,
                'nate_saved_count' => $nateSavedCount,
                'naver_saved_count' => $naverSavedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Nate news for trend', [
                'trend_id' => $event->trend->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
