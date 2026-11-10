<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Базовый FormRequest для API.
 *
 * При ошибке валидации возвращает JSON вида:
 * { "message": "Invalid fields", "errors": { "field": ["..."] } } и статус 422.
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Invalid fields',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
