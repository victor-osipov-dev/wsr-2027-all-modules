<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ к пользовательским API-маршрутам только для роли «Пользователь».
 *
 * Администратор, получивший токен API, не имеет доступа к user-маршрутам
 * и получает ответ 403 {"message":"Forbidden for you"}.
 */
class EnsureUserHasUserRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isRegularUser()) {
            return response()->json([
                'message' => 'Forbidden for you',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
