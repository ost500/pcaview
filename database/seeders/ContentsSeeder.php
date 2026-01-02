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
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('contents')->truncate();
        DB::table('contents_images')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $bulletinCrawlService = app(JuboCrawlService::class);
        $brightSoriCrawlService = app(BrightSoriCrawlService::class);

        $brightSound = app(BrightSound::class);
        $brightSoriCrawlService->crawl($brightSound);

        $mschJubo = app(MschJubo::class);
        $bulletinCrawlService->crawl($mschJubo);
    }
}
