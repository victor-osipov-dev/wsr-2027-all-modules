<?php

namespace App\Models;

class Recipe extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    public const DIFFICULTIES = ['легко', 'средне', 'сложно'];
    protected $guarded = [];
    protected function casts(): array
    {
        return ['cooking_time' => 'integer', 'calories' => 'integer', 'price' => 'decimal:2'];
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function images()
    {
        return $this->hasMany(RecipeImage::class)->orderBy('id');
    }
    public function steps()
    {
        return $this->hasMany(Step::class)->orderBy('order');
    }
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient')->withPivot(['quantity', 'unit']);
    }
    public function getFormattedTimeAttribute(): string
    {
        $h = intdiv((int) $this->cooking_time, 60);
        $m = (int) $this->cooking_time % 60;
        $text = trim(
            ($h ? $h . ' ' . ($h === 1 ? 'час' : 'часа') : '') .
                ' ' .
                ($m ? $m . ' ' . ($m === 1 ? 'минута' : 'минут') : ''),
        );
        return $text === '' ? '0 минут' : $text;
    }
    public function getDefaultImageUrlAttribute(): ?string
    {
        $image = $this->images->firstWhere('is_default', true) ?? $this->images->first();
        return $image ? '/storage/' . $image->thumbnail : null;
    }
}
