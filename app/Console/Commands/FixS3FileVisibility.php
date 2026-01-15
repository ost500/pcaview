<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixS3FileVisibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:fix-visibility {path? : S3 path pattern to fix (e.g., thumbnails/)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix S3 file visibility to public for existing files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->argument('path') ?? '';

        $this->info("Fixing S3 file visibility for path: {$path}");

        $files = Storage::disk('s3')->allFiles($path);

        if (empty($files)) {
            $this->warn('No files found!');
            return 0;
        }

        $this->info('Found ' . count($files) . ' files. Starting...');

        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($files as $file) {
            try {
                Storage::disk('s3')->setVisibility($file, 'public');
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                $this->newLine();
                $this->error("Failed to set visibility for: {$file}");
                $this->error("Error: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Successfully updated: {$successCount} files");
        if ($failCount > 0) {
            $this->warn("Failed: {$failCount} files");
        }

        return 0;
    }
}
