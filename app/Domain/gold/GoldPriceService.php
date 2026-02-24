<?php

declare(strict_types=1);

namespace App\Domain\gold;

use App\Models\DomesticMetalPrice;
use Illuminate\Support\Facades\Cache;

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
