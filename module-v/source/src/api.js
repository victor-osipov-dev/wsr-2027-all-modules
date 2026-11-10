import axios from 'axios';

// Токен хранится только в localStorage — все остальные данные приходят с сервера.
export const token = () => localStorage.getItem('token') || '';
export const setToken = (value) => (value ? localStorage.setItem('token', value) : localStorage.removeItem('token'));

// Единый HTTP-клиент: Client уходит в КАЖДОМ запросе, Authorization — при наличии токена.
const api = axios.create({ baseURL: import.meta.env.VITE_API_BASE_URL || '/recipe-api' });
api.interceptors.request.use((config) => {
    config.headers.Client = import.meta.env.VITE_CLIENT_LOGIN || 'xxxxxx';
    const t = token();
    if (t) config.headers.Authorization = `Bearer ${t}`;
    return config;
});

// Приводит серверные 422-ошибки к объекту { поле: сообщение }.
export const fieldErrors = (error) => {
    const errors = (error && error.response && error.response.data && error.response.data.errors) || {};
    return Object.fromEntries(
        Object.entries(errors).map(([field, list]) => [field, Array.isArray(list) ? list[0] : list])
    );
};

// Текст общей ошибки запроса (или запасной вариант).
export const errorMessage = (error, fallback) =>
    (error && error.response && error.response.data && error.response.data.message) || fallback;

// Сервер может вернуть массив или объект пагинации — берём список рецептов.
export const asList = (payload) => (Array.isArray(payload) ? payload : (payload && payload.data) || []);

export default api;
