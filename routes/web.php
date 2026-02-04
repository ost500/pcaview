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

// AI 이미지 생성 테스트 라우트 (dev only)
if (app()->environment('local', 'development')) {
    Route::get('/test/ai-image', function () {
        $aiService = app(\App\Domain\ai\AiApiService::class);

        $title = '테스트 뉴스: 한국의 아름다운 봄 풍경';
        $body = '오늘 서울에서는 벚꽃이 만개하여 많은 시민들이 봄나들이를 즐겼습니다. 여의도 윤중로에는 벚꽃축제가 열려 가족 단위 방문객들로 붐볐습니다.';

        try {
            $imageUrl = $aiService->generateCheapNewsImage($title, $body);

            if ($imageUrl) {
                // Base64 이미지인 경우 직접 표시
                if (str_starts_with($imageUrl, 'data:image/')) {
                    return response()->make(base64_decode(explode(',', $imageUrl)[1]), 200, [
                        'Content-Type' => 'image/png',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'AI 이미지 생성 성공',
                    'image_url' => $imageUrl,
                    'image_length' => strlen($imageUrl),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'AI 이미지 생성 실패 - null 반환',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI 이미지 생성 예외 발생',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    })->name('test.ai.image');
}

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
