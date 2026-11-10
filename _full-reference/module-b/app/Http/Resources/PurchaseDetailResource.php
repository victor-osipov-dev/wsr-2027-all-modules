<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Детальное представление заказа: рецепт, шаги и признаки прохождения.
 *
 * @mixin \App\Models\Purchase
 */
class PurchaseDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $completions = $this->stepCompletions->keyBy('step_id');

        return [
            'id' => $this->id,
            'recipe' => [
                'id' => $this->recipe->id,
                'title' => $this->recipe->title,
                'description' => $this->recipe->description,
                'price' => (float) $this->recipe->price,
                'difficulty' => $this->recipe->difficulty,
                'cooking_time' => $this->recipe->cooking_time,
                'calories' => $this->recipe->calories,
                'formatted_time' => $this->recipe->formatted_time,
                'steps' => $this->recipe->steps->map(function ($step) use ($completions) {
                    $completion = $completions->get($step->id);

                    return [
                        'id' => $step->id,
                        'title' => $step->title,
                        'description' => $step->description,
                        'order' => (int) $step->order,
                        'is_completed' => $completion !== null && $completion->completed_at !== null,
                        'completed_at' => optional($completion?->completed_at)->toJSON(),
                    ];
                })->values(),
            ],
            'status' => $this->status,
            'paid_at' => optional($this->paid_at)->toJSON(),
            'created_at' => optional($this->created_at)->toJSON(),
        ];
    }
}
