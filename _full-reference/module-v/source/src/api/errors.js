/**
 * Разбор ошибок, приходящих от REST API.
 * Сервер возвращает 422 с телом { message, errors: { field: [msg] } }.
 */

const DEFAULT_MESSAGES = {
  0: 'Не удалось соединиться с сервером. Проверьте подключение.',
  400: 'Некорректный запрос.',
  401: 'Требуется авторизация.',
  403: 'Недостаточно прав для выполнения действия.',
  404: 'Данные не найдены.',
  409: 'Конфликт данных.',
  419: 'Время действия истекло.',
  422: 'Проверьте правильность заполнения полей.',
  429: 'Слишком много запросов. Попробуйте позже.',
  500: 'Ошибка на сервере. Попробуйте позже.',
};

/** Приводит массив/строку ошибок к массиву строк. */
const toMessages = (value) => {
  if (Array.isArray(value)) return value.filter(Boolean).map(String);
  if (value === undefined || value === null || value === '') return [];
  return [String(value)];
};

/**
 * Возвращает нормализованное описание ошибки.
 * @returns {{ status:number, message:string, errors:Object<string,string> }}
 */
export const parseApiError = (error) => {
  const response = error && error.response;
  if (!response) {
    return { status: 0, message: DEFAULT_MESSAGES[0], errors: {} };
  }
  const status = response.status;
  const data = response.data && typeof response.data === 'object' ? response.data : {};
  const errors = Object.entries(data.errors || {}).reduce((acc, [field, value]) => {
    const messages = toMessages(value);
    if (messages.length) acc[field] = messages[0];
    return acc;
  }, {});
  return {
    status,
    message: data.message || DEFAULT_MESSAGES[status] || 'Произошла ошибка.',
    errors,
  };
};

/** Быстрая проверка: ошибка сети (сервер вообще не ответил). */
export const isNetworkError = (error) => Boolean(error) && !error.response;
