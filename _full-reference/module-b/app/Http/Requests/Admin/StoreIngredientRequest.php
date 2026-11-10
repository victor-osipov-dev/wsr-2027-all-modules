<?php

namespace App\Http\Requests\Admin;

use App\Models\Ingredient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация создания ингредиента (название уникально, ≤ 20 символов, unit из списка).
 */
class StoreIngredientRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:20', 'unique:ingredients,title'],
            'unit' => ['required', Rule::in(Ingredient::UNITS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название ингредиента.',
            'title.max' => 'Название ингредиента не должно превышать 20 символов.',
            'title.unique' => 'Ингредиент с таким названием уже существует.',
            'unit.required' => 'Выберите единицу измерения.',
            'unit.in' => 'Единица измерения должна быть одной из: шт, л, кг, мг, ст.л.',
        ];
    }
}
