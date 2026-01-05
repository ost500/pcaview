<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoldPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoldPriceController extends Controller
{
    /**
     * Get latest gold price
     */
    public function latest(): JsonResponse
    {
        $latest = GoldPrice::getLatest();

        if (! $latest) {
            return response()->json([
                'message' => 'No gold price data available',
            ], 404);
        }

        return response()->json([
            'data' => [
                'price_date' => $latest->price_date->toISOString(),
                'pure_gold' => [
                    'buy' => $latest->p_pure,
                    'sell' => $latest->s_pure,
                ],
                '18k' => [
                    'buy' => $latest->p_18k,
                    'sell' => $latest->s_18k,
                ],
                '14k' => [
                    'buy' => $latest->p_14k,
                    'sell' => $latest->s_14k,
                ],
                'white_gold' => [
                    'buy' => $latest->p_white,
                    'sell' => $latest->s_white,
                ],
                'silver' => [
                    'buy' => $latest->p_silver,
                    'sell' => $latest->s_silver,
                ],
            ],
        ]);
    }

    /**
     * Get gold price history for chart
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:7d,1m,3m,6m,1y,all',
            'type' => 'nullable|in:pure,18k,14k,white,silver',
        ]);

        $period = $request->input('period', '1m');
        $type = $request->input('type', 'pure');

        // 기간 계산
        $startDate = null;
        switch ($period) {
            case '7d':
                $startDate = now()->subDays(7);
                break;
            case '1m':
                $startDate = now()->subMonth();
                break;
            case '3m':
                $startDate = now()->subMonths(3);
                break;
            case '6m':
                $startDate = now()->subMonths(6);
                break;
            case '1y':
                $startDate = now()->subYear();
                break;
            case 'all':
                // 모든 데이터
                break;
        }

        // 날짜별로 가장 최신 데이터만 가져오기 (서브쿼리 사용)
        $query = GoldPrice::query()
            ->selectRaw('DATE(price_date) as date, MAX(id) as max_id')
            ->groupBy('date');

        if ($startDate) {
            $query->having('date', '>=', $startDate->format('Y-m-d'));
        }

        $dateIds = $query->pluck('max_id');

        // 실제 데이터 가져오기
        $prices = GoldPrice::whereIn('id', $dateIds)
            ->orderBy('price_date', 'asc')
            ->get();

        // 데이터가 너무 많으면 간격을 두고 샘플링
        $maxPoints = 500; // 차트에 표시할 최대 포인트 수

        if ($prices->count() > $maxPoints) {
            $interval = (int) ceil($prices->count() / $maxPoints);
            $prices = $prices->filter(fn ($item, $index) => $index % $interval === 0);
        }

        // 타입별 데이터 추출
        $chartData = $prices->map(function ($price) use ($type) {
            $buyKey = "p_{$type}";
            $sellKey = "s_{$type}";

            // pure는 p_pure/s_pure 형식
            if ($type === 'pure') {
                $buyKey = 'p_pure';
                $sellKey = 's_pure';
            }

            return [
                'date' => $price->price_date->format('Y-m-d'),
                'timestamp' => $price->price_date->timestamp * 1000, // JavaScript timestamp
                'buy' => $price->$buyKey ?? null,
                'sell' => $price->$sellKey ?? null,
            ];
        })->filter(fn ($item) => $item['buy'] !== null);

        return response()->json([
            'period' => $period,
            'type' => $type,
            'total_points' => $chartData->count(),
            'data' => $chartData->values(),
        ]);
    }

    /**
     * Get statistics for a specific period
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:7d,1m,3m,6m,1y,all',
            'type' => 'nullable|in:pure,18k,14k,white,silver',
        ]);

        $period = $request->input('period', '1m');
        $type = $request->input('type', 'pure');

        // 기간 계산
        $query = GoldPrice::query();

        switch ($period) {
            case '7d':
                $query->where('price_date', '>=', now()->subDays(7));
                break;
            case '1m':
                $query->where('price_date', '>=', now()->subMonth());
                break;
            case '3m':
                $query->where('price_date', '>=', now()->subMonths(3));
                break;
            case '6m':
                $query->where('price_date', '>=', now()->subMonths(6));
                break;
            case '1y':
                $query->where('price_date', '>=', now()->subYear());
                break;
            case 'all':
                // 모든 데이터
                break;
        }

        $buyKey = "p_{$type}";
        if ($type === 'pure') {
            $buyKey = 'p_pure';
        }

        $prices = $query->orderBy('price_date', 'asc')->get();

        if ($prices->isEmpty()) {
            return response()->json([
                'message' => 'No data available for this period',
            ], 404);
        }

        $buyPrices = $prices->pluck($buyKey)->filter();

        $latest = $prices->last();
        $oldest = $prices->first();

        $statistics = [
            'period' => $period,
            'type' => $type,
            'current' => $latest->$buyKey ?? null,
            'highest' => $buyPrices->max(),
            'lowest' => $buyPrices->min(),
            'average' => round($buyPrices->average()),
            'change' => [
                'value' => ($latest->$buyKey ?? 0) - ($oldest->$buyKey ?? 0),
                'percentage' => $oldest->$buyKey > 0
                    ? round((($latest->$buyKey ?? 0) - ($oldest->$buyKey ?? 0)) / ($oldest->$buyKey ?? 1) * 100, 2)
                    : 0,
            ],
            'date_range' => [
                'start' => $oldest->price_date->toISOString(),
                'end' => $latest->price_date->toISOString(),
            ],
        ];

        return response()->json([
            'data' => $statistics,
        ]);
    }
}
