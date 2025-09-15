<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChurchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Church::create([
            'name' => '명성교회',
            'icon_url' => '/image/msch.webp',
            'logo_url' => 'http://www.msch.or.kr/kor22/1_main/images_v23/main_logo_new_v20120226.jpg',
            'address' => '서울시 강동구 구천면로 452',
            'address_url' => 'http://www.msch.or.kr/kor22/1_main/images_v23/img_60_popup02.png'
        ]);
    }
}
