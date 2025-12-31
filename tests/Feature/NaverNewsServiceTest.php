<?php

namespace Tests\Feature;

use App\Domain\news\NaverNewsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NaverNewsServiceTest extends TestCase
{
    private NaverNewsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NaverNewsService();
    }

    public function test_search_news_returns_array_of_news_items(): void
    {
        // 실제 Naver 뉴스 검색 호출
        $keyword = '금값';
        $news = $this->service->searchNews($keyword);

        // 결과 검증
        $this->assertIsArray($news);

        if (count($news) > 0) {
            $firstNews = $news[0];

            // 뉴스 아이템 속성 확인
            $this->assertArrayHasKey('title', $firstNews);
            $this->assertArrayHasKey('url', $firstNews);
            $this->assertArrayHasKey('snippet', $firstNews);
            $this->assertArrayHasKey('source', $firstNews);

            $this->assertNotEmpty($firstNews['title']);
            $this->assertNotEmpty($firstNews['url']);

            // 데이터 구조 출력 (디버깅용)
            dump('Keyword: ' . $keyword);
            dump('Total news fetched: ' . count($news));
            dump('First news:', $firstNews);
        }
    }

    public function test_search_news_returns_only_one_item(): void
    {
        $keyword = '기독교';
        $news = $this->service->searchNews($keyword);

        $this->assertIsArray($news);
        $this->assertLessThanOrEqual(1, count($news));
    }

    public function test_search_news_with_various_keywords(): void
    {
        $keywords = ['성탄절', '부활절', '예배'];

        foreach ($keywords as $keyword) {
            $news = $this->service->searchNews($keyword);

            $this->assertIsArray($news);

            if (count($news) > 0) {
                $this->assertArrayHasKey('title', $news[0]);
                $this->assertArrayHasKey('url', $news[0]);

                dump("Keyword: {$keyword} - Found: " . count($news) . " items");
            }
        }
    }

    public function test_news_item_has_valid_url(): void
    {
        $keyword = '교회';
        $news = $this->service->searchNews($keyword);

        if (count($news) > 0) {
            $url = $news[0]['url'];

            $this->assertNotEmpty($url);
            $this->assertStringStartsWith('http', $url);
        }
    }

    public function test_news_item_has_valid_published_date(): void
    {
        $keyword = '교회';
        $news = $this->service->searchNews($keyword);

        if (count($news) > 0) {
            $this->assertArrayHasKey('published_at', $news[0]);

            if ($news[0]['published_at']) {
                // 날짜 형식 검증
                $this->assertNotEmpty($news[0]['published_at']);
                dump('Published at: ' . $news[0]['published_at']);
            }
        }
    }

    public function test_handles_network_errors_gracefully(): void
    {
        // HTTP 요청 모킹 - 실패 시나리오
        Http::fake([
            'search.naver.com/*' => Http::response('', 500),
        ]);

        $news = $this->service->searchNews('테스트');

        // 에러 시 빈 배열 반환
        $this->assertIsArray($news);
        $this->assertEmpty($news);
    }

    public function test_handles_invalid_html_gracefully(): void
    {
        // HTTP 요청 모킹 - 잘못된 HTML
        Http::fake([
            'search.naver.com/*' => Http::response('<html><body>Invalid HTML</body></html>', 200),
        ]);

        $news = $this->service->searchNews('테스트');

        // 파싱 실패 시 빈 배열 반환
        $this->assertIsArray($news);
    }

    public function test_cleans_utf8_strings_properly(): void
    {
        $keyword = '교회';
        $news = $this->service->searchNews($keyword);

        if (count($news) > 0) {
            $title = $news[0]['title'];

            // 문자열이 정상적으로 정리되었는지 확인
            $this->assertStringNotContainsString('  ', $title); // 연속된 공백 없음
            $this->assertEquals(trim($title), $title); // trim된 상태
        }
    }
}
