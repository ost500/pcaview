<?php

namespace App\Domain\department;

use App\Models\Department;
use Carbon\Carbon;

interface DepartmentInterface
{
    public function getModel(): Department;

    public function contentsUrl(?string $path = null): string;

    public function contentsTitle(Carbon $date): string;
}
