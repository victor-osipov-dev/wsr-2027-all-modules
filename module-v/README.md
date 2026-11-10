# Модуль В. Клиентская часть «Кухни мира» (SPA)

Одностраничное приложение платформы рецептов. Стек: **Vue 3 + Vite + vue-router + axios**.
Заголовок `Client: <логин>` добавляется к каждому запросу, токен — в `Authorization: Bearer`.

## Структура

```
module-v/
├─ README.md
├─ .gitignore
└─ source/                 ← ИСХОДНИКИ (требование ТЗ — папка source)
   ├─ package.json
   ├─ vite.config.js
   ├─ index.html
   ├─ .env                 ← VITE_API_BASE_URL и VITE_CLIENT_LOGIN
   ├─ public/.htaccess     ← SPA-fallback для прямых ссылок
   └─ src/
      ├─ main.js           — точка входа
      ├─ App.vue           — шапка, навигация, роутер
      ├─ api.js            — axios-клиент (Client + Authorization)
      ├─ router.js         — маршруты и гвард авторизации
      ├─ bootstrap.min.css — шаблон Bootstrap 5
      └─ views/            — страницы (Login, Register, Recipes, Recipe, MyRecipes, RecipeForm, Constructor)
```

## Сборка и деплой

```bash
cd source
npm install
npm run build      # создаёт source/dist
```

1. Содержимое `source/dist` → в **корень** веб-хоста (`index.html`, `assets/`, `.htaccess`).
2. Каталог `source` → в подпапку **`source`** того же хоста (исходники по ТЗ).

`.htaccess` из `public/` попадает в `dist` и включает SPA-fallback: прямые ссылки
(`/login`, `/recipes/1`, `/constructor`) открывают `index.html`. Для nginx:
`try_files $uri $uri/ /index.html;`.

## Настройка

Файл `source/.env`:

| Переменная | Назначение | По умолчанию |
|---|---|---|
| `VITE_API_BASE_URL` | Базовый URL REST API | `/recipe-api` |
| `VITE_CLIENT_LOGIN` | Логин участника → заголовок `Client` | `xxxxxx` |

**Замените `VITE_CLIENT_LOGIN` на свой логин перед сборкой.**

## Маршруты

`/login`, `/register`, `/`, `/recipes/:id`, `/my`, `/recipes/new`, `/recipes/:id/edit`, `/constructor`.

## Эндпоинты API

`POST /register`, `POST /auth`, `GET /recipes`, `GET /recipes/{id}`,
`GET /recipes/my`, `GET /recipes/search`, `POST /recipes`, `POST /recipes/{id}` (`_method=PUT`),
`DELETE /recipes/{id}`, `GET /ingredients`.

Сортировка (`sort`): `created_at_desc`, `created_at_asc`, `cooking_time_asc`,
`cooking_time_desc`, `calories_asc`, `calories_desc`.
