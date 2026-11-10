<script setup>
import { useNotify } from '../composables/useNotify';

const { state, remove } = useNotify();
</script>

<template>
  <div class="notification-host" aria-live="polite" aria-atomic="true">
    <transition-group name="toast">
      <div
        v-for="notification in state.items"
        :key="notification.id"
        class="toast-card"
        :class="`toast-${notification.variant}`"
        role="alert"
      >
        <div class="toast-body">
          <strong v-if="notification.title" class="toast-title">{{ notification.title }}</strong>
          <!-- Рабочая ссылка на рецепт: реальный <a href> через vue-router -->
          <router-link
            v-if="notification.to"
            :to="notification.to"
            class="toast-link"
            @click="remove(notification.id)"
          >
            {{ notification.text }}
          </router-link>
          <span v-else>{{ notification.text }}</span>
        </div>
        <button type="button" class="toast-close" aria-label="Закрыть уведомление" @click="remove(notification.id)">×</button>
      </div>
    </transition-group>
  </div>
</template>
