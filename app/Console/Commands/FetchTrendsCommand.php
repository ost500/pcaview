<?php

namespace App\Console\Commands;

use App\Domain\trend\GoogleTrendsService;
use Illuminate\Console\Command;

class FetchTrendsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trends:fetch {--show : Show fetched trends in table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and save Google Trends data to database';

    /**
     * Execute the console command.
     */
    public function handle(GoogleTrendsService $service): int
    {
        $this->info('🔄 Fetching Google Trends...');

        $count = $service->fetchAndSave();

        if ($count === 0) {
            $this->error('❌ Failed to fetch trends. Check logs for details.');
            return self::FAILURE;
        }

        $this->info("✅ Successfully saved {$count} trends to database");

        // --show 옵션이 있으면 테이블로 표시
        if ($this->option('show')) {
            $this->newLine();
            $trends = $service->getLatestFromDatabase(10);

            $this->table(
                ['ID', 'Title', 'Traffic', 'Pub Date'],
                $trends->map(fn($t) => [
                    $t->id,
                    mb_substr($t->title, 0, 40) . (mb_strlen($t->title) > 40 ? '...' : ''),
                    $t->traffic_count ?? 'N/A',
                    $t->pub_date->format('Y-m-d H:i'),
                ])
            );
        }

        return self::SUCCESS;
    }
}
