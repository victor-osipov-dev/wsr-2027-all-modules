<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Работа с избранными рецептами пользователя.
 */
class FavoriteController extends Controller
{
    /**
     * GET /recipe-api/favorites — список избранного.
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::query()
            ->where('user_id', $request->user()->getKey())
            ->whereHas('recipe') // исключаем рецепты, помеченные на удаление
            ->with(['recipe.images'])
            ->latest()
            ->get();

        $recipes = $favorites->map(function (Favorite $favorite) {
            $recipe = $favorite->recipe;
            // Дата добавления в избранное прокидывается в ресурс.
            $recipe->favorited_at = $favorite->created_at;

            return $recipe;
        })->values();

        return response()->json(FavoriteResource::collection($recipes)->resolve(), 200);
    }

    /**
     * POST /recipe-api/favorites — добавить рецепт в избранное.
     */
    public function store(FavoriteRequest $request): JsonResponse
    {
        $recipe = Recipe::query()->find($request->integer('recipe_id'));

        if ($recipe === null) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }

        $alreadyExists = Favorite::query()
            ->where('user_id', $request->user()->getKey())
            ->where('recipe_id', $recipe->getKey())
            ->exists();

        if ($alreadyExists) {
            return response()->json(['message' => 'Recipe already in favorites'], 409);
        }

        Favorite::create([
            'user_id' => $request->user()->getKey(),
            'recipe_id' => $recipe->getKey(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recipe added to favorites',
        ], 201);
    }

    /**
     * DELETE /recipe-api/favorites/{recipeId} — удалить из избранного.
     */
    public function destroy(Request $request, int $recipeId): JsonResponse
    {
        $favorite = Favorite::query()
            ->where('user_id', $request->user()->getKey())
            ->where('recipe_id', $recipeId)
            ->first();

        if ($favorite === null) {
            return response()->json(['message' => 'Favorite not found'], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recipe removed from favorites',
        ], 200);
    }
}
