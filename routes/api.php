<?php

use App\Http\Controllers\Api\ContentsController;
use App\Http\Controllers\Api\FeedController;
use Illuminate\Support\Facades\Route;

Route::get('/symlink', function () {
    return redirect('https://link.coupang.com/a/dmWLqr');
});

Route::get('/feed', [FeedController::class, 'index']);

Route::get('/c/{church}', [ContentsController::class, 'getByChurch']);
Route::get('/c/{church}/departments', [ContentsController::class, 'getDepartments']);
