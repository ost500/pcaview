<?php

namespace Database\Seeders;

use App\Domain\department\MschYoutube\MschYoutube;
use App\Domain\department\MschYoutube\MschYoutubeCrawlService;
use Illuminate\Database\Seeder;

class MschYoutubeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mschYoutube = app(MschYoutube::class);
        $mschYoutubeCrawler = app(MschYoutubeCrawlService::class);
        $mschYoutubeCrawler->crawl($mschYoutube);
    }
}
