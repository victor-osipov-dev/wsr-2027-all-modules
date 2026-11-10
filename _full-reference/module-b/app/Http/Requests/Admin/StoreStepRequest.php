<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания шага рецепта.
 */
class StoreStepRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название шага.',
            'title.max' => 'Название шага не должно превышать 40 символов.',
            'description.required' => 'Укажите описание шага.',
            'description.max' => 'Описание шага не должно превышать 255 символов.',
        ];
    }
}
