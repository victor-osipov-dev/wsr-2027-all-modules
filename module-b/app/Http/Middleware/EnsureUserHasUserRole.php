<?php

namespace App\Http\Middleware;
class EnsureUserHasUserRole
{
    public function handle(
        \Illuminate\Http\Request $request,
        \Closure $next,
    ): \Symfony\Component\HttpFoundation\Response {
        if ($request->user() !== null && !$request->user()->isRegularUser()) {
            return response()->json(['message' => 'Forbidden for you'], 403);
        }
        return $next($request);
    }
}
