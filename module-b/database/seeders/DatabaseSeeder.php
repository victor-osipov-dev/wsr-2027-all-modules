<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@champ2026.lan'],
            ['name' => 'Администратор', 'password' => 'champ2026', 'role' => 'admin'],
        );
        User::updateOrCreate(
            ['email' => 'user@champ2026.lan'],
            ['name' => 'Иван Пользователь', 'password' => 'Champ2026', 'role' => 'user'],
        );
        collect(['Первые блюда', 'Вторые блюда', 'Салаты', 'Десерты'])->each(
            fn($name) => Category::firstOrCreate(['name' => $name]),
        );
        collect([
            'Свекла' => 'шт',
            'Капуста' => 'кг',
            'Картофель' => 'кг',
            'Морковь' => 'кг',
            'Лук' => 'шт',
            'Мясо' => 'кг',
            'Мука' => 'кг',
            'Молоко' => 'л',
            'Яйцо' => 'шт',
            'Сахар' => 'кг',
            'Сыр' => 'кг',
        ])->each(fn($unit, $title) => Ingredient::firstOrCreate(['title' => $title], ['unit' => $unit]));
        $recipes = [
            [
                'Борщ',
                'Классический борщ с говядиной, свеклой и капустой. Наваристый и ароматный.',
                120,
                200,
                'средне',
                0,
                'Первые блюда',
                [['Свекла', 2, 'шт'], ['Капуста', 0.5, 'кг'], ['Мясо', 0.7, 'кг']],
                [['Подготовка', 'Сварить бульон из мяса.'], ['Овощи', 'Нарезать свеклу и капусту.']],
            ],
            [
                'Окрошка',
                'Летняя окрошка на кефире с картофелем, яйцом и свежей зеленью. Освежающий вкус.',
                25,
                90,
                'легко',
                0,
                'Первые блюда',
                [['Картофель', 0.3, 'кг'], ['Яйцо', 3, 'шт'], ['Молоко', 0.5, 'л']],
                [['Отварить', 'Отварить картофель и яйца.'], ['Смешать', 'Нарезать и залить кефиром.']],
            ],
            [
                'Плов',
                'Мастер-класс по приготовлению рассыпчатого узбекского плова с говядиной и специями.',
                150,
                400,
                'сложно',
                350.0,
                'Вторые блюда',
                [['Мясо', 1, 'кг'], ['Морковь', 0.5, 'кг'], ['Лук', 3, 'шт']],
                [['Зирвак', 'Обжарить мясо с луком и морковью.'], ['Томление', 'Томить под крышкой 40 минут.']],
            ],
            [
                'Медовик',
                'Мастер-класс по приготовлению нежного медового торта с кремом из сметаны. Пошагово.',
                200,
                420,
                'сложно',
                500.0,
                'Десерты',
                [['Мука', 0.5, 'кг'], ['Молоко', 0.5, 'л'], ['Яйцо', 4, 'шт'], ['Сахар', 0.3, 'кг']],
                [['Коржи', 'Испечь медовые коржи.'], ['Сборка', 'Промазать коржи кремом.']],
            ],
            [
                'Пицца',
                'Домашняя пицца «Маргарита» с томатным соусом, моцареллой и базиликом на тонком тесте.',
                40,
                320,
                'средне',
                320.0,
                'Вторые блюда',
                [['Мука', 0.4, 'кг'], ['Сыр', 0.2, 'кг'], ['Молоко', 0.1, 'л']],
                [['Тесто', 'Замесить тесто и дать подойти.'], ['Выпечка', 'Выпекать при 220°C 15 минут.']],
            ],
        ];
        foreach (['Суп-лапша', 'Гречка по-купечески', 'Винегрет'] as $i => $title) {
            $recipes[] = [
                $title,
                'Домашний рецепт «' . $title . '»: простой и вкусный, подходит для ежедневного меню.',
                15 + $i * 35,
                120 + $i * 60,
                ['легко', 'средне', 'сложно'][$i],
                $i % 2 ? 150.0 : 0,
                'Вторые блюда',
                [['Мука', 1, 'кг']],
                [['Приготовление', 'Смешать ингредиенты и приготовить до готовности.']],
            ];
        }
        foreach (
            $recipes
            as $index => [$title, $description, $time, $calories, $difficulty, $price, $category, $ingredients, $steps]
        ) {
            $recipe = Recipe::create([
                'title' => $title,
                'description' => $description,
                'cooking_time' => $time,
                'calories' => $calories,
                'difficulty' => $difficulty,
                'price' => $price,
                'category_id' => Category::where('name', $category)->value('id'),
                'created_at' => now()->subDays(count($recipes) - $index),
            ]);
            $recipe->ingredients()->sync(
                collect($ingredients)
                    ->mapWithKeys(
                        fn($i) => [
                            Ingredient::where('title', $i[0])->value('id') => [
                                'quantity' => $i[1],
                                'unit' => $i[2],
                            ],
                        ],
                    )
                    ->all(),
            );
            foreach ($steps as $position => [$stepTitle, $stepDescription]) {
                $recipe
                    ->steps()
                    ->create(['title' => $stepTitle, 'description' => $stepDescription, 'order' => $position + 1]);
            }
            foreach ([0, 1] as $n) {
                $path = $this->makeOriginal($recipe, $index + $n);
                $recipe->images()->create(['path' => $path, 'thumbnail' => $path, 'is_default' => $n === 0]);
            }
        }
        Recipe::where('title', 'Пицца')->first()?->delete();
    }
    private function makeOriginal(Recipe $recipe, int $seed): string
    {
        $relative = 'recipes/' . $recipe->getKey() . '/original_' . Str::lower(Str::random(10)) . '.jpg';
        $image = imagecreatetruecolor(800, 600);
        imagefilledrectangle(
            $image,
            0,
            0,
            799,
            599,
            imagecolorallocate($image, (($seed * 37) % 200) + 30, (($seed * 53) % 200) + 30, (($seed * 71) % 200) + 30),
        );
        Storage::disk('public')->makeDirectory(dirname($relative));
        imagejpeg($image, Storage::disk('public')->path($relative), 85);
        imagedestroy($image);
        return $relative;
    }
}
