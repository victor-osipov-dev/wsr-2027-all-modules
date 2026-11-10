import { reactive, computed } from 'vue';
import { authApi } from '../api/auth';
import { getToken, setToken, clearToken } from '../utils/token';

/**
 * Хранилище состояния авторизации.
 * В localStorage лежит только токен; данные пользователя всегда запрашиваются с сервера.
 */
const state = reactive({
  token: getToken(),
  user: null,
  userLoaded: false,
});

const isAuthenticated = computed(() => Boolean(state.token));
const currentUserId = computed(() => (state.user ? state.user.id : null));

/** Сохраняет токен в состоянии и в localStorage. */
const applyToken = (token) => {
  state.token = token || '';
  setToken(token || '');
};

/** Авторизация: получает токен и сохраняет его. */
const login = async (credentials) => {
  const data = await authApi.login(credentials);
  applyToken(data && (data.token || data.access_token));
  state.userLoaded = false;
  await fetchUser();
  return data;
};

/** Регистрация нового пользователя. */
const register = async (payload) => authApi.register(payload);

/** Загружает профиль пользователя; ошибки не критичны. */
const fetchUser = async () => {
  if (!state.token) return null;
  try {
    const data = await authApi.me();
    state.user = data && data.data ? data.data : data;
  } catch (error) {
    state.user = null;
  } finally {
    state.userLoaded = true;
  }
  return state.user;
};

/** Выход: очищает токен (и, по возможности, сообщает серверу). */
const logout = async () => {
  try {
    await authApi.logout();
  } finally {
    applyToken('');
    state.user = null;
    state.userLoaded = false;
  }
};

/** Сброс сессии без обращения к серверу (например, при 401). */
const resetSession = () => {
  applyToken('');
  state.user = null;
  state.userLoaded = false;
};

export const useAuth = () => ({
  state,
  isAuthenticated,
  currentUserId,
  login,
  register,
  logout,
  fetchUser,
  resetSession,
});

export default useAuth;
