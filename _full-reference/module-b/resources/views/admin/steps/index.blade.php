@extends('admin.layouts.app')

@section('title', 'Шаги рецепта')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Шаги рецепта «{{ $recipe->title }}»</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.recipes.steps.create', $recipe) }}" class="btn btn-primary">Добавить шаг</a>
            <a href="{{ route('admin.recipes.index') }}" class="btn btn-outline-secondary">К рецептам</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Порядок</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th class="text-end">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recipe->steps as $step)
                    <tr>
                        <td>{{ $step->order }}</td>
                        <td>{{ $step->title }}</td>
                        <td>{{ $step->description }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.recipes.steps.edit', [$recipe, $step]) }}" class="btn btn-sm btn-primary">Редактировать</a>
                            <form action="{{ route('admin.recipes.steps.destroy', [$recipe, $step]) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Удалить шаг?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Шаги не добавлены.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
