<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\gold\GoldnityService;
use App\Domain\gold\GoldPriceService;
use App\Domain\ytplayer\YTPlayerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'YTPlayer API',
    description: 'YTPlayer 모바일 앱을 위한 RESTful API 문서'
)]
#[OA\Server(
    url: 'https://pcaview.abc',
    description: 'Production server'
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'Development server'
)]
#[OA\SecurityScheme(
    securityScheme: 'HMACSignature',
    type: 'apiKey',
    in: 'header',
    name: 'X-YTPlayer-Signature',
    description: 'HMAC-SHA256 서명'
)]
#[OA\SecurityScheme(
    securityScheme: 'Timestamp',
    type: 'apiKey',
    in: 'header',
    name: 'X-YTPlayer-Timestamp',
    description: 'Unix 타임스탬프 (초 단위)'
)]
#[OA\SecurityScheme(
    securityScheme: 'Nonce',
    type: 'apiKey',
    in: 'header',
    name: 'X-YTPlayer-Nonce',
    description: '고유 식별자 (UUID)'
)]
/**
 * YTPlayer 앱 API 컨트롤러
 */
class YTPlayerController extends Controller
{
    public function __construct(
        private readonly YTPlayerService $ytPlayerService,
        private readonly GoldPriceService $goldPriceService,
        private readonly GoldnityService $goldnityService
    ) {}

