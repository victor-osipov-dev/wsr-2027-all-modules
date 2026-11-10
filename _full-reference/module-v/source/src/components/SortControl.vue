<script setup>
import { config } from '../config';

/**
 * Переиспользуемый элемент сортировки списка рецептов.
 * Поддерживает сортировку по дате создания, времени приготовления и калорийности в обе стороны.
 */
defineProps({
  modelValue: { type: String, default: config.sort.createdDesc },
  label: { type: String, default: 'Сортировка' },
  id: { type: String, default: 'sort-select' },
});

defineEmits(['update:modelValue']);

const options = [
  { value: config.sort.createdDesc, label: 'Сначала новые (по дате)' },
  { value: config.sort.createdAsc, label: 'Сначала старые (по дате)' },
  { value: config.sort.timeAsc, label: 'Время приготовления ↑' },
  { value: config.sort.timeDesc, label: 'Время приготовления ↓' },
  { value: config.sort.caloriesAsc, label: 'Калорийность ↑' },
  { value: config.sort.caloriesDesc, label: 'Калорийность ↓' },
];
</script>

<template>
  <div class="form-group mb-0">
    <label v-if="label" :for="id" class="form-label">{{ label }}</label>
    <select
      :id="id"
      class="form-select"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
  </div>
</template>
