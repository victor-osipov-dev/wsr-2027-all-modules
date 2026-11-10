import { reactive, computed } from 'vue';

/**
 * Переиспользуемый помощник для форм с серверной валидацией.
 * Хранит значения полей и карту ошибок { field: message }, полученную от сервера (422).
 */
export const useForm = (initialValues = {}) => {
  const form = reactive({ ...initialValues });
  const rawErrors = reactive({});

  const errors = computed(() => rawErrors);

  /** Применяет ошибки, полученные от API. */
  const setErrors = (fieldErrors = {}) => {
    Object.keys(rawErrors).forEach((key) => delete rawErrors[key]);
    Object.entries(fieldErrors).forEach(([field, message]) => {
      rawErrors[field] = Array.isArray(message) ? message[0] : message;
    });
  };

  /** Очищает все ошибки. */
  const clearErrors = () => {
    Object.keys(rawErrors).forEach((key) => delete rawErrors[key]);
  };

  /** Устанавливает ошибку конкретного поля. */
  const setError = (field, message) => {
    rawErrors[field] = message;
  };

  /** Есть ли ошибка у поля. */
  const hasError = (field) => Boolean(rawErrors[field]);

  /** Меняет значения формы. */
  const fill = (values = {}) => {
    Object.assign(form, values);
  };

  /** Сбрасывает форму к исходным значениям. */
  const reset = () => {
    Object.assign(form, initialValues);
    clearErrors();
  };

  return { form, errors, setErrors, setError, clearErrors, hasError, fill, reset };
};

export default useForm;
