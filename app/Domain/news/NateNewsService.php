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
     * 키워드로 뉴스 검색 (상위 2개)
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
                    'q' => $keyword,
                ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch Nate news', [
                    'status' => $response->status(),
                    'keyword' => $keyword,
                ]);
                return [];
            }

            // 응답 본문을 UTF-8로 확실하게 변환
            $html = $response->body();

            // 인코딩 감지 및 UTF-8 변환 (잘못된 문자 무시)
            $encoding = mb_detect_encoding($html, ['UTF-8', 'EUC-KR', 'CP949'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                // iconv로 변환 시 잘못된 문자 무시 (//IGNORE)
                $html = iconv($encoding, 'UTF-8//IGNORE', $html);
                if ($html === false) {
                    // iconv 실패 시 mb_convert_encoding 사용
                    $html = mb_convert_encoding($response->body(), 'UTF-8', $encoding);
                }
            }

            return $this->parseNewsItems($html);
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

        // DOMDocument로 HTML 파싱 (이미 UTF-8로 변환된 상태)
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);

        // HTML 로드 (meta charset으로 인코딩 지정)
        $htmlWithCharset = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html;
        @$dom->loadHTML($htmlWithCharset);

        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Nate 뉴스 검색 결과 리스트 찾기
        $newsNodes = $xpath->query('//ul[@class="search-list"]/li[@class="items"]');

        $count = 0;
        foreach ($newsNodes as $node) {
            if ($count >= 2) break;

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
        $title = $titleNode->textContent;
        $title = $this->cleanUtf8String($title);

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
        $snippet = '';
        if ($snippetNodes->length > 0) {
            $snippet = $snippetNodes->item(0)->textContent;
            $snippet = $this->cleanUtf8String($snippet);
        }

        // 출처 및 발행일시 (span.time 내의 텍스트)
        $timeNodes = $xpath->query('.//span[@class="time"]', $node);
        $source = '';
        $publishedAt = null;
        if ($timeNodes->length > 0) {
            $timeText = $timeNodes->item(0)->textContent;
            $timeText = $this->cleanUtf8String($timeText);
            // 날짜와 언론사 분리 (예: "연합뉴스\n2025.12.23 14:30")
            $parts = array_map('trim', explode("\n", trim($timeText)));
            $source = trim($parts[0]);

            // 날짜 파싱
            if (isset($parts[1])) {
                $publishedAt = $this->parsePublishedDate($parts[1]);
            }
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
            'published_at' => $publishedAt,
        ];
    }

    /**
     * UTF-8 문자열 정리 (JSON 인코딩 가능하도록)
     */
    private function cleanUtf8String(string $text): string
    {
        // 공백 정리
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        // 이미 UTF-8로 변환되어 있으므로 추가 변환 불필요
        return $text;
    }

    /**
     * 발행 날짜 문자열 파싱
     *
     * @param string $dateString 날짜 문자열 (예: "2025.12.23 14:30", "1시간전", "어제 20:15")
     * @return string|null Carbon 날짜 문자열 또는 null
     */
    private function parsePublishedDate(string $dateString): ?string
    {
        try {
            // "YYYY.MM.DD HH:MM" 형식
            if (preg_match('/(\d{4})\.(\d{2})\.(\d{2})\s+(\d{2}):(\d{2})/', $dateString, $matches)) {
                $date = \Carbon\Carbon::createFromFormat(
                    'Y-m-d H:i',
                    "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}"
                );
                return $date->toDateTimeString();
            }

            // "N시간전" 형식
            if (preg_match('/(\d+)시간전/', $dateString, $matches)) {
                return \Carbon\Carbon::now()->subHours((int) $matches[1])->toDateTimeString();
            }

            // "N분전" 형식
            if (preg_match('/(\d+)분전/', $dateString, $matches)) {
                return \Carbon\Carbon::now()->subMinutes((int) $matches[1])->toDateTimeString();
            }

            // "어제 HH:MM" 형식
            if (preg_match('/어제\s+(\d{2}):(\d{2})/', $dateString, $matches)) {
                return \Carbon\Carbon::yesterday()
                    ->setTime((int) $matches[1], (int) $matches[2])
                    ->toDateTimeString();
            }

            // 파싱 실패 시 현재 시각 반환
            return \Carbon\Carbon::now()->toDateTimeString();
        } catch (\Exception $e) {
            Log::warning('Failed to parse published date', [
                'date_string' => $dateString,
                'error' => $e->getMessage(),
            ]);
            return \Carbon\Carbon::now()->toDateTimeString();
        }
    }
}
