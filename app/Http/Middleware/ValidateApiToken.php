<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    /**
     * 허용된 API 토큰 목록
     * 환경 변수에서 관리
     */
    private function getAllowedTokens(): array
    {
        $tokens = env('SYMLINK_API_TOKENS', '');

        if (empty($tokens)) {
            return [];
        }

        return array_map('trim', explode(',', $tokens));
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bearer 토큰 또는 X-API-Token 헤더 확인
        $token = $request->bearerToken() ?? $request->header('X-API-Token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API token is required. Please provide token in Authorization header or X-API-Token header.',
            ], 401);
        }

        $allowedTokens = $this->getAllowedTokens();

        if (empty($allowedTokens)) {
            return response()->json([
                'success' => false,
                'message' => 'API access is not configured. Please contact administrator.',
            ], 503);
        }

        if (!in_array($token, $allowedTokens, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token.',
            ], 403);
        }

        return $next($request);
    }
}
