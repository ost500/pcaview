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
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('departments')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get the first church (PCAview)
        $church = \App\Models\Church::first();

        if (!$church) {
            $this->command->error('No church found. Please run ChurchSeeder first.');
            return;
        }

        Department::create([
            'church_id' => $church->id,
            'name' => 'PCAview',
            'icon_image' => '/image/msch.webp'
        ]);

        Department::create([
            'church_id' => $church->id,
            'name' => '밝은소리',
            'icon_image' => '/image/bright_sori_icon.png'
        ]);

        Department::create([
            'church_id' => $church->id,
            'name' => '뉴송J 청년부',
            'icon_image' => '/image/newsongj.png'
        ]);

        Department::create([
            'church_id' => $church->id,
            'name' => 'PCAview 유튜브',
            'icon_image' => '/image/msch_youtube.png'
        ]);

        Department::create([
            'church_id' => $church->id,
            'name' => 'NFriends',
            'icon_image' => '/image/nfriends.png'
        ]);
    }
}
