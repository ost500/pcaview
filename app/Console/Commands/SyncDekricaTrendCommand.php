<?php

namespace App\Console\Commands;

use App\Jobs\SyncDekricaTrendData;
use Illuminate\Console\Command;

class SyncDekricaTrendCommand extends Command
{
    protected $signature = 'dekrica:sync {tag} {--department= : Department ID to assign}';

    protected $description = 'Sync trend data from Dekrica API';

    public function handle()
    {
        $tag = $this->argument('tag');
        $departmentId = $this->option('department') ? (int) $this->option('department') : null;

        $this->info("Syncing trend data for tag: {$tag}");

        try {
            // Dispatch job
            SyncDekricaTrendData::dispatch($tag, $departmentId);

            $this->info('Job dispatched successfully!');
            $this->info('Check logs for sync results.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to dispatch job: '.$e->getMessage());

            return 1;
        }
    }
}
