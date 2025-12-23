<?php

namespace App\Domain\department\MschJubo;

use App\Domain\department\DepartmentInterface;
use App\Models\Department;
use Carbon\Carbon;

class MschJubo implements DepartmentInterface
{
    public function getModel(): Department
    {
        return Department::where('name', 'PCAview')->first();
    }

    public function contentsUrl(?string $path = null): string
    {
        return "https://app.msch.or.kr/data/1_jubo/{$path}";
    }

    public function contentsTitle(Carbon $date): string
    {
        $formattedDate = $date->format('Y년 n월 j일');

        return $formattedDate . " 주보";
    }
}