    #[OA\Get(
        path: '/api/ytplayer/notice',
        summary: '공지사항 조회',
        tags: ['YTPlayer'],
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
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: '서비스 점검 안내'),
                                    new OA\Property(property: 'content', type: 'string', example: '2026년 2월 21일 새벽 2시부터 4시까지 서비스 점검이 예정되어 있습니다.'),
                                    new OA\Property(property: 'priority', type: 'integer', example: 10),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-20T16:30:00+09:00'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function notice(): JsonResponse
    {
        $notices = $this->ytPlayerService->getActiveNotices();

        return response()->json([
            'success' => true,
            'data'    => $notices->map(fn ($notice) => [
                'id'         => $notice->id,
                'title'      => $notice->title,
                'content'    => $notice->content,
                'priority'   => $notice->priority,
                'created_at' => $notice->created_at->toIso8601String(),
            ]),
        ]);
    }

    #[OA\Get(
        path: '/api/ytplayer/rewards',
        summary: '리워드 목록 조회',
        tags: ['YTPlayer'],
        parameters: [
            new OA\Parameter(
                name: 'application',
                in: 'query',
                required: false,
                description: '애플리케이션 이름 (필터링 용도)',
                schema: new OA\Schema(type: 'string', example: 'GOLDNITY')
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
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'application_id', type: 'integer', example: 1, nullable: true),
                                    new OA\Property(property: 'name', type: 'string', example: '프리미엄 구독권 1개월'),
                                    new OA\Property(property: 'description', type: 'string', example: '광고 없이 무제한으로 음악을 즐기세요'),
                                    new OA\Property(property: 'points_required', type: 'number', format: 'float', example: 1000.5),
                                    new OA\Property(property: 'duration', type: 'integer', example: 2592000, description: '리워드 지속 시간 (초)', nullable: true),
                                    new OA\Property(property: 'image_url', type: 'string', example: 'https://example.com/premium.jpg', nullable: true),
                                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(
                                        property: 'gold_info',
                                        properties: [
                                            new OA\Property(property: 'grams', type: 'number', format: 'float', example: 0.001176471, description: '포인트로 구매 가능한 금 그램 수'),
                                            new OA\Property(property: 'formatted_grams', type: 'string', example: '0.001176471', description: '포맷된 금 그램 수'),
                                            new OA\Property(property: 'unit', type: 'string', example: 'g'),
                                        ],
                                        type: 'object',
                                        description: 'GOLDNITY 앱 전용 금 정보',
                                        nullable: true
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'gold_price_info',
                            properties: [
                                new OA\Property(property: 'price_per_gram', type: 'number', format: 'float', example: 85000.0, description: '1g 당 금 시세 (KRW)'),
                                new OA\Property(property: 'price_per_0_001g', type: 'number', format: 'float', example: 85.0, description: '0.001g 당 금액 (KRW)'),
                                new OA\Property(property: 'unit', type: 'string', example: 'KRW'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object',
                            description: 'GOLDNITY 앱 전용 금 시세 정보',
                            nullable: true
                        ),
                    ]
                )
            ),
        ]
    )]
    public function rewards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application' => 'nullable|string|exists:applications,name',
        ]);

        $rewards = $this->ytPlayerService->getAvailableRewards($validated['application'] ?? null);

        $rewardsData = $rewards->map(fn ($reward) => [
            'id'              => $reward->id,
            'application_id'  => $reward->application_id,
            'name'            => $reward->name,
            'description'     => $reward->description,
            'points_required' => $reward->points_required,
            'duration'        => $reward->duration,
            'image_url'       => $reward->image_url,
            'expires_at'      => $reward->expires_at?->toIso8601String(),
        ])->toArray();

        // GOLDNITY 앱의 경우 금 정보 추가
        $isGoldnity = isset($validated['application']) && $validated['application'] === 'GOLDNITY';

        if ($isGoldnity) {
            $rewardsData = $this->goldnityService->enrichRewardsWithGoldInfo($rewardsData);
        }

        $response = [
            'success' => true,
            'data'    => $rewardsData,
        ];

        // GOLDNITY 앱의 경우 금 시세 정보 추가
        if ($isGoldnity) {
            $response['gold_price_info'] = $this->goldPriceService->getSmallUnitPrice();
        }

        return response()->json($response);
    }

    #[OA\Get(
        path: '/api/ytplayer/version_check',
        summary: '앱 버전 체크',
        tags: ['YTPlayer'],
        parameters: [
            new OA\Parameter(
                name: 'appVersion',
                in: 'query',
                required: true,
                description: '현재 앱 버전 (예: 1.0.0)',
                schema: new OA\Schema(type: 'string', example: '1.0.0')
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
                            properties: [
                                new OA\Property(property: 'is_update_required', type: 'boolean', example: true),
                                new OA\Property(property: 'latest_version', type: 'string', example: '1.1.0'),
                                new OA\Property(property: 'update_url', type: 'string', example: 'https://play.google.com/store/apps/details?id=com.ytplayer', nullable: true),
                                new OA\Property(property: 'message', type: 'string', example: '중요한 보안 업데이트가 포함되어 있습니다.', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function versionCheck(Request $request): JsonResponse
    {
        $request->validate([
            'appVersion' => 'required|string',
        ]);

        $currentVersion = $request->input('appVersion');
        $versionInfo    = $this->ytPlayerService->checkVersion($currentVersion);

        return response()->json([
            'success' => true,
            'data'    => $versionInfo,
        ]);
    }

    #[OA\Post(
        path: '/api/ytplayer/reward',
        summary: '리워드 적립',
        security: [
            ['HMACSignature' => [], 'Timestamp' => [], 'Nonce' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['encrypted', 'reward_type', 'application'],
                properties: [
                    new OA\Property(property: 'encrypted', type: 'string', example: 'user_hash_12345', description: '암호화된 사용자 식별자'),
                    new OA\Property(property: 'reward_type', type: 'string', enum: ['watch', 'ad', 'share', 'mining'], example: 'watch', description: '리워드 타입'),
                    new OA\Property(property: 'application', type: 'string', example: 'GOLDNITY', description: '애플리케이션 이름'),
                    new OA\Property(property: 'where', type: 'string', example: 'home_feed', description: '리워드 발생 위치', nullable: true),
                    new OA\Property(property: 'video_url', type: 'string', example: 'https://youtube.com/watch?v=example', description: '비디오 URL', nullable: true),
                    new OA\Property(property: 'video_time', type: 'integer', example: 300, description: '시청 시간 (초)', nullable: true),
                    new OA\Property(property: 'video_stringtime', type: 'string', example: '00:05:00', description: '시청 시간 (문자열)', nullable: true),
                ]
            )
        ),
        tags: ['YTPlayer'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'points_earned', type: 'number', format: 'float', example: 10.5),
                                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1550.5),
                                new OA\Property(property: 'total_earned', type: 'number', format: 'float', example: 2050.5),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Duplicate reward or validation error'),
            new OA\Response(response: 401, description: 'Invalid signature or timestamp expired'),
        ]
    )]
    public function reward(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted'        => 'required|string',
            'reward_type'      => 'required|string|in:watch,ad,share,mining',
            'application'      => 'required|string',
            'where'            => 'nullable|string',
            'video_url'        => 'nullable|string',
            'video_time'       => 'nullable|integer|min:0',
            'video_stringtime' => 'nullable|string',
        ]);

        // application을 대문자로 변환 (대소문자 구분 없이 처리)
        $validated['application'] = strtoupper($validated['application']);

        // application이 존재하는지 확인
        $application = \App\Models\Application::where('name', $validated['application'])->first();
        if (! $application) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid application name',
            ], 422);
        }

        $rewardLog = $this->ytPlayerService->logReward($validated, auth()->id());
        $balance   = $this->ytPlayerService->getUserBalance($validated['encrypted'], auth()->id());

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $rewardLog->id,
                'points_earned' => $rewardLog->points_earned,
                'balance'       => $balance['balance'],
                'total_earned'  => $balance['total_earned'],
                'created_at'    => $rewardLog->created_at->toIso8601String(),
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/api/ytplayer/install_count',
        summary: '설치 횟수 전송',
        security: [
            ['HMACSignature' => [], 'Timestamp' => [], 'Nonce' => []],
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'referrer', type: 'string', example: 'utm_source=google&utm_medium=cpc', nullable: true),
                ]
            )
        ),
        tags: ['YTPlayer'],
        responses: [
            new OA\Response(response: 201, description: 'Success'),
        ]
    )]
    public function installCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referrer' => 'nullable|string',
        ]);

        $installCount = $this->ytPlayerService->logInstall(
            $validated['referrer'] ?? null,
            $request->userAgent(),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $installCount->id,
                'created_at' => $installCount->created_at->toIso8601String(),
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/api/ytplayer/live_count',
        summary: '라이브 카운트 전송',
        security: [
            ['HMACSignature' => [], 'Timestamp' => [], 'Nonce' => []],
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'referrer', type: 'string', example: 'home_screen', nullable: true),
                    new OA\Property(property: 'session_id', type: 'string', example: 'session_abc123xyz', nullable: true),
                ]
            )
        ),
        tags: ['YTPlayer'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'active_users', type: 'integer', example: 127),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function liveCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referrer'   => 'nullable|string',
            'session_id' => 'nullable|string',
        ]);

        $liveCount = $this->ytPlayerService->logLiveCount(
            $validated['referrer'] ?? null,
            $request->userAgent(),
            $request->ip(),
            $validated['session_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $liveCount->id,
                'active_users' => $this->ytPlayerService->getActiveUserCount(),
                'created_at'   => $liveCount->created_at->toIso8601String(),
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/api/ytplayer/balance',
        summary: '포인트 잔액 조회',
        tags: ['YTPlayer'],
        parameters: [
            new OA\Parameter(
                name: 'encrypted',
                in: 'query',
                required: true,
                description: '암호화된 사용자 식별자',
                schema: new OA\Schema(type: 'string', example: 'user_hash_12345')
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
                            properties: [
                                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.5),
                                new OA\Property(property: 'total_earned', type: 'number', format: 'float', example: 2000.5),
                                new OA\Property(property: 'total_spent', type: 'number', format: 'float', example: 500.5),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function balance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted' => 'required|string',
        ]);

        $balance = $this->ytPlayerService->getUserBalance(
            $validated['encrypted'],
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'data'    => $balance,
        ]);
    }

    #[OA\Post(
        path: '/api/ytplayer/use_reward',
        summary: '리워드 사용 (교환)',
        security: [
            ['HMACSignature' => [], 'Timestamp' => [], 'Nonce' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['encrypted', 'reward_id'],
                properties: [
                    new OA\Property(property: 'encrypted', type: 'string', example: 'user_hash_12345'),
                    new OA\Property(property: 'reward_id', type: 'integer', example: 1),
                ]
            )
        ),
        tags: ['YTPlayer'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'reward_id', type: 'integer', example: 1),
                                new OA\Property(property: 'reward_name', type: 'string', example: '프리미엄 구독권 1개월'),
                                new OA\Property(property: 'points_spent', type: 'number', format: 'float', example: 1000.5),
                                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'balance', type: 'number', format: 'float', example: 500.5),
                                new OA\Property(property: 'total_spent', type: 'number', format: 'float', example: 1000.5),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Insufficient points or invalid reward',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Insufficient points'),
                    ]
                )
            ),
        ]
    )]
    public function useReward(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted'         => 'required|string',
            'reward_id'         => 'nullable|integer|exists:rewards,id',
            'reward_product_id' => 'nullable|integer|exists:reward_products,id',
        ]);

        // reward_id 또는 reward_product_id 중 하나는 필수
        if (! isset($validated['reward_id']) && ! isset($validated['reward_product_id'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Either reward_id or reward_product_id is required',
            ], 400);
        }

        try {
            $usage = $this->ytPlayerService->useReward(
                $validated['encrypted'],
                $validated['reward_id'] ?? 0,
                auth()->id(),
                $validated['reward_product_id'] ?? null
            );

            $balance = $this->ytPlayerService->getUserBalance($validated['encrypted'], auth()->id());

            // 응답 데이터 구성
            $responseData = [
                'id'           => $usage->id,
                'points_spent' => $usage->points_spent,
                'status'       => $usage->status,
                'balance'      => $balance['balance'],
                'total_spent'  => $balance['total_spent'],
                'created_at'   => $usage->created_at->toIso8601String(),
            ];

            // Reward 또는 RewardProduct 정보 추가
            if ($usage->reward_id) {
                $responseData['reward_id'] = $usage->reward_id;
                $responseData['reward_name'] = $usage->reward->name;
            } else {
                $responseData['reward_product_id'] = $usage->reward_product_id;
                $responseData['product_name'] = $usage->rewardProduct->name;
            }

            return response()->json([
                'success' => true,
                'data'    => $responseData,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Get(
        path: '/api/ytplayer/reward_history',
        summary: '리워드 사용 내역 조회',
        tags: ['YTPlayer'],
        parameters: [
            new OA\Parameter(
                name: 'encrypted',
                in: 'query',
                required: true,
                description: '암호화된 사용자 식별자',
                schema: new OA\Schema(type: 'string', example: 'user_hash_12345')
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: '조회할 개수 (기본: 20, 최대: 100)',
                schema: new OA\Schema(type: 'integer', example: 10, minimum: 1, maximum: 100)
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
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'reward_name', type: 'string', example: '프리미엄 구독권 1개월'),
                                    new OA\Property(property: 'points_spent', type: 'number', format: 'float', example: 1000.5),
                                    new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function rewardHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted' => 'required|string',
            'limit'     => 'nullable|integer|min:1|max:100',
        ]);

        $history = $this->ytPlayerService->getRewardUsageHistory(
            $validated['encrypted'],
            $validated['limit'] ?? 20
        );

        return response()->json([
            'success' => true,
            'data'    => $history->map(fn ($usage) => [
                'id'           => $usage->id,
                'reward_name'  => $usage->reward->name,
                'points_spent' => $usage->points_spent,
                'status'       => $usage->status,
                'created_at'   => $usage->created_at->toIso8601String(),
            ]),
        ]);
    }
}
