<?php

namespace App\Domain\department\NFriends;

use App\Domain\church\msch\MSCHContentsType;
use App\Domain\department\DepartmentInterface;
use App\Domain\ogimage\Events\ContentsNewEvent;
use App\Models\Contents;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NFriendsCrawlService
{

    public function crawl(DepartmentInterface $department)
    {
        $baseUrl = $department->contentsUrl();
        Log::info("NFriends: Starting crawl from {$baseUrl}");
        $response = Http::get($baseUrl);

        if ($response->successful()) {
            DB::transaction(function () use ($department, $response) {
                $html = $response->body();
                $departmentModel = $department->getModel();

                // HTML 파싱
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                // Use HTML5 doctype approach for better encoding handling
                $wrappedHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' .
                              $html .
                              '</body></html>';
                @$dom->loadHTML($wrappedHtml);
                libxml_clear_errors();

                $xpath = new DOMXPath($dom);

                // 게시글 목록 찾기 - 각 ul.li_body가 하나의 게시글
                $listItems = $xpath->query('//ul[contains(@class, "li_body")]');
                Log::info("NFriends: Found {$listItems->length} list items");

                foreach ($listItems as $listItem) {
                    try {
                        // 제목 링크 추출
                        $titleLinks = $xpath->query('.//a[contains(@class, "list_text_title")]', $listItem);
                        if ($titleLinks->length === 0) continue;

                        $titleLink = $titleLinks->item(0);
                        $title = trim($titleLink->textContent);
                        $href = $titleLink->getAttribute('href');
                        $detailUrl = 'https://nfriends.or.kr' . $href;

                        // 이미 존재하는지 확인
                        $alreadyContents = Contents::where('department_id', $departmentModel->id)
                            ->where('file_url', $detailUrl)->first();

                        if ($alreadyContents) {
                            Log::info("NFriends: Skipping existing content - {$title}");
                            continue;
                        }

                        Log::info("NFriends: Processing new content - {$title}");

                        // 날짜 추출 - li 태그 중에서 날짜 형식이 있는 것 찾기
                        $allLis = $xpath->query('.//li', $listItem);
                        $publishedAt = now();

                        foreach ($allLis as $li) {
                            $text = trim($li->textContent);
                            // 날짜 형식 확인 (YYYY-MM-DD 또는 YY.MM.DD)
                            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $text)) {
                                try {
                                    // 시간 정보 제거하고 날짜만 추출 (YYYY-MM-DD format)
                                    $dateText = preg_split('/\s+/', $text)[0];
                                    $publishedAt = Carbon::parse($dateText);
                                    break;
                                } catch (\Exception $e) {
                                    Log::warning("NFriends: Failed to parse date: {$text}");
                                }
                            } elseif (preg_match('/^(\d{2})\.(\d{2})\.(\d{2})/', $text, $matches)) {
                                try {
                                    // YY.MM.DD format - manually construct the date
                                    $year = '20' . $matches[1];  // Assume 2000-2099
                                    $month = $matches[2];
                                    $day = $matches[3];
                                    $publishedAt = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}");
                                    Log::info("NFriends: Parsed date {$text} as {$publishedAt->format('Y-m-d')}");
                                    break;
                                } catch (\Exception $e) {
                                    Log::warning("NFriends: Failed to parse date: {$text}");
                                }
                            }
                        }

                        // 작성자 추출
                        $writerNodes = $xpath->query('.//li[contains(@class, "list_text_writer")]', $listItem);
                        $author = '';
                        if ($writerNodes->length > 0) {
                            $author = trim($writerNodes->item(0)->textContent);
                        }

                        // 상세 페이지 크롤링
                        $detailResponse = Http::get($detailUrl);
                        $body = '';
                        $thumbnailUrl = null;

                        if ($detailResponse->successful()) {
                            $detailDom = new DOMDocument();
                            libxml_use_internal_errors(true);
                            // Use HTML5 doctype approach for better encoding handling
                            $detailHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' .
                                         $detailResponse->body() .
                                         '</body></html>';
                            @$detailDom->loadHTML($detailHtml);
                            libxml_clear_errors();

                            $detailXpath = new DOMXPath($detailDom);

                            // 본문 추출
                            $bodyNodes = $detailXpath->query('//div[contains(@class, "board_txt_area")]');
                            if ($bodyNodes->length > 0) {
                                $body = $this->getInnerHtml($bodyNodes->item(0));
                            }

                            // 첫 번째 이미지를 썸네일로 사용
                            $imageNodes = $detailXpath->query('//div[contains(@class, "board_txt_area")]//img');
                            if ($imageNodes->length > 0) {
                                $imgSrc = $imageNodes->item(0)->getAttribute('src');
                                if ($imgSrc && !str_starts_with($imgSrc, 'http')) {
                                    $thumbnailUrl = 'https://nfriends.or.kr' . $imgSrc;
                                } else {
                                    $thumbnailUrl = $imgSrc;
                                }
                            }
                        }

                        // Contents 생성 (title 중복 시 기존 것 반환)
                        $contents = Contents::firstOrCreate(
                            ['title' => $title],
                            [
                                'church_id' => $departmentModel->church_id,
                                'department_id' => $departmentModel->id, // 대표 department 설정
                                'type' => MSCHContentsType::HTML,
                                'file_type' => 'HTML',
                                'body' => $body,
                                'file_url' => $detailUrl,
                                'thumbnail_url' => $thumbnailUrl,
                                'published_at' => $publishedAt,
                            ]
                        );

                        // Attach to department via pivot table
                        $contents->departments()->attach($departmentModel->id);

                        event(new ContentsNewEvent($contents));

                        Log::info("NFriends: Created content - {$title} ({$publishedAt->format('Y-m-d')})");

                    } catch (\Exception $e) {
                        Log::error("NFriends crawl error: " . $e->getMessage());
                        Log::error("NFriends crawl error details", [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        continue;
                    }
                }
            });
        }
    }

    /**
     * Get inner HTML of a DOMNode
     */
    private function getInnerHtml($node)
    {
        $innerHTML = '';
        $children = $node->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $node->ownerDocument->saveHTML($child);
        }

        return trim($innerHTML);
    }
}
