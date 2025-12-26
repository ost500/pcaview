<?php

namespace App\Domain\news;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Naver 뉴스 검색 서비스
 */
class NaverNewsService
{
    private const SEARCH_URL = 'https://search.naver.com/search.naver';
    private const TIMEOUT = 30;

    /**
     * 키워드로 뉴스 검색 (상위 1개)
     */
    public function searchNews(string $keyword): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelApp/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'ko-KR,ko;q=0.9',
                    'Accept-Charset' => 'UTF-8',
                ])
                ->get(self::SEARCH_URL, [
                    'where' => 'news',
                    'query' => $keyword,
                ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch Naver news', [
                    'status' => $response->status(),
                    'keyword' => $keyword,
                ]);
                return [];
            }

            $html = $response->body();

            // 인코딩 감지 및 UTF-8 변환
            $encoding = mb_detect_encoding($html, ['UTF-8', 'EUC-KR', 'CP949'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $html = iconv($encoding, 'UTF-8//IGNORE', $html);
                if ($html === false) {
                    $html = mb_convert_encoding($response->body(), 'UTF-8', $encoding);
                }
            }

            return $this->parseNewsItems($html);
        } catch (\Exception $e) {
            Log::error('Error fetching Naver news', [
                'error' => $e->getMessage(),
                'keyword' => $keyword,
            ]);
            return [];
        }
    }

    /**
     * HTML에서 뉴스 아이템 파싱
     */
    private function parseNewsItems(string $html): array
    {
        $items = [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);

        $htmlWithCharset = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html;
        @$dom->loadHTML($htmlWithCharset);

        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Naver 뉴스 검색 결과 리스트 찾기 (2024 신규 구조)
        // API 기반 렌더링 구조: news_area 클래스 찾기
        $newsNodes = $xpath->query('//div[@class="news_area"]');

        // 구버전 구조 시도
        if ($newsNodes->length === 0) {
            $newsNodes = $xpath->query('//ul[@class="list_news"]/li');
        }

        // 또 다른 구조 시도
        if ($newsNodes->length === 0) {
            $newsNodes = $xpath->query('//div[contains(@class, "news_wrap")]');
        }

        // 마지막 fallback
        if ($newsNodes->length === 0) {
            $newsNodes = $xpath->query('//li[contains(@class, "bx")]');
        }

        Log::info('Naver news nodes found', ['count' => $newsNodes->length]);

        $count = 0;
        foreach ($newsNodes as $node) {
            if ($count >= 1) break; // 1개만 가져오기

            try {
                $item = $this->parseNewsNode($xpath, $node);
                if ($item) {
                    $items[] = $item;
                    $count++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse Naver news node', [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $items;
    }

    /**
     * 개별 뉴스 노드 파싱
     */
    private function parseNewsNode(\DOMXPath $xpath, \DOMNode $node): ?array
    {
        // 제목 찾기 (2024 신규 구조: news_tit 클래스)
        $titleNode = null;
        $titleNodes = $xpath->query('.//a[@class="news_tit"]', $node);

        if ($titleNodes->length === 0) {
            $titleNodes = $xpath->query('.//a[contains(@class, "news_tit")]', $node);
        }

        // a 태그의 title 속성 또는 텍스트 사용
        if ($titleNodes->length === 0) {
            $titleNodes = $xpath->query('.//a[contains(@class, "title")]', $node);
        }

        // API 기반 구조: 특정 클래스명 찾기
        if ($titleNodes->length === 0) {
            $titleNodes = $xpath->query('.//a[contains(@class, "api_txt_lines")]', $node);
        }

        if ($titleNodes->length === 0) {
            Log::warning('No title found in Naver news node');
            return null;
        }

        $titleNode = $titleNodes->item(0);

        // title 속성 우선 사용, 없으면 textContent
        $title = $titleNode->getAttribute('title');
        if (empty($title)) {
            $title = $titleNode->textContent;
        }
        $title = $this->cleanUtf8String($title);

        if (empty($title) || strlen($title) < 5) {
            Log::warning('Invalid title in Naver news', ['title' => $title]);
            return null;
        }

        // URL
        $url = $titleNode->getAttribute('href');
        if (!str_starts_with($url, 'http')) {
            $url = 'https://search.naver.com' . $url;
        }

        // 요약 찾기
        $snippetNodes = $xpath->query('.//div[@class="news_dsc"]', $node);
        if ($snippetNodes->length === 0) {
            $snippetNodes = $xpath->query('.//div[contains(@class, "dsc")]', $node);
        }
        if ($snippetNodes->length === 0) {
            $snippetNodes = $xpath->query('.//p', $node);
        }

        $snippet = '';
        if ($snippetNodes->length > 0) {
            $snippet = $snippetNodes->item(0)->textContent;
            $snippet = $this->cleanUtf8String($snippet);
        }

        // 출처 찾기
        $sourceNodes = $xpath->query('.//a[@class="info press"]', $node);
        if ($sourceNodes->length === 0) {
            $sourceNodes = $xpath->query('.//a[contains(@class, "press")]', $node);
        }
        if ($sourceNodes->length === 0) {
            $sourceNodes = $xpath->query('.//span[contains(@class, "press")]', $node);
        }

        $source = '';
        if ($sourceNodes->length > 0) {
            $source = $sourceNodes->item(0)->textContent;
            $source = $this->cleanUtf8String($source);
        }

        // 날짜 (span.info)
        $dateNodes = $xpath->query('.//span[@class="info"]', $node);
        $publishedAt = null;
        if ($dateNodes->length > 0) {
            $dateText = $dateNodes->item(0)->textContent;
            $dateText = $this->cleanUtf8String($dateText);
            $publishedAt = $this->parsePublishedDate($dateText);
        }

        // 이미지 (img.thumb)
        $imageNodes = $xpath->query('.//img[@class="thumb"]', $node);
        $picture = null;
        if ($imageNodes->length > 0) {
            $picture = $imageNodes->item(0)->getAttribute('src');
            if ($picture && !str_starts_with($picture, 'http')) {
                $picture = 'https:' . $picture;
            }
        }

        return [
            'title' => $title,
            'snippet' => $snippet,
            'url' => $url,
            'source' => $source,
            'picture' => $picture,
            'published_at' => $publishedAt,
        ];
    }

    /**
     * UTF-8 문자열 정리
     */
    private function cleanUtf8String(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);
        return $text;
    }

    /**
     * 발행 날짜 문자열 파싱
     */
    private function parsePublishedDate(string $dateString): ?string
    {
        try {
            // "N시간 전" 형식
            if (preg_match('/(\d+)시간 전/', $dateString, $matches)) {
                return \Carbon\Carbon::now()->subHours((int) $matches[1])->toDateTimeString();
            }

            // "N분 전" 형식
            if (preg_match('/(\d+)분 전/', $dateString, $matches)) {
                return \Carbon\Carbon::now()->subMinutes((int) $matches[1])->toDateTimeString();
            }

            // "N일 전" 형식
            if (preg_match('/(\d+)일 전/', $dateString, $matches)) {
                return \Carbon\Carbon::now()->subDays((int) $matches[1])->toDateTimeString();
            }

            // "YYYY.MM.DD" 형식
            if (preg_match('/(\d{4})\.(\d{2})\.(\d{2})/', $dateString, $matches)) {
                $date = \Carbon\Carbon::createFromFormat(
                    'Y-m-d',
                    "{$matches[1]}-{$matches[2]}-{$matches[3]}"
                );
                return $date->toDateTimeString();
            }

            return \Carbon\Carbon::now()->toDateTimeString();
        } catch (\Exception $e) {
            Log::warning('Failed to parse Naver published date', [
                'date_string' => $dateString,
                'error' => $e->getMessage(),
            ]);
            return \Carbon\Carbon::now()->toDateTimeString();
        }
    }
}
