@extends('admin.layouts.app')

@section('title', 'Просмотр рецепта')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $recipe->title }}</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.recipes.steps.index', $recipe) }}" class="btn btn-outline-dark">Шаги</a>
            <a href="{{ route('admin.recipes.edit', $recipe) }}" class="btn btn-primary">Редактировать</a>
            <a href="{{ route('admin.recipes.index') }}" class="btn btn-outline-secondary">К списку</a>
        </div>
    </div>

    @if ($recipe->trashed())
        <div class="alert alert-warning">Рецепт помечен на удаление.</div>
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Название</dt>
                        <dd class="col-sm-8">{{ $recipe->title }}</dd>

                        <dt class="col-sm-4">Описание</dt>
                        <dd class="col-sm-8">{{ $recipe->description }}</dd>

                        <dt class="col-sm-4">Время приготовления</dt>
                        <dd class="col-sm-8">{{ $recipe->cooking_time }} мин ({{ $recipe->formatted_time }})</dd>

                        <dt class="col-sm-4">Калорийность</dt>
                        <dd class="col-sm-8">{{ $recipe->calories !== null ? $recipe->calories.' ккал' : '—' }}</dd>

                        <dt class="col-sm-4">Сложность</dt>
                        <dd class="col-sm-8">{{ $recipe->difficulty }}</dd>

                        <dt class="col-sm-4">Категория</dt>
                        <dd class="col-sm-8">{{ $recipe->category?->name }}</dd>

                        <dt class="col-sm-4">Стоимость</dt>
                        <dd class="col-sm-8">
                            @if ($recipe->price > 0)
                                {{ number_format((float) $recipe->price, 2, ',', ' ') }} ₽
                            @else
                                Бесплатно
                            @endif
                        </dd>

                        <dt class="col-sm-4">Дата создания</dt>
                        <dd class="col-sm-8">{{ optional($recipe->created_at)->format('d.m.Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Ингредиенты</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recipe->ingredients as $ingredient)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $ingredient->title }}</span>
                            <span class="text-muted">{{ $ingredient->pivot->quantity }} {{ $ingredient->pivot->unit }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Ингредиенты не добавлены.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card mt-3">
                <div class="card-header">Шаги приготовления</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recipe->steps as $step)
                        <li class="list-group-item">
                            <strong>{{ $step->order }}. {{ $step->title }}</strong>
                            <div class="text-muted">{{ $step->description }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Шаги не добавлены.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Изображения и миниатюры</div>
                <div class="card-body">
                    @forelse ($recipe->images as $image)
                        <div class="mb-3">
                            <img src="{{ $image->thumbnailUrl() }}" class="img-thumbnail w-100 object-fit-contain"
                                 style="height: 160px;" alt="Миниатюра {{ $recipe->title }}">
                            <div class="mt-1">
                                @if ($image->is_default)
                                    <span class="badge text-bg-primary">по умолчанию</span>
                                @else
                                    <span class="badge text-bg-secondary">миниатюра 350×200</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Изображения не загружены.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
