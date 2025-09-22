<?php

namespace App\Domain\department\MschJubo;

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

class JuboCrawlService
{
    public function crawl(DepartmentInterface $department)
    {
        $today = Carbon::today();

        $baseUrl = $department->contentsUrl($today->year. "/");
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
                $type = MSCHContentsType::BULLETIN;

                $fileUrl = $baseUrl . $pdf;

                $findContent = Contents::where('file_url', $fileUrl)->first();

                if ($findContent) {
                    continue;
                }

                $newContents = Contents::create([
                    'department_id' => $department->getModel()->id,
                    'title' => $this->getTitle($department, $pdf, $type),
                    'type' => $type->name,
                    'file_url' => $fileUrl,
                    'published_at' => $this->getPublishedAt($pdf),
                ]);

                $contentsImages = $contentsImageService->getImagesFromPdf($newContents);
                $thumbnailService->getPdfThumbnail($newContents, $contentsImages->first());

                event(new ContentsNewEvent($newContents));
            }
        }
    }

    public function getTitle(DepartmentInterface $department, $fileName, MSCHContentsType $contentType)
    {
        $date = $this->getPublishedAt($fileName);

        return $department->contentsTitle($date);
    }

    public function getPublishedAt(string $fileName)
    {
        // 1. 확장자 제거
        $dateString = pathinfo($fileName, PATHINFO_FILENAME); // "20250420"

        // 2. Carbon으로 변환
        return Carbon::createFromFormat('Ymd', $dateString);
    }
}
