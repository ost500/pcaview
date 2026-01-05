<?php

namespace App\Console\Commands;

use App\Models\DomesticMetalPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchHistoricalGoldPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gold:fetch-historical {--years=1 : Number of years to fetch before oldest data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch historical gold prices from before the oldest existing data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 가장 오래된 데이터 조회
        $oldest = DomesticMetalPrice::orderBy('price_date', 'asc')->first();

        if (! $oldest) {
            $this->error('No existing data found. Run "gold:fetch" first to get initial data.');

            return 1;
        }

        $years = $this->option('years');
        $this->info("📜 Fetching {$years} year(s) of historical data before {$oldest->price_date->format('Y-m-d')}");

        // 가장 오래된 데이터보다 1년 전까지의 데이터 가져오기
        $endDate = $oldest->price_date->copy()->subDay(); // 가장 오래된 데이터 하루 전
        $startDate = $endDate->copy()->subYears($years);

        $days = $startDate->diffInDays($endDate);

        $this->info("Date range: {$startDate->format('Y-m-d')} ~ {$endDate->format('Y-m-d')} ({$days} days)");

        try {
            // API 호출
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.koreagoldx.co.kr/api/price/chart/list', [
                'srchDt' => $years.'Y',
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
                    DomesticMetalPrice::updateOrCreate(
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

            $this->info("✓ Successfully saved {$savedCount} historical gold price records");
            if ($skippedCount > 0) {
                $this->warn("⚠ Skipped {$skippedCount} records");
            }

            // 현재 가장 오래된 데이터 표시
            $newOldest = DomesticMetalPrice::orderBy('price_date', 'asc')->first();
            if ($newOldest) {
                $this->newLine();
                $this->info('Oldest Gold Price in Database:');
                $this->table(
                    ['Date', 'Pure Gold (Buy)', '18K (Buy)', '14K (Buy)'],
                    [[
                        $newOldest->price_date->format('Y-m-d H:i:s'),
                        number_format($newOldest->p_pure).'원',
                        number_format($newOldest->p_18k).'원',
                        number_format($newOldest->p_14k).'원',
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
