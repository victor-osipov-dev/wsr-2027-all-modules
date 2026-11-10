<?php

namespace App\Http\Requests\Admin;

/**
 * Валидация редактирования рецепта.
 *
 * Новые изображения необязательны, но при загрузке к ним применяются те же
 * ограничения, что и при создании.
 */
class UpdateRecipeRequest extends RecipeRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function imageRules(): array
    {
        return [
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=500,min_height=500'],
        ];
    }
}
