<?php

namespace App\Console\Commands;

use App\Domain\ogimage\OgImageGenerateService;
use Illuminate\Console\Command;

class OgImageGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ogimage:generate';

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
        $ogImageGenerator = app(OgImageGenerateService::class);
        $ogImageGenerator->generate();
    }
}
