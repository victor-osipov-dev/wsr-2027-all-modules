/**
 * Центральная конфигурация приложения.
 * Значения берутся из переменных окружения (файл .env) с безопасными значениями по умолчанию,
 * поэтому сборка не упадёт, если переменная не задана.
 */
const env = import.meta.env || {};

/** Приводит строку окружения к boolean. */
const toBool = (value, fallback = false) =>
  value === undefined || value === null || value === '' ? fallback : String(value) === 'true';

/** Приводит строку окружения к числу. */
const toNumber = (value, fallback) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const apiBaseUrl = env.VITE_API_BASE_URL || '/recipe-api';

export const config = Object.freeze({
  app: {
    name: 'Кухни мира',
    tagline: 'Платформа рецептов',
    year: new Date().getFullYear(),
  },

  /** Параметры REST API. */
  api: {
    baseURL: apiBaseUrl,
    timeout: toNumber(env.VITE_API_TIMEOUT, 20000),
    // Логин участника — уходит в заголовке Client каждого запроса по требованию ТЗ.
    client: env.VITE_CLIENT_LOGIN || 'xxxxxx',
    clientHeader: 'Client',
    authScheme: env.VITE_AUTH_SCHEME || 'Bearer',
    tokenKey: env.VITE_TOKEN_KEY || 'auth_token',
    demoFallback: toBool(env.VITE_DEMO_FALLBACK, true),
  },

  /** Эндпоинты API (при необходимости легко переопределить под конкретную площадку). */
  endpoints: {
    auth: {
      register: '/register',
      login: '/auth',
      logout: '/logout',
      me: '/user',
    },
    recipes: {
      list: '/recipes',
      my: '/recipes/my',
      search: '/recipes/search',
      detail: (id) => `/recipes/${id}`,
      create: '/recipes',
      update: (id) => `/recipes/${id}`,
      remove: (id) => `/recipes/${id}`,
    },
    ingredients: {
      list: '/ingredients',
    },
  },

  /** Параметры сортировки, которые отправляются на сервер в query-параметре sort. */
  sort: {
    createdDesc: 'created_at_desc',
    createdAsc: 'created_at_asc',
    timeDesc: 'cooking_time_desc',
    timeAsc: 'cooking_time_asc',
    caloriesDesc: 'calories_desc',
    caloriesAsc: 'calories_asc',
  },

  /** WebSocket-соединение (Laravel Echo + pusher-js). */
  websocket: {
    enabled: toBool(env.VITE_WS_ENABLED, true),
    driver: env.VITE_WS_DRIVER || 'pusher',
    key: env.VITE_WS_KEY || 'app-key',
    cluster: env.VITE_WS_CLUSTER || 'mt1',
    host: env.VITE_WS_HOST || (typeof window !== 'undefined' ? window.location.hostname : 'localhost'),
    port: toNumber(env.VITE_WS_PORT, 6001),
    forceTLS: toBool(env.VITE_WS_FORCE_TLS, false),
    channel: env.VITE_WS_CHANNEL || 'recipes',
    // Слушаем несколько вариантов имени события: разные сборки модуля Б бродкастят по-разному.
    events: [
      'RecipeCreated',
      '.RecipeCreated',
      '.recipe.created',
      'recipe.created',
      'App\\Events\\RecipeCreated',
    ],
  },
});

export default config;
