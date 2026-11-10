<script setup>
/** Переиспользуемое многострочное поле формы. */
defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  id: { type: String, required: true },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  rows: { type: [String, Number], default: 4 },
  hint: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="text-danger" aria-hidden="true">*</span>
    </label>
    <textarea
      :id="id"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :rows="rows"
      class="form-control"
      :class="{ 'is-invalid': Boolean(error) }"
      :aria-invalid="Boolean(error) ? 'true' : 'false'"
      @input="$emit('update:modelValue', $event.target.value)"
    ></textarea>
    <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
    <div v-else-if="hint" class="form-text">{{ hint }}</div>
  </div>
</template>
