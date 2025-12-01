<?php

namespace App\Domain\trend;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTrendsService
{
    private const RSS_URL = 'https://trends.google.co.kr/trending/rss?geo=KR';
    private const TIMEOUT = 30;

    public function __construct(
        private ?TrendRepository $repository = null
    ) {
        $this->repository = $repository ?? new TrendRepository();
    }

    /**
     * Google Trends RSS 데이터를 가져와서 파싱
     *
     * @return array<TrendItem>
     */
    public function fetchTrends(): array
    {
        try {
            // RSS 데이터 가져오기
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelApp/1.0)',
                ])
                ->get(self::RSS_URL);

            if (!$response->successful()) {
                Log::error('Failed to fetch Google Trends RSS', [
                    'status' => $response->status(),
                    'url' => self::RSS_URL,
                ]);

                return [];
            }

            // XML 파싱
            return $this->parseRss($response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching Google Trends', [
                'error' => $e->getMessage(),
                'url' => self::RSS_URL,
            ]);

            return [];
        }
    }

    /**
     * RSS XML 문자열을 파싱하여 TrendItem 배열로 반환
     *
     * @return array<TrendItem>
     */
    private function parseRss(string $xmlContent): array
    {
        try {
            // libxml 에러 처리 활성화
            libxml_use_internal_errors(true);

            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors = libxml_get_errors();
                Log::error('Failed to parse Google Trends RSS XML', [
                    'errors' => $errors,
                ]);
                libxml_clear_errors();

                return [];
            }

            // item 요소들 추출
            $items = [];
            foreach ($xml->channel->item as $item) {
                try {
                    $items[] = TrendItem::fromRssItem($item);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse trend item', [
                        'error' => $e->getMessage(),
                        'item' => $item->asXML(),
                    ]);
                    // 개별 아이템 파싱 실패해도 계속 진행
                    continue;
                }
            }

            return $items;
        } catch (\Exception $e) {
            Log::error('Error parsing Google Trends RSS', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 트렌드 데이터를 배열로 변환
     *
     * @param array<TrendItem> $trends
     * @return array<array>
     */
    public function trendsToArray(array $trends): array
    {
        return array_map(fn (TrendItem $item) => $item->toArray(), $trends);
    }

    /**
     * 상위 N개의 트렌드만 반환
     *
     * @param int $limit
     * @return array<TrendItem>
     */
    public function fetchTopTrends(int $limit = 10): array
    {
        $trends = $this->fetchTrends();

        return array_slice($trends, 0, $limit);
    }

    /**
     * 트렌드 데이터를 트래픽 순으로 정렬
     *
     * @param array<TrendItem> $trends
     * @return array<TrendItem>
     */
    public function sortByTraffic(array $trends): array
    {
        usort($trends, function (TrendItem $a, TrendItem $b) {
            $aTraffic = $a->trafficCount ?? 0;
            $bTraffic = $b->trafficCount ?? 0;

            return $bTraffic <=> $aTraffic;
        });

        return $trends;
    }

    /**
     * 트렌드 데이터를 가져와서 데이터베이스에 저장
     *
     * @return int 저장된 개수
     */
    public function fetchAndSave(): int
    {
        $trends = $this->fetchTrends();

        if (empty($trends)) {
            return 0;
        }

        return $this->repository->saveMany($trends);
    }

    /**
     * 데이터베이스에서 최신 트렌드 가져오기
     */
    public function getLatestFromDatabase(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->repository->getLatest($limit);
    }

    /**
     * 데이터베이스에서 오늘의 트렌드 가져오기
     */
    public function getTodayFromDatabase(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->repository->getToday($limit);
    }

    /**
     * 트렌드 검색
     */
    public function search(string $keyword, int $limit = 20): \Illuminate\Support\Collection
    {
        return $this->repository->search($keyword, $limit);
    }
}
