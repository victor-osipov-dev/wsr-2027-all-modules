@extends('admin.layouts.app')

@section('title', 'Ингредиенты')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Ингредиенты</h5>
        <a href="{{ route('admin.ingredients.create') }}" class="btn btn-primary">Добавить ингредиент</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Название</th>
                    <th>Единица измерения</th>
                    <th>Используется в рецептах</th>
                    <th class="text-end">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($ingredients as $ingredient)
                    <tr>
                        <td>{{ $ingredient->id }}</td>
                        <td>{{ $ingredient->title }}</td>
                        <td>{{ $ingredient->unit }}</td>
                        <td>{{ $ingredient->recipes_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.ingredients.edit', $ingredient) }}" class="btn btn-sm btn-primary">Редактировать</a>
                            <form action="{{ route('admin.ingredients.destroy', $ingredient) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Удалить ингредиент? Он будет удалён из всех рецептов.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Ингредиенты не найдены.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $ingredients->links() }}</div>
@endsection
