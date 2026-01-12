<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetNaverApiQuota extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'naver:reset-quota
                            {--date= : Reset quota for specific date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Naver API quota exceeded flag (for testing or manual reset)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ?: now()->format('Y-m-d');
        $cacheKey = 'naver_api_quota_exceeded_'.$date;

        $wasCached = Cache::has($cacheKey);

        if ($wasCached) {
            Cache::forget($cacheKey);
            $this->info("✓ Naver API quota flag cleared for {$date}");
            $this->line("  Naver news search is now enabled again.");
        } else {
            $this->info("No quota limit was set for {$date}");
        }

        return Command::SUCCESS;
    }
}
