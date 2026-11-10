<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Админ-панель') — Кухни мира</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
</head>

<body>
    <header class="bg-dark text-white d-flex justify-content-between align-items-center px-4 py-2">
        <a href="{{ route('admin.recipes.index') }}" class="text-white text-decoration-none fw-bold">Кухни мира</a>
        <span class="d-flex align-items-center gap-3"><span
                class="text-white-50 small">{{ auth()->user()?->email }}</span>
            <form method="POST" action="{{ route('admin.logout') }}" class="m-0">@csrf<button type="submit"
                    class="btn btn-sm btn-outline-light">Выйти</button></form>
        </span>
    </header>
    <main class="container-fluid p-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><strong>Проверьте правильность заполнения формы:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
