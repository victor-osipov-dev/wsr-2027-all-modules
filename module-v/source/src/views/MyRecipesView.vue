<script setup>
import { ref, onMounted } from 'vue';
import api, { asList } from '../api';

const recipes = ref([]);
const pending = ref(null);

// Загрузка только своих рецептов.
const load = async () => {
    recipes.value = asList((await api.get('/recipes/my')).data);
};

// Удаление после подтверждения, затем обновление списка.
const remove = async () => {
    await api.delete(`/recipes/${pending.value.id}`);
    pending.value = null;
    await load();
};

onMounted(load);
</script>

<template>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Мои рецепты</h1>
        <router-link to="/recipes/new" class="btn btn-primary">Создать рецепт</router-link>
    </div>

    <div class="row g-3">
        <div v-for="r in recipes" :key="r.id" class="col-md-6 col-lg-4">
            <article class="card h-100">
                <img
                    v-if="r.default_image || r.image"
                    :src="r.default_image || r.image"
                    class="card-img-top"
                    :alt="r.title"
                />
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">{{ r.title }}</h5>
                    <p class="card-text text-muted small">{{ r.description }}</p>
                    <p class="small">🔥 {{ r.calories }} ккал</p>
                    <div class="d-flex gap-2 mt-auto">
                        <router-link :to="`/recipes/${r.id}/edit`" class="btn btn-secondary btn-sm"
                            >Редактировать</router-link
                        >
                        <button type="button" class="btn btn-danger btn-sm" @click="pending = r">Удалить</button>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <!-- Модальное подтверждение удаления -->
    <div
        v-if="pending"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
        style="background: rgba(0, 0, 0, 0.5); z-index: 1050"
    >
        <div class="card card-body" style="max-width: 400px">
            <p class="mb-3">Удалить рецепт «{{ pending.title }}»? Действие необратимо.</p>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary" @click="pending = null">Отмена</button>
                <button type="button" class="btn btn-danger" @click="remove">Удалить</button>
            </div>
        </div>
    </div>
</template>
