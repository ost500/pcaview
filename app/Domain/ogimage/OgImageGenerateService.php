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

        $image = $manager->read(file_get_contents($thumbNail));

        $image->scale(width: 800);
        $image->crop(width: 800, height: 400);

        $encoded = $image->toPng();

        // S3에 OG 이미지 저장
        \Illuminate\Support\Facades\Storage::put('og_images/og_image.png', (string) $encoded);

        cache()->forget('OgImageGenerator');
    }
}
