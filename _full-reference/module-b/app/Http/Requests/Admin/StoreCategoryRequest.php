<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания категории (название уникально, максимум 40 символов).
 */
class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40', 'unique:categories,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите название категории.',
            'name.max' => 'Название категории не должно превышать 40 символов.',
            'name.unique' => 'Категория с таким названием уже существует.',
        ];
    }
}
