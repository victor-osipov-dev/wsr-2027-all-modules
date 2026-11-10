<?php

namespace App\Http\Requests\Api;

/**
 * Валидация сохранения прогресса по шагу рецепта.
 */
class StepCompleteRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipe_id' => ['required', 'integer'],
            'completed' => ['required', 'boolean'],
        ];
    }
}
