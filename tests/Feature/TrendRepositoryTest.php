<?php

namespace Tests\Feature;

use App\Domain\trend\GoogleTrendsService;
use App\Domain\trend\TrendRepository;
use App\Models\Trend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TrendRepository $repository;
    private GoogleTrendsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TrendRepository();
        $this->service = new GoogleTrendsService($this->repository);
    }

    public function test_fetch_and_save_stores_trends_in_database(): void
    {
        // 트렌드 데이터 가져와서 저장
        $count = $this->service->fetchAndSave();

        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseCount('trends', $count);

        // 첫 번째 트렌드 검증
        $firstTrend = Trend::first();
        $this->assertNotNull($firstTrend);
        $this->assertNotEmpty($firstTrend->title);
        $this->assertNotEmpty($firstTrend->link);
        $this->assertInstanceOf(\DateTime::class, $firstTrend->pub_date);

        dump("Saved {$count} trends to database");
        dump('First trend:', $firstTrend->toArray());
    }

    public function test_duplicate_trends_are_updated_not_inserted(): void
    {
        // 첫 번째 저장
        $count1 = $this->service->fetchAndSave();
        $this->assertGreaterThan(0, $count1);

        // 같은 데이터 다시 저장 (중복)
        $count2 = $this->service->fetchAndSave();

        // 개수는 같아야 함 (중복 insert 안 됨)
        $this->assertEquals($count1, Trend::count());
    }

    public function test_get_latest_from_database(): void
    {
        // 데이터 저장
        $this->service->fetchAndSave();

        // 최신 5개 가져오기
        $trends = $this->service->getLatestFromDatabase(5);

        $this->assertCount(5, $trends);
        $this->assertInstanceOf(Trend::class, $trends->first());

        // 날짜 순으로 정렬되었는지 확인 (최신순 = 큰 timestamp가 먼저)
        for ($i = 0; $i < $trends->count() - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $trends[$i]->pub_date->timestamp,
                $trends[$i + 1]->pub_date->timestamp
            );
        }
    }

    public function test_get_today_from_database(): void
    {
        // 오늘 날짜로 트렌드 생성
        Trend::factory()->count(3)->create([
            'pub_date' => now(),
        ]);

        // 어제 날짜로 트렌드 생성
        Trend::factory()->count(2)->create([
            'pub_date' => now()->subDay(),
        ]);

        // 오늘의 트렌드만 가져오기
        $todayTrends = $this->service->getTodayFromDatabase();

        $this->assertEquals(3, $todayTrends->count());

        // 모두 오늘 날짜인지 확인
        foreach ($todayTrends as $trend) {
            $this->assertTrue($trend->pub_date->isToday());
        }
    }

    public function test_search_finds_trends_by_keyword(): void
    {
        // 데이터 저장
        $this->service->fetchAndSave();

        $firstTrend = Trend::first();
        if (!$firstTrend) {
            $this->markTestSkipped('No trends available for search test');
        }

        // 첫 번째 트렌드의 제목 일부로 검색
        $keyword = mb_substr($firstTrend->title, 0, 2);
        $results = $this->service->search($keyword);

        $this->assertGreaterThan(0, $results->count());
        $this->assertTrue(
            $results->contains(fn($t) => str_contains($t->title, $keyword))
        );
    }

    public function test_delete_older_than_removes_old_trends(): void
    {
        // 데이터 저장
        $this->service->fetchAndSave();
        $initialCount = Trend::count();

        // 오래된 트렌드 생성 (40일 전)
        Trend::factory()->create([
            'pub_date' => now()->subDays(40),
        ]);

        $this->assertEquals($initialCount + 1, Trend::count());

        // 30일 이전 데이터 삭제
        $deleted = $this->repository->deleteOlderThan(30);

        $this->assertGreaterThanOrEqual(1, $deleted);
        $this->assertEquals($initialCount, Trend::count());
    }

    public function test_scope_by_traffic_orders_correctly(): void
    {
        // 데이터 저장
        $this->service->fetchAndSave();

        $trends = Trend::byTraffic()->limit(5)->get();

        $this->assertGreaterThan(0, $trends->count());

        // 트래픽 순으로 정렬되었는지 확인
        $traffic = $trends->pluck('traffic_count')->toArray();
        $sortedTraffic = collect($traffic)->sortDesc()->values()->toArray();
        $this->assertEquals($sortedTraffic, $traffic);
    }
}
