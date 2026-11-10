<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import BaseInput from '../components/BaseInput.vue';
import BaseCheckbox from '../components/BaseCheckbox.vue';
import { useForm } from '../composables/useForm';
import { useNotify } from '../composables/useNotify';
import { parseApiError } from '../api/errors';
import { useAuth } from '../stores/auth';

const router = useRouter();
const { register } = useAuth();
const { push } = useNotify();

const loading = ref(false);
const generalError = ref('');

const { form, errors, setErrors, clearErrors } = useForm({
  email: '',
  name: '',
  password: '',
  password_confirmation: '',
  consent: false,
});

/** Клиентская проверка перед отправкой (серверная валидация остаётся основной). */
const validate = () => {
  const found = {};
  if (!form.email.trim()) found.email = 'Укажите электронную почту';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) found.email = 'Некорректный адрес электронной почты';
  if (!form.name.trim()) found.name = 'Укажите имя';
  if (!form.password) found.password = 'Укажите пароль';
  if (!form.password_confirmation) found.password_confirmation = 'Повторите пароль';
  else if (form.password !== form.password_confirmation) found.password_confirmation = 'Пароли не совпадают';
  if (!form.consent) found.consent = 'Необходимо согласие на обработку персональных данных';
  return found;
};

const submit = async () => {
  generalError.value = '';
  clearErrors();

  const localErrors = validate();
  if (Object.keys(localErrors).length) {
    setErrors(localErrors);
    return;
  }

  loading.value = true;
  try {
    await register({
      email: form.email.trim(),
      name: form.name.trim(),
      password: form.password,
      password_confirmation: form.password_confirmation,
      consent: form.consent,
    });
    push({ variant: 'success', title: 'Регистрация завершена', text: 'Теперь войдите под своими данными.' });
    await router.push({ name: 'login' });
  } catch (error) {
    const parsed = parseApiError(error);
    // Приводим возможные синонимы полей к именам формы.
    const normalized = { ...parsed.errors };
    if (normalized.password_confirmation === undefined && normalized.password) {
      normalized.password_confirmation = normalized.password;
    }
    if (Object.keys(normalized).length) {
      setErrors(normalized);
    } else {
      generalError.value = parsed.message;
    }
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="container page-container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-9">
        <div class="panel auth-panel">
          <header class="panel-head">
            <h1 class="panel-title">Регистрация</h1>
            <p class="panel-subtitle">Создайте аккаунт, чтобы добавлять рецепты на платформу.</p>
          </header>

          <div v-if="generalError" class="alert alert-danger" role="alert">{{ generalError }}</div>

          <form novalidate @submit.prevent="submit">
            <BaseInput
              id="register-email"
              v-model="form.email"
              label="E-mail"
              type="email"
              placeholder="name@example.com"
              autocomplete="email"
              required
              :error="errors.email"
            />

            <BaseInput
              id="register-name"
              v-model="form.name"
              label="Имя"
              type="text"
              placeholder="Как вас зовут"
              autocomplete="given-name"
              required
              :error="errors.name"
            />

            <div class="row">
              <div class="col-md-6">
                <BaseInput
                  id="register-password"
                  v-model="form.password"
                  label="Пароль"
                  type="password"
                  placeholder="Минимум 5 символов"
                  autocomplete="new-password"
                  required
                  :error="errors.password"
                />
              </div>
              <div class="col-md-6">
                <BaseInput
                  id="register-password-confirmation"
                  v-model="form.password_confirmation"
                  label="Повтор пароля"
                  type="password"
                  placeholder="Повторите пароль"
                  autocomplete="new-password"
                  required
                  :error="errors.password_confirmation"
                />
              </div>
            </div>

            <BaseCheckbox
              id="register-consent"
              v-model="form.consent"
              label="Согласен на обработку персональных данных"
              required
              :error="errors.consent"
            />

            <button type="submit" class="btn btn-primary w-100 btn-lg" :disabled="loading">
              {{ loading ? 'Отправка…' : 'Зарегистрироваться' }}
            </button>
          </form>

          <p class="auth-hint">Уже есть аккаунт? <router-link to="/login">Войти</router-link></p>
        </div>
      </div>
    </div>
  </div>
</template>
