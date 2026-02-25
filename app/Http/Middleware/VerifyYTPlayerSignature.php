<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * YTPlayer API 요청 서명 검증 미들웨어
 *
 * 클라이언트가 보낸 HMAC 서명을 검증하여 요청의 무결성과 인증을 보장합니다.
 */
class VerifyYTPlayerSignature
{
    /**
     * 요청 처리
     */
    public function handle(Request $request, Closure $next): Response
    {
        // GET 요청은 서명 검증 생략 (공개 데이터 조회)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // 개발 환경에서 서명 검증 비활성화 옵션
        if (config('ytplayer.disable_signature_verification', false)) {
            return $next($request);
        }

        // 서명 검증이 필요한 POST 요청
        $signature = $request->header('X-YTPlayer-Signature');
        $timestamp = $request->header('X-YTPlayer-Timestamp');
        $nonce     = $request->header('X-YTPlayer-Nonce');

        // 필수 헤더 확인
        if (! $signature || ! $timestamp || ! $nonce) {
            return response()->json([
                'success' => false,
                'error'   => 'Missing required headers (X-YTPlayer-Signature, X-YTPlayer-Timestamp, X-YTPlayer-Nonce)',
            ], 401);
        }

        // 타임스탬프 검증 (5분 이내)
        $requestTime = (int) $timestamp;
        $currentTime = time();
        $maxAge      = 300; // 5분

        if (abs($currentTime - $requestTime) > $maxAge) {
            return response()->json([
                'success' => false,
                'error'   => 'Request timestamp expired',
            ], 401);
        }

        // 서명 생성 및 검증
        $expectedSignature = $this->generateSignature(
            $request->getContent(),
            $timestamp,
            $nonce
        );

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid signature',
            ], 401);
        }

        // Nonce 중복 체크 (5분 동안 동일한 nonce 사용 불가)
        $cacheKey = "ytplayer:nonce:{$nonce}";
        if (cache()->has($cacheKey)) {
            return response()->json([
                'success' => false,
                'error'   => 'Duplicate request (nonce already used)',
            ], 409);
        }

        // Nonce 저장 (5분 동안)
        cache()->put($cacheKey, true, now()->addMinutes(5));

        return $next($request);
    }

    /**
     * HMAC-SHA256 서명 생성
     */
    private function generateSignature(string $body, string $timestamp, string $nonce): string
    {
        $secret = config('ytplayer.signature_secret');

        if (! $secret) {
            throw new \RuntimeException('YTPlayer signature secret not configured');
        }

        // 서명 대상 문자열: timestamp + nonce + body
        $payload = $timestamp.$nonce.$body;

        return hash_hmac('sha256', $payload, $secret);
    }
}
