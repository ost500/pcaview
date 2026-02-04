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

    // API 설정
    private const API_BASE_URL = 'https://openrouter.ai/api/v1';
    private const SITE_URL = 'https://pcaview.com';

    // 모델 설정
    private const MODEL_TEXT_FAST = 'google/gemini-2.5-flash-lite';
    private const MODEL_IMAGE_PREMIUM = 'google/gemini-3-pro-image-preview';
    private const MODEL_IMAGE_FAST = 'sourceful/riverflow-v2-fast-preview';
    private const MODEL_TAG_FREE = 'xiaomi/mimo-v2-flash:free';

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

    /**
     * 공통 HTTP 헤더 생성
     */
    private function getHttpHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => self::SITE_URL,
            'X-Title'       => env('APP_NAME', 'PCAView'),
        ];
    }

    /**
     * OpenRouter API 호출 (텍스트 생성)
     */
    private function callTextApi(string $model, string $prompt, int $timeout = 100): ?array
    {
        $this->checkRateLimit();

        try {
            $response = Http::withHeaders($this->getHttpHeaders())
                ->timeout($timeout)
                ->post(self::API_BASE_URL . '/chat/completions', [
                    'model'    => $model,
                    'messages' => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            return $response->json();
        } catch (\Exception $e) {
            \Log::error('OpenRouter API 호출 실패', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * OpenRouter API 호출 (이미지 생성)
     */
    private function callImageApi(string $model, string $prompt, array $modalities = ['image']): ?array
    {
        $this->checkRateLimit();

        try {
            $response = Http::withHeaders($this->getHttpHeaders())
                ->timeout(120)
                ->post(self::API_BASE_URL . '/chat/completions', [
                    'model'      => $model,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'modalities' => $modalities,
                ]);

            $json = $response->json();

            // 디버깅용 상세 로깅
            \Log::info('AI 이미지 생성 API 응답', [
                'status' => $response->status(),
                'model' => $model,
                'response_keys' => array_keys($json ?? []),
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $json,
            ];
        } catch (\Exception $e) {
            \Log::error('이미지 생성 API 호출 실패', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 이미지 URL 추출 (여러 응답 형식 지원)
     */
    private function extractImageUrl(array $response): ?string
    {
        $message = $response['data']['choices'][0]['message'] ?? null;

        if (!$message || !isset($message['images']) || !is_array($message['images']) || empty($message['images'])) {
            \Log::warning('응답에 이미지 없음', [
                'has_message' => isset($message),
                'message_structure' => $message,
            ]);
            return null;
        }

        // imageUrl.url 또는 image_url.url 형식 둘 다 시도
        $imageUrl = $message['images'][0]['imageUrl']['url']
                 ?? $message['images'][0]['image_url']['url']
                 ?? null;

        if ($imageUrl) {
            \Log::info('이미지 URL 추출 성공', [
                'length' => strlen($imageUrl),
                'is_base64' => str_starts_with($imageUrl, 'data:image/'),
            ]);
        }

        return $imageUrl;
    }

    /**
     * 일반 AI 요청
     */
    public function request($question): ?Ai
    {
        $response = Http::withHeaders($this->getHttpHeaders())
            ->timeout(100)
            ->post(self::API_BASE_URL . '/chat/completions', [
                'model'    => self::MODEL_TEXT_FAST,
                'messages' => [
                    [
                        'role'    => 'user',
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
                'question_role' => 'user',
                'question'      => $question,
                'answer_role'   => $json['choices'][0]['message']['role'] ?? null,
                'answer'        => $json['choices'][0]['message']['content'] ?? null,
            ]);
        }

        Ai::create([
            'model'   => self::MODEL_TEXT_FAST,
            'created' => Carbon::now(),
            'answer'  => json_encode($json),
        ]);

        return null;
    }

    /**
     * 뉴스 본문 AI 리라이팅
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
7. **중요: 반드시 순수한 한글(현대 한국어)로만 작성하세요. 한자나 중국어 문자는 절대 사용하지 마세요**
8. 모든 단어와 문장을 한글로만 표현하세요

리라이팅된 본문만 순수 한글로 출력하세요:
PROMPT;

        $json = $this->callTextApi(self::MODEL_TEXT_FAST, $prompt);

        if ($json && isset($json['choices'][0]['message']['content'])) {
            $rewrittenContent = trim($json['choices'][0]['message']['content']);
            \Log::info('AI 리라이팅 성공', [
                'original_length' => mb_strlen($originalBody),
                'rewritten_length' => mb_strlen($rewrittenContent),
            ]);
            return $rewrittenContent;
        }

        \Log::warning('AI 리라이팅 실패');
        return null;
    }

    /**
     * 뉴스 이미지 생성 (고품질)
     */
    public function generateNewsImage(string $title, string $body): ?string
    {
        return $this->generateImage($title, $body, self::MODEL_IMAGE_PREMIUM, ['image', 'text']);
    }

    /**
     * 뉴스 이미지 생성 (저렴하고 빠름)
     */
    public function generateCheapNewsImage(string $title, string $body): ?string
    {
        return $this->generateImage($title, $body, self::MODEL_IMAGE_FAST, ['image']);
    }

    /**
     * 이미지 생성 공통 로직
     */
    private function generateImage(string $title, string $body, string $model, array $modalities): ?string
    {
        $summary = mb_substr(strip_tags($body), 0, 100);

        $prompt = <<<PROMPT
Create a professional, high-quality news article thumbnail image.

Title: {$title}
Summary: {$summary}

Style: Professional news editorial, modern, clean design, photorealistic
Aspect ratio: 16:9 for web thumbnail
PROMPT;

        $response = $this->callImageApi($model, $prompt, $modalities);

        if (!$response || !$response['success']) {
            \Log::warning('이미지 생성 실패', [
                'model' => $model,
                'status' => $response['status'] ?? 'unknown',
            ]);
            return null;
        }

        return $this->extractImageUrl($response);
    }

    /**
     * 뉴스 태그 생성
     */
    public function generateNewsTags(string $title, ?string $description, ?string $content, array $comments = []): ?array
    {
        // 댓글을 텍스트로 변환 (최대 50개)
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

        $json = $this->callTextApi(self::MODEL_TAG_FREE, $prompt);

        if ($json && isset($json['choices'][0]['message']['content'])) {
            $tagsText = $json['choices'][0]['message']['content'];

            // 쉼표로 분리하고 공백 제거
            $tags = array_map('trim', explode(',', $tagsText));
            $tags = array_slice($tags, 0, 10);
            $tags = array_filter($tags);

            \Log::info('AI 태그 생성 성공', [
                'count' => count($tags),
                'tags' => $tags,
            ]);

            return array_values($tags);
        }

        \Log::warning('AI 태그 생성 실패');
        return null;
    }
}
