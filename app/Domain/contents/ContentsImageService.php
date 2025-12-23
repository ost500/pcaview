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

        // S3에 임시 PDF 저장
        Storage::put($beforePath, $response->getBody()->getContents());

        // 로컬 임시 파일로 다운로드 (PDF 처리용)
        $tempPdfPath = sys_get_temp_dir() . "/{$contents->id}.pdf";
        file_put_contents($tempPdfPath, Storage::get($beforePath));

        $pdf = new Pdf($tempPdfPath);

        // 전체 페이지 변환
        $pageCount = $pdf->pageCount();
        $contentsImages = collect();

        for ($page = 1; $page <= $pageCount; $page++) {
            // 로컬 임시 파일로 저장
            $tempImagePath = sys_get_temp_dir() . "/{$contents->id}_{$page}.webp";
            $pdf->selectPage($page)
                ->format(OutputFormat::Webp)
                ->save($tempImagePath);

            // S3에 업로드
            $s3Path = "$type/after/{$contents->id}_{$page}." . OutputFormat::Webp->value;
            Storage::put($s3Path, file_get_contents($tempImagePath));

            // 로컬 임시 파일 삭제
            unlink($tempImagePath);

            // S3 URL 생성
            $fileUrl = Storage::url($s3Path);
            $contentsImages->push(ContentsImage::create([
                'contents_id' => $contents->id,
                'page' => $page,
                'file_url' => $fileUrl
            ]));
        }

        // 임시 파일들 삭제
        unlink($tempPdfPath);
        Storage::delete($beforePath);

        return $contentsImages->sortBy('page');
    }

}
