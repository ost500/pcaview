<?php

namespace Tests\Feature;

use App\Domain\trend\GoogleTrendsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleTrendsServiceTest extends TestCase
{
    private GoogleTrendsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoogleTrendsService();
    }

    public function test_fetch_trends_returns_array_of_trend_items(): void
    {
        // 실제 Google Trends RSS 호출
        $trends = $this->service->fetchTrends();

        // 결과 검증
        $this->assertIsArray($trends);

        if (count($trends) > 0) {
            $firstTrend = $trends[0];

            // TrendItem 속성 확인
            $this->assertNotEmpty($firstTrend->title);
            $this->assertNotEmpty($firstTrend->link);
            $this->assertInstanceOf(\DateTimeImmutable::class, $firstTrend->pubDate);

            // 데이터 구조 출력 (디버깅용)
            dump('Total trends fetched: ' . count($trends));
            dump('First trend:', $firstTrend->toArray());
        }
    }

    public function test_fetch_top_trends_limits_results(): void
    {
        $limit = 5;
        $trends = $this->service->fetchTopTrends($limit);

        $this->assertIsArray($trends);
        $this->assertLessThanOrEqual($limit, count($trends));
    }

    public function test_trends_to_array_converts_properly(): void
    {
        $trends = $this->service->fetchTrends();

        if (count($trends) > 0) {
            $trendsArray = $this->service->trendsToArray($trends);

            $this->assertIsArray($trendsArray);
            $this->assertArrayHasKey('title', $trendsArray[0]);
            $this->assertArrayHasKey('link', $trendsArray[0]);
            $this->assertArrayHasKey('pub_date', $trendsArray[0]);
        }
    }

    public function test_sort_by_traffic_orders_correctly(): void
    {
        $trends = $this->service->fetchTrends();

        if (count($trends) > 1) {
            $sorted = $this->service->sortByTraffic($trends);

            $this->assertIsArray($sorted);

            // 첫 번째 항목의 트래픽이 두 번째보다 크거나 같은지 확인
            if (isset($sorted[0]) && isset($sorted[1])) {
                $firstTraffic = $sorted[0]->trafficCount ?? 0;
                $secondTraffic = $sorted[1]->trafficCount ?? 0;

                $this->assertGreaterThanOrEqual($secondTraffic, $firstTraffic);
            }
        }
    }

    public function test_handles_network_errors_gracefully(): void
    {
        // HTTP 요청 모킹 - 실패 시나리오
        Http::fake([
            'trends.google.co.kr/*' => Http::response('', 500),
        ]);

        $trends = $this->service->fetchTrends();

        // 에러 시 빈 배열 반환
        $this->assertIsArray($trends);
        $this->assertEmpty($trends);
    }
}
