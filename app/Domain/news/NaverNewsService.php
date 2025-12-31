<?php

namespace App\Domain\news;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Naver 뉴스 검색 서비스 (API 방식)
 */
class NaverNewsService
{
    private const API_URL = 'https://openapi.naver.com/v1/search/news.json';
    private const TIMEOUT = 30;

    /**
     * 키워드로 뉴스 검색 (상위 1개)
     */
    public function searchNews(string $keyword, int $display = 10, string $sort = 'date'): array
    {
        try {
            $clientId = config('services.naver.client_id');
            $clientSecret = config('services.naver.client_secret');

            if (empty($clientId) || empty($clientSecret)) {
                Log::error('Naver API credentials not configured');
                return [];
            }

            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'X-Naver-Client-Id' => $clientId,
                    'X-Naver-Client-Secret' => $clientSecret,
                ])
                ->get(self::API_URL, [
                    'query' => $keyword,
                    'display' => $display,
                    'start' => 1,
                    'sort' => $sort, // sim (유사도순) 또는 date (날짜순)
                ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch Naver news via API', [
                    'status' => $response->status(),
                    'keyword' => $keyword,
                    'response' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            if (empty($data['items'])) {
                Log::info('No news found', ['keyword' => $keyword]);
                return [];
            }

            return $this->parseApiResponse($data['items']);
        } catch (\Exception $e) {
            Log::error('Error fetching Naver news via API', [
                'error' => $e->getMessage(),
                'keyword' => $keyword,
            ]);
            return [];
        }
    }

    /**
     * API 응답 파싱
     *
     * @return NaverNewsItem[]
     */
    private function parseApiResponse(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            try {
                $link = $item['link'] ?? '';

                // https://n.news.naver.com 으로 시작하는 링크만 필터링
                if (!str_starts_with($link, 'https://n.news.naver.com')) {
                    Log::debug('Skipping non-naver news link', ['link' => $link]);
                    continue;
                }

                $results[] = new NaverNewsItem(
                    title: $this->cleanHtmlTags($item['title'] ?? ''),
                    snippet: $this->cleanHtmlTags($item['description'] ?? ''),
                    url: $link,
                    source: $item['originallink'] ?? $link,
                    picture: null, // API에서는 이미지 정보 제공 안 함
                    publishedAt: $this->parsePublishedDate($item['pubDate'] ?? ''),
                );
            } catch (\Exception $e) {
                Log::warning('Failed to parse Naver news item', [
                    'error' => $e->getMessage(),
                    'item' => $item,
                ]);
                continue;
            }
        }

        return $results;
    }

    /**
     * HTML 태그 제거 및 특수문자 디코딩
     */
    private function cleanHtmlTags(string $text): string
    {
        // HTML 엔티티 디코딩 (&quot; -> ", &lt; -> < 등)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // HTML 태그 제거
        $text = strip_tags($text);

        // 연속된 공백을 하나로
        $text = preg_replace('/\s+/u', ' ', $text);

        // 앞뒤 공백 제거
        $text = trim($text);

        return $text;
    }

    /**
     * 발행 날짜 파싱
     * Naver API는 RFC 822 형식으로 날짜 제공 (예: "Mon, 31 Dec 2024 12:00:00 +0900")
     */
    private function parsePublishedDate(string $dateString): ?string
    {
        try {
            if (empty($dateString)) {
                return \Carbon\Carbon::now()->toDateTimeString();
            }

            // RFC 822 형식 파싱
            $date = \Carbon\Carbon::parse($dateString);
            return $date->toDateTimeString();
        } catch (\Exception $e) {
            Log::warning('Failed to parse Naver API published date', [
                'date_string' => $dateString,
                'error' => $e->getMessage(),
            ]);
            return \Carbon\Carbon::now()->toDateTimeString();
        }
    }
}
