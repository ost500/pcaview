<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
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
        // 로그인된 사용자가 guest 페이지 접근 시 profile로 리디렉션
        RedirectIfAuthenticated::redirectUsing(fn () => route('profile'));

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
    })
    ->withEvents(discover: [
        __DIR__.'/../app/Domain/*/*/event',
        __DIR__.'/../app/Domain/*/*/Events',
        __DIR__.'/../app/Domain/*/Events'])
    ->withExceptions(function (Exceptions $exceptions) {
        // API 라우트에서 인증 실패 시 JSON 응답 반환
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            return $request->is('api/*');
        });

        // API에서 인증되지 않은 요청 처리
        $exceptions->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });
    })->create();
