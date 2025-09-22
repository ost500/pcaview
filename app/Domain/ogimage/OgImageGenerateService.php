<?php

namespace App\Domain\ogimage;

use App\Models\Contents;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class OgImageGenerateService
{

    public function generate()
    {
        if (cache('OgImageGenerator')) {
            return;
        }

        cache(['OgImageGenerator', true]);

        $contents = Contents::latest()->first();
        $thumbNail = $contents->thumbnail_url;

        $manager = new ImageManager(
            new Driver()
        );

        if (filter_var($thumbNail, FILTER_VALIDATE_URL)) {
            $image = $manager->read(file_get_contents($thumbNail));
        } else {
            $image = $manager->read(public_path($thumbNail));
        }

        $image->scale(width: 800);
        $image->crop(width: 800, height: 400);

        $encoded = $image->toPng();

        $encoded->save(public_path('og_image.png'));

        cache()->forget('OgImageGenerator');
    }
}
