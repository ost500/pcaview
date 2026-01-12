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
use App\Domain\department\NFriends\NFriendsCrawlService;
use App\Domain\department\NFriends\NFriendsJubo;
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
        // MschJubo (PCAview)
        $mschJubo = app(MschJubo::class);
        if ($mschJubo->getModel()?->is_crawl) {
            $bulletinCrawlService = app(JuboCrawlService::class);
            $bulletinCrawlService->crawl($mschJubo);
            $this->info('Crawled: PCAview');
        } else {
            $this->info('Skipped: PCAview (is_crawl=false)');
        }

        // BrightSound
        $brightSound = app(BrightSound::class);
        if ($brightSound->getModel()?->is_crawl) {
            $brightSoriCrawlService = app(BrightSoriCrawlService::class);
            $brightSoriCrawlService->crawl($brightSound);
            $this->info('Crawled: BrightSound');
        } else {
            $this->info('Skipped: BrightSound (is_crawl=false)');
        }

        // NewsongJ
        $newsongJ = app(NewsongJJubo::class);
        if ($newsongJ->getModel()?->is_crawl) {
            $newsongJCrawlService = app(NewsongJCrawlService::class);
            $newsongJCrawlService->crawl($newsongJ);
            $this->info('Crawled: NewsongJ');
        } else {
            $this->info('Skipped: NewsongJ (is_crawl=false)');
        }

        // MschYoutube
        $mschYoutube = app(MschYoutube::class);
        if ($mschYoutube->getModel()?->is_crawl) {
            $mschYoutubeCrawler = app(MschYoutubeCrawlService::class);
            $mschYoutubeCrawler->crawl($mschYoutube);
            $this->info('Crawled: MschYoutube');
        } else {
            $this->info('Skipped: MschYoutube (is_crawl=false)');
        }

        // NFriends
        $nfriends = app(NFriendsJubo::class);
        if ($nfriends->getModel()?->is_crawl) {
            $nfriendsCrawlService = app(NFriendsCrawlService::class);
            $nfriendsCrawlService->crawl($nfriends);
            $this->info('Crawled: NFriends');
        } else {
            $this->info('Skipped: NFriends (is_crawl=false)');
        }
    }
}
