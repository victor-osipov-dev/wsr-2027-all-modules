<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import api, { asList } from '../api';

const ingredients = ref([]);
const selected = ref([]);
const recipes = ref([]);
const dragOver = ref(false);

// Ингредиенты в алфавитном порядке: доступные и выбранные.
const sorted = computed(() => [...ingredients.value].sort((a, b) => a.title.localeCompare(b.title, 'ru')));
const chosen = computed(() => sorted.value.filter((i) => selected.value.includes(i.id)));
const available = computed(() => sorted.value.filter((i) => !selected.value.includes(i.id)));

// Рецепты, содержащие ВСЕ выбранные ингредиенты; без выбора — весь список.
const load = async () => {
    try {
        const { data } = await api.get(selected.value.length ? '/recipes/search' : '/recipes', {
            params: { ingredients: selected.value, per_page: 50 },
        });
        recipes.value = asList(data);
    } catch (e) {
        const { data } = await api.get('/recipes', { params: { ingredients: selected.value, per_page: 50 } });
        recipes.value = asList(data);
    }
};

// HTML5 drag-and-drop: id ингредиента переносится в область «Выбрано».
const dragStart = (id, event) => event.dataTransfer.setData('text/plain', String(id));
const drop = (event) => {
    dragOver.value = false;
    const id = Number(event.dataTransfer.getData('text/plain'));
    if (id && !selected.value.includes(id)) selected.value.push(id);
};

// Изменение набора ингредиентов повторно запускает поиск.
watch(() => selected.value.slice().sort().join(','), load);

onMounted(async () => {
    const { data } = await api.get('/ingredients');
    ingredients.value = Array.isArray(data) ? data : data.data || [];
    await load();
});
</script>

<template>
    <h1 class="h3 mb-2">Конструктор рецептов</h1>
    <p class="text-muted">
        Перетащите ингредиенты в область «Выбрано» — покажем рецепты со всеми выбранными ингредиентами.
    </p>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <section class="card card-body h-100">
                <h2 class="h5">Ингредиенты (А–Я)</h2>
                <ul class="list-unstyled mb-0 d-flex flex-wrap gap-2">
                    <li
                        v-for="i in available"
                        :key="i.id"
                        class="badge bg-secondary"
                        style="cursor: grab"
                        draggable="true"
                        @dragstart="dragStart(i.id, $event)"
                        @click="selected.push(Number(i.id))"
                    >
                        {{ i.title }}
                    </li>
                </ul>
            </section>
        </div>
        <div class="col-md-6">
            <section
                class="card card-body h-100"
                :class="{ 'border-primary': dragOver }"
                @dragover.prevent="dragOver = true"
                @dragleave="dragOver = false"
                @drop.prevent="drop"
            >
                <h2 class="h5">Выбрано</h2>
                <ul class="list-unstyled mb-0 d-flex flex-wrap gap-2">
                    <li v-for="i in chosen" :key="i.id" class="badge bg-primary">
                        {{ i.title }}
                        <button
                            type="button"
                            class="btn-close btn-close-white ms-1 align-middle"
                            aria-label="Убрать"
                            @click="selected = selected.filter((id) => id !== i.id)"
                        ></button>
                    </li>
                </ul>
                <p v-if="!chosen.length" class="text-muted mb-0">Перетащите сюда ингредиенты</p>
            </section>
        </div>
    </div>

    <h2 class="h5">Найдено рецептов: {{ recipes.length }}</h2>
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
                <div class="card-body">
                    <h5 class="card-title">{{ r.title }}</h5>
                    <router-link :to="`/recipes/${r.id}`" class="btn btn-outline-primary btn-sm">Подробнее</router-link>
                </div>
            </article>
        </div>
    </div>
</template>
