<?php

use App\Http\Controllers\Api\GoldPriceController;
use App\Http\Controllers\ChurchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentsController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/c/{church}', [HomeController::class, 'church'])->name('church.home');

Route::get('/church', [ChurchController::class, 'index'])->name('church');
Route::get('/church/{id}', [ChurchController::class, 'show'])->name('church.show');
Route::get('/department', [DepartmentController::class, 'index'])->name('department');
Route::get('/department/{id}', [DepartmentController::class, 'show'])->where('id', '[0-9]+')->name('department.show');
Route::get('/department/{keyword}', [DepartmentController::class, 'keyword'])->name('department.keyword');

Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile')->middleware(['auth']);
Route::post('/profile/subscribe', [App\Http\Controllers\ProfileController::class, 'toggleSubscription'])->middleware('auth')->name('profile.subscribe');
Route::post('/profile/photo', [App\Http\Controllers\ProfileController::class, 'updateProfilePhoto'])->middleware('auth')->name('profile.photo.update');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('contents/{id}', [ContentsController::class, 'show'])->name('contents.show');

// 모바일 전용 라우트 (헤더 없음)
Route::prefix('m')->name('mobile.')->group(function () {
    Route::get('/', [HomeController::class, 'mobileIndex'])->name('home');
    Route::get('/c/{church}', [HomeController::class, 'mobileChurch'])->name('church.home');
    Route::get('/church/{id}', [ChurchController::class, 'mobileShow'])->name('church.show');
    Route::get('/department/{id}', [DepartmentController::class, 'mobileShow'])->where('id', '[0-9]+')->name('department.show');
    Route::get('/contents/{id}', [ContentsController::class, 'mobileShow'])->name('contents.show');
});

Route::post('/contents/{content}/comments', [CommentController::class, 'store'])->name('comments.store');
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->middleware('auth')->name('comments.destroy');

Route::post('/feed', [FeedController::class, 'store'])->middleware('auth')->name('feed.store');

Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy-policy');

// Gold Price API routes
Route::prefix('api/gold')->group(function () {
    Route::get('/latest', [GoldPriceController::class, 'latest'])->name('api.gold.latest');
    Route::get('/history', [GoldPriceController::class, 'history'])->name('api.gold.history');
    Route::get('/statistics', [GoldPriceController::class, 'statistics'])->name('api.gold.statistics');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
