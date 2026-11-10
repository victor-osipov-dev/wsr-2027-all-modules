import http from './http';
import { config } from '../config';

/**
 * Регистрация и авторизация.
 * Регистрация: POST /register { email, name, password, password_confirmation, consent }.
 * Авторизация: POST /auth { email, password } -> { token }.
 */
export const authApi = {
  register: async (payload) => {
    const { data } = await http.post(config.endpoints.auth.register, payload);
    return data;
  },

  login: async (credentials) => {
    const { data } = await http.post(config.endpoints.auth.login, credentials);
    return data;
  },

  logout: async () => {
    try {
      await http.post(config.endpoints.auth.logout);
    } catch (error) {
      // Выход не должен ломать приложение, даже если эндпоинт отсутствует.
    }
    return true;
  },

  me: async () => {
    const { data } = await http.get(config.endpoints.auth.me);
    return data;
  },
};

export default authApi;
