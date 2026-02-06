<?php

namespace App\Domain\ai;

use App\Models\Contents;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AI 기반 뉴스 처리 서비스
 *
 * - 뉴스 본문 AI 리라이팅
 * - AI 이미지 생성 및 업로드
 * - AI 댓글 생성 및 저장
 */
class AiNewsProcessingService
{
    // AI 처리 설정
    private const MAX_BODY_LENGTH_FOR_AI            = 5000;
    private const IMAGE_GENERATION_PROBABILITY_DEV  = 100;
    private const IMAGE_GENERATION_PROBABILITY_PROD = 50;
    private const COMMENT_GENERATION_COUNT_MIN      = 1; // 최소 댓글 개수
    private const COMMENT_GENERATION_COUNT_MAX      = 5; // 최대 댓글 개수
    private const COMMENT_GENERATION_PROBABILITY    = 30; // 댓글 생성 확률 (%)

    public function __construct(
        private AiApiService $aiApiService
    ) {}

    /**
     * 뉴스 본문 AI 리라이팅 및 이미지 생성
     *
     * @param  string                                                             $title                뉴스 제목
     * @param  string|null                                                        $body                 뉴스 본문
     * @param  string                                                             $url                  뉴스 URL
     * @param  int                                                                $departmentId         Department ID
     * @param  bool                                                               $allowImageGeneration 이미지 생성 허용 여부
     * @return array{body: string|null, isRewritten: bool, imageUrl: string|null}
     */
    public function processNewsContent(
        string $title,
        ?string $body,
        string $url,
        int $departmentId,
        bool $allowImageGeneration = true
    ): array {
        $result = [
            'body'        => $body,
            'isRewritten' => false,
            'imageUrl'    => null,
        ];

        if (! $body) {
            return $result;
        }

        // 본문 길이 확인
        if (mb_strlen($body) > self::MAX_BODY_LENGTH_FOR_AI) {
            Log::info('Body too long for AI processing, skipping', [
                'length' => mb_strlen($body),
                'url'    => $url,
            ]);

            return $result;
        }

        // AI 리라이팅
        try {
            $rewrittenBody = $this->aiApiService->rewriteNewsContent($body);
            if ($rewrittenBody) {
                $result['body']        = $rewrittenBody;
                $result['isRewritten'] = true;

                Log::info('News content rewritten by AI', [
                    'original_length'  => mb_strlen($body),
                    'rewritten_length' => mb_strlen($rewrittenBody),
                ]);

                // AI 이미지 생성 (허용된 경우에만)
                if ($allowImageGeneration) {
                    $result['imageUrl'] = $this->generateAndUploadImage($title, $rewrittenBody, $url, $departmentId);
                } else {
                    Log::info('AI 이미지 생성 스킵 (Batch당 1개 제한)', [
                        'url' => $url,
                    ]);
                }
            } else {
                Log::warning('Failed to rewrite news content, using original');
            }
        } catch (\Exception $e) {
            Log::error('AI processing exception', [
                'error' => $e->getMessage(),
                'url'   => $url,
            ]);
        }

        return $result;
    }

    /**
     * AI 이미지 생성 및 S3 업로드
     *
     * @param  string      $title        뉴스 제목
     * @param  string      $body         뉴스 본문
     * @param  string      $url          뉴스 URL
     * @param  int         $departmentId Department ID
     * @return string|null S3 URL 또는 Base64 이미지 URL
     */
    public function generateAndUploadImage(string $title, string $body, string $url, int $departmentId): ?string
    {
        try {
            if (! $this->shouldGenerateImage()) {
                Log::info('AI 이미지 생성 스킵 (확률)', [
                    'url'         => $url,
                    'probability' => $this->getImageGenerationProbability().'%',
                ]);

                return null;
            }

            $aiImageUrl = $this->aiApiService->generateCheapNewsImage($title, $body);
            if (! $aiImageUrl) {
                return null;
            }

            Log::info('AI 이미지 생성 성공', [
                'url'         => $url,
                'environment' => app()->environment(),
                'probability' => $this->getImageGenerationProbability().'%',
            ]);

            // S3 업로드
            $s3Url = $this->uploadImageToS3($aiImageUrl, $departmentId);
            if ($s3Url) {
                Log::info('AI image uploaded to S3', ['s3_url' => $s3Url]);

                return $s3Url;
            }

            return $aiImageUrl;
        } catch (\Exception $e) {
            Log::warning('Failed to generate AI image', [
                'error' => $e->getMessage(),
                'url'   => $url,
            ]);

            return null;
        }
    }

