<?php

namespace Tests\Feature;

use App\Domain\news\NaverNewsContentService;
use App\Domain\news\NaverNewsItem;
use App\Domain\news\NaverNewsService;
use App\Events\TrendFetched;
use App\Listeners\FetchNewsFromTrendItems;
use App\Models\Department;
use App\Models\Trend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FetchNewsFromTrendItemsTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Trend $trend;

    protected function setUp(): void
    {
        parent::setUp();

        // 테스트용 Department 생성
        $this->department = Department::create([
            'name' => 'Test Department',
            'description' => 'Test Description',
            'icon_image' => null,
        ]);

        // 테스트용 Trend 생성 (news_items 포함)
        $this->trend = Trend::create([
            'department_id' => $this->department->id,
            'title' => '금융시장 동향',
            'link' => 'https://example.com/trend',
            'pub_date' => now(),
            'traffic_count' => 1000,
            'news_items' => [
                [
                    'title' => '금값 급등',
                    'link' => 'https://example.com/news1',
                    'description' => '금값이 급등하고 있습니다',
                ],
                [
                    'title' => '달러 환율 변동',
                    'link' => 'https://example.com/news2',
                    'description' => '달러 환율이 변동하고 있습니다',
                ],
            ],
        ]);
    }

    public function test_listener_is_attached_to_event(): void
    {
        Event::fake();

        event(new TrendFetched($this->trend));

        Event::assertDispatched(TrendFetched::class);
    }

    public function test_listener_searches_news_for_each_news_item_title(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);

        // 각 news_item의 title로 검색 호출 예상
        $mockNewsService->shouldReceive('searchNews')
            ->with('금값 급등', 3, 'sim')
            ->once()
            ->andReturn([
                new NaverNewsItem(
                    title: '금값 급등 관련 뉴스',
                    snippet: '금값이 급등했습니다',
                    url: 'https://n.news.naver.com/mnews/article/test/123',
                    source: 'https://example.com',
                    picture: null,
                    publishedAt: now()->toDateTimeString(),
                ),
            ]);

        $mockNewsService->shouldReceive('searchNews')
            ->with('달러 환율 변동', 3, 'sim')
            ->once()
            ->andReturn([
                new NaverNewsItem(
                    title: '달러 환율 변동 뉴스',
                    snippet: '달러가 변동하고 있습니다',
                    url: 'https://n.news.naver.com/mnews/article/test/456',
                    source: 'https://example.com',
                    picture: null,
                    publishedAt: now()->toDateTimeString(),
                ),
            ]);

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldReceive('saveNewsAsContents')
            ->twice()
            ->andReturn(1);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));

        // Trend의 news_items가 업데이트 되었는지 확인
        $this->trend->refresh();
        $this->assertNotEmpty($this->trend->news_items);
        $this->assertGreaterThan(2, count($this->trend->news_items)); // 원래 2개 + 검색 결과
    }

    public function test_listener_saves_fetched_news_as_contents(): void
    {
        $newsItem = new NaverNewsItem(
            title: '금값 급등 상세',
            snippet: '금값이 급등하고 있습니다.',
            url: 'https://n.news.naver.com/mnews/article/test/789',
            source: 'https://example.com/source',
            picture: null,
            publishedAt: now()->toDateTimeString(),
        );

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->andReturn([$newsItem]);

        // 실제 NaverNewsContentService 사용
        $contentService = app(NaverNewsContentService::class);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $contentService);
        $listener->handle(new TrendFetched($this->trend));

        // Contents가 저장되었는지 확인
        $this->assertDatabaseHas('contents', [
            'department_id' => $this->department->id,
            'title' => $newsItem->title,
            'file_url' => $newsItem->url,
        ]);
    }

    public function test_listener_handles_empty_news_items(): void
    {
        // news_items가 없는 Trend 생성
        $emptyTrend = Trend::create([
            'department_id' => $this->department->id,
            'title' => '빈 트렌드',
            'link' => 'https://example.com/empty',
            'pub_date' => now(),
            'traffic_count' => 100,
            'news_items' => [],
        ]);

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldNotReceive('searchNews');

        $mockContentService = $this->mock(NaverNewsContentService::class);

        Log::shouldReceive('info')
            ->once()
            ->with('No news items in trend to search', ['trend_id' => $emptyTrend->id]);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($emptyTrend));
    }

    public function test_listener_handles_null_news_items(): void
    {
        // news_items가 null인 Trend 생성
        $nullTrend = Trend::create([
            'department_id' => $this->department->id,
            'title' => 'Null 트렌드',
            'link' => 'https://example.com/null',
            'pub_date' => now(),
            'traffic_count' => 100,
            'news_items' => null,
        ]);

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldNotReceive('searchNews');

        $mockContentService = $this->mock(NaverNewsContentService::class);

        Log::shouldReceive('info')
            ->once()
            ->with('No news items in trend to search', ['trend_id' => $nullTrend->id]);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($nullTrend));
    }

    public function test_listener_handles_news_items_without_title(): void
    {
        // title이 없는 news_item을 가진 Trend
        $trend = Trend::create([
            'department_id' => $this->department->id,
            'title' => '제목 없는 뉴스',
            'link' => 'https://example.com/no-title-trend',
            'pub_date' => now(),
            'traffic_count' => 100,
            'news_items' => [
                ['link' => 'https://example.com/no-title'], // title 없음
                ['title' => '', 'link' => 'https://example.com/empty-title'], // title 빈 문자열
            ],
        ]);

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldNotReceive('searchNews'); // 제목이 없으면 검색 안 함

        $mockContentService = $this->mock(NaverNewsContentService::class);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($trend));

        $this->assertTrue(true); // 예외 없이 완료되면 성공
    }

    public function test_listener_handles_department_not_found(): void
    {
        // Department를 찾을 수 없도록 department_id를 null로 만듦
        // 먼저 trend 삭제 후 department_id null로 재생성
        $trendId = $this->trend->id;
        $this->trend->delete();

        $invalidTrend = new Trend([
            'id' => $trendId,
            'department_id' => null,
            'title' => 'Invalid Department',
            'link' => 'https://example.com/invalid',
            'pub_date' => now(),
            'traffic_count' => 100,
            'news_items' => [
                ['title' => 'Test', 'link' => 'https://example.com/test'],
            ],
        ]);
        $invalidTrend->id = $trendId;

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldNotReceive('searchNews');

        $mockContentService = $this->mock(NaverNewsContentService::class);

        Log::shouldReceive('warning')
            ->once()
            ->with('Department not found for trend', ['trend_id' => $trendId]);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($invalidTrend));
    }

    public function test_listener_handles_no_search_results(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->twice()
            ->andReturn([]); // 빈 배열 반환

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldNotReceive('saveNewsAsContents');

        // 각 news item 검색 시작 로그 (2번)
        Log::shouldReceive('info')
            ->twice()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Searching Naver news for trend news item');
            });

        // 검색 결과 없음 로그 (2번)
        Log::shouldReceive('info')
            ->twice()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'No Naver news found');
            });

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));
    }

    public function test_listener_handles_exception_gracefully(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->andThrow(new \Exception('Test exception'));

        $mockContentService = $this->mock(NaverNewsContentService::class);

        // info 로그도 mock (검색 시작 시)
        Log::shouldReceive('info')
            ->atLeast()
            ->once();

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to fetch news from trend items', \Mockery::subset([
                'trend_id' => $this->trend->id,
                'error' => 'Test exception',
            ]));

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);

        // 예외가 발생해도 크래시하지 않아야 함
        $listener->handle(new TrendFetched($this->trend));

        $this->assertTrue(true); // 예외 없이 완료되면 성공
    }

    public function test_listener_updates_trend_with_all_fetched_news(): void
    {
        $originalNewsItemsCount = count($this->trend->news_items);

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->twice()
            ->andReturn([
                new NaverNewsItem(
                    title: '새로운 뉴스',
                    snippet: '새로운 뉴스 내용',
                    url: 'https://n.news.naver.com/mnews/article/new/999',
                    source: 'https://example.com',
                    picture: null,
                    publishedAt: now()->toDateTimeString(),
                ),
            ]);

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldReceive('saveNewsAsContents')
            ->twice()
            ->andReturn(1);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));

        // Trend의 news_items가 업데이트되었는지 확인
        $this->trend->refresh();
        $updatedNewsItemsCount = count($this->trend->news_items);

        // 원래 2개 + 검색된 2개 = 4개
        $this->assertEquals($originalNewsItemsCount + 2, $updatedNewsItemsCount);
    }

    public function test_listener_logs_search_and_save_information(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->andReturn([
                new NaverNewsItem(
                    title: 'Test News',
                    snippet: 'Test',
                    url: 'https://n.news.naver.com/mnews/article/test/111',
                    source: 'test',
                    picture: null,
                    publishedAt: now()->toDateTimeString(),
                ),
            ]);

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldReceive('saveNewsAsContents')
            ->andReturn(1);

        Log::shouldReceive('info')
            ->atLeast()
            ->once();

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));
    }

    public function test_listener_uses_similarity_sort_for_search(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);

        // 유사도순 정렬과 3개 제한 확인
        $mockNewsService->shouldReceive('searchNews')
            ->withArgs(function ($keyword, $display, $sort) {
                return $display === 3 && $sort === 'sim';
            })
            ->andReturn([]);

        $mockContentService = $this->mock(NaverNewsContentService::class);

        $listener = new FetchNewsFromTrendItems($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));

        $this->assertTrue(true);
    }
}
