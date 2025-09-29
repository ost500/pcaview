<?php

namespace App\Console\Commands;

use App\Domain\church\msch\MSCH;
use App\Domain\department\BrightSound\BrightSoriCrawlService;
use App\Domain\department\BrightSound\BrightSound;
use App\Domain\department\MschJubo\JuboCrawlService;
use App\Domain\department\MschJubo\MschJubo;
use App\Domain\department\MschYoutube\MschYoutube;
use App\Domain\department\MschYoutube\MschYoutubeCrawlService;
use App\Domain\department\NewsongJ\NewsongJCrawlService;
use App\Domain\department\NewsongJ\NewsongJJubo;
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
        $mschJubo = app(MschJubo::class);
        $bulletinCrawlService->crawl($mschJubo);

        $brightSoriCrawlService = app(BrightSoriCrawlService::class);
        $brightSound = app(BrightSound::class);
        $brightSoriCrawlService->crawl($brightSound);

        $newsongJCrawlService = app(NewsongJCrawlService::class);
        $newsongJ = app(NewsongJJubo::class);
        $newsongJCrawlService->crawl($newsongJ);

        $mschYoutube = app(MschYoutube::class);
        $mschYoutubeCrawler = app(MschYoutubeCrawlService::class);
        $mschYoutubeCrawler->crawl($mschYoutube);
    }
}
