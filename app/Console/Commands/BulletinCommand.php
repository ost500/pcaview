<?php

namespace App\Console\Commands;

use App\Domain\church\msch\MSCH;
use App\Domain\department\BrightSound\BrightSoriCrawlService;
use App\Domain\department\BrightSound\BrightSound;
use App\Domain\department\MschJubo\JuboCrawlService;
use App\Domain\department\MschJubo\MschJubo;
use Illuminate\Console\Command;

class BulletinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contents:seed';

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

        $mschJubo = app(MschJubo::class);
        $bulletinCrawlService->crawl($mschJubo);

        $brightSound = app(BrightSound::class);
        $brightSoriCrawlService->crawl($brightSound);
    }
}
