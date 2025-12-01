<?php

namespace App\Domain\trend;

use App\Models\Trend;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrendRepository
{
    /**
     * TrendItem을 데이터베이스에 저장 (중복 방지)
     */
    public function save(TrendItem $item): Trend
    {
        return Trend::updateOrCreate(
            [
                'title' => $item->title,
                'pub_date' => $item->pubDate,
            ],
            [
                'description' => $item->description,
                'link' => $item->link,
                'image_url' => $item->imageUrl,
                'traffic_count' => $item->trafficCount,
                'picture' => $item->picture,
                'picture_source' => $item->pictureSource,
                'news_items' => $item->newsItems,
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
