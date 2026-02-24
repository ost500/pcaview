<?php

declare(strict_types=1);

namespace App\Domain\gold;

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
            // TODO: 실제 금 시세 API 연동
            // 예: 한국금거래소, MetalPriceAPI 등

            // 임시: 평균 금 시세 (1g 기준 약 85,000원)
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
