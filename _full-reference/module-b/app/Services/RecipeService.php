<?php

namespace App\Services;

use App\Events\RecipeCreated;
use App\Models\Recipe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Бизнес-логика работы с рецептами (создание, редактирование, удаление).
 *
 * Инкапсулирует работу с ингредиентами, шагами и изображениями, а также
 * перегенерацию водяных знаков при изменении названия рецепта.
 */
class RecipeService
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {
    }

    /**
     * Создаёт рецепт вместе со связями и загруженными изображениями.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $authorId = null): Recipe
    {
        $recipe = DB::transaction(function () use ($data, $authorId) {
            $recipe = Recipe::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'cooking_time' => (int) $data['cooking_time'],
                'calories' => $data['calories'] ?? null,
                'difficulty' => $data['difficulty'],
                'price' => $data['price'] ?? 0,
                'category_id' => $data['category_id'],
                'user_id' => $authorId,
            ]);

            $this->syncIngredients($recipe, $data['ingredients'] ?? []);
            $this->syncSteps($recipe, $data['steps'] ?? []);
            $this->storeImages($recipe, $data['images'] ?? [], $data['default_image'] ?? null);

            return $recipe;
        });

        // Уведомляем подключённых клиентов (Модуль В) о новом рецепте.
        $this->broadcastCreated($recipe);

        return $recipe;
    }

    /**
     * Обновляет рецепт, пересобирая связи и обрабатывая изображения.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Recipe $recipe, array $data): Recipe
    {
        $titleChanged = $recipe->title !== $data['title'];

        DB::transaction(function () use ($recipe, $data) {
            $recipe->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'cooking_time' => (int) $data['cooking_time'],
                'calories' => $data['calories'] ?? null,
                'difficulty' => $data['difficulty'],
                'price' => $data['price'] ?? 0,
                'category_id' => $data['category_id'],
            ]);

            $this->syncIngredients($recipe, $data['ingredients'] ?? []);
            $this->syncSteps($recipe, $data['steps'] ?? []);

            // Удаляем отмеченные изображения (вместе с файлами на диске).
            $this->deleteImages($recipe, $data['delete_images'] ?? []);

            // Добавляем новые изображения и назначаем изображение по умолчанию.
            $this->storeImages($recipe, $data['images'] ?? [], $data['default_image'] ?? null);
        });

        // Если название изменилось — обновляем водяные знаки на всех миниатюрах.
        if ($titleChanged) {
            $this->imageService->regenerateThumbnails($recipe->fresh('images'));
        }

        return $recipe->fresh(['images', 'ingredients', 'steps', 'category']);
    }

    /**
     * Мягкое удаление рецепта.
     */
    public function delete(Recipe $recipe): void
    {
        $recipe->delete();
    }

    /**
     * Восстановление рецепта, помеченного на удаление.
     */
    public function restore(Recipe $recipe): void
    {
        $recipe->restore();
    }

    /**
     * Окончательное удаление рецепта вместе со всеми данными и файлами.
     */
    public function forceDelete(Recipe $recipe): void
    {
        DB::transaction(function () use ($recipe) {
            // Удаляем каталог с оригиналами и миниатюрами.
            $this->imageService->deleteRecipeDirectory($recipe);

            // Явно чистим связанные данные (помимо каскадных ограничений БД).
            $recipe->ingredients()->detach();
            $recipe->images()->get()->each(fn ($image) => $image->delete());
            $recipe->steps()->delete();
            $recipe->purchases()->delete();
            $recipe->favorites()->delete();
            $recipe->forceDelete();
        });
    }

    /**
     * Полностью заменяет список ингредиентов рецепта.
     *
     * @param  array<int, array<string, mixed>>  $ingredients
     */
    public function syncIngredients(Recipe $recipe, array $ingredients): void
    {
        $sync = [];

        foreach ($ingredients as $ingredient) {
            if (empty($ingredient['ingredient_id'])) {
                continue;
            }

            $sync[$ingredient['ingredient_id']] = [
                'quantity' => $ingredient['quantity'] ?? 1,
                'unit' => $ingredient['unit'] ?? 'шт',
            ];
        }

        $recipe->ingredients()->sync($sync);
    }

    /**
     * Полностью заменяет список шагов рецепта.
     *
     * @param  array<int, array<string, mixed>>  $steps
     */
    public function syncSteps(Recipe $recipe, array $steps): void
    {
        $recipe->steps()->delete();

        $position = 1;

        foreach ($steps as $step) {
            if (empty($step['title'])) {
                continue;
            }

            $recipe->steps()->create([
                'title' => $step['title'],
                'description' => $step['description'] ?? '',
                'order' => isset($step['order']) && is_numeric($step['order']) ? (int) $step['order'] : $position,
            ]);

            $position++;
        }
    }

    /**
     * Сохраняет новые изображения и назначает изображение по умолчанию.
     *
     * @param  array<int, UploadedFile>  $images
     */
    private function storeImages(Recipe $recipe, array $images, mixed $defaultImage): void
    {
        /** @var array<int, \App\Models\RecipeImage> $created */
        $created = [];

        foreach (array_values($images) as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $created[$index] = $this->imageService->storeRecipeImage($recipe, $file, $recipe->title, false);
        }

        $recipe->load('images');

        // Определяем выбранное изображение по умолчанию.
        $selected = null;

        if (is_string($defaultImage)) {
            if (str_starts_with($defaultImage, 'existing_')) {
                $id = (int) substr($defaultImage, strlen('existing_'));
                $selected = $recipe->images->firstWhere('id', $id);
            } elseif (str_starts_with($defaultImage, 'new_')) {
                $index = (int) substr($defaultImage, strlen('new_'));
                $selected = $created[$index] ?? null;
            }
        }

        // Если явный выбор не найден — главным становится первое изображение.
        $selected ??= $recipe->images->first();

        if ($selected === null) {
            return;
        }

        $recipe->images()->update(['is_default' => false]);
        $recipe->images()->whereKey($selected->getKey())->update(['is_default' => true]);
    }

    /**
     * Удаляет выбранные изображения вместе с файлами.
     *
     * @param  array<int, int|string>  $imageIds
     */
    private function deleteImages(Recipe $recipe, array $imageIds): void
    {
        $imageIds = array_filter(array_map('intval', $imageIds));

        if ($imageIds === []) {
            return;
        }

        $images = $recipe->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            $this->imageService->deleteImageFiles($image);
            $image->delete();
        }
    }

    /**
     * Отправляет событие о создании рецепта, не прерывая работу при сбое broadcast.
     */
    private function broadcastCreated(Recipe $recipe): void
    {
        try {
            $recipe->loadMissing(['images', 'category']);
            RecipeCreated::dispatch($recipe);
        } catch (Throwable $e) {
            Log::warning('Не удалось отправить событие RecipeCreated: '.$e->getMessage());
        }
    }
}
