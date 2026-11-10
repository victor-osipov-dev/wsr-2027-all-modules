@extends('admin.layouts.app')

@section('title', $recipe->exists ? 'Редактирование рецепта' : 'Новый рецепт')

@section('content')
    @php
        // Подготовка строк ингредиентов (значения из old() или из модели).
        $oldIngredients = old('ingredients');
        if ($oldIngredients === null) {
            $ingredientRows = $recipe->exists
                ? $recipe->ingredients->map(fn ($i) => [
                    'ingredient_id' => $i->id,
                    'quantity' => $i->pivot->quantity,
                    'unit' => $i->pivot->unit,
                ])->values()->all()
                : [];
            while (count($ingredientRows) < 2) {
                $ingredientRows[] = ['ingredient_id' => '', 'quantity' => '', 'unit' => 'шт'];
            }
        } else {
            $ingredientRows = array_values($oldIngredients);
        }

        // Подготовка строк шагов.
        $oldSteps = old('steps');
        if ($oldSteps === null) {
            $stepRows = $recipe->exists
                ? $recipe->steps->map(fn ($s) => [
                    'title' => $s->title,
                    'description' => $s->description,
                    'order' => $s->order,
                ])->values()->all()
                : [];
            while (count($stepRows) < 2) {
                $stepRows[] = ['title' => '', 'description' => '', 'order' => ''];
            }
        } else {
            $stepRows = array_values($oldSteps);
        }

        // Выбор изображения по умолчанию: старое значение формы либо текущее главное изображение.
        $selectedDefault = old('default_image');
        if ($selectedDefault === null) {
            $defaultImageId = $recipe->exists
                ? optional($recipe->images->firstWhere('is_default', true))->id
                : null;
            $selectedDefault = $defaultImageId ? 'existing_'.$defaultImageId : 'new_0';
        }
    @endphp

    <form method="POST"
          action="{{ $recipe->exists ? route('admin.recipes.update', $recipe) : route('admin.recipes.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($recipe->exists)
            @method('PUT')
        @endif

        <div class="card mb-3">
            <div class="card-header">Основная информация</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Название рецепта *</label>
                        <input type="text" name="title" id="title" maxlength="20"
                               value="{{ old('title', $recipe->title) }}"
                               class="form-control @error('title') is-invalid @enderror">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Не более 20 символов.</div>
                    </div>

                    <div class="col-md-3">
                        <label for="cooking_time" class="form-label">Время приготовления, мин *</label>
                        <input type="number" name="cooking_time" id="cooking_time" min="1"
                               value="{{ old('cooking_time', $recipe->cooking_time) }}"
                               class="form-control @error('cooking_time') is-invalid @enderror">
                        @error('cooking_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label for="calories" class="form-label">Калорийность, ккал</label>
                        <input type="number" name="calories" id="calories" min="0"
                               value="{{ old('calories', $recipe->calories) }}"
                               class="form-control @error('calories') is-invalid @enderror">
                        @error('calories')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-12">
                        <label for="description" class="form-label">Описание рецепта *</label>
                        <textarea name="description" id="description" rows="3" maxlength="250"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $recipe->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">От 50 до 250 символов.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="difficulty" class="form-label">Сложность *</label>
                        <select name="difficulty" id="difficulty" class="form-select @error('difficulty') is-invalid @enderror">
                            <option value="">— выберите —</option>
                            @foreach (\App\Models\Recipe::DIFFICULTIES as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty', $recipe->difficulty) === $difficulty)>{{ $difficulty }}</option>
                            @endforeach
                        </select>
                        @error('difficulty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="category_id" class="form-label">Категория *</label>
                        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">— выберите —</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $recipe->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label for="price" class="form-label">Цена, ₽</label>
                        <input type="number" name="price" id="price" min="0" step="0.01"
                               value="{{ old('price', $recipe->price) }}"
                               class="form-control @error('price') is-invalid @enderror">
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">0 — бесплатно, либо больше 100.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ингредиенты --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Ингредиенты *</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-ingredient>Добавить ингредиент</button>
            </div>
            <div class="card-body">
                <div id="ingredients-container">
                    @foreach ($ingredientRows as $index => $row)
                        <div class="row g-2 align-items-end ingredient-row mb-2">
                            <div class="col-md-6">
                                <select name="ingredients[{{ $index }}][ingredient_id]"
                                        class="form-select @error("ingredients.$index.ingredient_id") is-invalid @enderror">
                                    <option value="">— выберите ингредиент —</option>
                                    @foreach ($ingredients as $ingredient)
                                        <option value="{{ $ingredient->id }}"
                                                @selected((string) ($row['ingredient_id'] ?? '') === (string) $ingredient->id)>
                                            {{ $ingredient->title }} ({{ $ingredient->unit }})
                                        </option>
                                    @endforeach
                                </select>
                                @error("ingredients.$index.ingredient_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="0.01" min="0.01" placeholder="Количество"
                                       name="ingredients[{{ $index }}][quantity]"
                                       value="{{ $row['quantity'] ?? '' }}"
                                       class="form-control @error("ingredients.$index.quantity") is-invalid @enderror">
                                @error("ingredients.$index.quantity")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <select name="ingredients[{{ $index }}][unit]"
                                        class="form-select @error("ingredients.$index.unit") is-invalid @enderror">
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit }}" @selected(($row['unit'] ?? 'шт') === $unit)>{{ $unit }}</option>
                                    @endforeach
                                </select>
                                @error("ingredients.$index.unit")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100" data-remove-row title="Удалить">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('ingredients')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Шаги --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Шаги приготовления *</span>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-step>Добавить шаг</button>
            </div>
            <div class="card-body">
                <div id="steps-container">
                    @foreach ($stepRows as $index => $row)
                        <div class="border rounded p-3 mb-2 step-row">
                            <div class="row g-2">
                                <div class="col-md-9">
                                    <label class="form-label">Название шага *</label>
                                    <input type="text" maxlength="40" name="steps[{{ $index }}][title]"
                                           value="{{ $row['title'] ?? '' }}"
                                           class="form-control @error("steps.$index.title") is-invalid @enderror">
                                    @error("steps.$index.title")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Порядок</label>
                                    <input type="number" min="0" name="steps[{{ $index }}][order]"
                                           value="{{ $row['order'] ?? '' }}"
                                           class="form-control @error("steps.$index.order") is-invalid @enderror">
                                    @error("steps.$index.order")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger w-100" data-remove-row title="Удалить">×</button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Описание шага *</label>
                                    <textarea maxlength="255" rows="2" name="steps[{{ $index }}][description]"
                                              class="form-control @error("steps.$index.description") is-invalid @enderror">{{ $row['description'] ?? '' }}</textarea>
                                    @error("steps.$index.description")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('steps')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Изображения --}}
        <div class="card mb-3">
            <div class="card-header">Изображения рецепта</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="images" class="form-label">
                        {{ $recipe->exists ? 'Добавить изображения' : 'Изображения *' }}
                    </label>
                    <input type="file" name="images[]" id="images" multiple
                           accept="image/jpeg,image/png,image/webp"
                           class="form-control @error('images') is-invalid @enderror">
                    @error('images')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @foreach ($errors->getBag('default')->getMessages() as $errorKey => $errorMessages)
                        @if (str_starts_with((string) $errorKey, 'images.'))
                            @foreach ($errorMessages as $errorMessage)
                                <div class="text-danger small">{{ $errorMessage }}</div>
                            @endforeach
                        @endif
                    @endforeach
                    <div class="form-text">
                        Минимум 2 изображения (при создании). Каждое — не более 2 Мб и не меньше 500×500 px.
                        Форматы: jpg, jpeg, png, webp. Для каждого создаётся миниатюра 350×200 webp с водяным знаком.
                    </div>
                </div>

                {{-- Существующие изображения (при редактировании) --}}
                @if ($recipe->exists && $recipe->images->isNotEmpty())
                    <h6 class="mt-4">Текущие изображения</h6>
                    <div class="row g-3">
                        @foreach ($recipe->images as $image)
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <img src="{{ $image->thumbnailUrl() }}" class="card-img-top object-fit-contain p-2"
                                         style="height: 160px; background:#f8f9fa;" alt="Изображение рецепта">
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input type="radio" name="default_image" id="default-{{ $image->id }}"
                                                   value="existing_{{ $image->id }}" class="form-check-input"
                                                   @checked($selectedDefault === 'existing_'.$image->id)>
                                            <label for="default-{{ $image->id }}" class="form-check-label">Изображение по умолчанию</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="delete_images[]" id="delete-{{ $image->id }}"
                                                   value="{{ $image->id }}" class="form-check-input">
                                            <label for="delete-{{ $image->id }}" class="form-check-label text-danger">Удалить это изображение</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Выбор изображения по умолчанию среди новых файлов --}}
                <h6 class="mt-4">Изображение по умолчанию (для новых файлов)</h6>
                <div class="row">
                    @for ($i = 0; $i < 8; $i++)
                        <div class="col-md-3 form-check ms-3">
                            <input type="radio" name="default_image" id="new-default-{{ $i }}"
                                   value="new_{{ $i }}" class="form-check-input"
                                   @checked($selectedDefault === 'new_'.$i)>
                            <label for="new-default-{{ $i }}" class="form-check-label">Новое изображение №{{ $i + 1 }}</label>
                        </div>
                    @endfor
                </div>
                <div class="form-text">По умолчанию главным становится первое изображение.</div>
                @error('default_image')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                {{ $recipe->exists ? 'Сохранить изменения' : 'Создать рецепт' }}
            </button>
            <a href="{{ route('admin.recipes.index') }}" class="btn btn-outline-secondary">Отмена</a>
        </div>
    </form>

    {{-- Скрытые шаблоны строк для динамического добавления (JS). --}}
    <div id="ingredient-row-template" class="d-none">
        <div class="col-md-6">
            <select name="ingredients[__INDEX__][ingredient_id]" class="form-select">
                <option value="">— выберите ингредиент —</option>
                @foreach ($ingredients as $ingredient)
                    <option value="{{ $ingredient->id }}">{{ $ingredient->title }} ({{ $ingredient->unit }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" step="0.01" min="0.01" placeholder="Количество"
                   name="ingredients[__INDEX__][quantity]" class="form-control">
        </div>
        <div class="col-md-2">
            <select name="ingredients[__INDEX__][unit]" class="form-select">
                @foreach ($units as $unit)
                    <option value="{{ $unit }}">{{ $unit }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100" data-remove-row title="Удалить">×</button>
        </div>
    </div>

    <div id="step-row-template" class="d-none">
        <div class="row g-2">
            <div class="col-md-9">
                <label class="form-label">Название шага *</label>
                <input type="text" maxlength="40" name="steps[__INDEX__][title]" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Порядок</label>
                <input type="number" min="0" name="steps[__INDEX__][order]" class="form-control">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger w-100" data-remove-row title="Удалить">×</button>
            </div>
            <div class="col-12">
                <label class="form-label">Описание шага *</label>
                <textarea maxlength="255" rows="2" name="steps[__INDEX__][description]" class="form-control"></textarea>
            </div>
        </div>
    </div>
@endsection
