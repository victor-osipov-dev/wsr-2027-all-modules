<?php

namespace App\Http\Requests\Api;

/**
 * Валидация регистрации пользователя.
 *
 * email — обязательный, валидный, уникальный;
 * password — минимум 5 символов, латиница, минимум одна строчная,
 * одна прописная буква и один спецсимвол (# _ - $ .).
 */
class RegisterRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:5'],
        ];
    }

    /**
     * Дополнительные проверки сложности пароля с отдельными сообщениями.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $password = (string) $this->input('password');

            if ($password === '') {
                return;
            }

            if (! preg_match('/^[A-Za-z0-9#_\-$.]+$/', $password)) {
                $validator->errors()->add('password', 'The password may contain only latin letters, digits and special characters');
            }

            if (! preg_match('/[a-z]/', $password)) {
                $validator->errors()->add('password', 'The password must contain at least one lowercase letter');
            }

            if (! preg_match('/[A-Z]/', $password)) {
                $validator->errors()->add('password', 'The password must contain at least one uppercase letter');
            }

            if (! preg_match('/[#_\-$.]/', $password)) {
                $validator->errors()->add('password', 'The password must contain at least one special character (# _ - $ .)');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required',
            'email.email' => 'The email must be a valid email address',
            'email.unique' => 'The email has already been taken',
            'password.required' => 'The password field is required',
            'password.min' => 'The password must be at least 5 characters',
        ];
    }
}
