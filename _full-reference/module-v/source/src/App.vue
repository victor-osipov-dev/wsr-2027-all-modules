<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import AppHeader from './components/AppHeader.vue';
import AppFooter from './components/AppFooter.vue';
import NotificationHost from './components/NotificationHost.vue';
import { initEcho } from './services/echo';
import { useAuth } from './stores/auth';

const router = useRouter();
const { isAuthenticated, fetchUser, resetSession } = useAuth();

/**
 * Обработка разлогина (401 от API): сбрасываем сессию и уводим на /login.
 */
const handleUnauthorized = async () => {
  resetSession();
  if (router.currentRoute.value.name !== 'login') {
    await router.push({ name: 'login' });
  }
};

onMounted(async () => {
  // Подключаем WebSocket-уведомления (работают и для гостей, и для авторизованных).
  initEcho();
  window.addEventListener('app:unauthorized', handleUnauthorized);

  // Если токен есть — подтягиваем профиль пользователя с сервера.
  if (isAuthenticated.value) {
    await fetchUser();
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('app:unauthorized', handleUnauthorized);
});
</script>

<template>
  <div class="app-shell">
    <AppHeader />

    <main class="app-main">
      <router-view v-slot="{ Component }">
        <transition name="fade-page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <AppFooter />

    <!-- Всплывающие уведомления (WebSocket и системные) -->
    <NotificationHost />
  </div>
</template>
