<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../stores/auth';
import { config } from '../config';

const router = useRouter();
const { isAuthenticated, state, logout } = useAuth();

// Состояние мобильного меню (бургер).
const menuOpen = ref(false);

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value;
};

const closeMenu = () => {
  menuOpen.value = false;
};

const userName = computed(() => {
  const user = state.user || {};
  return user.name || user.email || 'Профиль';
});

const handleLogout = async () => {
  await logout();
  closeMenu();
  await router.push({ name: 'recipes' });
};
</script>

<template>
  <header class="app-header" id="main-nav">
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <!-- Логотип -->
        <router-link to="/" class="navbar-brand" id="logo" @click="closeMenu">
          <span class="logo-mark" aria-hidden="true">
            <svg viewBox="0 0 64 64" width="34" height="34" role="img" aria-label="Логотип Кухни мира">
              <rect width="64" height="64" rx="14" fill="#e8590c" />
              <path d="M20 14v18a8 8 0 0 0 16 0V14" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" />
              <path d="M28 14v14" stroke="#fff" stroke-width="5" stroke-linecap="round" />
              <path d="M44 14c-6 0-8 6-8 12s2 12 8 12 8-6 8-12-2-12-8-12z" fill="#fff" />
            </svg>
          </span>
          <span class="brand-text">
            <strong>{{ config.app.name }}</strong>
            <small>{{ config.app.tagline }}</small>
          </span>
        </router-link>

        <!-- Бургер-меню (мобильная версия) -->
        <button
          class="navbar-toggler"
          type="button"
          :aria-expanded="menuOpen ? 'true' : 'false'"
          aria-label="Открыть меню"
          @click="toggleMenu"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-collapse" :class="{ show: menuOpen }">
          <ul class="navbar-nav">
            <li class="nav-item">
              <router-link to="/" class="nav-link" active-class="active" exact-active-class="active" @click="closeMenu">
                Рецепты
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/constructor" class="nav-link" active-class="active" @click="closeMenu">
                Конструктор
              </router-link>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <router-link to="/my" class="nav-link" active-class="active" @click="closeMenu">
                Мои рецепты
              </router-link>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <router-link to="/create" class="nav-link" active-class="active" @click="closeMenu">
                Создать рецепт
              </router-link>
            </li>
          </ul>

          <ul class="navbar-nav navbar-nav-auth">
            <template v-if="isAuthenticated">
              <li class="nav-item">
                <span class="nav-link nav-user" title="Вы вошли в систему">
                  <span class="user-dot" aria-hidden="true"></span>{{ userName }}
                </span>
              </li>
              <li class="nav-item">
                <button type="button" class="btn btn-outline-primary btn-sm nav-cta" @click="handleLogout">Выход</button>
              </li>
            </template>
            <template v-else>
              <li class="nav-item">
                <router-link to="/login" class="nav-link" active-class="active" @click="closeMenu">Войти</router-link>
              </li>
              <li class="nav-item">
                <router-link to="/register" class="btn btn-primary btn-sm nav-cta" @click="closeMenu">Регистрация</router-link>
              </li>
            </template>
          </ul>
        </div>
      </div>
    </nav>
  </header>
</template>
