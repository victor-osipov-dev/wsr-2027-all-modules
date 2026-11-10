<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import BaseInput from '../components/BaseInput.vue';
import { useForm } from '../composables/useForm';
import { useNotify } from '../composables/useNotify';
import { parseApiError } from '../api/errors';
import { useAuth } from '../stores/auth';

const router = useRouter();
const route = useRoute();
const { login } = useAuth();
const { push } = useNotify();

const loading = ref(false);
const generalError = ref('');

const { form, errors, setErrors, clearErrors } = useForm({ email: '', password: '' });

const submit = async () => {
  generalError.value = '';
  clearErrors();

  // Базовая клиентская проверка заполнения (серверная валидация — основная).
  const localErrors = {};
  if (!form.email.trim()) localErrors.email = 'Укажите электронную почту';
  if (!form.password) localErrors.password = 'Укажите пароль';
  if (Object.keys(localErrors).length) {
    setErrors(localErrors);
    return;
  }

  loading.value = true;
  try {
    await login({ email: form.email.trim(), password: form.password });
    push({ variant: 'success', title: 'Добро пожаловать!', text: 'Вы успешно авторизованы.' });
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null;
    await router.push(redirect || { name: 'recipes' });
  } catch (error) {
    const parsed = parseApiError(error);
    if (Object.keys(parsed.errors).length) {
      setErrors(parsed.errors);
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
      <div class="col-lg-5 col-md-8">
        <div class="panel auth-panel">
          <header class="panel-head">
            <h1 class="panel-title">Вход в личный кабинет</h1>
            <p class="panel-subtitle">Авторизуйтесь, чтобы публиковать и редактировать свои рецепты.</p>
          </header>

          <div v-if="generalError" class="alert alert-danger" role="alert">{{ generalError }}</div>

          <form novalidate @submit.prevent="submit">
            <BaseInput
              id="login-email"
              v-model="form.email"
              label="E-mail"
              type="email"
              placeholder="name@example.com"
              autocomplete="email"
              required
              :error="errors.email"
            />

            <BaseInput
              id="login-password"
              v-model="form.password"
              label="Пароль"
              type="password"
              placeholder="Введите пароль"
              autocomplete="current-password"
              required
              :error="errors.password"
            />

            <div class="form-row-between">
              <router-link to="/register" class="link-muted">Нет аккаунта? Зарегистрироваться</router-link>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg" :disabled="loading">
              {{ loading ? 'Вход…' : 'Войти' }}
            </button>
          </form>

          <p class="auth-hint">
            Тестовый пользователь: <strong>user@champ2026.lan</strong> / <strong>Champ2026</strong>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
