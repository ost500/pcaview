<?php

namespace App\Console\Commands;

use App\Models\GoldPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchGoldPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold:fetch {--days=7 : Number of days to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch gold prices from Korea Gold Exchange API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 데이터베이스에 데이터가 있는지 확인
        $hasData = GoldPrice::exists();

        // 데이터가 없으면 1년치, 있으면 지정된 일수만큼 가져오기
        if (! $hasData) {
            $days = 365;
            $this->info('🎯 First time fetch: Getting 1 year of gold price data...');
        } else {
            $days = $this->option('days');
            $this->info("Fetching gold prices for the last {$days} days...");
        }

        // 날짜 계산
        $endDate = now();
        $startDate = now()->subDays($days);

        try {
            // API 호출
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.koreagoldx.co.kr/api/price/chart/list', [
                'srchDt' => $days.'D',
                'type' => 'Au',
                'dataDateStart' => $startDate->format('Y.m.d'),
                'dataDateEnd' => $endDate->format('Y.m.d'),
            ]);

            if (! $response->successful()) {
                $this->error('Failed to fetch gold prices from API');

                return 1;
            }

            $data = $response->json();

            if (! isset($data['list']) || empty($data['list'])) {
                $this->error('No data received from API');

                return 1;
            }

            // 데이터 저장
            $savedCount = 0;
            $skippedCount = 0;

            $bar = $this->output->createProgressBar(count($data['list']));
            $bar->start();

            foreach ($data['list'] as $item) {
                try {
                    GoldPrice::updateOrCreate(
                        ['price_date' => $item['date']],
                        [
                            'p_pure' => $item['p_pure'],
                            's_pure' => $item['s_pure'],
                            'p_18k' => $item['p_18k'],
                            's_18k' => $item['s_18k'],
                            'p_14k' => $item['p_14k'],
                            's_14k' => $item['s_14k'],
                            'p_white' => $item['p_white'] ?? null,
                            's_white' => $item['s_white'] ?? null,
                            'p_silver' => $item['p_silver'] ?? null,
                            's_silver' => $item['s_silver'] ?? null,
                        ]
                    );
                    $savedCount++;
                } catch (\Exception $e) {
                    $skippedCount++;
                    $this->warn("\nSkipped item: ".$e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✓ Successfully saved {$savedCount} gold price records");
            if ($skippedCount > 0) {
                $this->warn("⚠ Skipped {$skippedCount} records");
            }

            // 최신 가격 표시
            $latest = GoldPrice::getLatest();
            if ($latest) {
                $this->newLine();
                $this->info('Latest Gold Price:');
                $this->table(
                    ['Date', 'Pure Gold (Buy)', '18K (Buy)', '14K (Buy)'],
                    [[
                        $latest->price_date->format('Y-m-d H:i:s'),
                        number_format($latest->p_pure).'원',
                        number_format($latest->p_18k).'원',
                        number_format($latest->p_14k).'원',
                    ]]
                );
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
