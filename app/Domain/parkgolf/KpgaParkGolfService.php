<?php

namespace App\Domain\parkgolf;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 대한파크골프협회(KPGA) 웹사이트에서 파크골프장 데이터 크롤링
 *
 * 웹사이트: https://kpga21.cafe24.com/info/club.php
 */
class KpgaParkGolfService
{
    private const BASE_URL = 'https://kpga21.cafe24.com/info/club.php';
    private const PAGE_SIZE = 20; // 한 페이지당 골프장 수 (추정)

    /**
     * 모든 파크골프장 데이터 가져오기
     *
     * @return array
     */
    public function fetchAllParkGolfs(): array
    {
        $allData = [];
        $page = 1;
        $hasMore = true;

        Log::info('KPGA: Starting to fetch park golf courses');

        while ($hasMore) {
            try {
                $pageData = $this->fetchPage($page);

                if (empty($pageData['courses'])) {
                    Log::info("KPGA: No more data at page {$page}");
                    break;
                }

                $allData = array_merge($allData, $pageData['courses']);

                Log::info("KPGA: Page {$page} - Found courses", [
                    'count' => count($pageData['courses']),
                    'total' => count($allData),
                ]);

                // 마지막 페이지 체크
                if (!$pageData['hasNext']) {
                    $hasMore = false;
                }

                $page++;

                // 서버 부하 방지
                usleep(500000); // 0.5초 대기

            } catch (\Exception $e) {
                Log::error("KPGA: Failed to fetch page {$page}", [
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        }

        Log::info("KPGA: Total park golf courses fetched", [
            'count' => count($allData),
        ]);

        return $allData;
    }

    /**
     * 특정 페이지의 데이터 가져오기
     *
     * @param int $page
     * @return array{courses: array, hasNext: bool}
     */
    private function fetchPage(int $page): array
    {
        $url = self::BASE_URL . "?bmode=list&page={$page}";

        Log::info("KPGA: Fetching page {$page}", ['url' => $url]);

        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'ko-KR,ko;q=0.9',
            ])
            ->get($url);

        if (!$response->successful()) {
            Log::error('KPGA: HTTP request failed', [
                'status' => $response->status(),
                'page' => $page,
            ]);

            return ['courses' => [], 'hasNext' => false];
        }

        $html = $response->body();

        // 인코딩 확인 및 변환
        if (mb_detect_encoding($html, ['UTF-8', 'EUC-KR'], true) === 'EUC-KR') {
            $html = iconv('EUC-KR', 'UTF-8//IGNORE', $html);
        }

        return $this->parseHtml($html);
    }

    /**
     * HTML 파싱
     *
     * @param string $html
     * @return array{courses: array, hasNext: bool}
     */
    private function parseHtml(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $courses = [];

        // 테이블의 각 행 추출 (헤더 제외)
        // <tr height='30' align=center> 형태의 데이터 행
        $rows = $xpath->query("//table[@class='tablevline']//tr[@height='30']");

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);

            if ($cells->length < 5) {
                continue; // 데이터가 부족한 행 건너뛰기
            }

            $region = trim($cells->item(0)->textContent);
            $name = trim($cells->item(1)->textContent);
            $address = trim($cells->item(2)->textContent);
            $area = trim($cells->item(3)->textContent);
            $holes = trim($cells->item(4)->textContent);

            // 빈 데이터 건너뛰기
            if (empty($name) || $name === '파크골프장명') {
                continue;
            }

            // 상세 페이지 링크에서 ID 추출 (선택사항)
            $linkNode = $xpath->query('.//a', $cells->item(1))->item(0);
            $detailUrl = null;
            if ($linkNode) {
                $href = $linkNode->getAttribute('href');
                if ($href) {
                    $detailUrl = 'https://kpga21.cafe24.com/info/club.php' . $href;
                }
            }

            $courses[] = [
                'name' => $name,
                'region' => $region,
                'address' => $address,
                'area' => $area ?: null,
                'holes' => $this->parseHoles($holes),
                'detail_url' => $detailUrl,
            ];
        }

