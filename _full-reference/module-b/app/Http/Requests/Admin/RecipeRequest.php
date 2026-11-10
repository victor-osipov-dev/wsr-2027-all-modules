<?php

namespace App\Http\Requests\Admin;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Базовый запрос для формы рецепта (создание и редактирование).
 *
 * Реализует правила валидации согласно конкурсному заданию:
 *  - название ≤ 20 символов;
 *  - описание 50–250 символов;
 *  - время приготовления — число;
 *  - сложность — одно из значений (легко, средне, сложно);
 *  - цена 0 (бесплатно) или больше 100;
 *  - изображения: от 2 шт, ≤ 2 Мб, ≥ 500px по каждой стороне, форматы jpg/jpeg/png/webp;
 *  - минимум 1 ингредиент и 1 шаг.
 */
abstract class RecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Удаляет пустые строки ингредиентов и шагов, нормализует цену.
     */
    protected function prepareForValidation(): void
    {
        $ingredients = collect($this->input('ingredients', []))
            ->filter(fn ($row) => filled($row['ingredient_id'] ?? null))
            ->values()
            ->all();

        $steps = collect($this->input('steps', []))
            ->filter(fn ($row) => filled($row['title'] ?? null) || filled($row['description'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'ingredients' => $ingredients,
            'steps' => $steps,
            'price' => $this->input('price') === '' ? 0 : $this->input('price'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'min:50', 'max:250'],
            'cooking_time' => ['required', 'integer', 'min:1'],
            'calories' => ['nullable', 'integer', 'min:0'],
            'difficulty' => ['required', Rule::in(Recipe::DIFFICULTIES)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],

            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'ingredients.*.unit' => ['required', Rule::in(Ingredient::UNITS)],

            'steps' => ['required', 'array', 'min:1'],
            'steps.*.title' => ['required', 'string', 'max:40'],
            'steps.*.description' => ['required', 'string', 'max:255'],
            'steps.*.order' => ['nullable', 'integer', 'min:0'],

            'default_image' => ['nullable', 'string'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
        ], $this->imageRules());
    }

    /**
     * Правила для загружаемых изображений (различаются при создании/редактировании).
     *
     * @return array<string, mixed>
     */
    abstract protected function imageRules(): array;

    /**
     * Дополнительные проверки: диапазон цены и наличие главного изображения.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $price = $this->input('price');

            if ($price !== null && $price !== '' && (float) $price > 0 && (float) $price <= 100) {
                $validator->errors()->add('price', 'Цена должна быть 0 (бесплатно) или больше 100.');
            }

            // Убеждаемся, что хотя бы одно изображение остаётся у рецепта (при редактировании).
            if ($this->route('recipe') !== null && $this->remainingImageCount() === 0) {
                $validator->errors()->add('images', 'У рецепта должно остаться хотя бы одно изображение.');
            }
        });
    }

    /**
     * Количество изображений, которые останутся после сохранения формы.
     */
    protected function remainingImageCount(): int
    {
        $newCount = count(array_filter((array) $this->file('images', []), fn ($file) => $file !== null));

        $recipe = $this->route('recipe');

        if ($recipe === null) {
            return $newCount;
        }

        $deleteIds = array_filter((array) $this->input('delete_images', []));
        $existingCount = $recipe->images()->whereNotIn('id', $deleteIds)->count();

        return $existingCount + $newCount;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название рецепта.',
            'title.max' => 'Название рецепта не должно превышать 20 символов.',
            'description.required' => 'Укажите описание рецепта.',
            'description.min' => 'Описание рецепта должно содержать минимум 50 символов.',
            'description.max' => 'Описание рецепта не должно превышать 250 символов.',
            'cooking_time.required' => 'Укажите время приготовления.',
            'cooking_time.integer' => 'Время приготовления должно быть числом.',
            'cooking_time.min' => 'Время приготовления должно быть положительным числом.',
            'calories.integer' => 'Калорийность должна быть целым числом.',
            'difficulty.required' => 'Выберите сложность приготовления.',
            'difficulty.in' => 'Сложность должна быть одной из: легко, средне, сложно.',
            'price.numeric' => 'Цена должна быть числом.',
            'price.min' => 'Цена не может быть отрицательной.',
            'category_id.required' => 'Выберите категорию рецепта.',
            'category_id.exists' => 'Выбранная категория не найдена.',
            'ingredients.required' => 'Добавьте хотя бы один ингредиент.',
            'ingredients.min' => 'Добавьте хотя бы один ингредиент.',
            'ingredients.*.ingredient_id.required' => 'Выберите ингредиент.',
            'ingredients.*.ingredient_id.exists' => 'Выбранный ингредиент не найден.',
            'ingredients.*.quantity.required' => 'Укажите количество ингредиента.',
            'ingredients.*.quantity.numeric' => 'Количество должно быть числом.',
            'ingredients.*.unit.required' => 'Выберите единицу измерения.',
            'ingredients.*.unit.in' => 'Недопустимая единица измерения.',
            'steps.required' => 'Добавьте хотя бы один шаг.',
            'steps.min' => 'Добавьте хотя бы один шаг.',
            'steps.*.title.required' => 'Укажите название шага.',
            'steps.*.title.max' => 'Название шага не должно превышать 40 символов.',
            'steps.*.description.required' => 'Укажите описание шага.',
            'steps.*.description.max' => 'Описание шага не должно превышать 255 символов.',
            'images.required' => 'Загрузите минимум 2 изображения.',
            'images.min' => 'Загрузите минимум 2 изображения.',
            'images.*.image' => 'Файл должен быть изображением.',
            'images.*.mimes' => 'Допустимые форматы изображений: jpg, jpeg, png, webp.',
            'images.*.max' => 'Размер изображения не должен превышать 2 Мб.',
            'images.*.dimensions' => 'Изображение должно быть не меньше 500×500 пикселей.',
            'images.*.uploaded' => 'Не удалось загрузить изображение (превышен допустимый размер).',
        ];
    }
}
