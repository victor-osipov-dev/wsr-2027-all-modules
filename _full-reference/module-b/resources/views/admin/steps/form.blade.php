@extends('admin.layouts.app')

@section('title', $step->exists ? 'Редактирование шага' : 'Новый шаг')

@section('content')
    <div class="card" style="max-width: 720px;">
        <div class="card-header">Шаг рецепта «{{ $recipe->title }}»</div>
        <div class="card-body">
            <form method="POST"
                  action="{{ $step->exists
                        ? route('admin.recipes.steps.update', [$recipe, $step])
                        : route('admin.recipes.steps.store', $recipe) }}">
                @csrf
                @if ($step->exists)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="title" class="form-label">Название шага *</label>
                    <input type="text" name="title" id="title" maxlength="40"
                           value="{{ old('title', $step->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Не более 40 символов.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Описание шага *</label>
                    <textarea name="description" id="description" rows="3" maxlength="255"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $step->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Не более 255 символов.</div>
                </div>

                <div class="mb-3">
                    <label for="order" class="form-label">Порядок выполнения</label>
                    <input type="number" name="order" id="order" min="0"
                           value="{{ old('order', $step->order) }}"
                           class="form-control @error('order') is-invalid @enderror">
                    @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $step->exists ? 'Сохранить' : 'Создать' }}</button>
                    <a href="{{ route('admin.recipes.steps.index', $recipe) }}" class="btn btn-outline-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
@endsection
