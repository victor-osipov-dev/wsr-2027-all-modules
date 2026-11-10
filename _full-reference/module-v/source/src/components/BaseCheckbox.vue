<script setup>
/** Переиспользуемый чекбокс формы (например, согласие на обработку данных). */
defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
  id: { type: String, required: true },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class="form-group form-check-group">
    <div class="form-check" :class="{ 'is-invalid': Boolean(error) }">
      <input
        :id="id"
        class="form-check-input"
        :class="{ 'is-invalid': Boolean(error) }"
        type="checkbox"
        :checked="modelValue"
        :required="required"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
      <label class="form-check-label" :for="id">
        {{ label }}
        <span v-if="required" class="text-danger" aria-hidden="true">*</span>
      </label>
    </div>
    <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
  </div>
</template>
