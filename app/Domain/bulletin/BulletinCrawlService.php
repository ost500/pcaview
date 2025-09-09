<?php

namespace App\Domain\bulletin;

use App\Domain\church\ChurchInterface;
use App\Domain\church\ChurchNewsInterface;
use App\Domain\contents\ContentsType;
use App\Models\Contents;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class BulletinCrawlService
{
    public function crawl(ChurchInterface $church)
    {
        $today = Carbon::today();

        if ($church instanceof ChurchInterface) {

            $baseUrl = $church->bulletinUrl() . $today->year;
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

                // 결과 출력
                foreach ($pdfFiles as $pdf) {
                    echo $pdf . "\n";
                    $title = $baseUrl . $pdf;

                    $findContent = Contents::where('title', $title)->first();

                    if ($findContent) {
                        break;
                    }

                    Contents::create([
                        'department_id' => $church->getDepartmentId(),
                        'title' => $title,
                        'type' => ContentsType::BULLETIN,
                        'file_url' => $baseUrl . $pdf,
                        'published_at' => $today,
                    ]);
                }
            }

        }

        if ($church instanceof ChurchNewsInterface) {
            $baseUrl = $church->getNewsUrl($today->year);
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

                // 결과 출력
                foreach ($pdfFiles as $pdf) {
                    echo $pdf . "\n";
                    $title = $baseUrl . $pdf;

                    $findContent = Contents::where('title', $title)->first();

                    if ($findContent) {
                        break;
                    }

                    Contents::create([
                        'department_id' => $church->getDepartmentId(),
                        'title' => $title,
                        'type' => ContentsType::NEWS,
                        'file_url' => $baseUrl . $pdf,
                        'published_at' => $today,
                    ]);
                }
            }
        }
    }
}
