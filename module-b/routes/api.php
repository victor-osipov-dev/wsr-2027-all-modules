<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;
Route::post('/register', [ApiController::class, 'register'])->name('api.register');
Route::post('/auth', [ApiController::class, 'login'])->name('api.auth');
Route::post('/webhook', [ApiController::class, 'webhook'])->name('api.webhook');
Route::middleware(['auth:sanctum', 'user'])->group(function () {
    Route::get('/recipes', [ApiController::class, 'recipes'])->name('api.recipes.index');
    Route::get('/recipes/{id}', [ApiController::class, 'recipe'])->name('api.recipes.show');
    Route::post('/favorites', [ApiController::class, 'favoriteStore'])->name('api.favorites.store');
    Route::get('/favorites', [ApiController::class, 'favoriteIndex'])->name('api.favorites.index');
    Route::delete('/favorites/{recipeId}', [ApiController::class, 'favoriteDestroy'])->name('api.favorites.destroy');
    Route::post('/purchase', [ApiController::class, 'purchaseStore'])->name('api.purchase.store');
    Route::get('/purchases', [ApiController::class, 'purchases'])->name('api.purchases.index');
    Route::get('/purchase/{id}', [ApiController::class, 'purchaseShow'])->name('api.purchase.show');
});
