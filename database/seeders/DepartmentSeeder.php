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
            'name' => 'PCAview',
            'icon_image' => '/image/msch.webp'
        ]);

        Department::create([
            'name' => '밝은소리',
            'icon_image' => '/image/bright_sori_icon.png'
        ]);

        Department::create([
            'name' => '뉴송J 청년부',
            'icon_image' => '/image/newsongj.png'
        ]);

        Department::create([
            'name' => 'PCAview 유튜브',
            'icon_image' => '/image/msch_youtube.png'
        ]);

        Department::create([
            'name' => 'NFriends',
            'icon_image' => '/image/nfriends.png'
        ]);
    }
}
