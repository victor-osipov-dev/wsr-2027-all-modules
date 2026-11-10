<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Детальное представление рецепта с изображениями, ингредиентами и шагами.
 *
 * @mixin \App\Models\Recipe
 */
class RecipeDetailResource extends JsonResource
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
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'images' => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'path' => '/storage/'.$image->path,
                'thumbnail' => '/storage/'.$image->thumbnail,
                'is_default' => (bool) $image->is_default,
            ])->values(),
            'ingredients' => $this->ingredients->map(fn ($ingredient) => [
                'id' => $ingredient->id,
                'title' => $ingredient->title,
                'pivot' => [
                    'quantity' => (float) $ingredient->pivot->quantity,
                    'unit' => $ingredient->pivot->unit,
                ],
            ])->values(),
            'steps' => $this->steps->map(fn ($step) => [
                'id' => $step->id,
                'title' => $step->title,
                'description' => $step->description,
                'order' => (int) $step->order,
            ])->values(),
            'formatted_time' => $this->formatted_time,
            'created_at' => optional($this->created_at)->toJSON(),
        ];
    }
}
