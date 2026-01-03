<?php

use App\Http\Controllers\Admin\ChurchController;
use App\Http\Controllers\Admin\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');

    Route::get('/churches', [ChurchController::class, 'index'])->name('churches.index');
    Route::get('/churches/{church}/edit', [ChurchController::class, 'edit'])->name('churches.edit');
    Route::put('/churches/{church}', [ChurchController::class, 'update'])->name('churches.update');
});
