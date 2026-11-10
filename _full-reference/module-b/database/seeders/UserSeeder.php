<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Начальные пользователи: администратор и обычный пользователь.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@champ2026.lan'],
            [
                'name' => 'Администратор',
                'password' => 'champ2026',
                'role' => User::ROLE_ADMIN,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'user@champ2026.lan'],
            [
                'name' => 'Иван Пользователь',
                'password' => 'Champ2026',
                'role' => User::ROLE_USER,
            ],
        );
    }
}
