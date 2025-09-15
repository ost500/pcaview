<?php

namespace App\Console\Commands;

use App\Domain\contents\ContentsImageService;
use App\Models\Contents;
use Illuminate\Console\Command;

class ContentsImageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contents:image';

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
        $contentsImageService= app(ContentsImageService::class);

        $contents = Contents::find(20);

        $contentsImageService->getImagesFromPdf($contents);
    }
}
