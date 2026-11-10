<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Демонстрационные рецепты: ингредиенты, шаги и изображения.
 *
 * Изображения генерируются средствами GD (офлайн), после чего для каждого
 * создаётся миниатюра 350×200 webp с водяным знаком через ImageService.
 */
class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $recipes = $this->recipes();

        foreach ($recipes as $index => $data) {
            $recipe = Recipe::query()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'cooking_time' => $data['cooking_time'],
                'calories' => $data['calories'],
                'difficulty' => $data['difficulty'],
                'price' => $data['price'],
                'category_id' => Category::query()->where('name', $data['category'])->value('id'),
            ]);

            // Даты создания «разносим» во времени, чтобы работала сортировка по новизне.
            $recipe->created_at = now()->subDays(count($recipes) - $index)->subMinutes($index);
            $recipe->updated_at = $recipe->created_at;
            $recipe->save();

            $this->attachIngredients($recipe, $data['ingredients']);
            $this->attachSteps($recipe, $data['steps']);
            $this->attachImages($recipe, $index);

            // Один рецепт помечаем на удаление (для проверки списка и восстановления).
            if (! empty($data['trashed'])) {
                $recipe->delete();
            }
        }
    }

    /**
     * Связывает ингредиенты с рецептом (с количеством и единицей измерения).
     *
     * @param  array<int, array{0: string, 1: float, 2: string}>  $ingredients
     */
    private function attachIngredients(Recipe $recipe, array $ingredients): void
    {
        $sync = [];

        foreach ($ingredients as [$title, $quantity, $unit]) {
            $ingredient = Ingredient::query()->where('title', $title)->first();

            if ($ingredient === null) {
                continue;
            }

            $sync[$ingredient->id] = ['quantity' => $quantity, 'unit' => $unit];
        }

        $recipe->ingredients()->sync($sync);
    }

    /**
     * Создаёт шаги рецепта.
     *
     * @param  array<int, array{0: string, 1: string}>  $steps
     */
    private function attachSteps(Recipe $recipe, array $steps): void
    {
        foreach ($steps as $position => [$title, $description]) {
            $recipe->steps()->create([
                'title' => $title,
                'description' => $description,
                'order' => $position + 1,
            ]);
        }
    }

    /**
     * Генерирует и сохраняет 2–3 изображения рецепта с миниатюрами.
     */
    private function attachImages(Recipe $recipe, int $seed): void
    {
        $count = ($seed % 2 === 0) ? 3 : 2;

        for ($i = 0; $i < $count; $i++) {
            $this->createImageFile($recipe, $seed + $i, $i === 0);
        }
    }

    /**
     * Создаёт файл-изображение 800×600 и миниатюру для него.
     */
    private function createImageFile(Recipe $recipe, int $seed, bool $isDefault): void
    {
        $disk = Storage::disk('public');
        $directory = 'recipes/'.$recipe->getKey();
        $disk->makeDirectory($directory);

        $token = Str::lower(Str::random(12));
        $relative = $directory.'/original_'.$token.'.jpg';
        $absolute = $disk->path($relative);

        $width = 800;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);

        // Вертикальный градиент с индивидуальным оттенком.
        for ($y = 0; $y < $height; $y++) {
            $red = (int) (($seed * 37 + $y) % 180) + 40;
            $green = (int) (($seed * 53 + $y) % 180) + 40;
            $blue = (int) (($seed * 71 + $y) % 180) + 40;
            $color = imagecolorallocate($image, $red, $green, $blue);
            imageline($image, 0, $y, $width, $y, $color);
        }

        // Несколько кругов для «фуд»-ассоциации.
        for ($c = 0; $c < 5; $c++) {
            $radius = 40 + (($seed * 13 + $c * 29) % 90);
            $cx = ($seed * 71 + $c * 137) % $width;
            $cy = ($seed * 43 + $c * 89) % $height;
            $fill = imagecolorallocate($image, (255 - $c * 20) % 255, (200 - $c * 15 + 255) % 255, (150 + $c * 10) % 255);
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $fill);
        }

        imagejpeg($image, $absolute, 85);
        imagedestroy($image);

        // Миниатюра с водяным знаком (название рецепта).
        $thumbnail = app(ImageService::class)->generateThumbnail($relative, $recipe->title);

        $recipe->images()->create([
            'path' => $relative,
            'thumbnail' => $thumbnail,
            'is_default' => $isDefault,
        ]);
    }

    /**
     * Набор демонстрационных рецептов.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recipes(): array
    {
        return [
            [
                'title' => 'Борщ',
                'description' => 'Классический украинский борщ с говядиной, свеклой и капустой. Наваристый и ароматный.',
                'cooking_time' => 120,
                'calories' => 200,
                'difficulty' => 'средне',
                'price' => 0,
                'category' => 'Первые блюда',
                'ingredients' => [['Свекла', 2, 'шт'], ['Капуста', 0.5, 'кг'], ['Мясо', 0.7, 'кг'], ['Вода', 3, 'л']],
                'steps' => [['Подготовка', 'Сварить бульон из мяса.'], ['Овощи', 'Нарезать свеклу и капусту, добавить в бульон.'], ['Финал', 'Варить до готовности 30 минут.']],
            ],
            [
                'title' => 'Окрошка',
                'description' => 'Летняя окрошка на кефире с картофелем, яйцом и свежей зеленью. Освежающий вкус.',
                'cooking_time' => 25,
                'calories' => 90,
                'difficulty' => 'легко',
                'price' => 0,
                'category' => 'Первые блюда',
                'ingredients' => [['Картофель', 0.3, 'кг'], ['Яйцо', 3, 'шт'], ['Молоко', 0.5, 'л']],
                'steps' => [['Отварить', 'Отварить картофель и яйца.'], ['Нарезать', 'Нарезать ингредиенты кубиками.'], ['Смешать', 'Залить кефиром и перемешать.']],
            ],
            [
                'title' => 'Суп-лапша',
                'description' => 'Домашний куриный суп с тонкой лапшой и ароматной зеленью. Сытное первое блюдо.',
                'cooking_time' => 80,
                'calories' => 150,
                'difficulty' => 'средне',
                'price' => 0,
                'category' => 'Первые блюда',
                'ingredients' => [['Мясо', 0.5, 'кг'], ['Мука', 0.2, 'кг'], ['Морковь', 0.15, 'кг']],
                'steps' => [['Бульон', 'Сварить куриный бульон.'], ['Лапша', 'Приготовить лапшу из муки.'], ['Соединить', 'Добавить лапшу в бульон.']],
            ],
            [
                'title' => 'Плов',
                'description' => 'Мастер-класс по приготовлению рассыпчатого узбекского плова с говядиной и специями.',
                'cooking_time' => 150,
                'calories' => 400,
                'difficulty' => 'сложно',
                'price' => 350.00,
                'category' => 'Вторые блюда',
                'ingredients' => [['Мясо', 1, 'кг'], ['Морковь', 0.5, 'кг'], ['Лук', 3, 'шт'], ['Вода', 1, 'л']],
                'steps' => [['Зирвак', 'Обжарить мясо с луком и морковью.'], ['Крупа', 'Добавить рис и воду.'], ['Томление', 'Томить под крышкой 40 минут.']],
            ],
            [
                'title' => 'Котлеты',
                'description' => 'Сочные домашние котлеты из смешанного фарша с луком. Отлично подходят на ужин.',
                'cooking_time' => 60,
                'calories' => 260,
                'difficulty' => 'средне',
                'price' => 0,
                'category' => 'Вторые блюда',
                'ingredients' => [['Мясо', 0.6, 'кг'], ['Лук', 2, 'шт'], ['Яйцо', 1, 'шт'], ['Молоко', 0.1, 'л']],
                'steps' => [['Фарш', 'Перемолоть мясо с луком.'], ['Формовка', 'Сформировать котлеты.'], ['Жарка', 'Обжарить до золотистой корочки.']],
            ],
            [
                'title' => 'Голубцы',
                'description' => 'Традиционные голубцы из капустных листьев с мясным фаршем и рисом в томатном соусе.',
                'cooking_time' => 130,
                'calories' => 300,
                'difficulty' => 'сложно',
                'price' => 250.00,
                'category' => 'Вторые блюда',
                'ingredients' => [['Капуста', 1, 'кг'], ['Мясо', 0.5, 'кг'], ['Лук', 2, 'шт'], ['Сыр', 0.1, 'кг']],
                'steps' => [['Листья', 'Подготовить капустные листья.'], ['Начинка', 'Завернуть фарш в листья.'], ['Тушение', 'Тушить в соусе 60 минут.']],
            ],
            [
                'title' => 'Оливье',
                'description' => 'Любимый всеми салат оливье с ветчиной, картофелем, яйцом и майонезом. Праздничная классика.',
                'cooking_time' => 45,
                'calories' => 220,
                'difficulty' => 'средне',
                'price' => 0,
                'category' => 'Салаты',
                'ingredients' => [['Картофель', 0.4, 'кг'], ['Яйцо', 4, 'шт'], ['Морковь', 0.2, 'кг']],
                'steps' => [['Отварить', 'Отварить овощи и яйца.'], ['Нарезать', 'Нарезать все мелкими кубиками.'], ['Заправить', 'Заправить майонезом.']],
            ],
            [
                'title' => 'Винегрет',
                'description' => 'Полезный овощной винегрет со свеклой, картофелем и квашеной капустой. Лёгкий салат.',
                'cooking_time' => 15,
                'calories' => 120,
                'difficulty' => 'легко',
                'price' => 0,
                'category' => 'Салаты',
                'ingredients' => [['Свекла', 1, 'шт'], ['Картофель', 0.3, 'кг'], ['Капуста', 0.3, 'кг']],
                'steps' => [['Овощи', 'Отварить свеклу и картофель.'], ['Нарезка', 'Нарезать кубиками.'], ['Смешать', 'Смешать с капустой и маслом.']],
            ],
            [
                'title' => 'Цезарь',
                'description' => 'Мастер-класс по приготовлению салата «Цезарь» с курицей, сухариками и фирменным соусом.',
                'cooking_time' => 35,
                'calories' => 250,
                'difficulty' => 'средне',
                'price' => 180.00,
                'category' => 'Салаты',
                'ingredients' => [['Мясо', 0.3, 'кг'], ['Сыр', 0.1, 'кг'], ['Яйцо', 2, 'шт']],
                'steps' => [['Курица', 'Обжарить курицу полосками.'], ['Соус', 'Приготовить соус «Цезарь».'], ['Сборка', 'Собрать салат и посыпать сухариками.']],
            ],
            [
                'title' => 'Медовик',
                'description' => 'Мастер-класс по приготовлению нежного медового торта с кремом из сметаны. Пошагово.',
                'cooking_time' => 200,
                'calories' => 420,
                'difficulty' => 'сложно',
                'price' => 500.00,
                'category' => 'Десерты',
                'ingredients' => [['Мука', 0.5, 'кг'], ['Молоко', 0.5, 'л'], ['Яйцо', 4, 'шт'], ['Сахар', 0.3, 'кг']],
                'steps' => [['Коржи', 'Испечь медовые коржи.'], ['Крем', 'Взбить сметанный крем.'], ['Сборка', 'Промазать коржи кремом.']],
            ],
            [
                'title' => 'Панкейки',
                'description' => 'Пышные американские панкейки на кефире. Быстрый и вкусный завтрак для всей семьи.',
                'cooking_time' => 10,
                'calories' => 210,
                'difficulty' => 'легко',
                'price' => 0,
                'category' => 'Десерты',
                'ingredients' => [['Мука', 0.2, 'кг'], ['Молоко', 0.3, 'л'], ['Яйцо', 2, 'шт'], ['Сахар', 0.05, 'кг']],
                'steps' => [['Тесто', 'Замесить жидкое тесто.'], ['Жарка', 'Обжарить панкейки на сухой сковороде.']],
            ],
            [
                'title' => 'Компот',
                'description' => 'Ароматный домашний компот из сухофруктов и яблок. Полезный напиток для всей семьи.',
                'cooking_time' => 90,
                'calories' => 75,
                'difficulty' => 'легко',
                'price' => 0,
                'category' => 'Напитки',
                'ingredients' => [['Сахар', 0.1, 'кг'], ['Вода', 2, 'л']],
                'steps' => [['Подготовка', 'Промыть сухофрукты.'], ['Варка', 'Варить 30 минут с сахаром.']],
            ],
            [
                'title' => 'Пицца',
                'description' => 'Домашняя пицца «Маргарита» с томатным соусом, моцареллой и базиликом на тонком тесте.',
                'cooking_time' => 40,
                'calories' => 320,
                'difficulty' => 'средне',
                'price' => 320.00,
                'category' => 'Вторые блюда',
                'ingredients' => [['Мука', 0.4, 'кг'], ['Сыр', 0.2, 'кг'], ['Молоко', 0.1, 'л']],
                'steps' => [['Тесто', 'Замесить тесто и дать подойти.'], ['Начинка', 'Выложить соус и сыр.'], ['Выпечка', 'Выпекать при 220°C 15 минут.']],
                'trashed' => true,
            ],
        ];
    }
}
