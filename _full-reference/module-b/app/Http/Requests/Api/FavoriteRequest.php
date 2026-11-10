<?php

namespace App\Http\Requests\Api;

/**
 * Валидация добавления рецепта в избранное.
 */
class FavoriteRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipe_id' => ['required', 'integer'],
        ];
    }
}
