<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applications = [
            [
                'name'        => 'GOLDNITY',
                'code'        => 'goldnity',
                'description' => 'GOLDNITY 앱 리워드 시스템',
                'is_active'   => true,
            ],
            [
                'name'        => 'YTPlayer',
                'code'        => 'ytplayer',
                'description' => 'YTPlayer 앱 리워드 시스템',
                'is_active'   => true,
            ],
        ];

        foreach ($applications as $application) {
            Application::firstOrCreate(
                ['code' => $application['code']],
                $application
            );
        }
    }
}
