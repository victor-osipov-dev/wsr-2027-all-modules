<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Избранный рецепт пользователя.
 */
class Favorite extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'recipe_id',
    ];

    /** Пользователь, добавивший рецепт в избранное. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Избранный рецепт. */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
