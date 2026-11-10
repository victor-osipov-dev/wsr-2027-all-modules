<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\StepController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API «Кухни мира»
|--------------------------------------------------------------------------
| Базовый префикс `recipe-api` задаётся в bootstrap/app.php.
| Публичные маршруты: register, auth, webhook.
| Остальные — только для авторизованных пользователей с ролью «user».
*/

Route::middleware(ForceJsonResponse::class)->group(function () {
    // --- Публичные маршруты -------------------------------------------------
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
    Route::post('/auth', [AuthController::class, 'login'])->name('api.auth');
    Route::post('/webhook', [WebhookController::class, 'handle'])->name('api.webhook');

    // --- Защищённые маршруты (Bearer-токен, роль user) ----------------------
    Route::middleware(['auth:sanctum', 'user'])->group(function () {
        Route::get('/recipes', [RecipeController::class, 'index'])->name('api.recipes.index');
        Route::get('/recipes/{id}', [RecipeController::class, 'show'])->name('api.recipes.show');

        Route::post('/favorites', [FavoriteController::class, 'store'])->name('api.favorites.store');
        Route::get('/favorites', [FavoriteController::class, 'index'])->name('api.favorites.index');
        Route::delete('/favorites/{recipeId}', [FavoriteController::class, 'destroy'])->name('api.favorites.destroy');

        Route::post('/purchase', [PurchaseController::class, 'store'])->name('api.purchase.store');
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('api.purchases.index');
        Route::get('/purchase/{id}', [PurchaseController::class, 'show'])->name('api.purchase.show');
        Route::get('/purchase/{id}/progress', [PurchaseController::class, 'progress'])->name('api.purchase.progress');

        Route::post('/steps/{stepId}/complete', [StepController::class, 'complete'])->name('api.steps.complete');
    });
});
