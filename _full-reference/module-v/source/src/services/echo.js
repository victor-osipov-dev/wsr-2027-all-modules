import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { config } from '../config';
import { useNotify } from '../composables/useNotify';

/**
 * Подключение к WebSocket-серверу (Laravel Echo + pusher-js).
 *
 * Требование ТЗ: при создании любого нового рецепта все пользователи (и гости, и авторизованные)
 * должны увидеть всплывающее уведомление с названием рецепта и рабочей ссылкой на него,
 * без перезагрузки страницы. Уведомление закрывается автоматически.
 */

let echo = null;
let channel = null;
let initialized = false;

/** Извлекает данные рецепта из возможных вариантов payload события. */
const extractRecipe = (payload) => {
  if (!payload || typeof payload !== 'object') return {};
  const source = payload.recipe || payload.model || payload.data || payload;
  const id = source.id || source.recipe_id || (source.recipe && source.recipe.id);
  const title = source.title || source.name || (source.recipe && source.recipe.title);
  return { id, title };
};

/** Показывает уведомление о новом рецепте. */
const handleRecipeCreated = (payload) => {
  try {
    const { id, title } = extractRecipe(payload);
    const { push } = useNotify();
    push({
      variant: 'success',
      title: 'Новый рецепт',
      text: title ? `Добавлен рецепт «${title}»` : 'Опубликован новый рецепт',
      to: id ? { name: 'recipe', params: { id } } : { name: 'recipes' },
      timeout: 7000,
    });
  } catch (error) {
    /* Уведомление не должно ломать приложение. */
  }
};

/** Подписывается на событие канала. */
const bindEvents = () => {
  config.websocket.events.forEach((eventName) => {
    try {
      channel.listen(eventName, handleRecipeCreated);
    } catch (error) {
      /* Некоторые варианты имён событий могут быть недоступны — это нормально. */
    }
  });
};

/** Инициализация подключения. Безопасна к повторным вызовам и к отсутствию сервера. */
export const initEcho = () => {
  if (initialized || !config.websocket.enabled) return echo;
  initialized = true;
  try {
    window.Pusher = Pusher;
    Pusher.logToConsole = false;

    const secure = config.websocket.forceTLS || (typeof window !== 'undefined' && window.location.protocol === 'https:');

    echo = new Echo({
      broadcaster: 'pusher',
      key: config.websocket.key,
      cluster: config.websocket.cluster,
      wsHost: config.websocket.host,
      wsPort: config.websocket.port,
      wssPort: config.websocket.port,
      forceTLS: secure,
      encrypted: secure,
      disableStats: true,
      enabledTransports: secure ? ['wss'] : ['ws'],
      authEndpoint: `${config.api.baseURL}/broadcasting/auth`,
    });

    channel = echo.channel(config.websocket.channel);
    bindEvents();

    // Подавляем шум от ошибок соединения (не выводим их в консоль).
    try {
      echo.connector.pusher.connection.bind('error', () => {});
      echo.connector.pusher.connection.bind('unavailable', () => {});
    } catch (error) {
      /* noop */
    }
  } catch (error) {
    echo = null;
    channel = null;
  }
  return echo;
};

/** Отключение (например, при выходе из приложения). */
export const disconnectEcho = () => {
  try {
    if (echo) echo.disconnect();
  } catch (error) {
    /* noop */
  }
  echo = null;
  channel = null;
  initialized = false;
};

export default initEcho;
