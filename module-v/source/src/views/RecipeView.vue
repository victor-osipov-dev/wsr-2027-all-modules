<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../api';

const route = useRoute();
const recipe = ref(null);
const message = ref('');

// Просмотр одного рецепта: название, описание, калорийность, время, изображение, ингредиенты.
onMounted(async () => {
    try {
        const { data } = await api.get(`/recipes/${route.params.id}`);
        recipe.value = data;
    } catch (e) {
        message.value = errorMessage(e, 'Рецепт не найден');
    }
});
</script>

<template>
    <div v-if="message" class="alert alert-danger" role="alert">{{ message }}</div>

    <article v-else-if="recipe" class="row g-4">
        <div class="col-md-6">
            <img
                v-if="recipe.default_image || recipe.image"
                :src="recipe.default_image || recipe.image"
                class="img-fluid rounded"
                :alt="recipe.title"
            />
        </div>
        <div class="col-md-6">
            <h1 class="h3">{{ recipe.title }}</h1>
            <p class="mb-2">
                🔥 {{ recipe.calories }} ккал · ⏱ {{ recipe.formatted_time || recipe.cooking_time + ' мин' }}
            </p>
            <p>{{ recipe.description }}</p>

            <h2 class="h5 mt-4">Ингредиенты</h2>
            <ul class="list-group">
                <li v-for="i in recipe.ingredients || []" :key="i.id" class="list-group-item">
                    {{ i.title
                    }}<span v-if="i.pivot && i.pivot.quantity"> — {{ i.pivot.quantity }} {{ i.pivot.unit }}</span>
                </li>
            </ul>

            <router-link to="/" class="btn btn-outline-primary mt-3">К списку рецептов</router-link>
        </div>
    </article>
</template>
