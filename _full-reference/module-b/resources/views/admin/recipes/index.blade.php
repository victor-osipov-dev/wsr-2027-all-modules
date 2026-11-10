@extends('admin.layouts.app')

@section('title', 'Рецепты')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Список рецептов</h5>
        <a href="{{ route('admin.recipes.create') }}" class="btn btn-primary">Добавить рецепт</a>
    </div>

    {{-- Панель фильтрации и сортировки --}}
    <form method="GET" action="{{ route('admin.recipes.index') }}" class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label for="difficulty" class="form-label">Сложность</label>
                    <select name="difficulty" id="difficulty" class="form-select">
                        <option value="">Все</option>
                        @foreach (\App\Models\Recipe::DIFFICULTIES as $difficulty)
                            <option value="{{ $difficulty }}" @selected($filters['difficulty'] === $difficulty)>{{ $difficulty }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="time_range" class="form-label">Время приготовления</label>
                    <select name="time_range" id="time_range" class="form-select">
                        <option value="">Любое</option>
                        <option value="up_to_15" @selected($filters['time_range'] === 'up_to_15')>до 15 мин</option>
                        <option value="from_60_to_120" @selected($filters['time_range'] === 'from_60_to_120')>60–120 мин</option>
                        <option value="over_120" @selected($filters['time_range'] === 'over_120')>свыше 120 мин</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="category_id" class="form-label">Категория</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">Все</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="price" class="form-label">Цена (0 — без ограничений)</label>
                    <input type="number" name="price" id="price" min="0" step="0.01"
                           value="{{ $filters['price'] }}" class="form-control" placeholder="0">
                </div>

                <div class="col-md-2">
                    <label for="sort" class="form-label">Сортировка</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="newest_desc" @selected($sort === 'newest_desc')>По новизне 🔽 (по умолчанию)</option>
                        <option value="newest_asc" @selected($sort === 'newest_asc')>По новизне 🔼</option>
                        <option value="time_asc" @selected($sort === 'time_asc')>По времени приготовления 🔼</option>
                        <option value="time_desc" @selected($sort === 'time_desc')>По времени приготовления 🔽</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Применить</button>
                    <a href="{{ route('admin.recipes.index') }}" class="btn btn-outline-secondary">Сброс</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Время</th>
                    <th>Калорийность</th>
                    <th>Цена</th>
                    <th>Сложность</th>
                    <th>Категория</th>
                    <th>Статус</th>
                    <th class="text-end">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recipes as $recipe)
                    <tr class="{{ $recipe->trashed() ? 'table-warning' : '' }}">
                        <td>
                            @if ($recipe->defaultImage())
                                <img src="{{ $recipe->defaultImage()->thumbnailUrl() }}"
                                     alt="{{ $recipe->title }}" class="thumb-preview object-fit-contain">
                            @else
                                <span class="text-muted small">нет</span>
                            @endif
                        </td>
                        <td>
                            {{ $recipe->title }}
                            @if ($recipe->trashed())
                                <br><span class="badge text-bg-warning">Помечен на удаление</span>
                            @endif
                        </td>
                        <td>{{ $recipe->formatted_time }}</td>
                        <td>{{ $recipe->calories !== null ? $recipe->calories.' ккал' : '—' }}</td>
                        <td>
                            @if ($recipe->price > 0)
                                {{ number_format((float) $recipe->price, 2, ',', ' ') }} ₽
                            @else
                                Бесплатно
                            @endif
                        </td>
                        <td>
                            @php
                                $difficultyClass = match ($recipe->difficulty) {
                                    'легко' => 'text-bg-success',
                                    'средне' => 'text-bg-warning',
                                    default => 'text-bg-danger',
                                };
                            @endphp
                            <span class="badge {{ $difficultyClass }}">{{ $recipe->difficulty }}</span>
                        </td>
                        <td>{{ $recipe->category?->name }}</td>
                        <td>
                            @if ($recipe->trashed())
                                <span class="badge text-bg-secondary">удалён</span>
                            @else
                                <span class="badge text-bg-success">активен</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.recipes.show', $recipe) }}" class="btn btn-sm btn-outline-secondary">Просмотр</a>
                            <a href="{{ route('admin.recipes.steps.index', $recipe) }}" class="btn btn-sm btn-outline-dark">Шаги</a>

                            @if ($recipe->trashed())
                                <form action="{{ route('admin.recipes.restore', $recipe) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success">Восстановить</button>
                                </form>
                                <form action="{{ route('admin.recipes.force-delete', $recipe) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить рецепт окончательно? Все данные и файлы будут удалены.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Удалить окончательно</button>
                                </form>
                            @else
                                <a href="{{ route('admin.recipes.edit', $recipe) }}" class="btn btn-sm btn-primary">Редактировать</a>
                                <form action="{{ route('admin.recipes.destroy', $recipe) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Пометить рецепт на удаление?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Рецепты не найдены.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{-- Пагинация в стиле Bootstrap 5 (по 5 записей на страницу). --}}
        {{ $recipes->links() }}
    </div>
@endsection
