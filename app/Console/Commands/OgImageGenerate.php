<?php

namespace App\Console\Commands;

use App\Models\Contents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

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
        $ogImageGenerator = app(OgImageGenerate::class);
        $ogImageGenerator->generate();
    }
}
