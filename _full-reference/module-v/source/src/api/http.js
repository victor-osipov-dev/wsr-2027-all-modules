import axios from 'axios';
import { config } from '../config';
import { getToken, clearToken } from '../utils/token';

/**
 * Единый HTTP-клиент приложения.
 *
 * Ключевые требования ТЗ, реализованные здесь:
 *  - baseURL указывает на REST API ({{host}}/recipe-api);
 *  - заголовок `Client: <ЛОГИН>` добавляется к КАЖДОМУ запросу;
 *  - после авторизации добавляется `Authorization: Bearer <token>`.
 */
const http = axios.create({
  baseURL: config.api.baseURL,
  timeout: config.api.timeout,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// Интерцептор запроса: заголовок Client + токен авторизации.
http.interceptors.request.use(
  (request) => {
    request.headers = request.headers || {};
    // Требование критериев: заголовок Client присутствует во всех запросах.
    request.headers[config.api.clientHeader] = config.api.client;

    const token = getToken();
    if (token) {
      request.headers.Authorization = `${config.api.authScheme} ${token}`;
    }
    return request;
  },
  (error) => Promise.reject(error),
);

// Интерцептор ответа: обработка 401 (сброс токена) и проброс остальных ошибок.
http.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error && error.response && error.response.status;
    const url = (error && error.config && error.config.url) || '';
    // Запрос профиля (/user) может быть не реализован на сервере — не разлогиниваем из-за него.
    const isProfileProbe = url.includes(config.endpoints.auth.me);

    if (status === 401 && !isProfileProbe) {
      clearToken();
      // Сообщаем приложению о разлогине, чтобы оно увело пользователя на /login.
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('app:unauthorized'));
      }
    }
    return Promise.reject(error);
  },
);

export default http;
