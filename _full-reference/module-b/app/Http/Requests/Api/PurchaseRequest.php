<?php

namespace App\Http\Requests\Api;

/**
 * Валидация покупки рецепта.
 */
class PurchaseRequest extends ApiFormRequest
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
