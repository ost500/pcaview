<?php

namespace App\Jobs;

use App\Models\Contents;
use App\Models\ContentsPlatformComment;
use App\Models\Department;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncDekricaTrendData implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tag,
        public ?int $departmentId = null
    ) {}

    public function handle(): void
    {
        try {
            // Department 찾기 또는 생성
            $department = $this->getDepartment();

            // Dekrica API 호출
            $response = $this->fetchTrendData($this->tag);

            if (! $response || empty($response['data'])) {
                Log::info('No trend data found', ['tag' => $this->tag]);

                return;
            }

            // 데이터 저장
            foreach ($response['data'] as $item) {
                $this->syncNewsItem($item, $department);
            }

            Log::info('Trend data synced', [
                'tag' => $this->tag,
                'count' => count($response['data']),
            ]);

        } catch (\Exception $e) {
            Log::error('Sync trend data failed', [
                'tag' => $this->tag,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getDepartment(): Department
    {
        if ($this->departmentId) {
            return Department::findOrFail($this->departmentId);
        }

        // "트렌드" department 찾기 또는 생성
        return Department::firstOrCreate(
            ['name' => '트렌드'],
            [
                'description' => '실시간 트렌드 뉴스',
                'icon_image' => '/pcaview_icon.png',
            ]
        );
    }

    private function fetchTrendData(string $tag): ?array
    {
        $baseUrl = config('services.dekrica.base_url');
        $apiKey = config('services.dekrica.api_key');

        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->get("{$baseUrl}/api/tags/search", [
            'tag' => $tag,
        ]);

        return $response->successful() ? $response->json() : null;
    }

    private function syncNewsItem(array $item, Department $department): void
    {
        DB::transaction(function () use ($item, $department) {
            // Contents 생성 또는 업데이트 (original_link 기준으로 중복 체크)
            $content = Contents::updateOrCreate(
                [
                    'file_url' => $item['original_link'],
                ],
                [
                    'department_id' => $department->id,
                    'type' => 'news',
                    'title' => $item['title'],
                    'body' => $item['content'] ?? $item['description'],
                    'thumbnail_url' => $item['thumbnail'] ?? null,
                    'published_at' => $item['pub_date'] ?? now(),
                    'file_type' => 'HTML',
                ]
            );

            // 플랫폼 댓글 동기화
            if (! empty($item['comments'])) {
                $this->syncComments($content, $item['comments']);
            }
        });
    }

    private function syncComments(Contents $content, array $comments): void
    {
        foreach ($comments as $comment) {
            ContentsPlatformComment::updateOrCreate(
                [
                    'comment_id' => $comment['comment_id'],
                ],
                [
                    'content_id' => $content->id,
                    'source' => $comment['source'],
                    'author' => $comment['author'],
                    'content' => $comment['content'],
                    'likes' => $comment['likes'] ?? 0,
                    'dislikes' => $comment['dislikes'] ?? 0,
                    'created_date' => $comment['created_date'] ?? null,
                    'is_best' => $comment['is_best'] ?? false,
                    'is_mobile' => $comment['is_mobile'] ?? false,
                    'reply_count' => $comment['reply_count'] ?? 0,
                ]
            );
        }
    }
}
