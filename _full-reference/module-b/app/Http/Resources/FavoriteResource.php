<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Представление рецепта в списке избранного.
 *
 * @mixin \App\Models\Recipe
 */
class FavoriteResource extends JsonResource
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
            'default_image' => $this->default_image_url,
            'formatted_time' => $this->formatted_time,
            'favorited_at' => optional($this->favorited_at)->toJSON(),
        ];
    }
}
