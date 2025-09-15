<?php

namespace App\Domain\department;

use App\Models\Department;
use Carbon\Carbon;

interface DepartmentInterface
{
    public function getModel(): Department;

    public function contentsUrl(string $path): string;

    public function contentsTile(Carbon $date): string;
}
