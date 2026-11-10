import { createRouter, createWebHistory } from 'vue-router';
import { hasToken } from '../utils/token';

/**
 * Маршрутизация SPA (vue-router).
 * Публичные маршруты: список рецептов и просмотр рецепта (доступны гостям).
 * Маршруты списка своих рецептов, конструктора и создания/редактирования требуют авторизации.
 */
const routes = [
  {
    path: '/',
    name: 'recipes',
    component: () => import('../views/RecipesListView.vue'),
    meta: { title: 'Рецепты' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { title: 'Вход', guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue'),
    meta: { title: 'Регистрация', guestOnly: true },
  },
  {
    path: '/recipes/:id',
    name: 'recipe',
    component: () => import('../views/RecipeView.vue'),
    meta: { title: 'Рецепт' },
    props: true,
  },
  {
    path: '/my',
    name: 'my-recipes',
    component: () => import('../views/MyRecipesView.vue'),
    meta: { title: 'Мои рецепты', requiresAuth: true },
  },
  {
    path: '/create',
    name: 'recipe-create',
    component: () => import('../views/RecipeFormView.vue'),
    meta: { title: 'Новый рецепт', requiresAuth: true },
  },
  {
    path: '/recipes/:id/edit',
    name: 'recipe-edit',
    component: () => import('../views/RecipeFormView.vue'),
    meta: { title: 'Редактирование рецепта', requiresAuth: true },
    props: true,
  },
  {
    path: '/constructor',
    name: 'constructor',
    component: () => import('../views/ConstructorView.vue'),
    meta: { title: 'Конструктор рецептов' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../views/NotFoundView.vue'),
    meta: { title: 'Страница не найдена' },
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL || '/'),
  routes,
  scrollBehavior: () => ({ top: 0 }),
});

// Навигационный охранник: проверяет авторизацию и «гостевые» маршруты.
router.beforeEach((to) => {
  const authenticated = hasToken();

  if (to.meta.requiresAuth && !authenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
  if (to.meta.guestOnly && authenticated) {
    return { name: 'recipes' };
  }
  return true;
});

// Заголовок документа.
router.afterEach((to) => {
  const base = 'Кухни мира — платформа рецептов';
  document.title = to.meta && to.meta.title ? `${to.meta.title} · ${base}` : base;
});

export default router;
