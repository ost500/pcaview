<?php

declare(strict_types=1);

namespace App\Domain\gold;

/**
 * GOLDNITY 앱 전용 서비스
 * 포인트를 금 gram으로 변환
 */
class GoldnityService
{
    public function __construct(
        private readonly GoldPriceService $goldPriceService
    ) {}

    /**
     * 포인트를 금 gram으로 변환
     *
     * @param  float $points 포인트 (1 포인트 = 1원)
     * @return float 금 gram
     */
    public function convertPointsToGold(float $points): float
    {
        $pricePerGram = $this->goldPriceService->getCurrentGoldPrice();

        if ($pricePerGram <= 0) {
            return 0.0;
        }

        // 포인트 = 원 단위 금액
        // 금 gram = 금액 / (1g 당 금 시세)
        $grams = $points / $pricePerGram;

        // 소수점 9자리까지 반올림
        return round($grams, 9);
    }

    /**
     * 리워드 데이터에 금 정보 추가
     */
    public function enrichRewardWithGoldInfo(array $reward): array
    {
        $pointsRequired = (float) $reward['points_required'];
        $goldGrams      = $this->convertPointsToGold($pointsRequired);

        return array_merge($reward, [
            'gold_info' => [
                'grams'           => $goldGrams,
                'formatted_grams' => number_format($goldGrams, 9),
                'unit'            => 'g',
            ],
        ]);
    }

    /**
     * 여러 리워드 데이터에 금 정보 추가
     */
    public function enrichRewardsWithGoldInfo(array $rewards): array
    {
        return array_map(
            fn ($reward) => $this->enrichRewardWithGoldInfo($reward),
            $rewards
        );
    }
}