    /**
     * AI 댓글 생성 및 저장
     *
     * @param Contents $contents      저장할 Contents 모델
     * @param string   $title         뉴스 제목
     * @param string   $body          뉴스 본문
     * @param bool     $isAiRewritten AI 리라이팅 여부
     */
    public function generateAndSaveComments(Contents $contents, string $title, string $body, bool $isAiRewritten): void
    {
        // AI 리라이팅된 뉴스만 댓글 생성
        if (! $isAiRewritten) {
            return;
        }

        // 확률 체크
        if (rand(1, 100) > self::COMMENT_GENERATION_PROBABILITY) {
            Log::info('AI 댓글 생성 스킵 (확률)', [
                'content_id'  => $contents->id,
                'probability' => self::COMMENT_GENERATION_PROBABILITY.'%',
            ]);

            return;
        }

        try {
            // 랜덤한 댓글 개수 생성 (1-5개)
            $commentCount = rand(self::COMMENT_GENERATION_COUNT_MIN, self::COMMENT_GENERATION_COUNT_MAX);

            $comments = $this->aiApiService->generateNewsComments($title, $body, $commentCount);

            if (! $comments || count($comments) === 0) {
                Log::warning('AI 댓글 생성 실패 - 빈 결과', [
                    'content_id' => $contents->id,
                ]);

                return;
            }

            // 댓글 저장
            foreach ($comments as $comment) {
                $contents->comments()->create([
                    'body'       => $comment['body'],
                    'guest_name' => $comment['name'],
                    'user_id'    => null, // 게스트 댓글
                ]);
            }

            Log::info('AI 댓글 저장 완료', [
                'content_id' => $contents->id,
                'requested'  => $commentCount,
                'generated'  => count($comments),
            ]);
        } catch (\Exception $e) {
            Log::error('AI 댓글 생성/저장 실패', [
                'content_id' => $contents->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * 이미지 생성 확률 결정
     */
    private function shouldGenerateImage(): bool
    {
        $probability = $this->getImageGenerationProbability();

        return rand(1, 100) <= $probability;
    }

    /**
     * 환경별 이미지 생성 확률 반환
     */
    private function getImageGenerationProbability(): int
    {
        return app()->environment('local', 'development')
            ? self::IMAGE_GENERATION_PROBABILITY_DEV
            : self::IMAGE_GENERATION_PROBABILITY_PROD;
    }

    /**
     * 외부 이미지 URL을 다운로드하여 S3에 업로드
     *
     * @param  string      $imageUrl     원본 이미지 URL (또는 Base64 데이터 URL)
     * @param  int         $departmentId Department ID
     * @return string|null S3 URL 또는 null (실패 시)
     */
    private function uploadImageToS3(string $imageUrl, int $departmentId): ?string
    {
        try {
            $imageData = null;
            $extension = 'png'; // 기본값

            // Base64 데이터 URL인 경우 (data:image/png;base64,...)
            if (str_starts_with($imageUrl, 'data:image/')) {
                preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageUrl, $matches);

                if (! empty($matches)) {
                    $extension  = $matches[1]; // png, jpeg, jpg, gif, webp 등
                    $base64Data = $matches[2];
                    $imageData  = base64_decode($base64Data);

                    if ($imageData === false) {
                        Log::warning('Failed to decode base64 image');

                        return null;
                    }

                    Log::info('Decoded base64 image', [
                        'extension' => $extension,
                        'size'      => strlen($imageData),
                    ]);
                } else {
                    Log::warning('Invalid base64 image format', ['url_prefix' => substr($imageUrl, 0, 100)]);

                    return null;
                }
            } else {
                // 외부 URL인 경우 다운로드
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get($imageUrl);

                if (! $response->successful()) {
                    Log::warning('Failed to download image', ['url' => $imageUrl]);

                    return null;
                }

                $imageData = $response->body();

                // 파일 확장자 추출 (기본값: jpg)
                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (empty($extension) || ! in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $extension = 'jpg';
                }
            }

            // 확장자 정규화
            $extension = strtolower($extension);
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $extension = 'png';
            }

            // S3 저장 경로 생성 (AI 이미지는 thumbnails 폴더에 저장)
            $fileName = Str::uuid().'.'.$extension;
            $s3Path   = "news/thumbnails/{$departmentId}/{$fileName}";

            // S3에 업로드
            Storage::put($s3Path, $imageData);

            // S3 URL 반환
            $s3Url = Storage::url($s3Path);

            Log::info('AI image uploaded to S3', [
                'path' => $s3Path,
                'url'  => $s3Url,
                'size' => strlen($imageData),
            ]);

            return $s3Url;
        } catch (\Exception $e) {
            Log::error('Failed to upload AI image to S3', [
                'error'     => $e->getMessage(),
                'image_url' => substr($imageUrl, 0, 100),
            ]);

            return null;
        }
    }
}
