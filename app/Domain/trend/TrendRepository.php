<?php

namespace App\Domain\trend;

use App\Events\TrendFetched;
use App\Models\Trend;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrendRepository
{

    /**
     * TrendItem을 데이터베이스에 저장 (중복 방지)
     * Title을 기반으로 Department도 자동 생성
     * TrendFetched 이벤트를 디스패치하여 비동기로 Nate 뉴스 가져오기 실행
     */
    public function save(TrendItem $item): Trend
    {
        // news_items의 title들을 모아서 description 생성 (최대 2개)
        $titles = array_filter(
            array_map(fn($newsItem) => $newsItem['title'] ?? '', $item->newsItems),
            fn($title) => !empty($title)
        );
        $description = implode(' | ', array_slice($titles, 0, 2));

        // Title을 기반으로 Department 생성 또는 조회
        $department = $this->findOrCreateDepartment($item->title, $description, $item->picture);

        $trend = Trend::updateOrCreate(
            [
                'title' => $item->title,
                'pub_date' => $item->pubDate,
            ],
            [
                'department_id' => $department->id,
                'description' => $description,
                'link' => $item->link,
                'image_url' => $item->imageUrl,
                'traffic_count' => $item->trafficCount,
                'picture' => $item->picture,
                'picture_source' => $item->pictureSource,
                'news_items' => $item->newsItems,
            ]
        );

        // TrendFetched 이벤트 디스패치 (리스너가 Nate 뉴스 가져오기 처리)
        TrendFetched::dispatch($trend);

        return $trend;
    }

    /**
     * Title을 기반으로 Department 찾기 또는 생성
     */
    private function findOrCreateDepartment(string $title, string $description, ?string $picture = null): Department
    {
        // Title을 Department name으로 사용
        // 같은 title이 이미 존재하면 재사용
        return Department::firstOrCreate(
            ['name' => $title],
            [
                'description' => $description,
                'icon_image' => $picture,
            ]
        );
    }

    /**
     * 여러 TrendItem을 한 번에 저장
     *
     * @param array<TrendItem> $items
     * @return int 저장된 개수
     */
    public function saveMany(array $items): int
    {
        $count = 0;

        DB::transaction(function () use ($items, &$count) {
            foreach ($items as $item) {
                $this->save($item);
                $count++;
            }
        });

        return $count;
    }

    /**
     * 최신 트렌드 가져오기
     */
    public function getLatest(int $limit = 10): Collection
    {
        return Trend::latest()->limit($limit)->get();
    }

    /**
     * 트래픽 순으로 트렌드 가져오기
     */
    public function getByTraffic(int $limit = 10): Collection
    {
        return Trend::byTraffic()->limit($limit)->get();
    }

    /**
     * 오늘의 트렌드 가져오기
     */
    public function getToday(int $limit = 10): Collection
    {
        return Trend::today()->latest()->limit($limit)->get();
    }

    /**
     * 특정 기간의 트렌드 가져오기
     */
    public function getBetweenDates(string $startDate, string $endDate, int $limit = 100): Collection
    {
        return Trend::betweenDates($startDate, $endDate)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * 오래된 트렌드 삭제 (데이터 정리)
     *
     * @param int $days 몇 일 이전 데이터 삭제
     * @return int 삭제된 개수
     */
    public function deleteOlderThan(int $days = 30): int
    {
        return Trend::where('pub_date', '<', now()->subDays($days))->delete();
    }

    /**
     * 모든 트렌드 삭제
     */
    public function deleteAll(): int
    {
        return Trend::query()->delete();
    }

    /**
     * 트렌드 검색
     */
    public function search(string $keyword, int $limit = 20): Collection
    {
        return Trend::where('title', 'like', "%{$keyword}%")
            ->orWhere('description', 'like', "%{$keyword}%")
            ->latest()
            ->limit($limit)
            ->get();
    }
}
