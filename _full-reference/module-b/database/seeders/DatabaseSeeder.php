<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Наполнение базы данных демонстрационными данными.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            IngredientSeeder::class,
            RecipeSeeder::class,
            PurchaseSeeder::class,
            FavoriteSeeder::class,
        ]);
    }
}
