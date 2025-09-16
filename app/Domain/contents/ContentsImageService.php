<?php

namespace App\Domain\contents;

use App\Models\Contents;
use App\Models\ContentsImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Enums\OutputFormat;
use Spatie\PdfToImage\Pdf;

class ContentsImageService
{

    public function getImagesFromPdf(Contents $contents): ?Collection
    {
        $response = Http::get($contents->file_url);

        if (!$response->successful()) {
            return null;
        }

        $type = $contents->type;
        $beforePath = "$type/before/{$contents->id}.pdf";
        Storage::disk('public')->put($beforePath, $response->getBody()->getContents());

        $pdf = new Pdf(Storage::disk('public')->path($beforePath));

        // after 디렉토리 생성
        $dir = Storage::disk('public')->path("$type/after");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 전체 페이지 변환
        $pageCount = $pdf->pageCount();
        $contentsImages = collect();

        for ($page = 1; $page <= $pageCount; $page++) {
            $pdf->selectPage($page)
                ->format(OutputFormat::Webp)
                ->save(Storage::disk('public')->path("$type/after/{$contents->id}_{$page}"));

            $afterFile = "/storage/$type/after/{$contents->id}_{$page}." . OutputFormat::Webp->value;
            $contentsImages->push(ContentsImage::create([
                'contents_id' => $contents->id,
                'page' => $page,
                'file_url' => $afterFile
            ]));
        }

        // 원본 PDF 삭제
        Storage::disk('public')->delete($beforePath);

        return $contentsImages->sortBy('page');
    }

}
