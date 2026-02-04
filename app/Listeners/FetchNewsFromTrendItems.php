<?php

namespace App\Listeners;

use App\Domain\news\NaverNewsContentService;
use App\Domain\news\NaverNewsService;
use App\Events\TrendFetched;
use App\Models\Department;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FetchNewsFromTrendItems implements ShouldQueue
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

            // Department 정보 가져오기
            $department = Department::with('church')->find($trend->department_id);
            if (! $department) {
                Log::warning('Department not found for trend', ['trend_id' => $trend->id]);

                return;
            }

            // news_items가 없으면 종료
            if (empty($trend->news_items)) {
                Log::info('No news items in trend to search', ['trend_id' => $trend->id]);

                return;
            }

            $allFetchedNews = [];
            $totalSavedCount = 0;

            // news_items의 각 뉴스 title로 검색
            foreach ($trend->news_items as $newsItem) {
                // news_item이 배열이고 title이 있는지 확인
                if (! is_array($newsItem) || empty($newsItem['title'])) {
                    continue;
                }

                $newsTitle = $newsItem['title'];

                Log::info('Searching Naver news for trend news item', [
                    'trend_id' => $trend->id,
                    'news_title' => $newsTitle,
                ]);

                // Naver 뉴스 검색 (상위 3개만 가져오기)
                $naverNewsItems = $this->naverNewsService->searchNews($newsTitle, display: 3, sort: 'sim');

                if (empty($naverNewsItems)) {
                    Log::info('No Naver news found for news item title', [
                        'trend_id' => $trend->id,
                        'news_title' => $newsTitle,
                    ]);

                    continue;
                }

                // Naver 뉴스를 Contents로 저장
                $savedCount = $this->naverNewsContentService->saveNewsAsContents($naverNewsItems, $department);
                $totalSavedCount += $savedCount;

                // 수집한 뉴스 누적
                $naverNewsArray = array_map(fn ($item) => $item->toArray(), $naverNewsItems);
                $allFetchedNews = array_merge($allFetchedNews, $naverNewsArray);

                Log::info('Fetched Naver news for news item', [
                    'trend_id' => $trend->id,
                    'news_title' => $newsTitle,
                    'fetched_count' => count($naverNewsItems),
                    'saved_count' => $savedCount,
                ]);
            }

            // 수집한 뉴스가 있으면 Trend 업데이트
            if (! empty($allFetchedNews)) {
                $allNewsItems = array_merge($trend->news_items ?? [], $allFetchedNews);
                $trend->update(['news_items' => $allNewsItems]);

                Log::info('Updated trend with news from items search', [
                    'trend_id' => $trend->id,
                    'total_fetched' => count($allFetchedNews),
                    'total_saved' => $totalSavedCount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch news from trend items', [
                'trend_id' => $event->trend->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // API 쿼터 초과 에러는 재시도하지 않음
            if (str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), 'limit exceeded')) {
                $this->fail($e);
            }
        }
    }
}
