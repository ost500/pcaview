<?php

namespace App\Console\Commands;

use App\Models\Contents;
use App\Models\Department;
use Illuminate\Console\Command;

class SyncContentsChurchId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contents:sync-church {--department-id= : Sync only for specific department} {--dry-run : Preview changes without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync contents church_id from their associated departments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Syncing contents church_id from departments...');

        $dryRun       = $this->option('dry-run');
        $departmentId = $this->option('department-id');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get departments query
        $departmentsQuery = Department::query();

        if ($departmentId) {
            $departmentsQuery->where('id', $departmentId);
            $this->info("Filtering by department ID: {$departmentId}");
        }

        $departments = $departmentsQuery->get();

        if ($departments->isEmpty()) {
            $this->error('❌ No departments found');

            return self::FAILURE;
        }

        $totalUpdated = 0;
        $totalSkipped = 0;

        $this->withProgressBar($departments, function ($department) use (&$totalUpdated, &$totalSkipped, $dryRun) {
            // Get all contents associated with this department through pivot table
            $contents = Contents::whereHas('departments', function ($query) use ($department) {
                $query->where('department_id', $department->id);
            })->get();

            foreach ($contents as $content) {
                // Skip if church_id is already correct
                if ($content->church_id === $department->church_id) {
                    $totalSkipped++;

                    continue;
                }

                if (! $dryRun) {
                    $content->update(['church_id' => $department->church_id]);
                }

                $totalUpdated++;
            }
        });

        $this->newLine(2);

        // Show summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Updated', $totalUpdated],
                ['Total Skipped (already synced)', $totalSkipped],
                ['Total Departments Processed', $departments->count()],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info("✅ Successfully synced {$totalUpdated} contents with their department's church_id");
        }

        return self::SUCCESS;
    }
}
