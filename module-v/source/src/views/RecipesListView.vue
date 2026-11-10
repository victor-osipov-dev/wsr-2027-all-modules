<script setup>
import { ref, onMounted } from 'vue';
import api, { asList, errorMessage } from '../api';

const recipes = ref([]);
const sort = ref('created_at_desc');
const message = ref('');

// Загрузка списка рецептов; выбранная сортировка уходит в query-параметр sort.
const load = async () => {
    message.value = '';
    try {
        const { data } = await api.get('/recipes', { params: { sort: sort.value } });
        recipes.value = asList(data);
    } catch (e) {
        message.value = errorMessage(e, 'Не удалось загрузить рецепты');
    }
};

onMounted(load);
</script>

<template>
    <h1 class="h3 mb-3">Рецепты народов мира</h1>

    <div class="row mb-3">
        <div class="col-md-5">
            <label class="form-label" for="sort">Сортировка</label>
            <select id="sort" v-model="sort" class="form-select" @change="load">
                <option value="created_at_desc">По дате создания (сначала новые)</option>
                <option value="created_at_asc">По дате создания (сначала старые)</option>
                <option value="cooking_time_asc">По времени приготовления (возрастание)</option>
                <option value="cooking_time_desc">По времени приготовления (убывание)</option>
                <option value="calories_asc">По калорийности (возрастание)</option>
                <option value="calories_desc">По калорийности (убывание)</option>
            </select>
        </div>
    </div>

    <div v-if="message" class="alert alert-danger" role="alert">{{ message }}</div>

    <div class="row g-3">
        <div v-for="r in recipes" :key="r.id" class="col-md-6 col-lg-4">
            <article class="card h-100">
                <router-link :to="`/recipes/${r.id}`">
                    <img
                        v-if="r.default_image || r.image"
                        :src="r.default_image || r.image"
                        class="card-img-top"
                        :alt="r.title"
                    />
                </router-link>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">{{ r.title }}</h5>
                    <p class="card-text text-muted small">{{ r.description }}</p>
                    <p class="small mb-2">
                        🔥 {{ r.calories }} ккал · ⏱ {{ r.formatted_time || r.cooking_time + ' мин' }}
                    </p>
                    <router-link :to="`/recipes/${r.id}`" class="btn btn-outline-primary btn-sm mt-auto"
                        >Подробнее</router-link
                    >
                </div>
            </article>
        </div>
    </div>
</template>
