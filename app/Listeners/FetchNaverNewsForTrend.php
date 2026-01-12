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

            // Naver 뉴스 검색 (trend title 사용)
            $naverNewsItems = $this->naverNewsService->searchNews($trend->title);

            if (empty($naverNewsItems)) {
                Log::info('No Naver news found for trend', ['trend_title' => $trend->title]);
                return;
            }

            // Naver 뉴스를 Contents로 저장
            $savedCount = $this->naverNewsContentService->saveNewsAsContents($naverNewsItems, $department);

            // NaverNewsItem 객체들을 배열로 변환
            $naverNewsArray = array_map(fn($item) => $item->toArray(), $naverNewsItems);

            // Trend의 news_items 업데이트
            $allNewsItems = array_merge($trend->news_items ?? [], $naverNewsArray);
            $trend->update(['news_items' => $allNewsItems]);

            Log::info('Naver news fetched for trend', [
                'trend_id' => $trend->id,
                'trend_title' => $trend->title,
                'saved_count' => $savedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Naver news for trend', [
                'trend_id' => $event->trend->id,
                'trend_title' => $event->trend->title,
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
