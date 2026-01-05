<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('trends:fetch')->hourly();
Schedule::command('church:fetch')->hourly();

// 국내 금 시세 자동 업데이트 - 매일 오전 9시
Schedule::command('gold:fetch')->dailyAt('09:00');

// 국내 은 시세 자동 업데이트 - 매일 오전 9시 5분
Schedule::command('silver:fetch')->dailyAt('09:05');

// 국제 금 시세 자동 업데이트 - 매일 오전 9시 10분
Schedule::command('international:gold')->dailyAt('09:10');

// 국제 은 시세 자동 업데이트 - 매일 오전 9시 15분
Schedule::command('international:silver')->dailyAt('09:15');
