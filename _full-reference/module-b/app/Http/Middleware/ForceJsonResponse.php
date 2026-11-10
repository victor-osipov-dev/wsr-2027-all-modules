<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Принудительно запрашивает JSON-ответ для всех API-маршрутов.
 *
 * Благодаря этому ошибки 401/403/422 возвращаются в формате JSON,
 * даже если клиент не прислал заголовок Accept: application/json.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
