<script setup>
/** Переиспользуемый выпадающий список формы. */
defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  id: { type: String, required: true },
  options: { type: Array, default: () => [] },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  placeholder: { type: String, default: '' },
  hint: { type: String, default: '' },
});

defineEmits(['update:modelValue', 'change']);
</script>

<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>
    <select
      :id="id"
      class="form-select"
      :class="{ 'is-invalid': Boolean(error) }"
      :required="required"
      :value="modelValue"
      :aria-invalid="Boolean(error) ? 'true' : 'false'"
      @change="$emit('update:modelValue', $event.target.value); $emit('change', $event)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
    <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
    <div v-else-if="hint" class="form-text">{{ hint }}</div>
  </div>
</template>
