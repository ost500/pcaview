<?php

namespace App\Listeners;

use App\Domain\news\NaverNewsContentService;
use App\Domain\news\NaverNewsService;
use App\Events\TrendFetched;
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

            // Department 정보 가져오기
            $department = Department::with('church')->find($trend->department_id);
            if (! $department) {
                Log::warning('Department not found for trend', ['trend_id' => $trend->id]);

                return;
            }

            // 검색 키워드 생성: church가 있으면 church.name과 department.name 조합, 없으면 department.name만
            if ($department->church) {
                $churchName = $department->church->name;
                $keyword    = $churchName === $department->name
                    ? $department->name
                    : $churchName.' '.$department->name;
            } else {
                $keyword = $department->name;
            }

            // Naver 뉴스 검색
            $naverNewsItems = $this->naverNewsService->searchNews($keyword);

            if (empty($naverNewsItems)) {
                Log::info('No Naver news found for trend', ['keyword' => $keyword]);

                return;
            }

            // Naver 뉴스를 Contents로 저장 (AI 이미지 생성 없음)
            $savedCount = $this->naverNewsContentService->saveNewsAsContents($naverNewsItems, $department, false);

            // NaverNewsItem 객체들을 배열로 변환
            $naverNewsArray = array_map(fn ($item) => $item->toArray(), $naverNewsItems);

            // Trend의 news_items 업데이트
            $allNewsItems = array_merge($trend->news_items ?? [], $naverNewsArray);
            $trend->update(['news_items' => $allNewsItems]);

            Log::info('Naver news fetched for trend', [
                'trend_id'    => $trend->id,
                'keyword'     => $keyword,
                'saved_count' => $savedCount,
            ]);
        } catch (\Exception $e) {
            $trend      = $event->trend;
            $department = Department::with('church')->find($trend->department_id);

            if ($department) {
                if ($department->church) {
                    $churchName = $department->church->name;
                    $keyword    = $churchName === $department->name
                        ? $department->name
                        : $churchName.' '.$department->name;
                } else {
                    $keyword = $department->name;
                }
            } else {
                $keyword = 'unknown';
            }

            Log::error('Failed to fetch Naver news for trend', [
                'trend_id' => $trend->id,
                'keyword'  => $keyword,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            // API 쿼터 초과 에러는 재시도하지 않음
            if (str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), 'limit exceeded')) {
                $this->fail($e);
            }
        }
    }
}
