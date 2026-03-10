<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SymlinkController extends Controller
{
    // AES-256 암호화 키 (32 bytes) - Cryptographically secure random key
    private const ENCRYPTION_KEY = '81f483f800328c8e61deaef1734bd2d6b465200da9f4a6fd50bf7961b724d947';

    /**
     * AES-256-CBC 암호화
     */
    private function encrypt(string $data): string
    {
        $iv = random_bytes(16); // 16 bytes IV for AES
        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            self::ENCRYPTION_KEY,
            OPENSSL_RAW_DATA,
            $iv
        );

        // IV + encrypted data를 base64로 인코딩
        return base64_encode($iv . $encrypted);
    }

    #[OA\Get(
        path: '/api/symlink',
        summary: 'Symlink URL 생성 (AES-256-CBC 암호화)',
        tags: ['Symlink'],
        parameters: [
            new OA\Parameter(
                name: 'ad_id',
                in: 'query',
                required: false,
                description: 'Advertising ID (optional)',
                schema: new OA\Schema(type: 'string', example: 'dYRxKS')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['YWJjZGVmZ2hpamtsbW5vcA==...encrypted']
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_id' => 'nullable|string|max:255',
        ]);

        $adId = $validated['ad_id'] ?? '';
        $url = "https://ddcou.com/redirect/symlink?ad_id=$adId";

        // AES-256-CBC 암호화
        $encryptedUrl = $this->encrypt($url);

        return response()->json([
            'success' => true,
            'data' => [
                $encryptedUrl,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/live/count',
        summary: 'Track ad click and return encrypted URL',
        tags: ['Ad Tracking'],
        parameters: [
            new OA\Parameter(
                name: 'ad_id',
                in: 'query',
                required: false,
                description: 'Advertising ID to track (optional, defaults to default ad)',
                schema: new OA\Schema(type: 'string', example: 'coupang_partner_001')
            ),
            new OA\Parameter(
                name: 'encrypted',
                in: 'query',
                required: false,
                description: 'Custom encrypted string to store (optional)',
                schema: new OA\Schema(type: 'string', example: 'custom_encrypted_data')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Encrypted URL returned with click tracking',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['YWJjZGVmZ2hpamtsbW5vcA==...encrypted']
                        ),
                    ]
                )
            ),
        ]
    )]
    public function count(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_id' => 'nullable|string|max:255',
            'encrypted' => 'nullable|string',
        ]);

        $adId = $validated['ad_id'] ?? 'default';
        $customEncrypted = $validated['encrypted'] ?? null;

        // 암호화된 URL 생성
        $url = "https://ddcou.com/redirect/symlink?ad_id=$adId";
        $encryptedUrl = $this->encrypt($url);

        // 클릭 추적 (ad_id 없으면 자동 생성)
        $adClick = AdClick::firstOrCreate(
            ['ad_id' => $adId],
            [
                'redirect_url' => $url,
                'click_count' => 0,
                'encrypted' => $customEncrypted ?? $encryptedUrl,
            ]
        );

        // encrypted 파라미터가 있으면 업데이트
        if ($customEncrypted !== null && $adClick->encrypted !== $customEncrypted) {
            $adClick->update(['encrypted' => $customEncrypted]);
        }

        // 클릭 카운트 증가
        $adClick->incrementClickCount();

        return response()->json([
            'success' => true,
            'data' => [$encryptedUrl],
        ]);
    }
}
