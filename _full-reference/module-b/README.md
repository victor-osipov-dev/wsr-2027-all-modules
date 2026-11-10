# «Кухни мира» — серверная часть (Модуль Б)

Серверное приложение на **Laravel 12**: админ-панель (Blade + Bootstrap 5) и
REST API (`/recipe-api/`) для платформы рецептов «Кухни мира».

Стек: **PHP 8.2+, Laravel 12, MySQL, Sanctum (Bearer-токены), нативный PHP GD**.

---

## 1. Возможности

### Админ-панель (`{{site-url}}/admin`)
* Авторизация администратора; после входа — перенаправление на **список рецептов**.
* Полный CRUD: **рецепты, ингредиенты, категории, шаги**; просмотр и удаление **заказов**.
* Серверная валидация форм с подсветкой полей (`is-invalid`) и сообщениями (`invalid-feedback`).
* Список рецептов: **все** рецепты, включая помеченные на удаление (с отметкой,
  восстановлением и окончательным удалением).
* Фильтры: сложность, время (до 15 / 60–120 / свыше 120 мин), категория, стоимость (0 — без ограничений).
* Сортировка: новизна ↑/↓, время приготовления ↑/↓ (по умолчанию — по новизне ↓).
* Пагинация **по 5 записей** (Bootstrap 5).
* Время приготовления в человекочитаемом виде: `120 → «2 часа»`, `80 → «1 час 20 минут»`, `25 → «25 минут»`.
* Выбор изображения по умолчанию (Bootstrap radio).
* Миниатюры **350×200 webp** (полное вписывание, аналог `object-fit: contain`) с **водяным
  знаком** — названием рецепта в правом нижнем углу. Оригиналы сохраняются; при смене
  названия миниатюры и водяные знаки перегенерируются.
* Заказы: клиент (email + ФИО), рецепт, стоимость, статус оплаты; пагинация по 5;
  удаление только для статусов «ошибка оплаты» и «истекло время на оплату».

### REST API (`{{site-url}}/recipe-api/`)
Публичные: `POST /register`, `POST /auth`, `POST /webhook`.
Защищённые (Bearer-токен, роль `user`): `GET /recipes`, `GET /recipes/{id}`,
`POST|GET /favorites`, `DELETE /favorites/{recipeId}`, `POST /purchase`,
`GET /purchases`, `GET /purchase/{id}`, `GET /purchase/{id}/progress`,
`POST /steps/{stepId}/complete`.

Формат ошибок:
* `401 {"message":"Unauthenticated"}`
* `403 {"message":"Forbidden for you"}` (токен администратора на user-маршрутах)
* `422 {"message":"Invalid fields","errors":{...}}`

---

## 2. Развёртывание

```bash
# 1. Зависимости
composer install

# 2. Окружение
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate

# 3. Укажите параметры MySQL в .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
#    и APP_URL (например, http://localhost)

# 4. Схема БД и демонстрационные данные
php artisan migrate --seed

# 5. Публичная ссылка на файлы изображений
php artisan storage:link
```

Документ-рут веб-сервера должен указывать на каталог **`public`**.
Если это невозможно, корневой `.htaccess` перенаправляет запросы в `public/`.

`APP_URL` определяет абсолютные ссылки (в т.ч. `payment_link` и адрес webhook).

### Учётные записи

| Роль            | Логин                   | Пароль      |
|-----------------|-------------------------|-------------|
| Администратор   | `admin@champ2026.lan`   | `champ2026` |
| Пользователь    | `user@champ2026.lan`    | `Champ2026` |

---

## 3. Структура проекта

```
app/
├── Events/RecipeCreated.php           # событие нового рецепта (публичный канал recipes)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                     # контроллеры админ-панели
│   │   └── Api/                       # контроллеры REST API
│   ├── Middleware/                    # ForceJsonResponse, EnsureUserIsAdministrator, EnsureUserHasUserRole
│   ├── Requests/                      # FormRequest: Admin и Api
│   └── Resources/                     # API-ресурсы (JSON-представления)
├── Models/                            # User, Recipe, Category, Ingredient, RecipeImage, Step, Favorite, Purchase, StepCompletion
├── Providers/AppServiceProvider.php
└── Services/
    ├── ImageService.php               # GD: миниатюры 350×200 webp + водяной знак
    ├── RecipeService.php              # бизнес-логика рецептов
    ├── PurchaseService.php            # покупки и webhook (md5(order_number))
    └── TimeFormatter.php              # человекочитаемое время
database/
├── migrations/                        # схема MySQL (3НФ)
├── seeders/                           # демо-данные (пользователи, рецепты, заказы)
└── factories/
resources/
├── fonts/                             # TTF для водяных знаков (verdana.ttf, arial.ttf)
└── views/admin/                       # Blade-шаблоны админ-панели
public/vendor/bootstrap/               # локальная сборка Bootstrap 5 (без CDN)
routes/                                # api.php, web.php, channels.php
```

---

## 4. Изображения

Для каждого загруженного изображения:
1. сохраняется **оригинал** (`storage/app/public/recipes/{id}/original_*.ext`);
2. создаётся **миниатюра 350×200 webp** (`thumb_350x200_*.webp`) — изображение вписывается
   целиком (letterbox, аналог `object-fit: contain`);
3. на миниатюру наносится **водяной знак** — название рецепта в правом нижнем углу
   (TTF из `resources/fonts`; при отсутствии шрифта — встроенный шрифт GD).

При изменении названия рецепта миниатюры и водяные знаки перегенерируются из оригиналов.
При окончательном удалении рецепта каталог с файлами удаляется.

---

## 5. WebSocket / уведомления (для Модуля В)

Событие `App\Events\RecipeCreated` реализует `ShouldBroadcast` и транслируется в
**публичный канал `recipes`** (событие `recipe.created`). Настройки — в `config/broadcasting.php`
(драйвер `reverb`, переменные `REVERB_*` в `.env`).

> Если пакет `laravel/reverb` недоступен в офлайн-кэше Composer, установите
> `BROADCAST_CONNECTION=log` — создание рецепта продолжит работать (событие логируется).

---

## 6. Примечания

* Все изображения обрабатываются **нативным PHP GD** — сторонние библиотеки не требуются.
* Для доступа к админ-панели роль пользователя должна быть `admin`.
* Проверка пароля при регистрации API: минимум 5 символов, латиница, минимум одна
  строчная и одна прописная буква, минимум один спецсимвол (`# _ - $ .`).
* Идентификатор webhook — `md5(order_number)`; валидная карта — `123456789`.
