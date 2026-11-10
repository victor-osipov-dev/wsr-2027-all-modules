# «Кухни мира» — серверная часть (Модуль Б)

Laravel 12: админ-панель (Blade + Bootstrap 5) и REST API (`/recipe-api/`).
Стек: PHP 8.2+, MySQL, Laravel Sanctum (Bearer-токены), нативный PHP GD (миниатюры 350×200 webp + водяной знак).

Вся бизнес-логика — в двух контроллерах: `app/Http/Controllers/AdminController.php` и `ApiController.php`.

## Развёртывание

```bash
composer install
copy .env.example .env      # Linux/macOS: cp .env.example .env
php artisan key:generate
# Укажите в .env параметры MySQL (DB_*) и APP_URL
php artisan migrate --seed
php artisan storage:link
```

Документ-рут веб-сервера — каталог `public`. Если это невозможно, корневой `.htaccess` перенаправляет запросы в `public/`.

## Доступы

| Роль          | Логин                 | Пароль    |
|---------------|-----------------------|-----------|
| Администратор | admin@champ2026.lan   | champ2026 |
| Пользователь  | user@champ2026.lan    | Champ2026 |

## Маршруты

* Админ-панель: `{{site-url}}/admin` (вход → список рецептов; CRUD + фильтры/сортировка/пагинация по 5).
* API: `{{site-url}}/recipe-api/`
  * публичные: `POST /register`, `POST /auth`, `POST /webhook`;
  * защищённые (Bearer, роль `user`): `GET /recipes`, `GET /recipes/{id}`,
    `POST /favorites`, `GET /favorites`, `DELETE /favorites/{recipeId}`,
    `POST /purchase`, `GET /purchases`, `GET /purchase/{id}`.

## Форматы ошибок API

* `401 {"message":"Unauthenticated"}`
* `403 {"message":"Forbidden for you"}` (токен администратора на user-маршрутах)
* `422 {"message":"Invalid fields","errors":{...}}`
