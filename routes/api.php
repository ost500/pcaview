<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ContentsController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RewardProductController;
use App\Http\Controllers\Auth\KakaoController;
use Illuminate\Support\Facades\Route;

Route::get('/symlink', function () {
    return redirect('https://link.coupang.com/a/dYRxKS');
});

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->middleware('auth:sanctum');
Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Kakao OAuth for API (mobile app)
Route::post('/auth/kakao/callback', [KakaoController::class, 'apiCallback']);

Route::get('/feed', [FeedController::class, 'index']);

// Notice routes
Route::get('/notices', [NoticeController::class, 'index']);
Route::get('/notices/all', [NoticeController::class, 'all'])->middleware('auth:sanctum');
Route::get('/notices/{notice}', [NoticeController::class, 'show']);
Route::post('/notices', [NoticeController::class, 'store'])->middleware('auth:sanctum');
Route::put('/notices/{notice}', [NoticeController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/notices/{notice}', [NoticeController::class, 'destroy'])->middleware('auth:sanctum');

Route::get('/c/{church}', [ContentsController::class, 'getByChurch']);
Route::get('/c/{church}/departments', [ContentsController::class, 'getDepartments']);
Route::get('/contents/{id}', [ContentsController::class, 'show']);
Route::delete('/contents/{id}', [ContentsController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/contents/{id}/delete', [ContentsController::class, 'destroy'])->middleware('auth:sanctum');

Route::post('/feed', [FeedController::class, 'store'])->middleware('auth:sanctum');

// Comment routes
Route::get('/contents/{contentId}/comments', [CommentController::class, 'index']);
Route::post('/contents/{contentId}/comments', [CommentController::class, 'store']);
Route::delete('/contents/{contentId}/comments/{commentId}', [CommentController::class, 'destroy']);

// Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::post('/profile/subscribe', [ProfileController::class, 'toggleSubscription']);
    Route::post('/profile/photo', [ProfileController::class, 'updateProfilePhoto']);
    Route::post('/profile/delete', [ProfileController::class, 'destroy']);
});

// Park Golf API routes
Route::prefix('parkgolf')->group(function () {
    // Course search and info (public)
    Route::get('/search', [App\Http\Controllers\Api\ParkGolfCourseController::class, 'search']);
    Route::get('/nearby', [App\Http\Controllers\Api\ParkGolfCourseController::class, 'nearby']);
    Route::get('/regions', [App\Http\Controllers\Api\ParkGolfCourseController::class, 'regions']);
    Route::get('/statistics', [App\Http\Controllers\Api\ParkGolfCourseController::class, 'statistics']);

    // Home screen (Sanctum authentication required) - Must be before /{id}
    Route::middleware('auth:sanctum')->get('/home', [App\Http\Controllers\Api\RecordController::class, 'home']);

    // Course detail - Keep this AFTER specific routes
    Route::get('/{id}', [App\Http\Controllers\Api\ParkGolfCourseController::class, 'show']);

    // Round API (Sanctum authentication required)
    Route::middleware('auth:sanctum')->prefix('rounds')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\RoundController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\RoundController::class, 'store']);
        Route::get('/{round}', [App\Http\Controllers\Api\RoundController::class, 'show']);
        Route::post('/{round}/complete', [App\Http\Controllers\Api\RoundController::class, 'complete']);
        Route::get('/{round}/scorecard', [App\Http\Controllers\Api\RoundController::class, 'scorecard']);
        Route::delete('/{round}', [App\Http\Controllers\Api\RoundController::class, 'destroy']);
    });

    // Record/Statistics API (Sanctum authentication required)
    Route::middleware('auth:sanctum')->prefix('records')->group(function () {
        Route::get('/statistics', [App\Http\Controllers\Api\RecordController::class, 'statistics']);
        Route::get('/rounds', [App\Http\Controllers\Api\RecordController::class, 'rounds']);
        Route::get('/monthly', [App\Http\Controllers\Api\RecordController::class, 'monthly']);
    });
});

// Symlink Visit API routes (토큰 인증 필요)
Route::prefix('symlink-visits')->middleware('api.token')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\SymlinkVisitController::class, 'index']);
    Route::get('/statistics', [App\Http\Controllers\Api\SymlinkVisitController::class, 'statistics']);
    Route::get('/count-by-ad', [App\Http\Controllers\Api\SymlinkVisitController::class, 'countByAdId']);
    Route::post('/', [App\Http\Controllers\Api\SymlinkVisitController::class, 'store']);
    Route::get('/{adId}', [App\Http\Controllers\Api\SymlinkVisitController::class, 'show']);
    Route::delete('/{adId}', [App\Http\Controllers\Api\SymlinkVisitController::class, 'destroy']);
});

// YTPlayer API routes
Route::prefix('ytplayer')->middleware('ytplayer.signature')->group(function () {
    // GET endpoints (서명 검증 생략)
    Route::get('/notice', [App\Http\Controllers\Api\YTPlayerController::class, 'notice']);
    Route::get('/rewards', [App\Http\Controllers\Api\YTPlayerController::class, 'rewards']);
    Route::get('/version_check', [App\Http\Controllers\Api\YTPlayerController::class, 'versionCheck']);
    Route::get('/balance', [App\Http\Controllers\Api\YTPlayerController::class, 'balance']); // 토큰 선택적
    Route::get('/reward/chart', [App\Http\Controllers\Api\RewardStatsController::class, 'rewardChart']);

    // Auth required endpoints (Sanctum token)
    Route::get('/reward/usages', [App\Http\Controllers\Api\YTPlayerController::class, 'rewardUsages'])->middleware('auth:sanctum');
    Route::get('/reward/usages/{id}', [App\Http\Controllers\Api\YTPlayerController::class, 'rewardUsageDetail'])->middleware('auth:sanctum');
    Route::patch('/reward/usages/{id}/phone', [App\Http\Controllers\Api\YTPlayerController::class, 'updateRewardUsagePhone'])->middleware('auth:sanctum');

    // POST endpoints (서명 검증 필수)
    Route::post('/reward', [App\Http\Controllers\Api\YTPlayerController::class, 'reward']);
    Route::post('/use_reward', [App\Http\Controllers\Api\YTPlayerController::class, 'useReward'])->middleware('auth:sanctum');
    Route::post('/install_count', [App\Http\Controllers\Api\YTPlayerController::class, 'installCount']);
    Route::post('/live_count', [App\Http\Controllers\Api\YTPlayerController::class, 'liveCount']);
});

// Reward Product API routes
Route::prefix('reward-products')->middleware('ytplayer.signature')->group(function () {
    Route::get('/', [RewardProductController::class, 'index']);
    Route::get('/categories', [RewardProductController::class, 'categories']);
    Route::get('/all', [RewardProductController::class, 'all'])->middleware('auth:sanctum');
    Route::get('/{rewardProduct}', [RewardProductController::class, 'show']);
    Route::post('/', [RewardProductController::class, 'store'])->middleware('auth:sanctum');
    Route::put('/{rewardProduct}', [RewardProductController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{rewardProduct}', [RewardProductController::class, 'destroy'])->middleware('auth:sanctum');
});
