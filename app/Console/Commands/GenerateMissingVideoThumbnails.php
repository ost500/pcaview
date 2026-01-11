<?php

namespace App\Console\Commands;

use App\Jobs\GenerateVideoThumbnail;
use App\Models\Contents;
use Illuminate\Console\Command;

class GenerateMissingVideoThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnails:generate-missing
                            {--church_id= : Filter by specific church ID}
                            {--limit= : Limit number of contents to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate thumbnails for video contents that are missing thumbnails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding video contents without thumbnails...');

        // 썸네일이 없는 video contents 찾기
        $query = Contents::where('file_type', 'video')
            ->whereNotNull('file_url')
            ->whereNull('thumbnail_url');

        // Church 필터 (옵션)
        if ($churchId = $this->option('church_id')) {
            $query->where('church_id', $churchId);
        }

        // Limit (옵션)
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $contents = $query->get();

        if ($contents->isEmpty()) {
            $this->info('No video contents found without thumbnails.');

            return Command::SUCCESS;
        }

        $this->info("Found {$contents->count()} video contents without thumbnails.");

        $bar = $this->output->createProgressBar($contents->count());
        $bar->start();

        foreach ($contents as $content) {
            GenerateVideoThumbnail::dispatch($content);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Thumbnail generation jobs have been dispatched to the queue.');
        $this->info('Run "php artisan queue:work" to process them.');

        return Command::SUCCESS;
    }
}
