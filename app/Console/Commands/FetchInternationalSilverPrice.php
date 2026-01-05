<?php

namespace App\Console\Commands;

use App\Models\InternationalMetalPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchInternationalSilverPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'international:silver {--days=7 : Number of days to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch international silver prices from Korea Gold Exchange API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 데이터베이스에 데이터가 있는지 확인
        $hasData = InternationalMetalPrice::exists();

        // 데이터가 없으면 1년치, 있으면 지정된 일수만큼 가져오기
        if (! $hasData) {
            $days = 365;
            $this->info('🎯 First time fetch: Getting 1 year of international silver price data...');
        } else {
            $days = $this->option('days');
            $this->info("Fetching international silver prices for the last {$days} days...");
        }

        // 날짜 계산
        $endDate = now();
        $startDate = now()->subDays($days);

        try {
            // API 호출
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://www.koreagoldx.co.kr/api/price/market/period/list', [
                'srchDt' => $days.'D',
                'type' => 'Ag',
                'dataDateStart' => $startDate->format('Y.m.d'),
                'dataDateEnd' => $endDate->format('Y.m.d'),
            ]);

            if (! $response->successful()) {
                $this->error('Failed to fetch international silver prices from API');

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
                    // 국제 은 시세는 해당 날짜의 레코드를 업데이트 (silver_usd만)
                    // bid 가격을 USD 가격으로 사용
                    $existing = InternationalMetalPrice::where('price_date', $item['date'])->first();

                    if ($existing) {
                        // 기존 레코드가 있으면 은 가격만 업데이트
                        $existing->update([
                            'silver_usd' => $item['bid'] ?? null,
                        ]);
                    } else {
                        // 기존 레코드가 없으면 은 가격만으로 새 레코드 생성
                        InternationalMetalPrice::create([
                            'price_date' => $item['date'],
                            'gold_usd' => 0,
                            'silver_usd' => $item['bid'] ?? null,
                        ]);
                    }
                    $savedCount++;
                } catch (\Exception $e) {
                    $skippedCount++;
                    $this->warn("\nSkipped item: ".$e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✓ Successfully saved {$savedCount} international silver price records");
            if ($skippedCount > 0) {
                $this->warn("⚠ Skipped {$skippedCount} records");
            }

            // 최신 가격 표시
            $latest = InternationalMetalPrice::getLatest();
            if ($latest) {
                $this->newLine();
                $this->info('Latest International Silver Price:');
                $this->table(
                    ['Date', 'Silver (USD/oz)'],
                    [[
                        $latest->price_date->format('Y-m-d H:i:s'),
                        $latest->silver_usd ? '$'.number_format($latest->silver_usd, 2) : 'N/A',
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
