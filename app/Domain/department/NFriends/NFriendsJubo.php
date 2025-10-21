<?php

namespace App\Domain\department\NFriends;

use App\Domain\department\DepartmentInterface;
use App\Models\Department;
use Carbon\Carbon;

class NFriendsJubo implements DepartmentInterface
{
    public function name()
    {
        return 'NFriends 교회학교';
    }

    public function getModel(): Department
    {
        $department = Department::where('name', $this->name())->first();
        if ($department) {
            return $department;
        }

        return Department::create([
            'name' => $this->name(),
            'icon_image' => '/image/nfriends.png'
        ]);
    }

    public function contentsUrl(?string $path = null): string
    {
        return 'https://nfriends.or.kr/news/?page=1';
    }

    public function contentsTitle(Carbon $date): string
    {
        $formattedDate = $date->format('y.m.d');

        return $formattedDate . " NFriends 교회학교 주보";
    }
}