        // 다음 페이지 존재 여부 확인
        // 페이지네이션에서 현재 페이지보다 큰 번호가 있는지 확인
        $hasNext = false;
        $pageLinks = $xpath->query("//div[@class='page_line']//a");
        foreach ($pageLinks as $link) {
            $linkText = trim($link->textContent);
            if (is_numeric($linkText)) {
                // 현재 페이지 확인 (색상이 다른 것이 현재 페이지)
                $colorAttr = $link->getAttribute('class');
                if ($colorAttr !== 'on' && !str_contains($link->getAttribute('href'), 'page_last')) {
                    $hasNext = true;
                }
            }
        }

        // 또는 "다음" 버튼 확인
        $nextButtons = $xpath->query("//div[@class='page_line']//img[contains(@src, 'btn_page_next')]");
        if ($nextButtons->length > 0) {
            $hasNext = true;
        }

        return [
            'courses' => $courses,
            'hasNext' => $hasNext,
        ];
    }

    /**
     * Hole 수 파싱
     *
     * @param string $holesText
     * @return int|null
     */
    private function parseHoles(string $holesText): ?int
    {
        if (empty($holesText)) {
            return null;
        }

        // 숫자만 추출
        if (preg_match('/(\d+)/', $holesText, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * 특정 지역의 파크골프장 검색
     *
     * @param string $region 지역명 (서울, 부산, 경기 등)
     * @return array
     */
    public function fetchByRegion(string $region): array
    {
        // 지역 코드 매핑
        $regionCodes = [
            '서울' => '1',
            '부산' => '2',
            '대구' => '3',
            '인천' => '4',
            '광주' => '5',
            '대전' => '6',
            '울산' => '7',
            '세종' => '8',
            '강원' => '9',
            '경기' => '10',
            '경남' => '11',
            '경북' => '12',
            '전남' => '13',
            '전북' => '14',
            '제주' => '15',
            '충남' => '16',
            '충북' => '17',
        ];

        $regionCode = $regionCodes[$region] ?? null;

        if (!$regionCode) {
            Log::warning("KPGA: Unknown region: {$region}");
            return [];
        }

        $url = self::BASE_URL . "?bmode=list&sh_sido={$regionCode}";

        Log::info("KPGA: Fetching region: {$region}", ['url' => $url]);

        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            return [];
        }

        $html = $response->body();

        // 인코딩 변환
        if (mb_detect_encoding($html, ['UTF-8', 'EUC-KR'], true) === 'EUC-KR') {
            $html = iconv('EUC-KR', 'UTF-8//IGNORE', $html);
        }

        $result = $this->parseHtml($html);

        return $result['courses'];
    }

    /**
     * 이름으로 검색
     *
     * @param string $searchName
     * @return array
     */
    public function searchByName(string $searchName): array
    {
        $allCourses = $this->fetchAllParkGolfs();

        return array_filter($allCourses, function ($course) use ($searchName) {
            return stripos($course['name'], $searchName) !== false;
        });
    }

    /**
     * 상세 정보 가져오기 (선택사항)
     *
     * @param string $detailUrl
     * @return array|null
     */
    public function fetchDetail(string $detailUrl): ?array
    {
        try {
            $response = Http::timeout(30)->get($detailUrl);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            // 인코딩 변환
            if (mb_detect_encoding($html, ['UTF-8', 'EUC-KR'], true) === 'EUC-KR') {
                $html = iconv('EUC-KR', 'UTF-8//IGNORE', $html);
            }

            // 상세 페이지 파싱 로직 (필요시 구현)
            // 예: 연락처, 운영시간, 이용요금 등

            return [];

        } catch (\Exception $e) {
            Log::error('KPGA: Failed to fetch detail', [
                'error' => $e->getMessage(),
                'url' => $detailUrl,
            ]);

            return null;
        }
    }
}
