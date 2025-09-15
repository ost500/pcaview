<?php

namespace App\Domain\church\msch\crwal;

use App\Domain\church\ChurchInterface;
use App\Domain\church\msch\MSCHContentsType;
use App\Domain\contents\ContentsImageService;
use App\Domain\contents\ThumbnailService;
use App\Models\Contents;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class JuboCrawlService
{
    public function crawl(ChurchInterface $church)
    {
        $today = Carbon::today();

        $baseUrl = $church->bulletinUrl() . $today->year . "/";
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
                    'department_id' => $church->getDepartmentId(),
                    'title' => $this->getTitle($church, $pdf, $type),
                    'type' => $type->name,
                    'file_url' => $fileUrl,
                    'published_at' => $this->getPublishedAt($pdf),
                ]);

                $contentsImages = $contentsImageService->getImagesFromPdf($newContents);
                $thumbnailService->getPdfThumbnail($newContents, $contentsImages->first());
            }

        }
    }

    public function getTitle(ChurchInterface $church, $fileName, MSCHContentsType $contentType)
    {
        $date = $this->getPublishedAt($fileName);

        return $church->getContentsTitle($contentType, $date);
    }

    public function getPublishedAt(string $fileName)
    {
        // 1. 확장자 제거
        $dateString = pathinfo($fileName, PATHINFO_FILENAME); // "20250420"

        // 2. Carbon으로 변환
        return Carbon::createFromFormat('Ymd', $dateString);
    }
}
