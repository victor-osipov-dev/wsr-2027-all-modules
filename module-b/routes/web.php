<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
Route::get('/', fn() => redirect()->route('admin.recipes.index'));
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('login', 'login')->middleware('guest')->name('login');
        Route::post('login', [AdminController::class, 'login'])
            ->middleware('guest')
            ->name('login.attempt');
        Route::post('logout', [AdminController::class, 'logout'])->name('logout');
        Route::redirect('/', '/admin/recipes')->middleware('auth');
        Route::patch('recipes/{recipe}/restore', [AdminController::class, 'restore'])
            ->middleware('auth')
            ->name('recipes.restore');
        Route::delete('recipes/{recipe}/force-delete', [AdminController::class, 'forceDelete'])
            ->middleware('auth')
            ->name('recipes.force-delete');
        Route::resource('recipes', AdminController::class)
            ->except(['show'])
            ->middleware('auth');
    });
