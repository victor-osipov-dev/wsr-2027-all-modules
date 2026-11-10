<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Категория рецепта.
 */
class Category extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /** Рецепты категории (включая помеченные на удаление). */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class)->withTrashed();
    }

    /** Существуют ли рецепты в данной категории. */
    public function hasRecipes(): bool
    {
        return $this->recipes()->exists();
    }
}
