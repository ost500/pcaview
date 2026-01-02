<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChurchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('churches')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Church::create([
            'name' => 'PCAview',
            'slug' => 'pcaview',
            'icon_url' => '/image/msch.webp',
            'logo_url' => '/image/msch_logo.jpg',
            'worship_time_image' => '/image/worship_time_image.png',
            'address' => '서울시 강동구 구천면로 452',
            'address_url' => '/image/msch_address.png'
        ]);
    }
}
