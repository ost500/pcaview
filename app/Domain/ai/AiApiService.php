<?php

namespace App\Domain\ai;

use App\Models\Ai;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AiApiService
{
    private string $apiToken;

    // Rate limit: 분당 최대 20개 요청
    private const RATE_LIMIT_MAX    = 20;
    private const RATE_LIMIT_WINDOW = 60; // 초

    public function __construct()
    {
        $this->apiToken = config('services.openrouter.api_key');
    }

    /**
     * Rate limit 체크 및 대기
     */
    private function checkRateLimit(): void
    {
        $cacheKey = 'openrouter_api_requests';
        $requests = Cache::get($cacheKey, []);
        $now      = time();

        // 1분 이내의 요청만 필터링
        $requests = array_filter($requests, fn ($timestamp) => $now - $timestamp < self::RATE_LIMIT_WINDOW);

        // 제한 초과 시 대기
        if (count($requests) >= self::RATE_LIMIT_MAX) {
            $oldestRequest = min($requests);
            $waitTime      = self::RATE_LIMIT_WINDOW - ($now - $oldestRequest) + 1;
            \Log::info("OpenRouter rate limit 도달. {$waitTime}초 대기 중...");
            sleep($waitTime);
            $requests = []; // 대기 후 리셋
        }

        // 현재 요청 추가
        $requests[] = $now;
        Cache::put($cacheKey, $requests, self::RATE_LIMIT_WINDOW);
    }

    public function request($question): ?Ai
    {
        // Rate limit 체크
        $this->checkRateLimit();

        $model    = 'qwen/qwen-2.5-7b-instruct';
        $siteUrl  = 'https://nalameter.com';
        $siteName = env('APP_NAME', 'Your Site Name');
        $role     = 'user';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => $siteUrl, // Optional. Site URL for rankings on openrouter.ai
            'X-Title'       => $siteName, // Optional. Site title for rankings on openrouter.ai
        ])->timeout(100)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model'    => $model,
            'messages' => [
                [
                    'role'    => $role,
                    'content' => $question,
                ],
            ],
        ]);
        $json = $response->json();

        if ($response->successful()) {

            return Ai::create([
                'ai_id'         => $json['id'],
                'provider'      => $json['provider'],
                'model'         => $json['model'],
                'created'       => Carbon::parse($json['created']),
                'question_role' => $role,
                'question'      => $question,
                'answer_role'   => $json['choices'][0]['message']['role'] ?? null,
                'answer'        => $json['choices'][0]['message']['content'] ?? null,
            ]);

        }

        Ai::create([
            'model'   => $model,
            'created' => Carbon::now(),
            'answer'  => json_encode($json),
        ]);

        return null;
    }

    /**
     * 뉴스 본문을 AI로 리라이팅하여 저작권 문제 해결
     *
     * @param  string      $originalBody 원본 뉴스 본문
     * @return string|null 리라이팅된 본문 또는 null
     */
    public function rewriteNewsContent(string $originalBody): ?string
    {
        $prompt = <<<PROMPT
다음 뉴스 기사 내용을 읽고, 같은 의미를 전달하되 완전히 새로운 문장으로 다시 작성해주세요.
저작권 문제를 피하기 위해 원문과 다른 표현과 구조를 사용하되, 핵심 정보는 모두 포함해야 합니다.

# 원본 기사:
{$originalBody}

## 리라이팅 규칙:
1. 원문의 핵심 정보와 사실은 정확히 유지
2. 문장 구조를 완전히 변경 (능동태↔수동태, 문장 순서 변경 등)
3. 동의어를 적극 활용하여 단어 변경
4. 자연스럽고 세련된 문체로 작성
5. 원문과 30% 이상 다른 표현 사용
6. 불필요한 설명이나 추가 내용 없이 본문만 출력

리라이팅된 본문만 출력하세요:
PROMPT;

        // Rate limit 체크
        $this->checkRateLimit();

        $model    = 'qwen/qwen-2.5-7b-instruct';
        $siteUrl  = 'https://nalameter.com';
        $siteName = env('APP_NAME', 'Your Site Name');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiToken,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => $siteUrl,
                'X-Title'       => $siteName,
            ])->timeout(100)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'    => $model,
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $json = $response->json();

            \Log::info('AI 리라이팅 응답 상태: '.$response->status());

            if ($response->successful() && isset($json['choices'][0]['message']['content'])) {
                $rewrittenContent = $json['choices'][0]['message']['content'];
                \Log::info("AI 리라이팅 성공. 원본 길이: ".mb_strlen($originalBody).", 결과 길이: ".mb_strlen($rewrittenContent));

                return trim($rewrittenContent);
            }

            \Log::warning('AI 리라이팅 실패. 상태: '.$response->status());

            return null;
        } catch (\Exception $e) {
            \Log::error('AI 리라이팅 예외 발생: '.$e->getMessage());

            return null;
        }
    }

    /**
     * 뉴스 제목과 내용을 바탕으로 이미지 생성
     *
     * OpenRouter의 /chat/completions 엔드포인트에 modalities 파라미터 사용
     *
     * @param  string      $title 뉴스 제목
     * @param  string      $body  뉴스 본문
     * @return string|null 생성된 이미지 URL 또는 null
     */
    public function generateNewsImage(string $title, string $body): ?string
    {
        // 본문에서 핵심 내용 추출 (처음 100자)
        $summary = mb_substr(strip_tags($body), 0, 100);

        $prompt = <<<PROMPT
Create a professional, high-quality news article thumbnail image.

Title: {$title}
Summary: {$summary}

Style: Professional news editorial, modern, clean design, photorealistic
Aspect ratio: 16:9 for web thumbnail
PROMPT;

        // Rate limit 체크
        $this->checkRateLimit();

        // 이미지 생성 모델 (Flux 또는 Gemini)
        $model    = 'black-forest-labs/flux-1.1-pro';
        $siteUrl  = 'https://nalameter.com';
        $siteName = env('APP_NAME', 'Your Site Name');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiToken,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => $siteUrl,
                'X-Title'       => $siteName,
            ])->timeout(120)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'      => $model,
                'modalities' => ['image', 'text'],
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $json = $response->json();

            \Log::info('AI 이미지 생성 응답 상태: '.$response->status());

            if (! $response->successful()) {
                \Log::warning('AI 이미지 생성 실패. 상태: '.$response->status(), ['response' => $json]);

                return null;
            }

            // 응답에서 이미지 추출
            if (isset($json['choices'][0]['message']['images']) && is_array($json['choices'][0]['message']['images'])) {
                $images = $json['choices'][0]['message']['images'];
                if (! empty($images)) {
                    $imageData = $images[0]; // 첫 번째 이미지 사용

                    // Base64 데이터 URL인 경우 (data:image/png;base64,...)
                    if (is_string($imageData) && str_starts_with($imageData, 'data:image/')) {
                        \Log::info('AI 이미지 생성 성공 (base64)', [
                            'title' => $title,
                            'length' => strlen($imageData),
                        ]);

                        return $imageData;
                    }
                }
            }

            // Content에서 이미지 URL 찾기 (대체 형식)
            if (isset($json['choices'][0]['message']['content'])) {
                $content = $json['choices'][0]['message']['content'];

                if (is_array($content)) {
                    foreach ($content as $item) {
                        if (isset($item['type']) && $item['type'] === 'image_url') {
                            $imageUrl = $item['image_url']['url'] ?? null;
                            if ($imageUrl) {
                                \Log::info('AI 이미지 생성 성공 (url)', ['url' => $imageUrl]);

                                return $imageUrl;
                            }
                        }
                    }
                }
            }

            \Log::warning('AI 이미지 생성 응답 형식 불일치', [
                'has_images' => isset($json['choices'][0]['message']['images']),
                'response' => json_encode($json),
            ]);

            return null;
        } catch (\Exception $e) {
            \Log::error('AI 이미지 생성 예외 발생: '.$e->getMessage());

            return null;
        }
    }

    /**
     * 뉴스 내용을 분석하여 태그 10개 생성
     *
     * @param  string     $title       뉴스 제목
     * @param  string     $description 뉴스 요약
     * @param  string     $content     뉴스 본문
     * @param  array      $comments    댓글 배열
     * @return array|null 태그 배열 또는 null
     */
    public function generateNewsTags(string $title, ?string $description, ?string $content, array $comments = []): ?array
    {
        // 댓글을 텍스트로 변환 (최대 50개만 사용)
        $commentTexts = array_slice(array_map(fn ($c) => $c['content'] ?? '', $comments), 0, 50);
        $commentsText = implode("\n", $commentTexts);

        $prompt = <<<PROMPT
다음 뉴스 기사를 분석하여 핵심 키워드 태그를 정확히 10개 추출해주세요.

# 뉴스 제목
{$title}

# 뉴스 요약
{$description}

# 뉴스 본문
{$content}

# 댓글 (일부)
{$commentsText}

## 태그 추출 규칙:
1. 정확히 10개의 태그를 생성하세요
2. 각 태그는 2-4단어 이내로 간결하게
3. 뉴스의 핵심 주제, 인물, 장소, 조직, 개념을 포함
4. 댓글에서 자주 언급되는 키워드도 고려
5. 중복 없이 고유한 태그만 생성
6. 검색에 유용한 태그를 우선

## 출력 형식:
태그1, 태그2, 태그3, 태그4, 태그5, 태그6, 태그7, 태그8, 태그9, 태그10

위 형식으로 정확히 10개의 태그만 쉼표로 구분하여 출력하세요.
PROMPT;

        // Rate limit 체크
        $this->checkRateLimit();

        $model    = 'xiaomi/mimo-v2-flash:free';
        $siteUrl  = 'https://nalameter.com';
        $siteName = env('APP_NAME', 'Your Site Name');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiToken,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => $siteUrl,
                'X-Title'       => $siteName,
            ])->timeout(100)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'    => $model,
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $json = $response->json();

            \Log::info('AI 태그 생성 응답 상태: '.$response->status());
            \Log::info('AI 태그 생성 응답 전체: '.json_encode($json));

            if ($response->successful() && isset($json['choices'][0]['message']['content'])) {
                $tagsText = $json['choices'][0]['message']['content'];
                \Log::info("AI 태그 응답 텍스트: {$tagsText}");

                // 쉼표로 분리하고 공백 제거
                $tags = array_map('trim', explode(',', $tagsText));

                // 정확히 10개로 조정
                $tags = array_slice($tags, 0, 10);

                // 빈 태그 제거
                $tags = array_filter($tags);

                \Log::info('파싱된 태그 개수: '.count($tags).', 태그: '.json_encode($tags));

                return array_values($tags);
            }

            \Log::warning('AI 응답 실패 또는 형식 불일치. 상태: '.$response->status());

            return null;
        } catch (\Exception $e) {
            \Log::error('AI 태그 생성 예외 발생: '.$e->getMessage());
            \Log::error('스택 트레이스: '.$e->getTraceAsString());

            return null;
        }
    }
}
