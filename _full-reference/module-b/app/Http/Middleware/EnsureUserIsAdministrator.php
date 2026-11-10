<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ к админ-панели только для пользователей с ролью «Администратор».
 */
class EnsureUserIsAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('admin.login')
                ->with('error', 'Требуется авторизация администратора.');
        }

        if (! $user->isAdmin()) {
            Auth::guard('web')->logout();

            return redirect()->route('admin.login')
                ->with('error', 'Доступ разрешён только администратору.');
        }

        return $next($request);
    }
}
