<?php

namespace App\Console\Commands;

use App\Domain\bulletin\BulletinCrawlService;
use App\Domain\church\MSCH;
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
        $bulletinCrawlService = app(BulletinCrawlService::class);
        $msch = app(Msch::class);
        $bulletinCrawlService->crawl($msch);
    }
}
