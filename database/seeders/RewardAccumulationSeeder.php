<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardAccumulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $goldnity = Application::where('code', 'GOLDNITY')->first();
        $ytplayer = Application::where('code', 'YTPLAYER')->first();

        // GOLDNITY 앱 적립 리워드
        if ($goldnity) {
            Reward::updateOrCreate(
                ['code' => 'mining', 'type' => 'accumulation'],
                [
                    'application_id'  => $goldnity->id,
                    'name'            => '마이닝 적립',
                    'description'     => '마이닝 1회당 적립되는 포인트',
                    'points_required' => 10,
                    'is_active'       => true,
                ]
            );
        }

        // YTPlayer 앱 적립 리워드
        if ($ytplayer) {
            Reward::updateOrCreate(
                ['code' => 'watch', 'type' => 'accumulation'],
                [
                    'application_id'  => $ytplayer->id,
                    'name'            => '비디오 시청 적립',
                    'description'     => '1분당 적립되는 포인트',
                    'points_required' => 10,
                    'is_active'       => true,
                ]
            );

            Reward::updateOrCreate(
                ['code' => 'ad', 'type' => 'accumulation'],
                [
                    'application_id'  => $ytplayer->id,
                    'name'            => '광고 시청 적립',
                    'description'     => '광고 시청 1회당 적립되는 포인트',
                    'points_required' => 50,
                    'is_active'       => true,
                ]
            );

            Reward::updateOrCreate(
                ['code' => 'share', 'type' => 'accumulation'],
                [
                    'application_id'  => $ytplayer->id,
                    'name'            => '공유 적립',
                    'description'     => '공유 1회당 적립되는 포인트',
                    'points_required' => 100,
                    'is_active'       => true,
                ]
            );
        }
    }
}
