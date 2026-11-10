<?php

namespace App\Events;

use App\Models\Recipe;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания нового рецепта.
 *
 * Транслируется в публичный канал `recipes`, который слушает клиентское
 * приложение (Модуль В) через WebSocket (Laravel Reverb).
 */
class RecipeCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Recipe $recipe,
    ) {
    }

    /**
     * Публичный канал вещания.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('recipes');
    }

    /**
     * Имя события на стороне клиента.
     */
    public function broadcastAs(): string
    {
        return 'recipe.created';
    }

    /**
     * Данные, передаваемые клиенту.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->recipe->loadMissing(['images', 'category']);

        return [
            'id' => $this->recipe->getKey(),
            'title' => $this->recipe->title,
            'description' => $this->recipe->description,
            'cooking_time' => $this->recipe->cooking_time,
            'calories' => $this->recipe->calories,
            'difficulty' => $this->recipe->difficulty,
            'price' => (float) $this->recipe->price,
            'formatted_time' => $this->recipe->formatted_time,
            'default_image' => $this->recipe->default_image_url,
            'created_at' => optional($this->recipe->created_at)->toJSON(),
        ];
    }
}
