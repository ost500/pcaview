<?php

use App\Http\Controllers\Admin\ChurchController;
use App\Http\Controllers\Admin\ContentsController;
use App\Http\Controllers\Admin\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');

    Route::get('/churches', [ChurchController::class, 'index'])->name('churches.index');
    Route::get('/churches/create', [ChurchController::class, 'create'])->name('churches.create');
    Route::post('/churches', [ChurchController::class, 'store'])->name('churches.store');
    Route::get('/churches/{church}/edit', [ChurchController::class, 'edit'])->name('churches.edit');
    Route::put('/churches/{church}', [ChurchController::class, 'update'])->name('churches.update');

    Route::get('/contents', [ContentsController::class, 'index'])->name('contents.index');
    Route::get('/contents/create', [ContentsController::class, 'create'])->name('contents.create');
    Route::post('/contents', [ContentsController::class, 'store'])->name('contents.store');
    Route::get('/contents/{content}/edit', [ContentsController::class, 'edit'])->name('contents.edit');
    Route::put('/contents/{content}', [ContentsController::class, 'update'])->name('contents.update');
    Route::delete('/contents/{content}', [ContentsController::class, 'destroy'])->name('contents.destroy');
});
