<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api, { setToken, fieldErrors, errorMessage } from '../api';

const router = useRouter();
const form = ref({ email: '', password: '' });
const errors = ref({});
const message = ref('');

// Авторизация: 422-ошибки сервера показываем у полей, при успехе сохраняем токен.
const submit = async () => {
    errors.value = {};
    message.value = '';
    try {
        const { data } = await api.post('/auth', form.value);
        setToken(data.token || data.access_token);
        router.push('/');
    } catch (e) {
        errors.value = fieldErrors(e);
        message.value = errorMessage(e, 'Не удалось войти');
    }
};
</script>

<template>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <h1 class="h3 mb-3">Вход</h1>
            <div v-if="message" class="alert alert-danger" role="alert">{{ message }}</div>
            <form class="card card-body" novalidate @submit.prevent="submit">
                <label class="form-label" for="login-email">E-mail</label>
                <input
                    id="login-email"
                    v-model="form.email"
                    type="email"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                />
                <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>

                <label class="form-label mt-3" for="login-password">Пароль</label>
                <input
                    id="login-password"
                    v-model="form.password"
                    type="password"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                />
                <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>

                <button type="submit" class="btn btn-primary mt-3">Войти</button>
                <p class="mt-2 mb-0">Нет аккаунта? <router-link to="/register">Зарегистрироваться</router-link></p>
            </form>
        </div>
    </div>
</template>
