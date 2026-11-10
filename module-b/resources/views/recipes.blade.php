@extends('layout')
@section('title', $form ? ($form->exists ? 'Редактирование рецепта' : 'Новый рецепт') : 'Рецепты')
@section('content')
    @if ($form)
        @php
            $ingredientRows = old(
                'ingredients',
                $form->exists
                    ? $form->ingredients
                        ->map(
                            fn($i) => [
                                'ingredient_id' => $i->id,
                                'quantity' => $i->pivot->quantity,
                                'unit' => $i->pivot->unit,
                            ],
                        )
                        ->all()
                    : [],
            );
            while (count($ingredientRows) < 3) {
                $ingredientRows[] = ['ingredient_id' => '', 'quantity' => '', 'unit' => 'шт'];
            }
            $stepRows = old(
                'steps',
                $form->exists
                    ? $form->steps->map(fn($s) => ['title' => $s->title, 'description' => $s->description])->all()
                    : [],
            );
            while (count($stepRows) < 3) {
                $stepRows[] = ['title' => '', 'description' => ''];
            }
            $def = $form->exists ? $form->images->firstWhere('is_default', true) : null;
            $defaultKey = old('default_image', $def ? 'existing_' . $def->id : 'new_0');
            $existingImages = $form->exists ? $form->images : collect();
        @endphp
        <h5 class="mb-3">{{ $form->exists ? 'Редактирование рецепта' : 'Новый рецепт' }}</h5>
        <form method="POST" enctype="multipart/form-data"
            action="{{ $form->exists ? route('admin.recipes.update', $form) : route('admin.recipes.store') }}">
            @csrf
            @if ($form->exists)
                @method('PUT')
            @endif
            <div class="mb-3"><label class="form-label" for="title">Название рецепта * (до 20 символов)</label><input
                    type="text" name="title" id="title" maxlength="20" value="{{ old('title', $form->title) }}"
                    class="form-control @error('title') is-invalid @enderror">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="row g-2">
                <div class="col-md-4 mb-2"><label class="form-label" for="cooking_time">Время, мин *</label><input
                        type="number" name="cooking_time" id="cooking_time" min="1"
                        value="{{ old('cooking_time', $form->cooking_time) }}"
                        class="form-control @error('cooking_time') is-invalid @enderror">
                    @error('cooking_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 mb-2"><label class="form-label" for="calories">Калорийность, ккал</label><input
                        type="number" name="calories" id="calories" min="0"
                        value="{{ old('calories', $form->calories) }}"
                        class="form-control @error('calories') is-invalid @enderror">
                    @error('calories')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 mb-2"><label class="form-label" for="price">Цена, ₽ (0 или &gt; 100)</label><input
                        type="number" name="price" id="price" min="0" step="0.01"
                        value="{{ old('price', $form->price) }}" class="form-control @error('price') is-invalid @enderror">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="mb-3"><label class="form-label" for="description">Описание * (50–250 символов)</label>
                <textarea name="description" id="description" rows="3" maxlength="250"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $form->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="row g-2">
                <div class="col-md-6 mb-2"><label class="form-label" for="difficulty">Сложность *</label><select
                        name="difficulty" id="difficulty" class="form-select @error('difficulty') is-invalid @enderror">
                        <option value="">— выберите —</option>
                        @foreach (\App\Models\Recipe::DIFFICULTIES as $difficulty)
                            <option value="{{ $difficulty }}" @selected(old('difficulty', $form->difficulty) === $difficulty)>{{ $difficulty }}</option>
                        @endforeach
                    </select>
                    @error('difficulty')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-2"><label class="form-label" for="category_id">Категория *</label><select
                        name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">— выберите —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $form->category_id) === (string) $category->id)>{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <h6 class="mt-3">Ингредиенты * (минимум 1)</h6>
            @foreach ($ingredientRows as $index => $row)
                <div class="input-group mb-2"><select name="ingredients[{{ $index }}][ingredient_id]"
                        class="form-select @error("ingredients.$index.ingredient_id") is-invalid @enderror">
                        <option value="">— выберите ингредиент —</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" @selected((string) ($row['ingredient_id'] ?? '') === (string) $ingredient->id)>{{ $ingredient->title }}
                                ({{ $ingredient->unit }})</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0.01" placeholder="Количество"
                        name="ingredients[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? '' }}"
                        class="form-control @error("ingredients.$index.quantity") is-invalid @enderror"><select
                        name="ingredients[{{ $index }}][unit]" class="form-select">
                        @foreach ($units as $unit)
                            <option value="{{ $unit }}" @selected(($row['unit'] ?? 'шт') === $unit)>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <h6 class="mt-3">Шаги приготовления * (минимум 1)</h6>
            @foreach ($stepRows as $index => $row)
                <div class="input-group mb-2"><input type="text" name="steps[{{ $index }}][title]" maxlength="40"
                        placeholder="Название шага" value="{{ $row['title'] ?? '' }}"
                        class="form-control @error("steps.$index.title") is-invalid @enderror"><input type="text"
                        name="steps[{{ $index }}][description]" maxlength="255" placeholder="Описание шага"
                        value="{{ $row['description'] ?? '' }}"
                        class="form-control @error("steps.$index.description") is-invalid @enderror"></div>
            @endforeach
            <h6 class="mt-3">Изображения {{ $form->exists ? '' : '*' }} — ≥1, ≤2 Мб, ≥500×500 px, jpg/jpeg/png/webp</h6>
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                class="form-control @error('images') is-invalid @enderror">
            @error('images')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @foreach ($existingImages as $image)
                <div class="form-check"><input type="radio" name="default_image" id="existing-{{ $image->id }}"
                        value="existing_{{ $image->id }}" class="form-check-input" @checked($defaultKey === 'existing_' . $image->id)><label
                        class="form-check-label" for="existing-{{ $image->id }}">Главное: <img
                            src="{{ '/storage/' . $image->thumbnail }}" alt="" style="height:40px"></label><input
                        type="checkbox" name="delete_images[]" id="delete-{{ $image->id }}"
                        value="{{ $image->id }}" class="form-check-input"><label class="form-check-label text-danger"
                        for="delete-{{ $image->id }}">Удалить это изображение</label></div>
            @endforeach
            @for ($i = 0; $i < 4; $i++)
                <div class="form-check form-check-inline"><input type="radio" name="default_image"
                        id="new-{{ $i }}" value="new_{{ $i }}" class="form-check-input"
                        @checked($defaultKey === 'new_' . $i)><label class="form-check-label" for="new-{{ $i }}">Новое
                        №{{ $i + 1 }}</label></div>
            @endfor
            <div class="mt-3 d-flex gap-2"><button type="submit"
                    class="btn btn-primary">{{ $form->exists ? 'Сохранить изменения' : 'Создать рецепт' }}</button><a
                    href="{{ route('admin.recipes.index') }}" class="btn btn-outline-secondary">Отмена</a></div>
        </form>
    @endif
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Список рецептов (включая помеченные на удаление)</h5><a
            href="{{ route('admin.recipes.create') }}" class="btn btn-primary">Добавить рецепт</a>
    </div>
    <form method="GET" action="{{ route('admin.recipes.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-2"><label class="form-label" for="f-difficulty">Сложность</label><select name="difficulty"
                id="f-difficulty" class="form-select">
                <option value="">Все</option>
                @foreach (\App\Models\Recipe::DIFFICULTIES as $difficulty)
                    <option value="{{ $difficulty }}" @selected($filters['difficulty'] === $difficulty)>{{ $difficulty }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label" for="time_range">Время</label><select name="time_range"
                id="time_range" class="form-select">
                <option value="">Любое</option>
                <option value="up_to_15" @selected($filters['time_range'] === 'up_to_15')>до 15 мин</option>
                <option value="from_60_to_120" @selected($filters['time_range'] === 'from_60_to_120')>60–120 мин</option>
                <option value="over_120" @selected($filters['time_range'] === 'over_120')>свыше 120 мин</option>
            </select></div>
        <div class="col-md-2"><label class="form-label" for="f-category">Категория</label><select name="category_id"
                id="f-category" class="form-select">
                <option value="">Все</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label" for="f-price">Цена (0 — без ограничений)</label><input
                type="number" name="price" id="f-price" min="0" step="0.01"
                value="{{ $filters['price'] }}" class="form-control" placeholder="0"></div>
        <div class="col-md-2"><label class="form-label" for="sort">Сортировка</label><select name="sort"
                id="sort" class="form-select">
                <option value="newest_desc" @selected($sort === 'newest_desc')>По новизне 🔽</option>
                <option value="newest_asc" @selected($sort === 'newest_asc')>По новизне 🔼</option>
                <option value="time_asc" @selected($sort === 'time_asc')>По времени 🔼</option>
                <option value="time_desc" @selected($sort === 'time_desc')>По времени 🔽</option>
            </select></div>
        <div class="col-md-2 d-flex gap-2"><button type="submit" class="btn btn-primary w-100">Применить</button><a
                href="{{ route('admin.recipes.index') }}" class="btn btn-outline-secondary">Сброс</a></div>
    </form>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Изображение</th>
                <th>Название</th>
                <th>Время</th>
                <th>Цена</th>
                <th>Сложность</th>
                <th>Категория</th>
                <th class="text-end">Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recipes as $recipe)
                <tr class="{{ $recipe->trashed() ? 'table-warning' : '' }}">
                    <td>
                        @if ($recipe->default_image_url)
                            <img src="{{ $recipe->default_image_url }}" alt="{{ $recipe->title }}"
                            style="height:40px">@else<span class="text-muted small">нет</span>
                        @endif
                    </td>
                    <td>{{ $recipe->title }} @if ($recipe->trashed())
                            <span class="badge text-bg-warning">Помечен на удаление</span>
                        @endif
                    </td>
                    <td>{{ $recipe->formatted_time }}</td>
                    <td>{{ $recipe->price > 0 ? number_format((float) $recipe->price, 2, ',', ' ') . ' ₽' : 'Бесплатно' }}
                    </td>
                    <td>{{ $recipe->difficulty }}</td>
                    <td>{{ $recipe->category?->name }}</td>
                    <td class="text-end text-nowrap">
                        @if ($recipe->trashed())
                            <form action="{{ route('admin.recipes.restore', $recipe) }}" method="POST"
                                class="d-inline">@csrf @method('PATCH')<button type="submit"
                                    class="btn btn-sm btn-success">Восстановить</button></form>
                            <form action="{{ route('admin.recipes.force-delete', $recipe) }}" method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Удалить окончательно? Все файлы будут удалены.');">@csrf
                                @method('DELETE')<button type="submit" class="btn btn-sm btn-danger">Удалить
                                    окончательно</button></form>
                        @else
                            <a href="{{ route('admin.recipes.edit', $recipe) }}"
                                class="btn btn-sm btn-primary">Редактировать</a>
                            <form action="{{ route('admin.recipes.destroy', $recipe) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Пометить рецепт на удаление?');">@csrf @method('DELETE')<button
                                    type="submit" class="btn btn-sm btn-outline-danger">Удалить</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Рецепты не найдены.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $recipes->links() }}</div>
@endsection
