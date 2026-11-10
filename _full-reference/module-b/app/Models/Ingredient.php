<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Ингредиент рецепта.
 */
class Ingredient extends Model
{
    use HasFactory;

    /** Допустимые единицы измерения. */
    public const UNITS = ['шт', 'л', 'кг', 'мг', 'ст.л.'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'unit',
    ];

    /** Рецепты, в которых используется ингредиент. */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredient')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps();
    }
}
