<?php

namespace App\Domain\department\MschYoutube;

use App\Domain\church\msch\MSCHContentsType;
use App\Domain\contents\ContentsFileType;
use App\Domain\department\DepartmentInterface;
use App\Models\Contents;
use Carbon\Carbon;
use GuzzleHttp\Client;

class MschYoutubeCrawlService
{

    public function __construct()
    {
    }

    public function crawl(DepartmentInterface $department)
    {
        $type = MSCHContentsType::VIDEO;

        $client = new Client([
            'base_uri' => 'https://www.youtube.com/',
            'timeout' => 10.0,
        ]);

        $channelId = "@onlylord";
        $url = "https://www.youtube.com/{$channelId}/videos";

        $response = $client->get($url);
        $html = (string)$response->getBody();

        // ytInitialData JSON 추출
        if (preg_match('/var ytInitialData = (.*?);<\/script>/', $html, $matches)) {
            $json = json_decode($matches[1], true);

            // 영상 데이터가 들어있는 부분까지 내려가야 함
            $contents = $json['contents']['twoColumnBrowseResultsRenderer']['tabs'][1]['tabRenderer']['content']['richGridRenderer']['contents']
                ?? [];

            foreach ($contents as $item) {
                if (!isset($item['richItemRenderer']['content'])) {
                    continue;
                }

                $video = $item['richItemRenderer']['content']['videoRenderer'];

                $video = [
                    'videoId' => $video['videoId'],
                    'title' => $video['title']['runs'][0]['text'] ?? '',
                    'thumbnail' => end($video['thumbnail']['thumbnails'])['url'] ?? '',
                    'publishedTime' => $video['publishedTimeText']['simpleText'] ?? '',
                    'link' => "https://www.youtube.com/watch?v=" . $video['videoId'],
                ];

                $publishedAtString = $video['publishedTime'];

                // 1. 숫자만 추출
                preg_match('/(\d+)/', $publishedAtString, $matches);
                $timeNumber = $matches[1];

                // 2. 현재 시간에서 빼기
                if (str_contains($publishedAtString, '분')) {
                    $publishedAt = Carbon::now()->subMinutes($timeNumber);
                } elseif (str_contains($publishedAtString, '시간')) {
                    $publishedAt = Carbon::now()->subHours($timeNumber);
                } elseif (str_contains($publishedAtString, '일')) {
                    $publishedAt = Carbon::now()->subDays($timeNumber);
                } elseif (str_contains($publishedAtString, '주')) {
                    $publishedAt = Carbon::now()->subWeeks($timeNumber);
                } elseif (str_contains($publishedAtString, '개월')) {
                    $publishedAt = Carbon::now()->subMonths($timeNumber);
                }


                Contents::updateOrCreate(
                    [
                        'department_id' => $department->getModel()->id,
                        'file_url' => $video['videoId'],
                    ],
                    [
                        'department_id' => $department->getModel()->id,
                        'title' => $video['title'],
                        'type' => $type->name,
                        'file_url' => $video['videoId'],
                        'published_at' => $publishedAt,
                        'thumbnail_url' => $video['thumbnail'],
                        'file_type' => ContentsFileType::YOUTUBE
                    ]
                );
            }
        }

        return [];
    }
}
