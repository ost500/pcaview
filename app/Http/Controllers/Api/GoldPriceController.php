<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomesticMetalPrice;
use App\Models\InternationalMetalPrice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoldPriceController extends Controller
{
    private const MAX_CHART_POINTS = 500;

    /**
     * Get latest gold price
     */
    public function latest(): JsonResponse
    {
        $latest = DomesticMetalPrice::getLatest();

        if (! $latest) {
            return response()->json([
                'message' => 'No gold price data available',
            ], 404);
        }

        return response()->json([
            'data' => [
                'price_date' => $latest->price_date->toISOString(),
                'pure_gold'  => [
                    'buy'  => $latest->p_pure,
                    'sell' => $latest->s_pure,
                ],
                '18k' => [
                    'buy'  => $latest->p_18k,
                    'sell' => $latest->s_18k,
                ],
                '14k' => [
                    'buy'  => $latest->p_14k,
                    'sell' => $latest->s_14k,
                ],
                'white_gold' => [
                    'buy'  => $latest->p_white,
                    'sell' => $latest->s_white,
                ],
                'silver' => [
                    'buy'  => $latest->p_silver,
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
            'type'   => 'nullable|in:pure,18k,14k,white,silver,gold,platinum,palladium',
            'market' => 'nullable|in:domestic,international',
        ]);

        $period = $request->input('period', '1m');
        $type   = $request->input('type', 'pure');
        $market = $request->input('market', 'domestic');

        if ($market === 'international') {
            return $this->getInternationalHistory($period, $type);
        }

        return $this->getDomesticHistory($period, $type);
    }

    /**
     * Get domestic metal price history
     */
    private function getDomesticHistory(string $period, string $type): JsonResponse
    {
        $startDate = $this->calculateStartDate($period);

        $query = DomesticMetalPrice::query()
            ->selectRaw('DATE(price_date) as date, MAX(id) as max_id')
            ->groupBy('date');

        if ($startDate) {
            $query->having('date', '>=', $startDate->format('Y-m-d'));
        }

        $dateIds = $query->pluck('max_id');

        $prices = DomesticMetalPrice::whereIn('id', $dateIds)
            ->orderBy('price_date', 'asc')
            ->get();

        if ($prices->isEmpty()) {
            return response()->json([
                'message' => 'No domestic price data available',
            ], 404);
        }

        $prices = $this->sampleData($prices);

        // Map type to actual column names
        $columnMap = [
            'pure'   => ['buy' => 'p_pure', 'sell' => 's_pure'],
            '18k'    => ['buy' => 'p_18k', 'sell' => 's_18k'],
            '14k'    => ['buy' => 'p_14k', 'sell' => 's_14k'],
            'white'  => ['buy' => 'p_platinum', 'sell' => 's_platinum'], // white는 platinum으로 매핑
            'silver' => ['buy' => 'p_silver', 'sell' => 's_silver'],
        ];

        $buyKey  = $columnMap[$type]['buy'] ?? 'p_pure';
        $sellKey = $columnMap[$type]['sell'] ?? 's_pure';

        $chartData = $prices->map(function ($price) use ($buyKey, $sellKey) {
            return [
                'date'      => $price->price_date->format('Y-m-d'),
                'timestamp' => $price->price_date->timestamp * 1000,
                'price'     => $price->$buyKey ?? null,
                'buy'       => $price->$buyKey ?? null,
                'sell'      => $price->$sellKey ?? null,
            ];
        })->filter(fn ($item) => $item['price'] !== null);

        return response()->json([
            'market'       => 'domestic',
            'period'       => $period,
            'type'         => $type,
            'total_points' => $chartData->count(),
            'data'         => $chartData->values(),
        ]);
    }

    /**
     * Get statistics for a specific period
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:7d,1m,3m,6m,1y,all',
            'type'   => 'nullable|in:pure,18k,14k,white,silver',
        ]);

        $period = $request->input('period', '1m');
        $type   = $request->input('type', 'pure');

        // 기간 계산
        $query = DomesticMetalPrice::query();

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
            'period'  => $period,
            'type'    => $type,
            'current' => $latest->$buyKey ?? null,
            'highest' => $buyPrices->max(),
            'lowest'  => $buyPrices->min(),
            'average' => round($buyPrices->average()),
            'change'  => [
                'value'      => ($latest->$buyKey ?? 0) - ($oldest->$buyKey ?? 0),
                'percentage' => $oldest->$buyKey > 0
                    ? round((($latest->$buyKey ?? 0) - ($oldest->$buyKey ?? 0)) / ($oldest->$buyKey ?? 1) * 100, 2)
                    : 0,
            ],
            'date_range' => [
                'start' => $oldest->price_date->toISOString(),
                'end'   => $latest->price_date->toISOString(),
            ],
        ];

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Calculate start date based on period
     */
    private function calculateStartDate(string $period): ?\Carbon\Carbon
    {
        return match ($period) {
            '7d'    => now()->subDays(7),
            '1m'    => now()->subMonth(),
            '3m'    => now()->subMonths(3),
            '6m'    => now()->subMonths(6),
            '1y'    => now()->subYear(),
            'all'   => null,
            default => null,
        };
    }

    /**
     * Sample data points if too many
     */
    private function sampleData(Collection $collection): Collection
    {
        if ($collection->count() <= self::MAX_CHART_POINTS) {
            return $collection;
        }

        $interval = (int) ceil($collection->count() / self::MAX_CHART_POINTS);

        return $collection->filter(fn ($item, $index) => $index % $interval === 0);
    }

    /**
     * Get international gold price history
     */
    private function getInternationalHistory(string $period, string $type): JsonResponse
    {
        $startDate = $this->calculateStartDate($period);

        $query = InternationalMetalPrice::query();

        if ($startDate) {
            $query->where('price_date', '>=', $startDate);
        }

        $prices = $query->orderBy('price_date', 'asc')->get();

        if ($prices->isEmpty()) {
            return response()->json([
                'message' => 'No international price data available',
            ], 404);
        }

        $prices = $this->sampleData($prices);

        $columnMap = [
            'pure'      => 'gold_usd',
            'gold'      => 'gold_usd',
            'silver'    => 'silver_usd',
            'platinum'  => 'platinum_usd',
            'palladium' => 'palladium_usd',
        ];

        $column = $columnMap[$type] ?? 'gold_usd';

        $chartData = $prices->map(function ($price) use ($column) {
            return [
                'date'      => $price->price_date->format('Y-m-d'),
                'timestamp' => $price->price_date->timestamp * 1000,
                'price'     => $price->$column ?? null,
            ];
        })->filter(fn ($item) => $item['price'] !== null);

        return response()->json([
            'market'       => 'international',
            'period'       => $period,
            'type'         => $type,
            'total_points' => $chartData->count(),
            'data'         => $chartData->values(),
        ]);
    }
}
