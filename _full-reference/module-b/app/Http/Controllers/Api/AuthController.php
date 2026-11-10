<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Регистрация и авторизация пользователей через API.
 */
class AuthController extends Controller
{
    /**
     * POST /recipe-api/register
     *
     * Создаёт пользователя с ролью «user» и хешированным паролем.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $email = (string) $request->input('email');

        User::create([
            'name' => (string) ($request->input('name') ?: Str::before($email, '@')),
            'email' => $email,
            'password' => (string) $request->input('password'),
            'role' => User::ROLE_USER,
        ]);

        return response()->json(['success' => true], 201);
    }

    /**
     * POST /recipe-api/auth
     *
     * Проверяет учётные данные и выдаёт персональный токен Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->input('email'))->first();

        if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid data',
                'errors' => [
                    'email' => ['Invalid credentials'],
                ],
            ], 422);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }
}
