<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departments')->truncate();

        Department::create([
            'name' => '밝은소리',
            'icon_image' => '/image/bright_sori_icon.png'
        ]);

        Department::create([
            'name' => '명성교회 주보',
            'icon_image' => '/image/msch.webp'
        ]);
    }
}
