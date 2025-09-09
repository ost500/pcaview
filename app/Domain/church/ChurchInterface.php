<?php

namespace App\Domain\church;

use phpDocumentor\Reflection\Types\Integer;

interface ChurchInterface
{
    public function bulletinUrl(): string;

    public function getDepartmentId(): int;
}
