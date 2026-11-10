@extends('admin.layouts.app')

@section('title', $category->exists ? 'Редактирование категории' : 'Новая категория')

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-header">Категория</div>
        <div class="card-body">
            <form method="POST"
                  action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
                @csrf
                @if ($category->exists)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label">Название категории *</label>
                    <input type="text" name="name" id="name" maxlength="40"
                           value="{{ old('name', $category->name) }}"
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Не более 40 символов, название уникально.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $category->exists ? 'Сохранить' : 'Создать' }}</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
@endsection
