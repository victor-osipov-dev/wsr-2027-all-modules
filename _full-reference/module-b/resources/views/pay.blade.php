<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оплата заказа — Кухни мира</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
</head>
<body class="bg-body">
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow" style="width: 520px;">
        <div class="card-body p-4">
            <h4 class="mb-3">Тестовый платёжный шлюз</h4>

            <p class="mb-1">Заказ № <strong>{{ $orderId }}</strong></p>
            <p class="text-muted small">
                Валидный номер карты: <strong>123456789</strong>. Любой другой номер считается невалидным.
            </p>

            <form method="POST" action="{{ $webhook }}">
                @csrf
                <div class="mb-3">
                    <label for="card" class="form-label">Номер карты</label>
                    <input type="text" name="card" id="card" class="form-control" value="123456789">
                </div>
                <input type="hidden" name="purchase_id" id="purchase_id" value="{{ md5((string) $orderId) }}">
                <input type="hidden" name="status" value="paid">

                <p class="small text-muted">
                    purchase_id формируется как md5 от номера заказа (см. API-документацию).
                </p>

                <button type="submit" class="btn btn-primary w-100">Оплатить</button>
            </form>

            <p class="mt-3 mb-0 text-muted small">
                Адрес webhook: <code>{{ $webhook }}</code>
            </p>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
