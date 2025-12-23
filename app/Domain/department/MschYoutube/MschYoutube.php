<?php

namespace App\Domain\department\MschYoutube;

use App\Domain\department\DepartmentInterface;
use App\Models\Department;
use Carbon\Carbon;

class MschYoutube implements DepartmentInterface
{

    public function getModel(): Department
    {
        $departmentName = 'PCAview 유튜브';
        $department = Department::where('name', $departmentName)->first();
        if (!$department) {
            $department = Department::create([
                'name' => $departmentName,
                'icon_image' => "/image/msch_youtube.png"
            ]);
        }
        return $department;
    }

    public function contentsUrl(?string $path = null): string
    {
        return "";
    }

    public function contentsTitle(Carbon $date): string
    {
        return "";
    }
}
