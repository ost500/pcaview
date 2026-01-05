<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('trends:fetch')->hourly();
Schedule::command('church:fetch')->hourly();

// 금 시세 자동 업데이트 - 매일 오전 9시
Schedule::command('gold:fetch')->dailyAt('09:00');
