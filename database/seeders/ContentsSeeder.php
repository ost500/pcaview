<?php

namespace Database\Seeders;

use App\Domain\department\BrightSound\BrightSoriCrawlService;
use App\Domain\department\BrightSound\BrightSound;
use App\Domain\department\MschJubo\JuboCrawlService;
use App\Domain\department\MschJubo\MschJubo;
use App\Models\Contents;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contents')->truncate();

        $bulletinCrawlService = app(JuboCrawlService::class);
        $brightSoriCrawlService = app(BrightSoriCrawlService::class);

        $brightSound = app(BrightSound::class);
        $brightSoriCrawlService->crawl($brightSound);

        $mschJubo = app(MschJubo::class);
        $bulletinCrawlService->crawl($mschJubo);
    }
}
