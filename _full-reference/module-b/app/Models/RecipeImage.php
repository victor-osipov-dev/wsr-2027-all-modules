<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Изображение рецепта.
 *
 * Хранит путь к оригиналу (`path`) и путь к сгенерированной миниатюре
 * 350×200 webp с водяным знаком (`thumbnail`).
 */
class RecipeImage extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipe_id',
        'path',
        'thumbnail',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /** Рецепт, которому принадлежит изображение. */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /** Относительный URL миниатюры. */
    public function thumbnailUrl(): string
    {
        return '/storage/'.$this->thumbnail;
    }
}
