<?php

namespace App\Domain\church\msch\crwal;

use App\Domain\church\DepartmentInterface;

interface CrawlInterface
{
    public function crawl(DepartmentInterface $church);
}
