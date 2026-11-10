import { config } from '../config';

/**
 * Работа с токеном авторизации.
 * По требованию ТЗ в localStorage ХРАНИТСЯ ТОЛЬКО токен — все остальные данные приходят с сервера.
 */
const storage = typeof window !== 'undefined' ? window.localStorage : null;

export const getToken = () => {
  try {
    return (storage && storage.getItem(config.api.tokenKey)) || '';
  } catch (error) {
    return '';
  }
};

export const setToken = (token) => {
  try {
    if (token) {
      storage.setItem(config.api.tokenKey, token);
    } else {
      storage.removeItem(config.api.tokenKey);
    }
  } catch (error) {
    /* localStorage может быть недоступен — игнорируем */
  }
};

export const clearToken = () => setToken('');

export const hasToken = () => Boolean(getToken());
