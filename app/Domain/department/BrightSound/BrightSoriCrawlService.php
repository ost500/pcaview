<?php

namespace App\Domain\department\BrightSound;

use App\Domain\church\msch\MSCHContentsType;
use App\Domain\contents\ContentsImageService;
use App\Domain\contents\ThumbnailService;
use App\Domain\department\DepartmentInterface;
use App\Domain\ogimage\Events\ContentsNewEvent;
use App\Models\Contents;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class BrightSoriCrawlService
{
    public function crawl(DepartmentInterface $department)
    {
        $today = Carbon::today();

        $baseUrl = $department->contentsUrl($today->year);
        $response = Http::get($baseUrl);

        if ($response->successful()) {
            $dom = new DOMDocument();
            @$dom->loadHTML($response->body()); // @ 를 사용해 HTML 경고 억제

            $xpath = new DOMXPath($dom);

            $pdfLinks = $xpath->query('//a[substring(@href, string-length(@href) - 3) = ".pdf"]');

            $pdfFiles = [];
            foreach ($pdfLinks as $link) {
                $pdfFiles[] = $link->getAttribute('href');
            }

            $contentsImageService = app(ContentsImageService::class);
            $thumbnailService = app(ThumbnailService::class);

            // 결과 출력
            foreach ($pdfFiles as $pdf) {
                $type = MSCHContentsType::NEWS;
                $fileUrl = $baseUrl . "/" . $pdf;

                $findContent = Contents::where('file_url', $fileUrl)->first();

                if ($findContent) {
                    continue;
                }

                $departmentModel = $department->getModel();

                // title 중복 시 기존 것 반환
                $newContents = Contents::firstOrCreate(
                    ['title' => $this->getTitle($pdf)],
                    [
                        'church_id' => $departmentModel->church_id,
                        'department_id' => $departmentModel->id, // 대표 department 설정
                        'type' => $type->name,
                        'file_url' => $fileUrl,
                        'published_at' => $this->getPublishedAt($pdf),
                    ]
                );

                // Attach to department via pivot table
                $newContents->departments()->attach($departmentModel->id);

                $contentsImages = $contentsImageService->getImagesFromPdf($newContents);
                $thumbnailService->getPdfThumbnail($newContents, $contentsImages->first());

                event(new ContentsNewEvent($newContents));
            }
        }
    }

    public function getTitle($fileName)
    {
        $raw = pathinfo($fileName, PATHINFO_FILENAME); // "20250420"
        // 1. URL 디코딩
        $decoded = urldecode($raw); // "2025.0105-900호"

        // 2. 정규식으로 년, 월일, 호 추출
        preg_match('/(\d{4})\.(\d{2})(\d{2})-(.+)호/', $decoded, $matches);

        $issue = $matches[4];

        $date = $this->getPublishedAt($fileName);

        return $date->format('Y년 n월 j일') . " {$issue}호 밝은소리";
    }

    public function getPublishedAt(string $fileName)
    {
        $raw = pathinfo($fileName, PATHINFO_FILENAME); // "20250420"
        // 1. URL 디코딩
        $decoded = urldecode($raw); // "2025.0105-900호"

        // 2. 정규식으로 년, 월일, 호 추출
        preg_match('/(\d{4})\.(\d{2})(\d{2})-(.+)호/', $decoded, $matches);


        $year = $matches[1];
        $month = $matches[2];
        $day = $matches[3];
        $issue = $matches[4];

        // 3. Carbon 객체 생성
        $date = Carbon::createFromFormat('Ymd', "$year$month$day");

        return $date;
    }
}
