<?php

namespace App\Providers;

class AppServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();
        \Illuminate\Auth\Middleware\Authenticate::redirectUsing(fn() => route('admin.login'));
        \Illuminate\Support\Facades\Route::bind(
            'recipe',
            fn($value) => \App\Models\Recipe::withTrashed()->findOrFail($value),
        );
    }
}
