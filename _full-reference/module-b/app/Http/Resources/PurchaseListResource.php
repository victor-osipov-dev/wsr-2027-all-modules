<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Представление заказа в списке покупок пользователя.
 *
 * @mixin \App\Models\Purchase
 */
class PurchaseListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recipe' => [
                'id' => $this->recipe->id,
                'title' => $this->recipe->title,
                'price' => (float) $this->recipe->price,
                'default_image' => $this->recipe->default_image_url,
            ],
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'paid_at' => optional($this->paid_at)->toJSON(),
            'expires_at' => optional($this->expires_at)->toJSON(),
            'created_at' => optional($this->created_at)->toJSON(),
        ];
    }
}
