<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Категории рецептов.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Первые блюда',
            'Вторые блюда',
            'Салаты',
            'Десерты',
            'Напитки',
        ];

        foreach ($categories as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
