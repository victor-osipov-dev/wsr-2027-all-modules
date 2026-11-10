<?php

namespace App\Models;

use App\Services\TimeFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Рецепт.
 *
 * Помимо основных полей содержит связи с изображениями, ингредиентами
 * (через промежуточную таблицу с количеством и единицей измерения) и шагами.
 * Реализовано мягкое удаление (SoftDeletes) с возможностью восстановления.
 */
class Recipe extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** Допустимые уровни сложности. */
    public const DIFFICULTIES = ['легко', 'средне', 'сложно'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'cooking_time',
        'calories',
        'difficulty',
        'price',
        'category_id',
        'user_id',
    ];

    /**
     * Виртуальные атрибуты, доступные в JSON и Blade.
     *
     * @var list<string>
     */
    protected $appends = [
        'formatted_time',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cooking_time' => 'integer',
            'calories' => 'integer',
            'price' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Локальный scope фильтрации списка рецептов (используется API и админ-панелью).
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['category_id']), fn (Builder $q) => $q->where('category_id', $filters['category_id']))
            ->when(! empty($filters['difficulty']), fn (Builder $q) => $q->where('difficulty', $filters['difficulty']))
            ->when(isset($filters['time_min']) && $filters['time_min'] !== '', fn (Builder $q) => $q->where('cooking_time', '>=', (int) $filters['time_min']))
            ->when(isset($filters['time_max']) && $filters['time_max'] !== '', fn (Builder $q) => $q->where('cooking_time', '<=', (int) $filters['time_max']))
            // Цена 0 обрабатывается как «без ограничений».
            ->when(isset($filters['price_min']) && $filters['price_min'] !== '' && (float) $filters['price_min'] > 0, fn (Builder $q) => $q->whereRaw('COALESCE(price, 0) >= ?', [(float) $filters['price_min']]))
            ->when(isset($filters['price_max']) && $filters['price_max'] !== '' && (float) $filters['price_max'] > 0, fn (Builder $q) => $q->whereRaw('COALESCE(price, 0) <= ?', [(float) $filters['price_max']]));
    }

    /** Категория рецепта. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Автор рецепта (администратор, создавший запись). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Изображения рецепта. */
    public function images(): HasMany
    {
        return $this->hasMany(RecipeImage::class)->orderBy('id');
    }

    /** Шаги приготовления. */
    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('order');
    }

    /** Ингредиенты рецепта с количеством и единицей измерения. */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient')
            ->withPivot(['quantity', 'unit'])
            ->withTimestamps();
    }

    /** Записи избранного для рецепта. */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** Покупки рецепта. */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Человекочитаемое время приготовления (например «2 часа», «1 час 20 минут»).
     */
    protected function formattedTime(): Attribute
    {
        return Attribute::make(
            get: fn (): string => app(TimeFormatter::class)->humanize($this->cooking_time),
        );
    }

    /** Рецепт доступен бесплатно (цена 0 или не задана). */
    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    /**
     * Изображение по умолчанию (или первое доступное).
     */
    public function defaultImage(): ?RecipeImage
    {
        return $this->images->firstWhere('is_default', true)
            ?? $this->images->first();
    }

    /**
     * Относительный URL изображения по умолчанию (миниатюры).
     */
    protected function defaultImageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $image = $this->defaultImage();

                return $image ? '/storage/'.$image->thumbnail : null;
            },
        );
    }
}
