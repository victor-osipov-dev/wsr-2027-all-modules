<script setup>
defineProps({
  title: { type: String, default: 'Ничего не найдено' },
  text: { type: String, default: '' },
  icon: { type: String, default: '🍽️' },
});
</script>

<template>
  <div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true">{{ icon }}</span>
    <h3 class="empty-state__title">{{ title }}</h3>
    <p v-if="text" class="empty-state__text">{{ text }}</p>
    <slot />
  </div>
</template>
