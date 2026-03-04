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

            // 해당 날짜의 금 시세 조회 (없으면 기본값 사용)
            $goldPrice        = DomesticMetalPrice::whereDate('price_date', $date->format('Y-m-d'))->first();
            $goldPricePerGram = $goldPrice?->s_pure ? $goldPrice->s_pure / 3.75 : 85000.0;

            // 하루에 3-7번 리워드 적립 (랜덤)
            $rewardCount = rand(3, 7);

            for ($i = 0; $i < $rewardCount; $i++) {
                // 리워드 타입별 포인트 (랜덤)
                $rewardTypes = [
                    ['type' => 'watch', 'points' => rand(50, 200) / 10],  // 5-20 포인트
                    ['type' => 'ad', 'points' => rand(30, 100) / 10],      // 3-10 포인트
                    ['type' => 'mining', 'points' => rand(10, 50) / 10],   // 1-5 포인트
                ];

                $reward        = $rewardTypes[array_rand($rewardTypes)];
                $pointsEarned  = $reward['points'];
                $beforeBalance = $currentBalance;
                $currentBalance += $pointsEarned;

                // 포인트와 잔액의 금 가치 계산
                $pointsValue       = $goldPricePerGram > 0 ? $pointsEarned * $goldPricePerGram : 0;
                $afterBalanceValue = $goldPricePerGram > 0 ? $currentBalance * $goldPricePerGram : 0;

                // 하루 중 랜덤 시간에 적립
                $timestamp = $date->copy()->addHours(rand(6, 23))->addMinutes(rand(0, 59));

                RewardLog::create([
                    'encrypted'               => $encrypted,
                    'reward_type'             => $reward['type'],
                    'where'                   => 'mobile_app',
                    'video_url'               => $reward['type'] === 'watch' ? 'https://youtube.com/watch?v='.bin2hex(random_bytes(5)) : null,
                    'video_time'              => $reward['type'] === 'watch' ? rand(60, 600) : null,
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
