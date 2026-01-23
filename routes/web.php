<?php

use App\Http\Controllers\Api\GoldPriceController;
use App\Http\Controllers\ChurchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentsController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SymlinkController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/symlink', [SymlinkController::class, 'track'])->name('symlink.track');
Route::get('/c/{church}', [HomeController::class, 'church'])->name('church.home');

Route::get('/church', [ChurchController::class, 'index'])->name('church');
Route::get('/church/{id}', [ChurchController::class, 'show'])->name('church.show');
Route::get('/department', [DepartmentController::class, 'index'])->name('department');
Route::get('/department/{id}', [DepartmentController::class, 'show'])->where('id', '[0-9]+')->name('department.show');
Route::get('/department/{keyword}', [DepartmentController::class, 'keyword'])->name('department.keyword');
Route::post('/department', [DepartmentController::class, 'store'])->middleware('auth')->name('department.store');

Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
Route::post('/profile/subscribe', [App\Http\Controllers\ProfileController::class, 'toggleSubscription'])->middleware('auth')->name('profile.subscribe');
Route::post('/profile/photo', [App\Http\Controllers\ProfileController::class, 'updateProfilePhoto'])->middleware('auth')->name('profile.photo.update');
Route::post('/profile/delete', [App\Http\Controllers\ProfileController::class, 'destroy'])->middleware('auth')->name('profile.delete');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('contents/{id}', [ContentsController::class, 'show'])->name('contents.show');
Route::post('contents/{id}/delete', [ContentsController::class, 'destroy'])->middleware('auth')->name('contents.destroy');

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

// Church Contents API routes
Route::prefix('api/church')->group(function () {
    Route::get('/{churchSlug}/contents', [App\Http\Controllers\Api\ChurchContentsController::class, 'index'])->name('api.church.contents');
    Route::get('/{churchSlug}/videos', [App\Http\Controllers\Api\ChurchContentsController::class, 'videos'])->name('api.church.videos');
    Route::get('/id/{churchId}/contents', [App\Http\Controllers\Api\ChurchContentsController::class, 'byId'])->name('api.church.contents.byId');
    Route::get('/id/{churchId}/videos', [App\Http\Controllers\Api\ChurchContentsController::class, 'videosByChurchId'])->name('api.church.videos.byId');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
