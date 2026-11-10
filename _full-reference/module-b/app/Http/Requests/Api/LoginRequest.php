<?php

namespace App\Http\Requests\Api;

/**
 * Валидация авторизации пользователя.
 */
class LoginRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required',
            'password.required' => 'The password field is required',
        ];
    }
}
