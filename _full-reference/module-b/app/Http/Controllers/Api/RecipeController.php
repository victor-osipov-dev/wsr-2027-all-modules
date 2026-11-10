<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeDetailResource;
use App\Http\Resources\RecipeListResource;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Просмотр рецептов через API (только для роли «user»).
 *
 * Рецепты, помеченные на удаление, в выдачу не попадают.
 */
class RecipeController extends Controller
{
    /** Значения сортировки. */
    private const SORTS = [
        'newest_desc' => ['created_at', 'desc'],
        'newest_asc' => ['created_at', 'asc'],
        'time_asc' => ['cooking_time', 'asc'],
        'time_desc' => ['cooking_time', 'desc'],
        'calories_asc' => ['calories', 'asc'],
        'calories_desc' => ['calories', 'desc'],
    ];

    /**
     * GET /recipe-api/recipes
     *
     * Поддерживает фильтры (category_id, difficulty, time_min, time_max,
     * price_min, price_max), пагинацию (page, per_page) и сортировку (sort).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 5);
        $perPage = max(1, min($perPage, 100));

        $query = Recipe::query()
            ->with(['category', 'images'])
            ->filter($this->filters($request));

        $this->applySort($query, (string) $request->input('sort', 'newest_desc'));

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'current_page' => $paginator->currentPage(),
            'data' => RecipeListResource::collection($paginator->items())->resolve(),
            'first_page_url' => $paginator->url(1),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'path' => $paginator->path(),
            'per_page' => $paginator->perPage(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ], 200);
    }

    /**
     * GET /recipe-api/recipes/{id}
     *
     * Детальная карточка рецепта с изображениями, ингредиентами и шагами.
     */
    public function show(int $id): JsonResponse
    {
        $recipe = Recipe::query()
            ->with(['category', 'images', 'ingredients', 'steps'])
            ->find($id);

        if ($recipe === null) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }

        return response()->json(
            (new RecipeDetailResource($recipe))->resolve(),
            200,
        );
    }

    /**
     * Извлекает параметры фильтрации из запроса.
     *
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return [
            'category_id' => $request->input('category_id'),
            'difficulty' => $request->input('difficulty'),
            'time_min' => $request->input('time_min'),
            'time_max' => $request->input('time_max'),
            'price_min' => $request->input('price_min'),
            'price_max' => $request->input('price_max'),
        ];
    }

    /**
     * Применяет сортировку, подставляя значение по умолчанию (новизна, убывание).
     */
    private function applySort(Builder $query, string $sort): void
    {
        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['newest_desc'];

        $query->orderBy($column, $direction);
    }
}
