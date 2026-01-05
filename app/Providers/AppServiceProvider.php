<?php

namespace App\Providers;

use App\Events\TrendFetched;
use App\Listeners\FetchNateNewsForTrend;
use App\Listeners\FetchNaverNewsForTrend;
use App\Listeners\SyncDekricaTrendListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Kakao\KakaoExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
            FetchNateNewsForTrend::class
        );

        // Dekrica API 연동
        Event::listen(
            TrendFetched::class,
            SyncDekricaTrendListener::class
        );

        // Naver 뉴스 API 연동
        Event::listen(
            TrendFetched::class,
            FetchNaverNewsForTrend::class
        );

        // Kakao Socialite Provider
        Event::listen(
            SocialiteWasCalled::class,
            KakaoExtendSocialite::class.'@handle'
        );
    }
}
