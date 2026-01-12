<?php

namespace App\Console\Commands;

use App\Events\TrendFetched;
use App\Models\Church;
use App\Models\Department;
use App\Models\Trend;
use Illuminate\Console\Command;

class FetchChurchData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'church:fetch
                            {--hours=1 : Skip trends fetched within this many hours}
                            {--force : Force fetch all trends regardless of last fetch time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch data for all churches and their departments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $force = $this->option('force');

        $this->info('Starting to fetch trends for all churches and departments...');

        if ($force) {
            $this->warn('Force mode enabled: All trends will be processed');
        } else {
            $this->info("Skipping trends fetched within the last {$hours} hour(s)");
        }

        // 모든 church 가져오기
        $churches = Church::with('departments')->get();

        $totalDepartments = 0;
        $totalEvents = 0;
        $skippedCount = 0;

        foreach ($churches as $church) {
            $this->info("Processing church: {$church->name}");

            $departments = $church->departments->reverse();

            if ($departments->isEmpty()) {
                $this->warn("  No departments found for {$church->name}");
                continue;
            }

            foreach ($departments as $department) {
                $totalDepartments++;

                // is_crawl이 false이면 스킵
                if (!$department->is_crawl) {
                    $this->comment("  Skipping {$department->name} (is_crawl=false)");
                    $skippedCount++;
                    continue;
                }

                // 해당 department의 최신 trend 가져오기
                $latestTrend = Trend::where('department_id', $department->id)
                    ->latest('pub_date')
                    ->first();

                if (!$latestTrend) {
                    $this->info("  No trends found for department: {$department->name}, creating initial trend...");

                    // 기본 Trend 생성
                    $latestTrend = Trend::create([
                        'department_id' => $department->id,
                        'title' => $department->name,
                        'description' => $department->name,
                        'link' => $department->url ?? '#',
                        'image_url' => $department->icon_image ?? '/pcaview_icon.png',
                        'traffic_count' => 0,
                        'pub_date' => now(),
                        'picture' => null,
                        'picture_source' => null,
                        'news_items' => [],
                        'last_fetched_at' => null,
                    ]);

                    $this->line("  ✓ Created initial trend for {$department->name}");
                }

                // Force 모드가 아니면 최근에 처리된 trend는 건너뛰기
                if (!$force && $latestTrend->last_fetched_at) {
                    $hoursSinceLastFetch = $latestTrend->last_fetched_at->diffInHours(now());

                    if ($hoursSinceLastFetch < $hours) {
                        $this->comment("  Skipping {$department->name} (last fetched {$hoursSinceLastFetch}h ago)");
                        $skippedCount++;
                        continue;
                    }
                }

                $this->line("  Dispatching TrendFetched for department: {$department->name}");
                TrendFetched::dispatch($latestTrend);

                // last_fetched_at 업데이트
                $latestTrend->update(['last_fetched_at' => now()]);

                $totalEvents++;
            }
        }

        $this->newLine();
        $this->info("✓ Completed!");
        $this->info("  Total churches processed: {$churches->count()}");
        $this->info("  Total departments processed: {$totalDepartments}");
        $this->info("  TrendFetched events dispatched: {$totalEvents}");
        $this->info("  Trends skipped (recently fetched): {$skippedCount}");

        return Command::SUCCESS;
    }
}
