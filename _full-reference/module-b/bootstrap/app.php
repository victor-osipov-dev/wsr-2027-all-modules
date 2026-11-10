<?php

use App\Http\Middleware\EnsureUserHasUserRole;
use App\Http\Middleware\EnsureUserIsAdministrator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Bootstrap приложения (Laravel 12)
|--------------------------------------------------------------------------
| Здесь регистрируются маршруты, middleware и обработка исключений.
| API публикуется под префиксом `recipe-api` согласно конкурсному заданию:
|   {{site-url}}/recipe-api/...
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: 'recipe-api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Псевдонимы middleware: 'admin' — доступ к админ-панели,
        // 'user' — доступ к пользовательским маршрутам API.
        $middleware->alias([
            'admin' => EnsureUserIsAdministrator::class,
            'user' => EnsureUserHasUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Все ошибки API отдаём в формате JSON.
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('recipe-api/*') || $request->expectsJson();
        });

        // Точное тело ответа 401 при отсутствии/невалидности токена.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('recipe-api/*')) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return null;
        });
    })->create();
