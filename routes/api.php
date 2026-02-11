<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ContentsController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Auth\KakaoController;
use Illuminate\Support\Facades\Route;

Route::get('/symlink', function () {
    return redirect('https://link.coupang.com/a/dmWLqr');
});

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->middleware('auth:sanctum');
Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Kakao OAuth for API (mobile app)
Route::post('/auth/kakao/callback', [KakaoController::class, 'apiCallback']);

Route::get('/feed', [FeedController::class, 'index']);

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
    Route::get('/{id}', [App\Http\Controllers\Api\ParkGolfCourseController::class, 'show']);

    // Home screen (Sanctum authentication required)
    Route::middleware('auth:sanctum')->get('/home', [App\Http\Controllers\Api\RecordController::class, 'home']);

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
