<?php

namespace App\Jobs;

use App\Models\Contents;
use App\Models\ContentsPlatformComment;
use App\Models\Department;
use App\Models\Tag;
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
            // Dekrica API 호출
            // Dekrica API 호출
            $response = $this->fetchTrendData($this->tag);

            if (! $response || empty($response['data'])) {
                Log::info('No trend data found', ['tag' => $this->tag]);

                return;
            }

            // Department 찾기 또는 생성 (첫 번째 뉴스 아이템 정보 사용)
            $firstItem = $response['data'][0] ?? null;
            $department = $this->getDepartment($firstItem);

            // is_crawl이 false이면 스킵
            if (!$department->is_crawl) {
                Log::info('Skipping trend sync for department with is_crawl=false', [
                    'tag' => $this->tag,
                    'department_id' => $department->id,
                    'department_name' => $department->name,
                ]);

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

    private function getDepartment(?array $firstItem): Department
    {
        if ($this->departmentId) {
            return Department::findOrFail($this->departmentId);
        }

        // Department 찾기
        $department = Department::where('name', $this->tag)->first();

        // Department가 존재하고 description과 icon_image가 이미 있으면 그대로 반환
        if ($department && $department->description && $department->icon_image) {
            return $department;
        }

        // Department가 없거나 description/icon_image가 null이면 업데이트
        $attributes = ['name' => $this->tag];
        $values = [];

        // description이 null이면 첫 번째 뉴스의 title로 채우기
        if (!$department || !$department->description) {
            $values['description'] = $firstItem['title'] ?? '실시간 트렌드 뉴스';
        }

        // icon_image가 null이면 첫 번째 뉴스의 thumbnail로 채우기
        if (!$department || !$department->icon_image) {
            $values['icon_image'] = $firstItem['thumbnail'] ?? null;
        }

        return Department::updateOrCreate($attributes, $values);
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

            // 태그들 동기화
            $this->syncTags($content, $item);

            // 플랫폼 댓글 동기화
            if (! empty($item['comments'])) {
                $this->syncComments($content, $item['comments']);
            }
        });
    }

    private function syncTags(Contents $content, array $item): void
    {
        $tagIds = [];

        // 검색 키워드 태그 추가
        $searchTag = Tag::firstOrCreate(['name' => $this->tag]);
        $tagIds[] = $searchTag->id;

        // 뉴스 아이템에 포함된 태그들 추가
        if (!empty($item['tags']) && is_array($item['tags'])) {
            foreach ($item['tags'] as $tag) {
                $tagName = $tag['name'];
                if (empty($tagName) || !is_string($tagName)) {
                    continue;
                }

                $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
        }

        // 중복 제거
        $tagIds = array_unique($tagIds);

        // 기존 태그들 가져오기
        $existingTagIds = $content->tags()->pluck('tags.id')->toArray();

        // 새로 추가할 태그들
        $newTagIds = array_diff($tagIds, $existingTagIds);

        // 새 태그 연결 및 usage_count 증가
        if (!empty($newTagIds)) {
            $content->tags()->attach($newTagIds);

            // 각 태그의 사용 횟수 증가
            Tag::whereIn('id', $newTagIds)->each(function ($tag) {
                $tag->incrementUsage();
            });
        }
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
