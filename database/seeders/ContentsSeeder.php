<?php

namespace Database\Seeders;

use App\Domain\department\BrightSound\BrightSoriCrawlService;
use App\Domain\department\BrightSound\BrightSound;
use App\Domain\department\MschJubo\JuboCrawlService;
use App\Domain\department\MschJubo\MschJubo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bulletinCrawlService = app(JuboCrawlService::class);
        $brightSoriCrawlService = app(BrightSoriCrawlService::class);

        $brightSound = app(BrightSound::class);
        $brightSoriCrawlService->crawl($brightSound);

        $mschJubo = app(MschJubo::class);
        $bulletinCrawlService->crawl($mschJubo);
    }
}
