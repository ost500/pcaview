<?php

namespace App\Domain\news;

use App\Enums\ContentsType;
use App\Models\Contents;
use App\Models\Department;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Naver 뉴스를 Contents로 변환하여 저장하는 서비스
 */
class NaverNewsContentService
{
    /**
     * 현재 처리 중인 뉴스 URL (원문 링크용)
     */
    private ?string $currentNewsUrl = null;

    /**
     * Naver 뉴스 배열을 Contents로 변환하여 저장
     *
     * @param NaverNewsItem[] $newsItems Naver 뉴스 아이템 배열
     * @param Department $department 연결할 Department
     * @return int 저장된 개수
     */
    public function saveNewsAsContents(array $newsItems, Department $department): int
    {
        $savedCount = 0;

        foreach ($newsItems as $newsItem) {
            try {
                // NaverNewsItem 타입 확인
                if (!$newsItem instanceof NaverNewsItem) {
                    Log::warning('Invalid news item type', ['type' => get_class($newsItem)]);
                    continue;
                }

                // 필수 필드 확인
                if (empty($newsItem->title) || empty($newsItem->url)) {
                    continue;
                }

                // URL 기반으로 중복 체크
                $existingContent = Contents::where('file_url', $newsItem->url)
                    ->where('department_id', $department->id)
                    ->first();

                if ($existingContent) {
                    continue; // 이미 존재하면 스킵
                }

                // 뉴스 URL에서 본문 내용 및 제목 크롤링
                $this->currentNewsUrl = $newsItem->url;
                $newsData = $this->fetchNewsBody($newsItem->url);

                // 제목, 본문, 발행일시 추출 (크롤링한 데이터가 있으면 사용, 없으면 원본 사용)
                $title = $newsData['title'] ?? $newsItem->title;
                $body = $newsData['body'] ?? $newsItem->snippet ?? null;
                $publishedAt = $newsData['published_at'] ?? $newsItem->publishedAt ?? now();

                // 저작권 문제로 뉴스 이미지는 저장하지 않음
                $thumbnailUrl = null;

                // Contents 생성 (이미 NaverNewsService에서 UTF-8 변환됨)
                $contents = Contents::create([
                    'church_id' => $department->church_id,
                    'department_id' => $department->id, // 대표 department 설정
                    'type' => ContentsType::NAVER_NEWS, // Naver 뉴스 타입
                    'title' => $title,
                    'body' => $body,
                    'file_url' => $newsItem->url,
                    'thumbnail_url' => $thumbnailUrl,
                    'published_at' => $publishedAt, // 크롤링한 발행일시 우선 사용
                ]);

                // Attach to department via pivot table
                $contents->departments()->attach($department->id);

                // 네이버/네이트 뉴스는 이미지를 ContentsImage에 저장하지 않음
                // 본문 HTML에 이미 이미지가 포함되어 있으므로 별도 저장 불필요

                $savedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to save Nate news as Contents', [
                    'error' => $e->getMessage(),
                    'news_item' => $newsItem,
                ]);
            }
        }

        return $savedCount;
    }

    /**
     * 뉴스 URL에서 제목, 본문, 발행일시, 이미지 크롤링
     *
     * @param string $url 뉴스 URL
     * @return array{title: string|null, body: string|null, published_at: string|null, images: array} 제목, 본문, 발행일시, 이미지 배열
     */
    private function fetchNewsBody(string $url): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelApp/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'ko-KR,ko;q=0.9',
                    'Accept-Charset' => 'UTF-8',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Failed to fetch news body', ['url' => $url]);
                return ['title' => null, 'body' => null, 'published_at' => null, 'images' => []];
            }

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

            return $this->extractTitleAndBodyFromHtml($html);

        } catch (\Exception $e) {
            Log::error('Error fetching news body', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return ['title' => null, 'body' => null, 'published_at' => null, 'images' => []];
        }
    }

    /**
     * HTML에서 제목, 본문, 발행일시, 이미지 추출
     *
     * @param string $html HTML 내용 (UTF-8 인코딩)
     * @return array{title: string|null, body: string|null, published_at: string|null, images: array} 제목, 본문, 발행일시, 이미지 배열
     */
    private function extractTitleAndBodyFromHtml(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // HTML 로드 (meta charset으로 인코딩 지정)
        $htmlWithCharset = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html;
        @$dom->loadHTML($htmlWithCharset);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 제목 추출
        $title = null;
        $titleSelectors = [
            "//h1[@id='articleTitle']",        // 네이트 뉴스 제목
            "//h1[@class='articleTitle']",
            "//h2[@id='articleTitle']",
            "//h2[@class='articleTitle']",
            "//meta[@property='og:title']/@content",  // OG 태그
            "//meta[@name='title']/@content",
            "//h1[contains(@class, 'article-title')]",
            "//h1[contains(@class, 'news-title')]",
            "//h1",                            // 일반 h1
            "//title",                         // 페이지 타이틀
        ];

        foreach ($titleSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $titleNode = $nodes->item(0);
                if ($titleNode) {
                    $title = trim($titleNode->nodeValue ?? $titleNode->textContent);
                    if (!empty($title)) {
                        // 사이트명 및 카테고리 제거
                        // "뉴스 제목 - 네이트뉴스" -> "뉴스 제목"
                        // ":네이트 연예" -> ""
                        // "네이트 뉴스 - 제목" -> "제목"
                        $title = preg_replace('/^[:\s]*(네이트|NATE)\s*(뉴스|연예|스포츠|경제|사회|정치|IT|News)[:\s]*/ui', '', $title);
                        $title = preg_replace('/\s*[-|:]\s*(네이트|NATE)\s*(뉴스|연예|스포츠|경제|사회|정치|IT|News).*$/ui', '', $title);
                        $title = trim($title);
                        break;
                    }
                }
            }
        }

        // 본문 추출
        $bodyHtml = null;
        $bodySelectors = [
            // 네이트 뉴스 전용
            "//div[@id='articleContents']",   // 네이트 뉴스 기사 내용 (최우선)
            "//div[@id='realArtcContents']",  // 네이트 뉴스 실제 기사 내용
            "//div[@id='articleBody']",
            "//div[@id='newsBody']",
            "//div[@class='articleBody']",
            // 일반적인 뉴스 사이트
            "//article",
            "//div[contains(@class, 'article-body')]",
            "//div[contains(@class, 'article-content')]",
            "//div[contains(@class, 'news-body')]",
            "//div[contains(@class, 'content-body')]",
            "//div[contains(@class, 'post-content')]",
            "//div[contains(@class, 'entry-content')]",
            "//div[@id='article-body']",
            "//div[@id='article-content']",
            "//div[@id='news-content']",
            "//main",
        ];

        foreach ($bodySelectors as $selector) {
            $nodes = $xpath->query($selector);

            if ($nodes && $nodes->length > 0) {
                $node = $nodes->item(0);

                // saveHTML()로 변경 - 이미지 포함한 모든 HTML 요소 보존
                $bodyHtml = $dom->saveHTML($node);

                // 광고, 스크립트 등 불필요한 요소 제거
                $bodyHtml = $this->cleanHtml($bodyHtml);

                // 이미지 URL 수정
                $bodyHtml = $this->fixImageUrls($bodyHtml);

                // 전체 본문 저장 (화면 표시 시 절반으로 자름)
                break;
            }
        }

        // 발행일시 추출
        $publishedAt = null;
        $dateSelectors = [
            "//*[@id='articleView']/p/span[2]/em",                  // 네이트 뉴스 articleView 발행일 (최우선)
            "//meta[@property='article:published_time']/@content",  // OG article 발행일
            "//meta[@name='article:published_time']/@content",
            "//time[@class='date']/@datetime",                      // 네이트 뉴스 날짜
            "//time[@itemprop='datePublished']/@datetime",          // Schema.org
            "//span[@class='date']",                                // 네이트 뉴스 날짜 텍스트
            "//span[contains(@class, 'article-date')]",
            "//span[contains(@class, 'news-date')]",
            "//div[@class='info']//span[contains(text(), '20')]",  // 날짜 포함 텍스트
        ];

        foreach ($dateSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $dateNode = $nodes->item(0);
                if ($dateNode) {
                    $dateText = trim($dateNode->nodeValue ?? $dateNode->textContent);
                    if (!empty($dateText)) {
                        // 날짜 파싱 시도
                        $parsedDate = $this->parsePublishedDate($dateText);
                        if ($parsedDate) {
                            $publishedAt = $parsedDate;
                            break;
                        }
                    }
                }
            }
        }

        // 본문에서 이미지 URL 추출
        $images = [];
        if ($bodyHtml) {
            $imgPattern = '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i';
            if (preg_match_all($imgPattern, $bodyHtml, $matches)) {
                $images = array_slice($matches[1], 0, 10); // 최대 10개 이미지
            }
        }

        return [
            'title' => $title,
            'body' => $bodyHtml,
            'published_at' => $publishedAt,
            'images' => $images,
        ];
    }

    /**
     * HTML 정리 (광고, 스크립트, 이미지 등 제거)
     *
     * @param string $html 원본 HTML (UTF-8 인코딩)
     * @return string 정리된 HTML
     */
    private function cleanHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // HTML 로드 (meta charset으로 인코딩 지정)
        $htmlWithCharset = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html;
        @$dom->loadHTML($htmlWithCharset);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 제거할 요소들 (네이트 뉴스 광고 패턴 + 이미지 포함)
        $removeSelectors = [
            // 저작권 보호를 위한 이미지 제거
            "//img",                             // 모든 이미지 태그
            "//picture",                         // picture 태그
            "//figure",                          // figure 태그 (이미지 포함)
            // 네이트 뉴스 광고
            "//*[@id='ad_innerView']",           // 네이트 광고 div
            "//*[@id='adDiv']",                  // 네이트 광고 div
            "//*[@id='topBannerWrap']",          // 상단 배너
            "//*[@id='inContentAd']",            // 본문 내 광고
            "//*[contains(@id, 'ad_') and not(contains(@class, 'imgad_area'))]", // ad_로 시작하는 ID (imgad_area 제외)
            "//*[contains(@class, 'ad_') and not(contains(@class, 'imgad_area'))]", // ad_를 포함하는 class (imgad_area 제외)
            "//*[contains(@class, 'ad-')]",      // ad-를 포함하는 class
            "//*[contains(@class, 'adArea')]",   // 광고 영역
            "//*[contains(@class, 'advertisement')]", // 광고
            "//*[contains(@class, 'banner')]",   // 배너
            // 일반 광고 요소
            "//iframe",                          // iframe (광고 또는 외부 컨텐츠)
            "//script",                          // JavaScript
            "//style",                           // CSS
            "//ins",                             // 구글 애드센스
            "//*[contains(@class, 'adsbygoogle')]", // 구글 애드센스
            "//*[@data-ad-slot]",                // 광고 슬롯
            "//noscript",                        // noscript 태그
            // 기타 불필요한 요소
            "//*[@id='reactionDiv']",            // 리액션
            "//*[contains(@class, 'relation')]", // 관련 기사
            "//*[contains(@class, 'recommend')]", // 추천 기사
        ];

        // 각 요소 제거
        foreach ($removeSelectors as $selector) {
            $nodes = $xpath->query($selector);
            foreach ($nodes as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        // saveHTML()로 변경 - 이미지 및 모든 HTML 요소 보존
        $html = '';
        if ($dom->documentElement) {
            // body 태그 내용만 추출
            $body = $xpath->query('//body')->item(0);
            if ($body) {
                $html = $dom->saveHTML($body);
                // body 태그 자체 제거
                $html = preg_replace('/<\/?body[^>]*>/', '', $html);
            } else {
                $html = $dom->saveHTML($dom->documentElement);
            }
        }

        // 구글 광고 주석 제거
        $html = preg_replace('/<!--.*?google_ad.*?-->/s', '', $html);

        // HTML 주석 제거
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // 빈 줄 정리
        $html = preg_replace('/\n\s*\n/', "\n", $html);

        return trim($html);
    }

    /**
     * 본문을 절반으로 자르기 (저작권 보호)
     *
     * @param string $html 원본 HTML
     * @return string 절반으로 잘린 HTML
     */
    private function truncateToHalf(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 모든 텍스트 노드와 이미지 가져오기
        $allNodes = $xpath->query('.//text()[normalize-space()] | .//img');

        if (!$allNodes || $allNodes->length === 0) {
            return $html;
        }

        // 절반 지점 계산
        $halfPoint = (int) ceil($allNodes->length / 2);

        // 절반 이후의 노드들 제거
        $nodesToRemove = [];
        for ($i = $halfPoint; $i < $allNodes->length; $i++) {
            $node = $allNodes->item($i);
            if ($node) {
                $nodesToRemove[] = $node;
            }
        }

        foreach ($nodesToRemove as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        $truncatedHtml = $dom->saveHTML();

        // "계속 읽기" 링크 추가
        $truncatedHtml .= '<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">';
        $truncatedHtml .= '<p style="margin: 0; color: #6c757d; font-size: 14px;">저작권 보호를 위해 본문의 일부만 표시됩니다.</p>';
        $truncatedHtml .= '<a href="' . htmlspecialchars($this->currentNewsUrl ?? '') . '" target="_blank" rel="noopener noreferrer" style="display: inline-block; margin-top: 10px; color: #007bff; text-decoration: none; font-weight: 500;">';
        $truncatedHtml .= '원문 보기 →</a>';
        $truncatedHtml .= '</div>';

        return $truncatedHtml;
    }

    /**
     * 이미지 URL에 https 프로토콜 추가 (네이트 뉴스 이미지 패턴 처리)
     *
     * @param string $html 원본 HTML
     * @return string 수정된 HTML
     */
    private function fixImageUrls(string $html): string
    {
        // thumbnews URL의 경우 /// 다음의 실제 이미지 URL 추출
        $html = preg_replace_callback(
            '/src=["\']\/\/thumbnews\.nateimg\.co\.kr\/[^\/]+\/\/\/([^"\']+)["\']/i',
            function ($matches) {
                return 'src="https://' . $matches[1] . '"';
            },
            $html
        );

        // 일반적인 // 로 시작하는 URL은 https:// 로 변경
        $html = preg_replace(
            '/src=["\']\/\/([^"\']+)["\']/i',
            'src="https://$1"',
            $html
        );

        // 추가로 다른 이미지 관련 속성들도 처리 (lazy loading 등)
        $html = preg_replace(
            '/(data-src|data-original|data-lazy)=["\']\/\/([^"\']+)["\']/i',
            '$1="https://$2"',
            $html
        );

        return $html;
    }

    /**
     * 외부 이미지 URL을 다운로드하여 S3에 업로드
     *
     * @param string $imageUrl 원본 이미지 URL
     * @param int $departmentId Department ID
     * @param bool $isThumbnail 썸네일 여부 (기본값: false)
     * @return string|null S3 URL 또는 null (실패 시)
     */
    private function uploadImageToS3(string $imageUrl, int $departmentId, bool $isThumbnail = false): ?string
    {
        try {
            // 외부 이미지 다운로드
            $response = Http::timeout(10)->get($imageUrl);

            if (!$response->successful()) {
                Log::warning('Failed to download image', ['url' => $imageUrl]);
                return null;
            }

            // 파일 확장자 추출 (기본값: jpg)
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'jpg';
            }

            // S3 저장 경로 생성 (썸네일과 일반 이미지 구분)
            $fileName = Str::uuid() . '.' . $extension;
            $folder = $isThumbnail ? 'thumbnails' : 'images';
            $s3Path = "news/{$folder}/{$departmentId}/{$fileName}";

            // S3에 업로드
            Storage::put($s3Path, $response->body());

            // S3 URL 반환
            return Storage::url($s3Path);
        } catch (\Exception $e) {
            Log::error('Failed to upload image to S3', [
                'error' => $e->getMessage(),
                'image_url' => $imageUrl,
            ]);
            return null;
        }
    }

    /**
     * 발행 날짜 문자열 파싱
     *
     * @param string $dateString 날짜 문자열 (예: "2025.12.23 14:30", "1시간전", "어제 20:15", ISO 8601)
     * @return string|null Carbon 날짜 문자열 또는 null
     */
    private function parsePublishedDate(string $dateString): ?string
    {
        try {
            // ISO 8601 형식 (예: 2025-12-23T14:30:00Z, 2025-12-23T14:30:00+09:00)
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $dateString)) {
                $date = \Carbon\Carbon::parse($dateString);
                return $date->toDateTimeString();
            }

            // "YYYY.MM.DD HH:MM" 형식
            if (preg_match('/(\d{4})\.(\d{2})\.(\d{2})\s+(\d{2}):(\d{2})/', $dateString, $matches)) {
                $date = \Carbon\Carbon::createFromFormat(
                    'Y-m-d H:i',
                    "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}"
                );
                return $date->toDateTimeString();
            }

            // "YYYY-MM-DD HH:MM" 형식 (네이트 뉴스 articleView)
            if (preg_match('/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/', $dateString, $matches)) {
                $date = \Carbon\Carbon::createFromFormat(
                    'Y-m-d H:i',
                    "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}"
                );
                return $date->toDateTimeString();
            }

            // "YYYY-MM-DD HH:MM:SS" 형식
            if (preg_match('/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/', $dateString, $matches)) {
                $date = \Carbon\Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}"
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

            // 파싱 실패 시 null 반환 (현재 시각 사용하지 않음)
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to parse published date', [
                'date_string' => $dateString,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
