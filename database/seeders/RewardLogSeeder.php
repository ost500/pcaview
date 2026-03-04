<?php

namespace Database\Seeders;

use App\Models\DomesticMetalPrice;
use App\Models\RewardBalance;
use App\Models\RewardLog;
use Illuminate\Database\Seeder;

class RewardLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 테스트용 암호화된 사용자 식별자
        $encrypted = 'test_user_'.bin2hex(random_bytes(8));

        $this->command->info("Creating reward logs for encrypted: {$encrypted}");

        // 사용자 리워드 잔액 초기화
        $userReward = RewardBalance::firstOrCreate(
            ['encrypted' => $encrypted],
            [
                'balance'      => 0,
                'total_earned' => 0,
                'total_spent'  => 0,
            ]
        );

        // 30일간 데이터 생성
        $startDate      = now()->subDays(30)->startOfDay();
        $currentBalance = 0;

        for ($day = 0; $day < 30; $day++) {
            $date = $startDate->copy()->addDays($day);

            // 해당 날짜의 금 시세 조회 (없으면 생성)
            $goldPrice = DomesticMetalPrice::whereDate('price_date', $date->format('Y-m-d'))->first();

            if (! $goldPrice) {
                // 금 시세가 없으면 임의의 값으로 생성 (한돈 기준 85,000 ~ 90,000원)
                $sPure     = rand(85000, 90000);
                $goldPrice = DomesticMetalPrice::create([
                    'price_date'  => $date,
                    'p_pure'      => $sPure - 1000,
                    's_pure'      => $sPure,
                    'p_18k'       => intval($sPure * 0.75 - 500),
                    's_18k'       => intval($sPure * 0.75),
                    'p_14k'       => intval($sPure * 0.58 - 500),
                    's_14k'       => intval($sPure * 0.58),
                    'p_platinum'  => rand(40000, 45000),
                    's_platinum'  => rand(45000, 50000),
                    'p_silver'    => rand(1000, 1200),
                    's_silver'    => rand(1200, 1400),
                ]);
            }

            $goldPricePerGram = $goldPrice->s_pure / 3.75;

            // 하루에 3-7번 리워드 적립 (랜덤)
            $rewardCount = rand(3, 7);

            for ($i = 0; $i < $rewardCount; $i++) {
                // mining과 mining_screen만 사용
                $whereValues = ['mining', 'mining_screen'];
                $whereValue  = $whereValues[array_rand($whereValues)];

                $pointsEarned  = rand(10, 50) / 10; // 1-5 포인트
                $beforeBalance = $currentBalance;
                $currentBalance += $pointsEarned;

                // 포인트와 잔액의 금 가치 계산
                $pointsValue       = $goldPricePerGram > 0 ? $pointsEarned * $goldPricePerGram : 0;
                $afterBalanceValue = $goldPricePerGram > 0 ? $currentBalance * $goldPricePerGram : 0;

                // 하루 중 랜덤 시간에 적립
                $timestamp = $date->copy()->addHours(rand(6, 23))->addMinutes(rand(0, 59));

                RewardLog::create([
                    'encrypted'               => $encrypted,
                    'reward_type'             => 'mining',
                    'where'                   => $whereValue,
                    'video_url'               => null,
                    'video_time'              => null,
                    'points_earned'           => $pointsEarned,
                    'points_value'            => $pointsValue,
                    'before_balance'          => $beforeBalance,
                    'after_balance'           => $currentBalance,
                    'after_balance_value'     => $afterBalanceValue,
                    'metal_domestic_price_id' => $goldPrice?->id,
                    'created_at'              => $timestamp,
                    'updated_at'              => $timestamp,
                ]);
            }
        }

        // 사용자 잔액 업데이트
        $userReward->update([
            'balance'      => $currentBalance,
            'total_earned' => $currentBalance,
        ]);

        $this->command->info('✓ Created 30 days of reward logs');
        $this->command->info("✓ Total balance: {$currentBalance}");
        $this->command->info("✓ Encrypted: {$encrypted}");
        $this->command->newLine();
        $this->command->warn('📊 Test the chart API with:');
        $this->command->line("GET /api/ytplayer/reward_chart?encrypted={$encrypted}&days=30");
    }
}
