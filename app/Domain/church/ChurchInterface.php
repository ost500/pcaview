<?php

namespace App\Domain\church;

use App\Domain\church\msch\MSCHContentsType;
use Carbon\Carbon;

interface ChurchInterface
{
    public function bulletinUrl(): string;

    public function getDepartmentId(): int;

    public function getContentsTitle(MSCHContentsType $type, Carbon $date): string;
}
