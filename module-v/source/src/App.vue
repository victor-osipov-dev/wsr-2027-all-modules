<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { token, setToken } from './api';

const router = useRouter();
const authed = ref(Boolean(token()));
router.afterEach(() => {
    authed.value = Boolean(token());
});

// Выход: очищаем токен и отправляем на страницу входа.
const logout = () => {
    setToken('');
    router.push('/login');
};
</script>

<template>
    <!-- Шапка с навигацией (пункты меняются в зависимости от авторизации) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3" id="main-nav">
        <router-link to="/" class="navbar-brand fw-bold" id="logo">🍲 Кухни мира</router-link>
        <div class="navbar-nav me-auto">
            <router-link to="/" class="nav-link">Рецепты</router-link>
            <router-link to="/constructor" class="nav-link">Конструктор</router-link>
            <router-link v-if="authed" to="/my" class="nav-link">Мои рецепты</router-link>
            <router-link v-if="authed" to="/recipes/new" class="nav-link">Создать рецепт</router-link>
        </div>
        <div class="navbar-nav">
            <router-link v-if="!authed" to="/login" class="nav-link">Войти</router-link>
            <router-link v-if="!authed" to="/register" class="nav-link">Регистрация</router-link>
            <button v-if="authed" type="button" class="btn btn-outline-light btn-sm" @click="logout">Выход</button>
        </div>
    </nav>

    <main class="container py-4">
        <router-view />
    </main>
</template>

<style>
/* Единый вид карточек рецептов во всех списках */
.card-img-top {
    height: 190px;
    object-fit: cover;
}
</style>
