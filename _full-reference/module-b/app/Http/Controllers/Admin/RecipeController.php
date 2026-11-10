<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRecipeRequest;
use App\Http\Requests\Admin\UpdateRecipeRequest;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Services\RecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Управление рецептами в админ-панели.
 *
 * Список содержит все рецепты (в том числе помеченные на удаление) с
 * фильтрацией, сортировкой и пагинацией по 5 записей.
 */
class RecipeController extends Controller
{
    /** Доступные варианты сортировки. */
    private const SORTS = [
        'newest_desc' => ['created_at', 'desc'],
        'newest_asc' => ['created_at', 'asc'],
        'time_asc' => ['cooking_time', 'asc'],
        'time_desc' => ['cooking_time', 'desc'],
    ];

    public function __construct(
        private readonly RecipeService $recipeService,
    ) {
    }

    /**
     * Список рецептов с фильтрами, сортировкой и пагинацией.
     */
    public function index(Request $request): View
    {
        $filters = [
            'difficulty' => $request->input('difficulty'),
            'time_range' => $request->input('time_range'),
            'category_id' => $request->input('category_id'),
            'price' => $request->input('price'),
        ];

        $sort = (string) $request->input('sort', 'newest_desc');

        $query = Recipe::query()
            ->withTrashed()
            ->with(['category', 'images'])
            ->filter([
                'difficulty' => $filters['difficulty'],
                'category_id' => $filters['category_id'],
            ]);

        // Фильтр по времени приготовления.
        $query->when($filters['time_range'] === 'up_to_15', fn ($q) => $q->where('cooking_time', '<=', 15))
            ->when($filters['time_range'] === 'from_60_to_120', fn ($q) => $q->whereBetween('cooking_time', [60, 120]))
            ->when($filters['time_range'] === 'over_120', fn ($q) => $q->where('cooking_time', '>', 120));

        // Фильтр по стоимости: 0 — без ограничений.
        $query->when(is_numeric($filters['price']) && (float) $filters['price'] > 0,
            fn ($q) => $q->whereRaw('COALESCE(price, 0) <= ?', [(float) $filters['price']]));

        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['newest_desc'];
        $query->orderBy($column, $direction);

        $recipes = $query->paginate(5)->withQueryString();

        return view('admin.recipes.index', [
            'recipes' => $recipes,
            'filters' => $filters,
            'sort' => $sort,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Форма создания рецепта.
     */
    public function create(): View
    {
        return view('admin.recipes.form', [
            'recipe' => new Recipe(),
            'categories' => Category::query()->orderBy('name')->get(),
            'ingredients' => Ingredient::query()->orderBy('title')->get(),
            'units' => Ingredient::UNITS,
        ]);
    }

    /**
     * Сохранение нового рецепта.
     */
    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['images'] = array_filter((array) $request->file('images', []));

        $this->recipeService->create($data, $request->user()->getKey());

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Рецепт успешно создан.');
    }

    /**
     * Просмотр рецепта.
     */
    public function show(Recipe $recipe): View
    {
        $recipe->load(['category', 'images', 'ingredients', 'steps', 'user']);

        return view('admin.recipes.show', [
            'recipe' => $recipe,
        ]);
    }

    /**
     * Форма редактирования рецепта.
     */
    public function edit(Recipe $recipe): View
    {
        $recipe->load(['images', 'ingredients', 'steps']);

        return view('admin.recipes.form', [
            'recipe' => $recipe,
            'categories' => Category::query()->orderBy('name')->get(),
            'ingredients' => Ingredient::query()->orderBy('title')->get(),
            'units' => Ingredient::UNITS,
        ]);
    }

    /**
     * Обновление рецепта.
     */
    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $data = $request->validated();
        $data['images'] = array_filter((array) $request->file('images', []));

        $this->recipeService->update($recipe, $data);

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Рецепт успешно обновлён.');
    }

    /**
     * Мягкое удаление (пометка на удаление).
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->recipeService->delete($recipe);

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Рецепт помечен на удаление.');
    }

    /**
     * Восстановление рецепта.
     */
    public function restore(Recipe $recipe): RedirectResponse
    {
        $this->recipeService->restore($recipe);

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Рецепт восстановлен.');
    }

    /**
     * Окончательное удаление вместе с файлами и связанными данными.
     */
    public function forceDelete(Recipe $recipe): RedirectResponse
    {
        $this->recipeService->forceDelete($recipe);

        return redirect()->route('admin.recipes.index')
            ->with('success', 'Рецепт удалён окончательно.');
    }
}
