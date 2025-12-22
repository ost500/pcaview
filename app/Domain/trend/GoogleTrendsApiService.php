<?php

namespace App\Domain\trend;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Trends 비공식 API를 사용하여 더 많은 트렌드 데이터 가져오기
 *
 * 주의: 비공식 API이므로 Google 정책에 따라 변경될 수 있음
 */
class GoogleTrendsApiService
{
    private const API_URL = 'https://trends.google.com/trends/api/dailytrends';
    private const TIMEOUT = 30;

    /**
     * 일일 트렌드 데이터를 API로 가져오기
     *
     * @param string $geo 지역 코드 (KR, US, etc.)
     * @param int $hl 언어 (ko, en, etc.)
     * @return array
     */
    public function fetchDailyTrends(string $geo = 'KR', string $hl = 'ko'): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelApp/1.0)',
                    'Accept-Language' => 'ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
                ])
                ->get(self::API_URL, [
                    'hl' => $hl,
                    'geo' => $geo,
                    'ns' => 15, // Number of stories
                ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch Google Trends API', [
                    'status' => $response->status(),
                    'url' => self::API_URL,
                ]);

                return [];
            }

            // 응답은 ")]}'" 로 시작하는 JSON (보안을 위한 prefix)
            $body = $response->body();
            $json = substr($body, 5); // Remove )]}' prefix

            $data = json_decode($json, true);

            if (!$data) {
                Log::error('Failed to parse Google Trends API response');
                return [];
            }

            return $this->parseTrendsData($data);
        } catch (\Exception $e) {
            Log::error('Error fetching Google Trends API', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * API 응답 데이터를 TrendItem 배열로 변환
     */
    private function parseTrendsData(array $data): array
    {
        $items = [];

        if (!isset($data['default']['trendingSearchesDays'])) {
            return [];
        }

        foreach ($data['default']['trendingSearchesDays'] as $day) {
            if (!isset($day['trendingSearches'])) {
                continue;
            }

            foreach ($day['trendingSearches'] as $trend) {
                try {
                    $items[] = $this->parseTrendItem($trend);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse trend item', [
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }

        return $items;
    }

    /**
     * 개별 트렌드 아이템 파싱
     */
    private function parseTrendItem(array $trend): TrendItem
    {
        $title = $trend['title']['query'] ?? '';

        // 트래픽 카운트 (문자열 "100000+" → 숫자)
        $trafficString = $trend['formattedTraffic'] ?? '0';
        $trafficCount = (int) preg_replace('/[^0-9]/', '', $trafficString);

        // 이미지
        $picture = null;
        $pictureSource = null;
        if (isset($trend['image']['imageUrl'])) {
            $picture = $trend['image']['imageUrl'];
            $pictureSource = $trend['image']['source'] ?? null;
        }

        // 관련 뉴스 아이템들
        $newsItems = [];
        if (isset($trend['articles'])) {
            foreach ($trend['articles'] as $article) {
                $newsItems[] = [
                    'title' => $article['title'] ?? '',
                    'snippet' => $article['snippet'] ?? '',
                    'url' => $article['url'] ?? '',
                    'picture' => $article['image']['imageUrl'] ?? null,
                    'source' => $article['source'] ?? '',
                ];
            }
        }

        // 발행 시간
        $pubDate = new \DateTimeImmutable();
        if (isset($trend['formattedDate'])) {
            try {
                $pubDate = new \DateTimeImmutable($trend['formattedDate']);
            } catch (\Exception $e) {
                // 파싱 실패 시 현재 시간 사용
            }
        }

        return new TrendItem(
            title: $title,
            description: '',
            link: 'https://trends.google.com/trending/rss?geo=KR',
            pubDate: $pubDate,
            imageUrl: $picture,
            trafficCount: $trafficCount,
            picture: $picture,
            pictureSource: $pictureSource,
            newsItems: $newsItems,
        );
    }

    /**
     * 실시간 트렌드 가져오기 (Realtime Search Trends)
     */
    public function fetchRealtimeTrends(string $geo = 'KR', string $hl = 'ko', int $count = 50): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelApp/1.0)',
                ])
                ->get('https://trends.google.com/trends/api/realtimetrends', [
                    'hl' => $hl,
                    'geo' => $geo,
                    'category' => 'all',
                    'fi' => 0,
                    'fs' => 0,
                    'ri' => $count,
                    'rs' => $count,
                ]);

            if (!$response->successful()) {
                return [];
            }

            $body = $response->body();
            $json = substr($body, 5); // Remove )]}' prefix
            $data = json_decode($json, true);

            // 실시간 트렌드 파싱 로직
            // (구조가 다를 수 있으므로 별도 처리 필요)

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching realtime trends', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
