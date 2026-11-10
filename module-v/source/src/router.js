import { createRouter, createWebHistory } from 'vue-router';
import { token } from './api';
import LoginView from './views/LoginView.vue';
import RegisterView from './views/RegisterView.vue';
import RecipesListView from './views/RecipesListView.vue';
import RecipeView from './views/RecipeView.vue';
import MyRecipesView from './views/MyRecipesView.vue';
import RecipeFormView from './views/RecipeFormView.vue';
import ConstructorView from './views/ConstructorView.vue';

// Маршруты SPA. /recipes/new объявлен раньше /recipes/:id, иначе перехватится как id.
const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: LoginView, meta: { guest: 1 } },
        { path: '/register', component: RegisterView, meta: { guest: 1 } },
        { path: '/', component: RecipesListView },
        { path: '/recipes/new', component: RecipeFormView, meta: { auth: 1 } },
        { path: '/recipes/:id/edit', component: RecipeFormView, meta: { auth: 1 } },
        { path: '/recipes/:id', component: RecipeView },
        { path: '/my', component: MyRecipesView, meta: { auth: 1 } },
        { path: '/constructor', component: ConstructorView },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

// Гвард: закрытые маршруты только с токеном, гостевые — только без него.
router.beforeEach((to) => {
    if (to.meta.auth && !token()) return '/login';
    if (to.meta.guest && token()) return '/';
});

export default router;
