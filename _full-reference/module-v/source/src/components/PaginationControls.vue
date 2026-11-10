<script setup>
import { computed } from 'vue';

/** Постраничная навигация списка (Bootstrap-совместимая). */
const props = defineProps({
  currentPage: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  maxVisible: { type: Number, default: 5 },
});

const emit = defineEmits(['change']);

const pages = computed(() => {
  const total = props.lastPage;
  const current = props.currentPage;
  const windowSize = Math.min(props.maxVisible, total);
  let start = Math.max(1, current - Math.floor(windowSize / 2));
  const end = Math.min(total, start + windowSize - 1);
  start = Math.max(1, end - windowSize + 1);
  return Array.from({ length: end - start + 1 }, (unused, index) => start + index);
});

const go = (page) => {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage) {
    emit('change', page);
  }
};
</script>

<template>
  <nav v-if="lastPage > 1" class="pagination-nav" aria-label="Постраничная навигация">
    <ul class="pagination">
      <li class="page-item" :class="{ disabled: currentPage <= 1 }">
        <button type="button" class="page-link" :disabled="currentPage <= 1" @click="go(currentPage - 1)">Назад</button>
      </li>
      <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page === currentPage }">
        <button type="button" class="page-link" @click="go(page)">{{ page }}</button>
      </li>
      <li class="page-item" :class="{ disabled: currentPage >= lastPage }">
        <button type="button" class="page-link" :disabled="currentPage >= lastPage" @click="go(currentPage + 1)">
          Вперёд
        </button>
      </li>
    </ul>
  </nav>
</template>
