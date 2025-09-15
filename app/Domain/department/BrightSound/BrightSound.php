<?php

namespace App\Domain\department\BrightSound;

use App\Domain\department\DepartmentInterface;
use App\Models\Department;
use Carbon\Carbon;

class BrightSound implements DepartmentInterface
{
    public function getModel(): Department
    {
        return Department::where('name', '밝은소리')->first();
    }

    public function contentsUrl(string $path): string
    {
        return "https://app.msch.or.kr/data/2_sori/{$path}";
    }

    public function contentsTile(Carbon $date): string
    {
        $formattedDate = $date->format('Y년 n월 j일');

        return $formattedDate . " 밝은소리";
    }
}
