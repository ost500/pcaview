<?php

namespace App\Domain\church;

interface ChurchNewsInterface
{

    public function getNewsUrl(string $year): string;
}
