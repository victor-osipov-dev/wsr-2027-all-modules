<script setup>
import EmptyState from '../components/EmptyState.vue';
</script>

<template>
  <div class="container page-container">
    <EmptyState
      icon="🧭"
      title="Страница не найдена"
      text="Возможно, ссылка устарела или была введена с ошибкой."
    >
      <router-link to="/" class="btn btn-primary">Вернуться к рецептам</router-link>
    </EmptyState>
  </div>
</template>
