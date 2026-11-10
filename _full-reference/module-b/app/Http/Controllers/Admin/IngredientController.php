<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIngredientRequest;
use App\Http\Requests\Admin\UpdateIngredientRequest;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Управление ингредиентами.
 *
 * При удалении ингредиент удаляется из всех рецептов, где он используется.
 */
class IngredientController extends Controller
{
    /**
     * Список ингредиентов.
     */
    public function index(): View
    {
        $ingredients = Ingredient::query()
            ->withCount('recipes')
            ->orderBy('title')
            ->paginate(10);

        return view('admin.ingredients.index', [
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * Форма создания ингредиента.
     */
    public function create(): View
    {
        return view('admin.ingredients.form', [
            'ingredient' => new Ingredient(),
            'units' => Ingredient::UNITS,
        ]);
    }

    /**
     * Сохранение ингредиента.
     */
    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        Ingredient::create($request->validated());

        return redirect()->route('admin.ingredients.index')
            ->with('success', 'Ингредиент создан.');
    }

    /**
     * Форма редактирования ингредиента.
     */
    public function edit(Ingredient $ingredient): View
    {
        return view('admin.ingredients.form', [
            'ingredient' => $ingredient,
            'units' => Ingredient::UNITS,
        ]);
    }

    /**
     * Обновление ингредиента.
     */
    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $ingredient->update($request->validated());

        return redirect()->route('admin.ingredients.index')
            ->with('success', 'Ингредиент обновлён.');
    }

    /**
     * Удаление ингредиента (и удаление его из всех рецептов).
     */
    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->recipes()->detach();
        $ingredient->delete();

        return redirect()->route('admin.ingredients.index')
            ->with('success', 'Ингредиент удалён из всех рецептов.');
    }
}
