<?php

namespace App\Listeners;

use App\Domain\news\NateNewsContentService;
use App\Domain\news\NateNewsService;
use App\Events\TrendFetched;
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

            // Department와 Church 정보 가져오기
            $department = Department::with('church')->find($trend->department_id);
            if (! $department) {
                Log::warning('Department not found for trend', ['trend_id' => $trend->id]);

                return;
            }

            // Department에 church_id가 없으면 기본 church (maple) 할당
            if (! $department->church_id) {
                $defaultChurch = \App\Models\Church::where('slug', 'maple')->first();
                if ($defaultChurch) {
                    $department->update(['church_id' => $defaultChurch->id]);
                    $department->load('church'); // Relationship 새로고침
                    Log::info('Assigned default church to department', [
                        'department_id' => $department->id,
                        'church_id'     => $defaultChurch->id,
                    ]);
                }
            }

            if (! $department->church) {
                Log::warning('Church not found for department', [
                    'trend_id'      => $trend->id,
                    'department_id' => $department->id,
                ]);

                return;
            }

            // 검색 키워드 생성: church.name과 department.name이 같으면 department.name만, 다르면 합침
            $churchName = $department->church->name;
            $keyword    = $churchName === $department->name
                ? $department->name
                : $churchName.' '.$department->name;

            // Nate 뉴스 검색
            $nateNews = $this->nateNewsService->searchNews($keyword);

            if (empty($nateNews)) {
                Log::info('No Nate news found for trend', ['keyword' => $keyword]);

                return;
            }

            // Nate 뉴스를 Contents로 저장
            $savedCount = $this->nateNewsContentService->saveNewsAsContents($nateNews, $department);

            // Trend의 news_items 업데이트
            $allNewsItems = array_merge($trend->news_items ?? [], $nateNews);
            $trend->update(['news_items' => $allNewsItems]);

            Log::info('Nate news fetched for trend', [
                'trend_id'    => $trend->id,
                'keyword'     => $keyword,
                'saved_count' => $savedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Nate news for trend', [
                'trend_id' => $event->trend->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
