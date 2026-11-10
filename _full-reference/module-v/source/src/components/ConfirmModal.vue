<script setup>
defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, default: 'Подтвердите действие' },
  text: { type: String, default: 'Вы уверены?' },
  confirmText: { type: String, default: 'Удалить' },
  cancelText: { type: String, default: 'Отмена' },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
  <transition name="fade-page">
    <div v-if="visible" class="modal-overlay" role="dialog" aria-modal="true" @click.self="emit('cancel')">
      <div class="modal-box modal-confirm">
        <header class="modal-head">
          <h2 class="modal-heading">{{ title }}</h2>
          <button type="button" class="modal-x" aria-label="Закрыть" @click="emit('cancel')">×</button>
        </header>
        <div class="modal-body-text">{{ text }}</div>
        <footer class="modal-actions">
          <button type="button" class="btn btn-outline-primary" :disabled="loading" @click="emit('cancel')">
            {{ cancelText }}
          </button>
          <button type="button" class="btn btn-danger" :disabled="loading" @click="emit('confirm')">
            {{ loading ? 'Удаление…' : confirmText }}
          </button>
        </footer>
      </div>
    </div>
  </transition>
</template>
