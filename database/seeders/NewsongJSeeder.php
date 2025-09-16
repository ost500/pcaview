<?php

namespace Database\Seeders;

use App\Domain\department\NewsongJ\NewsongJCrawlService;
use App\Domain\department\NewsongJ\NewsongJJubo;
use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewsongJSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newsongJCrawlService = app(NewsongJCrawlService::class);
        $newsongJ = app(NewsongJJubo::class);
        $newsongJCrawlService->crawl($newsongJ);
    }
}
