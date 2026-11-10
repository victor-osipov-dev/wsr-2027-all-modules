<?php

namespace App\Providers;

use App\Models\Recipe;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Провайдер приложения: привязки моделей, настройка пагинации.
 */
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Пагинация админ-панели в стиле Bootstrap 5 (требование шаблона).
        Paginator::useBootstrapFive();

        Date::use(\Carbon\Carbon::class);

        // Админ-панель должна видеть и рецепты, помеченные на удаление.
        Route::bind('recipe', fn (string $value) => Recipe::withTrashed()->findOrFail($value));
    }
}
