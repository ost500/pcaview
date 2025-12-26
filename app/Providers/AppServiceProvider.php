<?php

namespace App\Providers;

use App\Events\TrendFetched;
use App\Listeners\FetchNateNewsForTrend;
use App\Listeners\FetchNaverNewsForTrend;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 이벤트-리스너 등록
        Event::listen(
            TrendFetched::class,
            [
                FetchNateNewsForTrend::class,
                FetchNaverNewsForTrend::class,
            ]
        );
    }
}
