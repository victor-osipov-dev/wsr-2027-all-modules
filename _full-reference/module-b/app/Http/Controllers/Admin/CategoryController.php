<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Управление категориями рецептов.
 */
class CategoryController extends Controller
{
    /**
     * Список категорий.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('recipes')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Форма создания категории.
     */
    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(),
        ]);
    }

    /**
     * Сохранение категории.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория создана.');
    }

    /**
     * Форма редактирования категории.
     */
    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
        ]);
    }

    /**
     * Обновление категории.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория обновлена.');
    }

    /**
     * Удаление категории.
     *
     * Запрещено, если в категории есть рецепты.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->hasRecipes()) {
            return back()->with('error', 'Нельзя удалить категорию, в которой есть рецепты.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория удалена.');
    }
}
