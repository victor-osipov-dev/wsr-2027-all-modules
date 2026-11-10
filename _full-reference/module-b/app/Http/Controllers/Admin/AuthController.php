<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Авторизация администратора.
 *
 * После успешного входа — перенаправление на список рецептов.
 */
class AuthController extends Controller
{
    /**
     * Экран входа.
     */
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Обработка формы входа.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('auth_error', 'Неправильный логин или пароль');
        }

        // Доступ к панели разрешён только администратору.
        if (! $request->user()->isAdmin()) {
            Auth::logout();

            return back()
                ->withInput($request->only('email'))
                ->with('auth_error', 'Неправильный логин или пароль');
        }

        $request->session()->regenerate();

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Вы вошли как администратор.');
    }

    /**
     * Выход из системы.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
