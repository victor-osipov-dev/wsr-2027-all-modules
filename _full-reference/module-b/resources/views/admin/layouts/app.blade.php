<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Админ-панель') — Кухни мира</title>
    {{-- Локальная сборка Bootstrap 5 (без CDN, работает офлайн). --}}
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
</head>
<body>
<div class="d-flex">
    {{-- Боковое меню --}}
    <aside class="app-sidebar p-3 flex-shrink-0" style="width: 250px;">
        <a href="{{ route('admin.recipes.index') }}" class="navbar-brand d-block mb-4 text-white">
            <strong>Кухни мира</strong><br>
            <span class="small text-white-50">админ-панель</span>
        </a>

        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.recipes.*') ? 'active' : '' }}"
                   href="{{ route('admin.recipes.index') }}">Рецепты</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                   href="{{ route('admin.categories.index') }}">Категории</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.ingredients.*') ? 'active' : '' }}"
                   href="{{ route('admin.ingredients.index') }}">Ингредиенты</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                   href="{{ route('admin.orders.index') }}">Заказы</a>
            </li>
        </ul>
    </aside>

    {{-- Основная область --}}
    <div class="flex-grow-1">
        <header class="navbar navbar-dark bg-dark px-4">
            <span class="navbar-brand mb-0 h6">@yield('title', 'Админ-панель')</span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small">{{ auth()->user()?->email }}</span>
                <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Выйти</button>
                </form>
            </div>
        </header>

        <main class="p-4">
            @include('admin.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/admin.js') }}"></script>
@stack('scripts')
</body>
</html>
