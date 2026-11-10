<?php

namespace Database\Seeders;

use App\Models\Favorite;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Демонстрационное избранное пользователя.
 */
class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'user@champ2026.lan')->first();

        if ($user === null) {
            return;
        }

        foreach (['Борщ', 'Винегрет', 'Медовик'] as $title) {
            $recipe = Recipe::query()->where('title', $title)->first();

            if ($recipe === null) {
                continue;
            }

            Favorite::query()->firstOrCreate([
                'user_id' => $user->getKey(),
                'recipe_id' => $recipe->getKey(),
            ]);
        }
    }
}
