<?php

namespace App\Console\Commands;

use App\Events\TrendFetched;
use App\Models\Church;
use App\Models\Department;
use App\Models\Trend;
use Illuminate\Console\Command;

class FetchAllDepartmentTrends extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trends:fetch-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch trends for all churches and their departments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fetch trends for all churches and departments...');

        // 모든 church 가져오기
        $churches = Church::with('departments')->get();

        $totalDepartments = 0;
        $totalEvents = 0;

        foreach ($churches as $church) {
            $this->info("Processing church: {$church->name}");

            $departments = $church->departments;

            if ($departments->isEmpty()) {
                $this->warn("  No departments found for {$church->name}");
                continue;
            }

            foreach ($departments as $department) {
                $totalDepartments++;

                // 해당 department의 최신 trend 가져오기
                $latestTrend = Trend::where('department_id', $department->id)
                    ->latest('pub_date')
                    ->first();

                if ($latestTrend) {
                    $this->line("  Dispatching TrendFetched for department: {$department->name}");
                    TrendFetched::dispatch($latestTrend);
                    $totalEvents++;
                } else {
                    $this->warn("  No trends found for department: {$department->name}");
                }
            }
        }

        $this->newLine();
        $this->info("✓ Completed!");
        $this->info("  Total churches processed: {$churches->count()}");
        $this->info("  Total departments processed: {$totalDepartments}");
        $this->info("  Total TrendFetched events dispatched: {$totalEvents}");

        return Command::SUCCESS;
    }
}
