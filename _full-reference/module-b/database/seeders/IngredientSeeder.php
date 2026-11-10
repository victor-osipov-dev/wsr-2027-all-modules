<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

/**
 * Ингредиенты с единицами измерения.
 */
class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            ['title' => 'Свекла', 'unit' => 'шт'],
            ['title' => 'Капуста', 'unit' => 'кг'],
            ['title' => 'Картофель', 'unit' => 'кг'],
            ['title' => 'Морковь', 'unit' => 'кг'],
            ['title' => 'Лук', 'unit' => 'шт'],
            ['title' => 'Мясо', 'unit' => 'кг'],
            ['title' => 'Соль', 'unit' => 'мг'],
            ['title' => 'Сахар', 'unit' => 'кг'],
            ['title' => 'Мука', 'unit' => 'кг'],
            ['title' => 'Молоко', 'unit' => 'л'],
            ['title' => 'Масло', 'unit' => 'ст.л.'],
            ['title' => 'Яйцо', 'unit' => 'шт'],
            ['title' => 'Вода', 'unit' => 'л'],
            ['title' => 'Чеснок', 'unit' => 'шт'],
            ['title' => 'Сыр', 'unit' => 'кг'],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::query()->firstOrCreate(
                ['title' => $ingredient['title']],
                ['unit' => $ingredient['unit']],
            );
        }
    }
}
