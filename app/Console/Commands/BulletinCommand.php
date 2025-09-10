<?php

namespace App\Console\Commands;

use App\Domain\church\msch\crwal\BrightSoriCrawlService;
use App\Domain\church\msch\crwal\JuboCrawlService;
use App\Domain\church\msch\MSCH;
use Illuminate\Console\Command;

class BulletinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:bulletin';

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
        $bulletinCrawlService = app(JuboCrawlService::class);
        $brightSoriCrawlService = app(BrightSoriCrawlService::class);

        $msch = app(Msch::class);

        $bulletinCrawlService->crawl($msch);
        $brightSoriCrawlService->crawl($msch);
    }
}
