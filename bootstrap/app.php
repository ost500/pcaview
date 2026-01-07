<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware([])->group(base_path('routes/sitemap.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 미들웨어 별칭 등록
        $middleware->alias([
            'admin' => AdminAuth::class,
        ]);

        // 로그인된 사용자가 로그인/회원가입 페이지 접근 시 profile로 리다이렉트
        $middleware->redirectUsersTo('/profile');
    })
    ->withEvents(discover: [
        __DIR__.'/../app/Domain/*/*/event',
        __DIR__.'/../app/Domain/*/*/Events',
        __DIR__.'/../app/Domain/*/Events'])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
