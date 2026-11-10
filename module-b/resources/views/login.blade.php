<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход в админ-панель — Кухни мира</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
</head>

<body class="bg-body">
    <div class="d-flex align-items-center justify-content-center min-vh-100">
        <div class="card shadow" style="width:400px">
            <div class="card-body p-4">
                <h4 class="text-center mb-1">Кухни мира</h4>
                <p class="text-center text-muted mb-4">Вход в админ-панель</p>
                <form method="POST" action="{{ route('admin.login.attempt') }}" novalidate>
                    @csrf
                    <div class="mb-3"><label for="email" class="form-label">E-mail</label><input type="email"
                            name="email" id="email" value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror" autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3"><label for="password" class="form-label">Пароль</label><input type="password"
                            name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check mb-3"><input type="checkbox" name="remember" id="remember" value="1"
                            class="form-check-input"><label for="remember" class="form-check-label">Запомнить
                            меня</label></div>
                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                    @if (session('auth_error'))
                        <div class="alert alert-danger py-2 mt-3 mb-0">{{ session('auth_error') }}</div>
                    @endif
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
