import http from './http';
import { config } from '../config';
import { isNetworkError } from './errors';
import { demoRecipes, demoIngredients, sortDemoRecipes, isDemoFallbackEnabled } from '../data/demo';

/** Разрешён ли фолбэк на демо-данные для данной ошибки (только полное отсутствие ответа). */
const useFallback = (error) => isDemoFallbackEnabled() && isNetworkError(error);

/** Нормализует список рецептов: сервер может вернуть массив или объект пагинации. */
export const normalizeList = (payload) => {
  if (Array.isArray(payload)) {
    return { items: payload, meta: { current_page: 1, last_page: 1, per_page: payload.length, total: payload.length } };
  }
  const source = payload || {};
  const items = Array.isArray(source.data) ? source.data : Array.isArray(source.items) ? source.items : [];
  return {
    items,
    meta: {
      current_page: Number(source.current_page || source.page || 1),
      last_page: Number(source.last_page || source.pages || 1),
      per_page: Number(source.per_page || items.length || 1),
      total: Number(source.total || items.length),
    },
  };
};

const demoList = (params = {}) => {
  const { page = 1, per_page = 12 } = params;
  const sorted = sortDemoRecipes(demoRecipes, params.sort);
  const start = (Number(page) - 1) * Number(per_page);
  const items = sorted.slice(start, start + Number(per_page));
  return {
    data: items,
    current_page: Number(page),
    last_page: Math.max(1, Math.ceil(sorted.length / Number(per_page))),
    per_page: Number(per_page),
    total: sorted.length,
    demo: true,
  };
};

/** Фильтрация набора рецептов по ингредиентам (когда сервер вернул полный список). */
const filterByIngredients = (payload, wantedIds) => {
  const { items } = normalizeList(payload);
  const withIngredients = items.filter((recipe) => Array.isArray(recipe.ingredients));
  if (!withIngredients.length) return payload;
  const matched = withIngredients.filter((recipe) =>
    wantedIds.every((id) => recipe.ingredients.some((item) => String(item.id) === id)),
  );
  return Array.isArray(payload) ? matched : { ...(payload || {}), data: matched, total: matched.length };
};

/** Демонстрационный поиск по ингредиентам. */
const demoSearch = (wantedIds) => {
  const matched = demoRecipes.filter((recipe) =>
    wantedIds.every((id) => (recipe.ingredients || []).some((item) => String(item.id) === id)),
  );
  return { data: matched, total: matched.length, demo: true };
};

/** REST-клиент рецептов. */
export const recipesApi = {
  /** Список рецептов с сортировкой и пагинацией. */
  list: async (params = {}) => {
    try {
      const { data } = await http.get(config.endpoints.recipes.list, { params });
      return data;
    } catch (error) {
      if (useFallback(error)) return demoList(params);
      throw error;
    }
  },

  /** Список рецептов текущего пользователя. */
  my: async (params = {}) => {
    try {
      const { data } = await http.get(config.endpoints.recipes.my, { params });
      return data;
    } catch (error) {
      const status = error && error.response && error.response.status;
      // Фолбэк, если сервер не поддерживает /recipes/my: запрашиваем с флагом my=1.
      if (status === 404 || status === 405) {
        try {
          const { data } = await http.get(config.endpoints.recipes.list, { params: { ...params, my: 1 } });
          return data;
        } catch (fallbackError) {
          if (isDemoFallbackEnabled() && isNetworkError(fallbackError)) {
            return { data: demoRecipes.filter((recipe) => recipe.user_id === 1), demo: true };
          }
          throw fallbackError;
        }
      }
      if (isDemoFallbackEnabled() && isNetworkError(error)) return { data: demoRecipes.filter((recipe) => recipe.user_id === 1), demo: true };
      throw error;
    }
  },

  /** Детальная информация о рецепте. */
  detail: async (id) => {
    try {
      const { data } = await http.get(config.endpoints.recipes.detail(id));
      return data;
    } catch (error) {
      if (isDemoFallbackEnabled() && isNetworkError(error)) {
        const found = demoRecipes.find((recipe) => String(recipe.id) === String(id));
        if (found) return found;
      }
      throw error;
    }
  },

  /** Поиск рецептов, содержащих ВСЕ указанные ингредиенты. */
  searchByIngredients: async (ingredientIds = [], params = {}) => {
    if (!ingredientIds.length) return recipesApi.list(params);
    const wanted = ingredientIds.map(String);
    try {
      const { data } = await http.get(config.endpoints.recipes.search, {
        params: { ingredients: ingredientIds, ...params },
      });
      return data;
    } catch (error) {
      const status = error && error.response && error.response.status;
      // Фолбэк: сервер отфильтрует основной список по параметру ingredients.
      if (status === 400 || status === 404 || status === 405) {
        try {
          const { data } = await http.get(config.endpoints.recipes.list, {
            params: { ...params, ingredients: ingredientIds },
          });
          return filterByIngredients(data, wanted);
        } catch (fallbackError) {
          if (isDemoFallbackEnabled() && isNetworkError(fallbackError)) return demoSearch(wanted);
          throw fallbackError;
        }
      }
      if (isDemoFallbackEnabled() && isNetworkError(error)) return demoSearch(wanted);
      throw error;
    }
  },

  /** Создание рецепта (multipart/form-data, поддерживает загрузку изображения). */
  create: async (formData) => {
    // Content-Type для FormData axios/browser выставит сам (вместе с boundary).
    const { data } = await http.post(config.endpoints.recipes.create, formData);
    return data;
  },

  /**
   * Редактирование рецепта.
   * Используем POST + _method=PUT: так надёжно работают multipart-запросы с файлами
   * с любым серверным фреймворком (Laravel/Django/Yii).
   */
  update: async (id, formData) => {
    formData.append('_method', 'PUT');
    const { data } = await http.post(config.endpoints.recipes.update(id), formData);
    return data;
  },

  /** Удаление рецепта. */
  remove: async (id) => {
    const { data } = await http.delete(config.endpoints.recipes.remove(id));
    return data;
  },
};

export const ingredientsApi = {
  /** Список ингредиентов (сервер отдаёт в алфавитном порядке; сортируем на всякий случай). */
  list: async () => {
    try {
      const { data } = await http.get(config.endpoints.ingredients.list);
      const items = Array.isArray(data) ? data : data && Array.isArray(data.data) ? data.data : [];
      return [...items].sort((a, b) => String(a.title || '').localeCompare(String(b.title || ''), 'ru'));
    } catch (error) {
      if (isDemoFallbackEnabled() && isNetworkError(error)) return demoIngredients;
      throw error;
    }
  },
};

export default recipesApi;
