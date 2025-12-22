<?php

namespace App\Domain\news;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nate 뉴스 검색 서비스
 */
class NateNewsService
{
    private const SEARCH_URL = 'https://news.nate.com/search';
    private const TIMEOUT = 30;

    /**
     * 키워드로 뉴스 검색 (상위 5개)
     */
    public function searchNews(string $keyword): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelApp/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'ko-KR,ko;q=0.9',
                ])
                ->get(self::SEARCH_URL, [
                    'q' => $keyword,
                ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch Nate news', [
                    'status' => $response->status(),
                    'keyword' => $keyword,
                ]);
                return [];
            }

            return $this->parseNewsItems($response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching Nate news', [
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

        // DOMDocument로 HTML 파싱
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // Nate 뉴스 검색 결과 리스트 찾기
        $newsNodes = $xpath->query('//ul[@class="search-list"]/li[@class="items"]');

        $count = 0;
        foreach ($newsNodes as $node) {
            if ($count >= 5) break;

            try {
                $item = $this->parseNewsNode($xpath, $node);
                if ($item) {
                    $items[] = $item;
                    $count++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse news node', [
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
        // 제목 (h2.tit)
        $titleNodes = $xpath->query('.//h2[@class="tit"]', $node);
        if ($titleNodes->length === 0) return null;

        $titleNode = $titleNodes->item(0);
        $title = trim($titleNode->textContent);

        // URL (메인 링크)
        $linkNodes = $xpath->query('.//a[@class="thumb-wrap"]', $node);
        $url = '';
        if ($linkNodes->length > 0) {
            $url = $linkNodes->item(0)->getAttribute('href');
            // 절대 URL로 변환
            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            } elseif (!str_starts_with($url, 'http')) {
                $url = 'https://news.nate.com' . $url;
            }
        }

        // 요약 (span.txt)
        $snippetNodes = $xpath->query('.//span[@class="txt"]', $node);
        $snippet = $snippetNodes->length > 0 ? trim($snippetNodes->item(0)->textContent) : '';

        // HTML 태그 제거 및 정리
        $snippet = strip_tags($snippet);
        $snippet = preg_replace('/\s+/', ' ', $snippet);
        $snippet = trim($snippet);

        // 출처 (span.time 내의 첫 번째 텍스트)
        $timeNodes = $xpath->query('.//span[@class="time"]', $node);
        $source = '';
        if ($timeNodes->length > 0) {
            $timeText = $timeNodes->item(0)->textContent;
            // 날짜 부분 제거하고 언론사만 추출
            $parts = explode("\n", trim($timeText));
            $source = trim($parts[0]);
        }

        // 이미지 (thumb 영역의 img)
        $imageNodes = $xpath->query('.//div[@class="thumb"]//img', $node);
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
        ];
    }
}
