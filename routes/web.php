<?php

use App\Http\Controllers\ChurchController;
use App\Http\Controllers\ContentsController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/church', [ChurchController::class, 'index'])->name('church');
Route::get('/church/{id}', [ChurchController::class, 'show'])->name('church.show');
Route::get('/department', [DepartmentController::class, 'index'])->name('department');
Route::get('/department/{id}', [DepartmentController::class, 'show'])->name('department.show');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('contents/{id}', [ContentsController::class, 'show'])->name('contents.show');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
