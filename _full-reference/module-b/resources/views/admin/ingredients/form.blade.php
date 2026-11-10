@extends('admin.layouts.app')

@section('title', $ingredient->exists ? 'Редактирование ингредиента' : 'Новый ингредиент')

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-header">Ингредиент</div>
        <div class="card-body">
            <form method="POST"
                  action="{{ $ingredient->exists ? route('admin.ingredients.update', $ingredient) : route('admin.ingredients.store') }}">
                @csrf
                @if ($ingredient->exists)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="title" class="form-label">Название ингредиента *</label>
                    <input type="text" name="title" id="title" maxlength="20"
                           value="{{ old('title', $ingredient->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Не более 20 символов, название уникально.</div>
                </div>

                <div class="mb-3">
                    <label for="unit" class="form-label">Единица измерения *</label>
                    <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror">
                        <option value="">— выберите —</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit }}" @selected(old('unit', $ingredient->unit) === $unit)>{{ $unit }}</option>
                        @endforeach
                    </select>
                    @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $ingredient->exists ? 'Сохранить' : 'Создать' }}</button>
                    <a href="{{ route('admin.ingredients.index') }}" class="btn btn-outline-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
@endsection
