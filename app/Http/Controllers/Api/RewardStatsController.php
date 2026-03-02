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
                                            new OA\Property(property: 'open_balance', type: 'number', format: 'float', example: 1400.0, description: '당일 시작 잔액 (시가)'),
                                            new OA\Property(property: 'close_balance', type: 'number', format: 'float', example: 1500.5, description: '당일 종료 잔액 (종가)'),
                                            new OA\Property(property: 'gold_earned', type: 'number', format: 'float', example: 100.5, description: '당일 획득 금 양'),
                                            new OA\Property(property: 'gold_price', type: 'number', format: 'float', example: 85000.0, description: '당일 금 시세 (1g 기준 KRW)'),
                                            new OA\Property(property: 'open_value', type: 'number', format: 'float', example: 1400.0, description: '시작 잔액 원화 가치'),
                                            new OA\Property(property: 'close_value', type: 'number', format: 'float', example: 1500.5, description: '종료 잔액 원화 가치'),
                                            new OA\Property(property: 'open_balance_value', type: 'number', format: 'float', example: 31800000.0, description: '시작 잔액의 금 가치 (원화)'),
                                            new OA\Property(property: 'close_balance_value', type: 'number', format: 'float', example: 34100000.0, description: '종료 잔액의 금 가치 (원화)'),
                                            new OA\Property(property: 'gold_grams', type: 'number', format: 'float', example: 0.017653, description: '종료 시점 금 그램 환산'),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'start_date', type: 'string', example: '2026-01-27'),
                                new OA\Property(property: 'end_date', type: 'string', example: '2026-02-26'),
                                new OA\Property(property: 'current_balance', type: 'number', format: 'float', example: 1500.5),
                                new OA\Property(property: 'current_gold_price', type: 'number', format: 'float', example: 85000.0, description: '현재 금 시세'),
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

        // 일별 시가(첫 before_balance), 종가(마지막 after_balance), 획득 금 합계, 금 가치 조회
        $dailyStats = RewardLog::where('encrypted', $encrypted)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->whereNotNull('after_balance')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('(SELECT before_balance FROM reward_logs WHERE encrypted = "'.$encrypted.'" AND DATE(created_at) = DATE(reward_logs.created_at) ORDER BY created_at ASC LIMIT 1) as open_balance'),
                DB::raw('MAX(after_balance) as close_balance'),
                DB::raw('SUM(points_earned) as gold_earned'),
                DB::raw('(SELECT after_balance_value FROM reward_logs WHERE encrypted = "'.$encrypted.'" AND DATE(created_at) = DATE(reward_logs.created_at) ORDER BY created_at ASC LIMIT 1) as open_balance_value'),
                DB::raw('MAX(after_balance_value) as close_balance_value')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // 일별 금 시세 조회 (한돈 3.75g -> 1g 변환)
        $goldPrices = DomesticMetalPrice::where('price_date', '>=', $startDate)
            ->where('price_date', '<=', $endDate)
            ->select('price_date', DB::raw('s_pure / 3.75 as gold_price'))
            ->get()
            ->keyBy(fn ($item) => $item->price_date->format('Y-m-d'));

        // 날짜별 데이터 생성
        $chartData            = [];
        $previousBalance      = 0;
        $previousBalanceValue = 0;
        $latestGoldPrice      = DomesticMetalPrice::getLatest();
        $currentGoldPrice     = $latestGoldPrice?->s_pure ? $latestGoldPrice->s_pure / 3.75 : 85000.0;

        for ($i = 0; $i < $days; $i++) {
            $date              = $startDate->copy()->addDays($i);
            $dateStr           = $date->format('Y-m-d');
            $stats             = $dailyStats[$dateStr] ?? null;
            $openBalance       = $stats?->open_balance ?? $previousBalance;
            $closeBalance      = $stats?->close_balance ?? $previousBalance;
            $goldEarned        = $stats?->gold_earned ?? 0;
            $goldPrice         = $goldPrices[$dateStr]?->gold_price ?? $currentGoldPrice;
            $openBalanceValue  = $stats?->open_balance_value ?? $previousBalanceValue;
            $closeBalanceValue = $stats?->close_balance_value ?? $previousBalanceValue;

            // 잔액이 업데이트되면 이후 날짜의 기본값으로 사용
            if ($stats) {
                $previousBalance      = $closeBalance;
                $previousBalanceValue = $closeBalanceValue;
            }

            $chartData[] = [
                'date'                => $dateStr,
                'open_balance'        => (float) $openBalance,
                'close_balance'       => (float) $closeBalance,
                'gold_earned'         => (float) $goldEarned,
                'gold_price'          => (float) $goldPrice,
                'open_value'          => (float) $openBalance,
                'close_value'         => (float) $closeBalance,
                'open_balance_value'  => (float) $openBalanceValue,
                'close_balance_value' => (float) $closeBalanceValue,
                'gold_grams'          => $goldPrice > 0 ? round($closeBalance / $goldPrice, 9) : 0,
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
                'chart'              => $chartData,
                'start_date'         => $startDate->format('Y-m-d'),
                'end_date'           => $endDate->format('Y-m-d'),
                'current_balance'    => (float) $currentBalance,
                'current_gold_price' => (float) $currentGoldPrice,
            ],
        ]);
    }
}
