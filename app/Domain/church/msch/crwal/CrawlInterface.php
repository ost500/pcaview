<?php

namespace App\Domain\church\msch\crwal;

use App\Domain\church\ChurchInterface;

interface CrawlInterface
{
    public function crawl(ChurchInterface $church);
}
