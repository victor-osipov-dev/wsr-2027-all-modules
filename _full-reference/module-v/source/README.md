# source — исходники SPA «Кухни мира» (Модуль В)

Этот каталог содержит полные исходники проекта и коммитится в Git.
Подробные инструкции по сборке, деплою и настройке API — в файле `../README.md`.

Кратко:

```bash
npm install
npm run build     # результат в ./dist — публикуется на веб-хост
```

Перед сборкой укажите в `.env` свой логин (`VITE_CLIENT_LOGIN`) и при необходимости
адрес API (`VITE_API_BASE_URL`).

Структура:

- `src/config` — адрес API, заголовок Client, параметры сортировки, WebSocket;
- `src/api` — axios-клиент (`http.js`) и REST-модули (`auth.js`, `recipes.js`);
- `src/stores` — состояние авторизации;
- `src/composables` — `useForm` (валидация), `useNotify` (уведомления);
- `src/services/echo.js` — WebSocket-уведомления;
- `src/router` — маршруты SPA;
- `src/components` — переиспользуемые компоненты;
- `src/views` — экраны приложения;
- `src/assets/css` — `bootstrap.min.css` (локальная подборка Bootstrap 5) и `app.css`.
