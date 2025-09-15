<?php

namespace App\Domain\contents;

use App\Models\Contents;
use App\Models\ContentsImage;

class ThumbnailService
{
    public function getPdfThumbnail(Contents $contents, ContentsImage $image)
    {
        $contents->update(['thumbnail_url' => $image->file_url]);
    }
}
