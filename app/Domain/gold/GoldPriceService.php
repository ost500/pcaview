<?php

declare(strict_types=1);

namespace App\Domain\gold;

use App\Models\DomesticMetalPrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 금 시세 조회 서비스
 */
class GoldPriceService
{
    /**
     * 현재 금 시세 조회 (1g 기준 KRW)
     */
    public function getCurrentGoldPrice(): float
    {
        return Cache::remember('gold_price_per_gram', now()->addMinutes(30), function () {
            // metal_domestic_prices 테이블에서 최신 순금 판매가 조회
            $latestPrice = DomesticMetalPrice::getLatest();

            if ($latestPrice && $latestPrice->s_pure > 0) {
                return (float) $latestPrice->s_pure;
            }

            // 데이터가 없는 경우 기본값 (1g 기준 약 85,000원)
            return 85000.0;
        });
    }

    /**
     * API에서 최신 금 시세를 가져와 DB에 저장
     */
    public function fetchAndSaveLatestPrice(): ?DomesticMetalPrice
    {
        try {
            // 최근 7일간의 금 시세 가져오기
            $endDate   = now();
            $startDate = now()->subDays(7);

            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.koreagoldx.co.kr/api/price/chart/list', [
                'srchDt'        => '7D',
                'type'          => 'Au',
                'dataDateStart' => $startDate->format('Y.m.d'),
                'dataDateEnd'   => $endDate->format('Y.m.d'),
            ]);

            if (! $response->successful()) {
                Log::error('Failed to fetch gold price from API', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! isset($data['list']) || empty($data['list'])) {
                Log::warning('No gold price data received from API');

                return null;
            }

            // 최신 데이터만 저장
            $latestItem = $data['list'][0];

            $metalPrice = DomesticMetalPrice::updateOrCreate(
                ['price_date' => $latestItem['date']],
                [
                    'p_pure'     => $latestItem['p_pure'],
                    's_pure'     => $latestItem['s_pure'],
                    'p_18k'      => $latestItem['p_18k'],
                    's_18k'      => $latestItem['s_18k'],
                    'p_14k'      => $latestItem['p_14k'],
                    's_14k'      => $latestItem['s_14k'],
                    'p_platinum' => $latestItem['p_white'] ?? null,
                    's_platinum' => $latestItem['s_white'] ?? null,
                    'p_silver'   => $latestItem['p_silver'] ?? null,
                    's_silver'   => $latestItem['s_silver'] ?? null,
                ]
            );

            // 캐시 클리어
            Cache::forget('gold_price_per_gram');

            Log::info('Successfully fetched and saved latest gold price', [
                'price_date' => $metalPrice->price_date,
                's_pure'     => $metalPrice->s_pure,
            ]);

            return $metalPrice;
        } catch (\Exception $e) {
            Log::error('Error fetching gold price from API', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * 0.001g에 해당하는 금액 계산
     *
     * @return array{
     *     price_per_gram: float,
     *     price_per_0_001g: float,
     *     unit: string,
     *     updated_at: string
     * }
     */
    public function getSmallUnitPrice(): array
    {
        $pricePerGram  = $this->getCurrentGoldPrice();
        $pricePer0001g = round($pricePerGram * 0.001, 2);

        return [
            'price_per_gram'    => $pricePerGram,
            'price_per_0_001g'  => $pricePer0001g,
            'unit'              => 'KRW',
            'updated_at'        => now()->toIso8601String(),
        ];
    }

    /**
     * 특정 무게(g)에 해당하는 금액 계산
     */
    public function calculatePrice(float $grams): float
    {
        $pricePerGram = $this->getCurrentGoldPrice();

        return round($pricePerGram * $grams, 2);
    }
}
