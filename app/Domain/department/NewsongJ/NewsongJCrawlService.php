<?php

namespace App\Domain\department\NewsongJ;

use App\Domain\church\msch\MSCHContentsType;
use App\Domain\department\DepartmentInterface;
use App\Domain\ogimage\Events\ContentsNewEvent;
use App\Models\Contents;
use App\Models\ContentsImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class NewsongJCrawlService
{

    public function crawl(DepartmentInterface $department)
    {
        $baseUrl = $department->contentsUrl();
        $response = Http::get($baseUrl);

        if ($response->successful()) {

            DB::transaction(function () use ($department, $response) {
                $json = $response->json()['items'];
                $departmentModel = $department->getModel();

                foreach ($json as $item) {
                    $alreadyContents = Contents::where('department_id', $departmentModel->id)
                        ->where('file_url', $item['permalink'])->first();

                    if ($alreadyContents) {
                        continue;
                    }

                    $contents = Contents::create([
                        'title' => $item['title'],
                        'church_id' => $departmentModel->church_id,
                        'department_id' => $departmentModel->id, // 대표 department 설정
                        'type' => MSCHContentsType::NEWS,
                        'body' => '',
                        'file_url' => $item['permalink'] ?? '',
                        'thumbnail_url' => $item['media'][0]['medium']['url'] ?? $item['media'][0]['url'] ?? null,
                        'published_at' => Carbon::createFromTimestamp($item['published_at'] / 1000),
                    ]);

                    // Attach to department via pivot table
                    $contents->departments()->attach($departmentModel->id);

                    foreach ($item['media'] as $index => $media) {
                        if (!isset($media['medium'])) {
                            $fileUrl = $media['url'];
                        } else {
                            $fileUrl = $media['medium']['url'];
                        }
                        ContentsImage::create([
                            'contents_id' => $contents->id,
                            'page' => $index + 1,
                            'file_url' => $fileUrl ?? '',
                            'title' => $media['title'] ?? null,
                        ]);
                    }
                    event(new ContentsNewEvent($contents));
                }
            });
        }
    }
}
