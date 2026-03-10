<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SymlinkController extends Controller
{
    // AES-256 암호화 키 (32 bytes)
    private const ENCRYPTION_KEY = 'pcaview-symlink-key-2026-secure';

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
        return base64_encode($iv.$encrypted);
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
            'data'    => [
                $encryptedUrl,
            ],
        ]);
    }

}
