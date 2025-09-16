<?php

namespace App\Domain\department\NewsongJ;

use App\Domain\department\DepartmentInterface;
use App\Models\Department;
use Carbon\Carbon;

class NewsongJJubo implements DepartmentInterface
{
    public function name()
    {
        return '뉴송J';
    }

    public function getModel(): Department
    {
        $department = Department::where('name', $this->name())->first();
        if ($department) {
            return $department;
        }

        return Department::create([
            'name' => $this->name(),
            'icon_image' => '/image/newsongj.png'
        ]);
    }

    public function contentsUrl(?string $path = null): string
    {
        return 'https://pf.kakao.com/rocket-web/web/profiles/_Zxaxdsxb/posts?includePinnedPost=true';
    }

    public function contentsTitle(Carbon $date): string
    {
        $formattedDate = $date->format('Y년 n월 j일');

        return $formattedDate . " 주보";
    }
}
