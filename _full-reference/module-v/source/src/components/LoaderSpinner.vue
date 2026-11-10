<script setup>
defineProps({
  text: { type: String, default: 'Загрузка…' },
});
</script>

<template>
  <div class="loader" role="status">
    <span class="spinner"></span>
    <span class="loader-text">{{ text }}</span>
  </div>
</template>
