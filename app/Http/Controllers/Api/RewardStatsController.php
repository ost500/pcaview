<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomesticMetalPrice;
use App\Models\RewardLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class RewardStatsController extends Controller
{
    #[OA\Get(
        path: '/api/ytplayer/reward_chart',
        summary: '일별 리워드 잔액 및 가치 그래프 데이터',
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
                name: 'days',
                in: 'query',
                required: false,
                description: '조회 기간 (일) - 기본 30일',
                schema: new OA\Schema(type: 'integer', example: 30, minimum: 1, maximum: 365)
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
                                new OA\Property(
                                    property: 'chart',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'date', type: 'string', example: '2026-02-26'),
                                            new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.5, description: '해당 일자 종료 시점 잔액'),
                                            new OA\Property(property: 'gold_price', type: 'number', format: 'float', example: 85000.0, description: '해당 일자 금 시세 (1g)'),
                                            new OA\Property(property: 'value', type: 'number', format: 'float', example: 1500.5, description: '잔액의 원화 가치 (balance)'),
                                            new OA\Property(property: 'gold_grams', type: 'number', format: 'float', example: 0.017653, description: '금 그램 환산'),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'start_date', type: 'string', example: '2026-01-27'),
                                new OA\Property(property: 'end_date', type: 'string', example: '2026-02-26'),
                                new OA\Property(property: 'current_balance', type: 'number', format: 'float', example: 1500.5),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function rewardChart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted' => 'required|string',
            'days'      => 'nullable|integer|min:1|max:365',
        ]);

        $days      = $validated['days'] ?? 30;
        $encrypted = $validated['encrypted'];
        $endDate   = now();
        $startDate = now()->subDays($days - 1)->startOfDay();

        // 일별 마지막 잔액 조회 (reward_logs의 after_balance 기준)
        $dailyBalances = RewardLog::where('encrypted', $encrypted)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->whereNotNull('after_balance')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('MAX(after_balance) as balance')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // 일별 금 시세 조회
        $goldPrices = DomesticMetalPrice::where('price_date', '>=', $startDate)
            ->where('price_date', '<=', $endDate)
            ->select('price_date', 's_pure as gold_price')
            ->get()
            ->keyBy(fn ($item) => $item->price_date->format('Y-m-d'));

        // 날짜별 데이터 생성
        $chartData       = [];
        $previousBalance = 0;
        $latestGoldPrice = DomesticMetalPrice::getLatest()?->s_pure ?? 85000.0;

        for ($i = 0; $i < $days; $i++) {
            $date      = $startDate->copy()->addDays($i);
            $dateStr   = $date->format('Y-m-d');
            $balance   = $dailyBalances[$dateStr]->balance ?? $previousBalance;
            $goldPrice = $goldPrices[$dateStr]->gold_price ?? $latestGoldPrice;

            // 잔액이 업데이트되면 이후 날짜의 기본값으로 사용
            if (isset($dailyBalances[$dateStr])) {
                $previousBalance = $balance;
            }

            $chartData[] = [
                'date'       => $dateStr,
                'balance'    => (float) $balance,
                'gold_price' => (float) $goldPrice,
                'value'      => (float) $balance, // 포인트 = 원화
                'gold_grams' => $goldPrice > 0 ? round($balance / $goldPrice, 9) : 0,
            ];
        }

        // 현재 잔액 (가장 마지막 after_balance)
        $currentBalance = RewardLog::where('encrypted', $encrypted)
            ->whereNotNull('after_balance')
            ->orderBy('created_at', 'desc')
            ->value('after_balance') ?? 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'chart'           => $chartData,
                'start_date'      => $startDate->format('Y-m-d'),
                'end_date'        => $endDate->format('Y-m-d'),
                'current_balance' => (float) $currentBalance,
            ],
        ]);
    }
}
