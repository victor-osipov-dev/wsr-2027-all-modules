# Соответствие критериям Модуля В (чек-лист)

Таблица сопоставляет каждый аспект критериев Модуля В с реализацией в исходниках
(`module-v/source`).

## Модуль В — общие требования

| Критерий | Реализация |
|---|---|
| Приложение доступно по адресу | SPA собирается в `dist/` и публикуется в корень `{{host}}`. См. `README.md`. |
| Исходники в папке `source` | Весь проект лежит в `module-v/source/`. |
| Используется предоставленный шаблон | Шаблон в комплекте отсутствовал — воссоздан чистый каркас на Bootstrap-совместимых классах: `src/assets/css/bootstrap.min.css`, `src/assets/css/app.css`, `src/components/AppHeader.vue`, `src/components/AppFooter.vue`. |
| Качество кода JS (уровень 2) | Приложение разбито на компоненты (`src/components`, `src/views`), используются стрелочные функции, `const`, тернарные операторы, переиспользуемые `composables` (`src/composables/useForm.js`, `useNotify.js`). |
| Шаблон отображается корректно на всех страницах | Единый каркас `App.vue` (шапка + контент + подвал) для всех маршрутов. |
| Отсутствуют ошибки в консоли | Глобальный `app.config.errorHandler` (`src/main.js`), подавление логов pusher (`src/services/echo.js`), обработка ошибок изображений (`RecipeCard.vue`, `RecipeView.vue`), try/catch вокруг необязательных запросов. |
| Проект размещён в GIT (минимум 2 коммита) | Инструкция и примеры осмысленных коммитов в `README.md`. |
| Реализованы уведомления с названием рецепта и ссылкой | `src/services/echo.js` (событие `RecipeCreated`), `src/components/NotificationHost.vue` (название рецепта + `<router-link>` на рецепт). |
| Ограничения показа уведомлений (авто-закрытие) | `src/composables/useNotify.js` — автоскрытие по `setTimeout` (7 c), кнопка закрытия в `NotificationHost.vue`. |

## Модуль В — реализация приложения

| Критерий | Файлы |
|---|---|
| Реализована регистрация | `src/views/RegisterView.vue`, `src/api/auth.js` |
| Регистрация: проверка повторного пароля | `RegisterView.vue` (`validate`) |
| Регистрация: обязательный чекбокс согласия | `RegisterView.vue`, `src/components/BaseCheckbox.vue` |
| Регистрация: ошибки выводятся | `RegisterView.vue` (`errors.*`), `src/components/BaseInput.vue` (`invalid-feedback`) |
| Регистрация: поля подсвечиваются | класс `is-invalid` в `BaseInput.vue` / `BaseCheckbox.vue`, стили в `bootstrap.min.css` |
| Регистрация: редирект на вход | `RegisterView.vue` → `router.push({ name: 'login' })` |
| Реализована авторизация | `src/views/LoginView.vue`, `src/stores/auth.js`, `src/api/http.js` |
| Авторизация: ошибки выводятся / поля подсвечиваются | `LoginView.vue` + `BaseInput.vue` |
| Авторизация: редирект на список рецептов | `LoginView.vue` → `router.push({ name: 'recipes' })` |
| Список рецептов реализован | `src/views/RecipesListView.vue`, `src/components/RecipeCard.vue` |
| Список рецептов: все данные (название, описание, калорийность, время, изображение) | `RecipeCard.vue`, `src/utils/recipe.js` |
| Сортировка по дате создания (обе стороны) | `RecipesListView.vue`, `src/components/SortControl.vue`, `src/config/index.js` (`sort`) |
| Сортировка по времени приготовления (обе стороны) | там же |
| Сортировка по калорийности (обе стороны) | там же |
| Создание рецепта | `src/views/RecipeFormView.vue`, `src/api/recipes.js` (`create`) |
| Создание: валидация и подсветка полей | `RecipeFormView.vue` (`validate`, `setErrors`), `BaseInput`/`BaseTextarea`/`BaseSelect` |
| Редактирование рецепта | `RecipeFormView.vue` (`recipesApi.update`) |
| Редактирование: валидация и подсветка | там же |
| Удаление только своих рецептов | `src/views/MyRecipesView.vue` (`recipesApi.remove`) |
| Можно редактировать только свои рецепты | проверка владельца в `RecipeFormView.vue` (`pickOwnerId`) и `RecipeView.vue` (`canEdit`) |
| Модальное подтверждение удаления | `src/components/ConfirmModal.vue` |
| Список своих рецептов | `src/views/MyRecipesView.vue` (`recipesApi.my`) |
| Список своих рецептов: изображение и кнопки | `RecipeCard.vue` (props `show-actions`, `show-date`) |
| Вывод единичного рецепта (все поля) | `src/views/RecipeView.vue` |
| Конструктор: drag-and-drop (добавление и удаление) | `src/views/ConstructorView.vue` (HTML5 DnD + клики), зоны «доступные»/«выбранные» |
| Конструктор: ингредиенты в алфавитном порядке | `ConstructorView.vue` (`sortedIngredients`), `src/api/recipes.js` (`ingredientsApi.list`) |
| Конструктор: корректный поиск (все выбранные ингредиенты) | `recipesApi.searchByIngredients`, `ConstructorView.vue` (`runSearch`) |
| Конструктор: повторный поиск при изменении | `watch(selectionKey, runSearch)` в `ConstructorView.vue` |
| Конструктор: ссылка на рецепт | `RecipeCard.vue` (router-link на `recipe`) |

## Общие требования ТЗ

| Требование | Реализация |
|---|---|
| Заголовок `Client: <ЛОГИН>` во всех запросах | `src/api/http.js` (интерцептор запроса), значение из `src/config/index.js` (`VITE_CLIENT_LOGIN`) |
| `Authorization: Bearer <token>` | `src/api/http.js` |
| Токен хранится только в localStorage | `src/utils/token.js` |
| Обработка 401/403/422 | `src/api/http.js` (401), `src/api/errors.js` (разбор ошибок) |
| Маршрутизация SPA | `src/router/index.js` |
| WebSocket (Laravel Echo + pusher-js) | `src/services/echo.js` |
