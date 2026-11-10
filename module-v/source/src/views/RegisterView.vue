<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api, { fieldErrors, errorMessage } from '../api';

const router = useRouter();
const form = ref({ email: '', name: '', password: '', password_confirmation: '', consent: false });
const errors = ref({});
const message = ref('');

// Регистрация: серверная валидация, при успехе — переход на страницу входа.
const submit = async () => {
    errors.value = {};
    message.value = '';
    if (form.value.password !== form.value.password_confirmation) {
        errors.value = { password: 'Пароли не совпадают' };
        return;
    }
    try {
        await api.post('/register', form.value);
        router.push('/login');
    } catch (e) {
        errors.value = fieldErrors(e);
        message.value = errorMessage(e, 'Не удалось зарегистрироваться');
    }
};
</script>

<template>
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <h1 class="h3 mb-3">Регистрация</h1>
            <div v-if="message" class="alert alert-danger" role="alert">{{ message }}</div>
            <form class="card card-body" novalidate @submit.prevent="submit">
                <label class="form-label" for="reg-email">E-mail</label>
                <input
                    id="reg-email"
                    v-model="form.email"
                    type="email"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                />
                <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>

                <label class="form-label mt-3" for="reg-name">Имя</label>
                <input
                    id="reg-name"
                    v-model="form.name"
                    type="text"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.name }"
                />
                <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>

                <label class="form-label mt-3" for="reg-password">Пароль</label>
                <input
                    id="reg-password"
                    v-model="form.password"
                    type="password"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                />
                <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>

                <label class="form-label mt-3" for="reg-password2">Повтор пароля</label>
                <input
                    id="reg-password2"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.password_confirmation }"
                />
                <div v-if="errors.password_confirmation" class="invalid-feedback">
                    {{ errors.password_confirmation }}
                </div>

                <div class="form-check mt-3">
                    <input
                        id="reg-consent"
                        v-model="form.consent"
                        type="checkbox"
                        required
                        class="form-check-input"
                        :class="{ 'is-invalid': errors.consent }"
                    />
                    <label class="form-check-label" for="reg-consent">Согласен на обработку персональных данных</label>
                    <div v-if="errors.consent" class="invalid-feedback">{{ errors.consent }}</div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Зарегистрироваться</button>
                <p class="mt-2 mb-0">Уже есть аккаунт? <router-link to="/login">Войти</router-link></p>
            </form>
        </div>
    </div>
</template>
