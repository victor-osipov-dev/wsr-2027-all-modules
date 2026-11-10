<?php

namespace App\Http\Requests\Admin;

/**
 * Валидация создания рецепта (требуется минимум 2 изображения).
 */
class StoreRecipeRequest extends RecipeRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function imageRules(): array
    {
        return [
            'images' => ['required', 'array', 'min:2'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=500,min_height=500'],
        ];
    }
}
