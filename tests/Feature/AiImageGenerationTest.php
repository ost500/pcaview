<?php

namespace Tests\Feature;

use App\Domain\ai\AiApiService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AiImageGenerationTest extends TestCase
{
    protected AiApiService $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiService = app(AiApiService::class);
    }

    /**
     * AI 이미지 생성 테스트
     *
     * @return void
     */
    public function test_generate_cheap_news_image(): void
    {
        // 테스트용 뉴스 데이터
        $title = '테스트 뉴스: 한국의 아름다운 봄 풍경';
        $body = '오늘 서울에서는 벚꽃이 만개하여 많은 시민들이 봄나들이를 즐겼습니다. 여의도 윤중로에는 벚꽃축제가 열려 가족 단위 방문객들로 붐볐습니다.';

        // 이미지 생성 실행
        $imageUrl = $this->aiService->generateCheapNewsImage($title, $body);

        // 결과 출력
        if ($imageUrl) {
            $this->assertNotNull($imageUrl);
            $this->assertIsString($imageUrl);

            // Base64 이미지인지 확인
            if (str_starts_with($imageUrl, 'data:image/')) {
                echo "\n✅ AI 이미지 생성 성공 (Base64)\n";
                echo "길이: ".strlen($imageUrl)." bytes\n";
                echo "형식: ".substr($imageUrl, 0, 30)."...\n";
            } else {
                echo "\n✅ AI 이미지 생성 성공 (URL)\n";
                echo "URL: ".$imageUrl."\n";
            }

            // 이미지를 파일로 저장 (선택사항)
            if (str_starts_with($imageUrl, 'data:image/')) {
                preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageUrl, $matches);
                if (! empty($matches)) {
                    $extension = $matches[1];
                    $base64Data = $matches[2];
                    $imageData = base64_decode($base64Data);

                    $filename = storage_path('app/test_ai_image_'.time().'.'.$extension);
                    file_put_contents($filename, $imageData);
                    echo "이미지 저장됨: {$filename}\n";
                }
            }
        } else {
            $this->fail('AI 이미지 생성 실패 - null 반환');
        }
    }

    /**
     * AI 이미지 생성 응답 구조 분석 테스트
     *
     * @return void
     */
    public function test_analyze_ai_image_response(): void
    {
        $title = '뉴스 이미지 테스트';
        $body = '이것은 API 응답 구조를 분석하기 위한 테스트입니다.';

        // 로그 확인을 위해 실행
        $imageUrl = $this->aiService->generateCheapNewsImage($title, $body);

        echo "\n📊 로그 파일을 확인하세요:\n";
        echo "tail -f storage/logs/laravel.log\n\n";
        echo "다음 정보를 확인할 수 있습니다:\n";
        echo "1. AI 이미지 생성 API 응답 전체\n";
        echo "2. response_keys (응답 구조)\n";
        echo "3. full_response (전체 응답 데이터)\n";
        echo "4. Message 구조 분석\n\n";

        // 테스트는 항상 통과 (로그 확인이 목적)
        $this->assertTrue(true);
    }
}
