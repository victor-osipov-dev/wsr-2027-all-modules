<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { fieldErrors, errorMessage } from '../api';

const route = useRoute();
const router = useRouter();
const id = computed(() => route.params.id);
const form = ref({ title: '', description: '', calories: '', cooking_time: '' });
const errors = ref({});
const message = ref('');
const file = ref(null);
const ingredients = ref([]);
const selected = ref([]);

// Справочник ингредиентов (алфавитный порядок); при редактировании — данные рецепта.
onMounted(async () => {
    const { data } = await api.get('/ingredients');
    ingredients.value = (Array.isArray(data) ? data : data.data || []).sort((a, b) =>
        a.title.localeCompare(b.title, 'ru')
    );
    if (id.value) {
        const { data: r } = await api.get(`/recipes/${id.value}`);
        form.value = { title: r.title, description: r.description, calories: r.calories, cooking_time: r.cooking_time };
        selected.value = (r.ingredients || []).map((i) => i.id);
    }
});

// Отправка multipart/form-data: создание POST /recipes, правка POST + _method=PUT.
const submit = async () => {
    errors.value = {};
    message.value = '';
    const data = new FormData();
    Object.entries(form.value).forEach(([key, value]) => data.append(key, value));
    selected.value.forEach((item) => data.append('ingredients[]', item));
    if (file.value) data.append('image', file.value);
    try {
        if (id.value) {
            data.append('_method', 'PUT');
            await api.post(`/recipes/${id.value}`, data);
        } else {
            await api.post('/recipes', data);
        }
        router.push('/my');
    } catch (e) {
        errors.value = fieldErrors(e);
        message.value = errorMessage(e, 'Проверьте заполнение полей');
    }
};
</script>

<template>
    <h1 class="h3 mb-3">{{ id ? 'Редактирование рецепта' : 'Новый рецепт' }}</h1>
    <div v-if="message" class="alert alert-danger" role="alert">{{ message }}</div>

    <form class="card card-body" novalidate @submit.prevent="submit">
        <label class="form-label" for="f-title">Название</label>
        <input
            id="f-title"
            v-model="form.title"
            type="text"
            required
            class="form-control"
            :class="{ 'is-invalid': errors.title }"
        />
        <div v-if="errors.title" class="invalid-feedback">{{ errors.title }}</div>

        <label class="form-label mt-3" for="f-description">Описание</label>
        <textarea
            id="f-description"
            v-model="form.description"
            required
            rows="4"
            class="form-control"
            :class="{ 'is-invalid': errors.description }"
        ></textarea>
        <div v-if="errors.description" class="invalid-feedback">{{ errors.description }}</div>

        <div class="row">
            <div class="col-md-6">
                <label class="form-label mt-3" for="f-calories">Калорийность, ккал</label>
                <input
                    id="f-calories"
                    v-model="form.calories"
                    type="number"
                    min="0"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.calories }"
                />
                <div v-if="errors.calories" class="invalid-feedback">{{ errors.calories }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label mt-3" for="f-time">Время, минут</label>
                <input
                    id="f-time"
                    v-model="form.cooking_time"
                    type="number"
                    min="1"
                    required
                    class="form-control"
                    :class="{ 'is-invalid': errors.cooking_time }"
                />
                <div v-if="errors.cooking_time" class="invalid-feedback">{{ errors.cooking_time }}</div>
            </div>
        </div>

        <label class="form-label mt-3" for="f-ingredients">Ингредиенты (Ctrl — несколько)</label>
        <select
            id="f-ingredients"
            v-model="selected"
            multiple
            size="6"
            class="form-select"
            :class="{ 'is-invalid': errors.ingredients }"
        >
            <option v-for="i in ingredients" :key="i.id" :value="i.id">{{ i.title }}</option>
        </select>
        <div v-if="errors.ingredients" class="invalid-feedback">{{ errors.ingredients }}</div>

        <label class="form-label mt-3" for="f-image">Изображение</label>
        <input
            id="f-image"
            type="file"
            accept="image/*"
            class="form-control"
            :class="{ 'is-invalid': errors.image }"
            @change="file = $event.target.files[0]"
        />
        <div v-if="errors.image" class="invalid-feedback">{{ errors.image }}</div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">{{ id ? 'Сохранить' : 'Опубликовать рецепт' }}</button>
            <router-link to="/my" class="btn btn-outline-secondary">Отмена</router-link>
        </div>
    </form>
</template>
