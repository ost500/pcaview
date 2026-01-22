<?php

use App\Http\Controllers\Api\ContentsController;
use Illuminate\Support\Facades\Route;

Route::get('/symlink', function () {
    return redirect('https://link.coupang.com/a/dmWLqr');
});

Route::get('/c/{church}', [ContentsController::class, 'getByChurch']);
Route::get('/c/{church}/departments', [ContentsController::class, 'getDepartments']);
