<?php

namespace App\Domain\contents;

use App\Models\Contents;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Enums\OutputFormat;
use Spatie\PdfToImage\Pdf;

class ThumbnailService
{
    public function getPdfThumbnail(Contents $contents)
    {
        $response = Http::get($contents->file_url);

        if (!$response->successful()) {
            return;
        }

        $type = $contents->type;
        $cachePath = "$type/before/{$contents->id}";
        Storage::disk('public')->put($cachePath, $response->getBody()->getContents());

        $pdf = new Pdf(Storage::disk('public')->path($cachePath));

        // directory 있는지 확인
        $dir = Storage::disk('public')->path($type . "/after");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true); // 재귀적으로 디렉터리 생성
        }

        // pdf to webp 로 변환
        $savePath = $dir . "/" . $contents->id;
        $pdf->format(OutputFormat::Webp)->save($savePath);

        // contents update
        Storage::disk('public')->delete($cachePath);
        $contents->update(['thumbnail_url' => $savePath . "." . OutputFormat::Webp->value]);
    }
}
