<?php

use App\Http\Middleware\EnsureUserHasUserRole;

return \Illuminate\Foundation\Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'recipe-api',
    )
    ->withMiddleware(fn($middleware) => $middleware->alias(['user' => EnsureUserHasUserRole::class]))
    ->withExceptions(function ($exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn(\Illuminate\Http\Request $request) => $request->is('recipe-api/*') || $request->expectsJson(),
        );
        $exceptions->render(
            fn(\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) => $request->is(
                'recipe-api/*',
            )
                ? response()->json(['message' => 'Unauthenticated'], 401)
                : null,
        );
        $exceptions->render(
            fn(\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) => $request->is(
                'recipe-api/*',
            )
                ? response()->json(['message' => 'Invalid fields', 'errors' => $e->errors()], 422)
                : null,
        );
    })
    ->create();
