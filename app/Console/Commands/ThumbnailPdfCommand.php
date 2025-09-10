<?php

namespace App\Console\Commands;

use App\Domain\contents\ThumbnailService;
use App\Models\Contents;
use Illuminate\Console\Command;

class ThumbnailPdfCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:thumbnail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $thumbnailService = app(ThumbnailService::class);
        $contents = Contents::find(10);
        $thumbnailService->getPdfThumbnail($contents);
    }
}
