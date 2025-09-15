<?php

namespace App\Domain\church\msch;

use App\Domain\church\DepartmentInterface;
use App\Domain\church\ChurchNewsInterface;
use App\Models\Department;
use Carbon\Carbon;

class MSCH implements DepartmentInterface, ChurchNewsInterface
{
    public function bulletinUrl(): string
    {
        return "https://app.msch.or.kr/data/1_jubo/";
    }

    public function getDepartmentId(): int
    {
        $department = Department::where('name', 'MSCH')->first();
        if (!$department) {
            $department = Department::create(['name' => 'MSCH']);
        }
        return $department->id;
    }

    public function getNewsUrl(string $year): string
    {
        return "https://app.msch.or.kr/data/2_sori/{$year}";
    }

    public function getContentsTitle(MSCHContentsType $type, Carbon $date): string
    {
        $formattedDate = $date->format('Y년 n월 j일');

        if ($type == MSCHContentsType::BULLETIN) {
            return $formattedDate . " 주보";
        }
        if ($type == MSCHContentsType::NEWS) {
            return $formattedDate . " 밝은소리";
        }

        return $formattedDate . " 소식";
    }

}
