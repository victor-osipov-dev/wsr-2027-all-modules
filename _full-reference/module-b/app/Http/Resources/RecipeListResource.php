<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Краткое представление рецепта для списков API.
 *
 * @mixin \App\Models\Recipe
 */
class RecipeListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'cooking_time' => $this->cooking_time,
            'calories' => $this->calories,
            'difficulty' => $this->difficulty,
            'price' => (float) $this->price,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'default_image' => $this->default_image_url,
            'formatted_time' => $this->formatted_time,
            'created_at' => optional($this->created_at)->toJSON(),
        ];
    }
}
