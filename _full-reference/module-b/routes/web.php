<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\StepController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Веб-маршруты: админ-панель и служебные страницы
|--------------------------------------------------------------------------
*/

// Корень приложения ведёт в список рецептов админ-панели.
Route::get('/', fn () => redirect()->route('admin.recipes.index'));

// Демонстрационная страница оплаты, на которую ведёт payment_link.
Route::get('/pay', function (Request $request) {
    return view('pay', [
        'orderId' => $request->query('order_id'),
        'webhook' => $request->query('url'),
    ]);
})->name('pay');

/*
|--------------------------------------------------------------------------
| Админ-панель ({{site-url}}/admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Гостевые маршруты: экран входа.
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
    });

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Маршруты, доступные только администратору.
    Route::middleware('admin')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.recipes.index'))->name('dashboard');

        // Рецепты: восстановление и окончательное удаление.
        Route::patch('recipes/{recipe}/restore', [RecipeController::class, 'restore'])->name('recipes.restore');
        Route::delete('recipes/{recipe}/force-delete', [RecipeController::class, 'forceDelete'])->name('recipes.force-delete');
        Route::resource('recipes', RecipeController::class);

        // Шаги рецепта (вложенные маршруты).
        Route::prefix('recipes/{recipe}/steps')->name('recipes.steps.')->group(function () {
            Route::get('/', [StepController::class, 'index'])->name('index');
            Route::get('create', [StepController::class, 'create'])->name('create');
            Route::post('/', [StepController::class, 'store'])->name('store');
            Route::get('{step}/edit', [StepController::class, 'edit'])->name('edit');
            Route::put('{step}', [StepController::class, 'update'])->name('update');
            Route::delete('{step}', [StepController::class, 'destroy'])->name('destroy');
        });

        // Категории и ингредиенты (полный CRUD без отдельной страницы просмотра).
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('ingredients', IngredientController::class)->except(['show']);

        // Заказы пользователей.
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    });
});
