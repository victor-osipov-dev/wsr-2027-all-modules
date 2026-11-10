<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStepRequest;
use App\Http\Requests\Admin\UpdateStepRequest;
use App\Models\Recipe;
use App\Models\Step;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Управление шагами рецепта (вложенные маршруты внутри рецепта).
 */
class StepController extends Controller
{
    /**
     * Список шагов рецепта.
     */
    public function index(Recipe $recipe): View
    {
        return view('admin.steps.index', [
            'recipe' => $recipe->load('steps'),
        ]);
    }

    /**
     * Форма создания шага.
     */
    public function create(Recipe $recipe): View
    {
        $nextOrder = (int) $recipe->steps()->max('order') + 1;

        return view('admin.steps.form', [
            'recipe' => $recipe,
            'step' => new Step(['order' => $nextOrder]),
        ]);
    }

    /**
     * Сохранение нового шага.
     */
    public function store(StoreStepRequest $request, Recipe $recipe): RedirectResponse
    {
        $data = $request->validated();
        $data['order'] = $data['order'] ?? ((int) $recipe->steps()->max('order') + 1);

        $recipe->steps()->create($data);

        return redirect()->route('admin.recipes.steps.index', $recipe)
            ->with('success', 'Шаг добавлен.');
    }

    /**
     * Форма редактирования шага.
     */
    public function edit(Recipe $recipe, Step $step): View
    {
        $this->ensureStepBelongsToRecipe($step, $recipe);

        return view('admin.steps.form', [
            'recipe' => $recipe,
            'step' => $step,
        ]);
    }

    /**
     * Обновление шага.
     */
    public function update(UpdateStepRequest $request, Recipe $recipe, Step $step): RedirectResponse
    {
        $this->ensureStepBelongsToRecipe($step, $recipe);

        $data = $request->validated();
        $data['order'] = $data['order'] ?? $step->order;

        $step->update($data);

        return redirect()->route('admin.recipes.steps.index', $recipe)
            ->with('success', 'Шаг обновлён.');
    }

    /**
     * Удаление шага.
     */
    public function destroy(Recipe $recipe, Step $step): RedirectResponse
    {
        $this->ensureStepBelongsToRecipe($step, $recipe);

        $step->delete();

        return redirect()->route('admin.recipes.steps.index', $recipe)
            ->with('success', 'Шаг удалён.');
    }

    /**
     * Проверяет, что шаг принадлежит указанному рецепту.
     */
    private function ensureStepBelongsToRecipe(Step $step, Recipe $recipe): void
    {
        abort_unless($step->recipe_id === $recipe->getKey(), 404);
    }
}
