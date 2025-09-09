<?php

namespace App\Domain\church;

use App\Models\Department;

class MSCH implements ChurchInterface, ChurchNewsInterface
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
}
