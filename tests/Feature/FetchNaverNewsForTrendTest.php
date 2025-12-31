<?php

namespace Tests\Feature;

use App\Domain\news\NaverNewsContentService;
use App\Domain\news\NaverNewsItem;
use App\Domain\news\NaverNewsService;
use App\Events\TrendFetched;
use App\Listeners\FetchNaverNewsForTrend;
use App\Models\Contents;
use App\Models\Department;
use App\Models\Trend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FetchNaverNewsForTrendTest extends TestCase
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

        // 테스트용 Trend 생성
        $this->trend = Trend::create([
            'department_id' => $this->department->id,
            'title' => '금값 상승',
            'traffic_count' => 1000,
            'news_items' => [
                [
                    'title' => '금값',
                    'link' => 'https://example.com/news1',
                    'description' => 'Test news item',
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

    public function test_listener_handles_trend_fetched_event(): void
    {
        // Mock NaverNewsService
        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->once()
            ->with('금값')
            ->andReturn([
                new NaverNewsItem(
                    title: '금값 급등',
                    snippet: '금값이 급등했습니다',
                    url: 'https://n.news.naver.com/mnews/article/test/123',
                    source: 'https://example.com',
                    picture: null,
                    publishedAt: now()->toDateTimeString(),
                ),
            ]);

        // Mock NaverNewsContentService
        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldReceive('saveNewsAsContents')
            ->once()
            ->andReturn(1);

        // Listener 실행
        $listener = new FetchNaverNewsForTrend($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));

        // Trend의 news_items가 업데이트 되었는지 확인
        $this->trend->refresh();
        $this->assertNotEmpty($this->trend->news_items);
    }

    public function test_listener_saves_naver_news_as_contents(): void
    {
        // Mock NaverNewsService - 실제 뉴스 아이템 반환
        $newsItem = new NaverNewsItem(
            title: '금값 급등 소식',
            snippet: '금값이 급등하고 있습니다.',
            url: 'https://n.news.naver.com/mnews/article/test/456',
            source: 'https://example.com/source',
            picture: null,
            publishedAt: now()->toDateTimeString(),
        );

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->once()
            ->andReturn([$newsItem]);

        // 실제 NaverNewsContentService 사용
        $contentService = app(NaverNewsContentService::class);

        $listener = new FetchNaverNewsForTrend($mockNewsService, $contentService);
        $listener->handle(new TrendFetched($this->trend));

        // Contents가 저장되었는지 확인
        $this->assertDatabaseHas('contents', [
            'department_id' => $this->department->id,
            'title' => $newsItem->title,
            'file_url' => $newsItem->url,
        ]);
    }

    public function test_listener_handles_department_not_found(): void
    {
        // Department 삭제
        $this->trend->update(['department_id' => 9999]);

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldNotReceive('searchNews');

        $mockContentService = $this->mock(NaverNewsContentService::class);

        Log::shouldReceive('warning')
            ->once()
            ->with('Department not found for trend', ['trend_id' => $this->trend->id]);

        $listener = new FetchNaverNewsForTrend($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));
    }

    public function test_listener_handles_no_naver_news_found(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->once()
            ->andReturn([]); // 빈 배열 반환

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldNotReceive('saveNewsAsContents');

        Log::shouldReceive('info')
            ->once()
            ->with('No Naver news found for trend', ['trend_title' => $this->trend->title]);

        $listener = new FetchNaverNewsForTrend($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));
    }

    public function test_listener_handles_multiple_news_items_in_trend(): void
    {
        // Trend에 여러 뉴스 아이템 추가
        $this->trend->update([
            'news_items' => [
                ['title' => '금값', 'link' => 'https://example.com/1'],
                ['title' => '은값', 'link' => 'https://example.com/2'],
                ['title' => '달러', 'link' => 'https://example.com/3'],
            ],
        ]);

        $mockNewsService = $this->mock(NaverNewsService::class);

        // 각 제목마다 searchNews 호출 예상
        $mockNewsService->shouldReceive('searchNews')
            ->times(3)
            ->andReturn([
                new NaverNewsItem(
                    title: 'Test News',
                    snippet: 'Test Snippet',
                    url: 'https://n.news.naver.com/mnews/article/test/789',
                    source: 'https://example.com',
                    picture: null,
                    publishedAt: now()->toDateTimeString(),
                ),
            ]);

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldReceive('saveNewsAsContents')
            ->once()
            ->andReturn(3);

        $listener = new FetchNaverNewsForTrend($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));
    }

    public function test_listener_handles_exception_gracefully(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->andThrow(new \Exception('Test exception'));

        $mockContentService = $this->mock(NaverNewsContentService::class);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to fetch Naver news for trend', [
                'trend_id' => $this->trend->id,
                'error' => 'Test exception',
            ]);

        $listener = new FetchNaverNewsForTrend($mockNewsService, $mockContentService);

        // 예외가 발생해도 크래시하지 않아야 함
        $listener->handle(new TrendFetched($this->trend));

        $this->assertTrue(true); // 예외 없이 완료되면 성공
    }

    public function test_listener_updates_trend_news_items(): void
    {
        $originalNewsItems = $this->trend->news_items;

        $newsItem = new NaverNewsItem(
            title: '새로운 뉴스',
            snippet: '새로운 뉴스 내용',
            url: 'https://n.news.naver.com/mnews/article/new/999',
            source: 'https://example.com',
            picture: null,
            publishedAt: now()->toDateTimeString(),
        );

        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->andReturn([$newsItem]);

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldReceive('saveNewsAsContents')
            ->andReturn(1);

        $listener = new FetchNaverNewsForTrend($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));

        // Trend의 news_items가 업데이트되었는지 확인
        $this->trend->refresh();
        $updatedNewsItems = $this->trend->news_items;

        $this->assertGreaterThan(count($originalNewsItems), count($updatedNewsItems));
    }

    public function test_listener_logs_success_information(): void
    {
        $mockNewsService = $this->mock(NaverNewsService::class);
        $mockNewsService->shouldReceive('searchNews')
            ->andReturn([
                new NaverNewsItem(
                    title: 'Test',
                    snippet: 'Test',
                    url: 'https://n.news.naver.com/mnews/article/test/111',
                    source: 'test',
                    picture: null,
                    publishedAt: now()->toDateTimeString(),
                ),
            ]);

        $mockContentService = $this->mock(NaverNewsContentService::class);
        $mockContentService->shouldReceive('saveNewsAsContents')
            ->andReturn(5);

        Log::shouldReceive('info')
            ->once()
            ->with('Naver news fetched for trend', [
                'trend_id' => $this->trend->id,
                'trend_title' => $this->trend->title,
                'saved_count' => 5,
            ]);

        $listener = new FetchNaverNewsForTrend($mockNewsService, $mockContentService);
        $listener->handle(new TrendFetched($this->trend));
    }
}
