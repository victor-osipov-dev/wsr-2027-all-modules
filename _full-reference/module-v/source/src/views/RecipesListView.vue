<script setup>
import { ref, onMounted } from 'vue';
import RecipeCard from '../components/RecipeCard.vue';
import SortControl from '../components/SortControl.vue';
import PaginationControls from '../components/PaginationControls.vue';
import LoaderSpinner from '../components/LoaderSpinner.vue';
import EmptyState from '../components/EmptyState.vue';
import { recipesApi, normalizeList } from '../api/recipes';
import { parseApiError } from '../api/errors';
import { config } from '../config';

const recipes = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 12 });
const sort = ref(config.sort.createdDesc);
const currentPage = ref(1);
const perPage = 12;
const loading = ref(false);
const errorMessage = ref('');
const isDemo = ref(false);

/** Загружает список рецептов с сервера с учётом сортировки и пагинации. */
const load = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const payload = await recipesApi.list({
      sort: sort.value,
      page: currentPage.value,
      per_page: perPage,
    });
    const normalized = normalizeList(payload);
    recipes.value = normalized.items;
    meta.value = normalized.meta;
    isDemo.value = Boolean(payload && payload.demo);
  } catch (error) {
    const parsed = parseApiError(error);
    errorMessage.value = parsed.message;
    recipes.value = [];
  } finally {
    loading.value = false;
  }
};

/** Смена сортировки перезапрашивает данные с сервера. */
const changeSort = async (value) => {
  sort.value = value;
  currentPage.value = 1;
  await load();
};

/** Переход на другую страницу пагинации. */
const changePage = async (page) => {
  currentPage.value = page;
  await load();
};

onMounted(load);
</script>

<template>
  <div class="container page-container">
    <div class="row">
      <!-- Боковая панель: сортировка и подсказки -->
      <aside class="col-lg-3 col-md-4 mb-4">
        <div class="panel panel-side sticky-side">
          <h2 class="panel-title-sm">Сортировка</h2>
          <SortControl id="sort-list" v-model="sort" label="" @update:modelValue="changeSort" />

          <ul class="side-list">
            <li><span class="side-dot"></span> По дате добавления</li>
            <li><span class="side-dot"></span> По времени приготовления</li>
            <li><span class="side-dot"></span> По калорийности</li>
          </ul>

          <button type="button" class="btn btn-outline-primary w-100" :disabled="loading" @click="load">
            {{ loading ? 'Обновление…' : 'Обновить список' }}
          </button>

          <p class="side-note">Новые рецепты появляются в списке автоматически — следите за уведомлениями.</p>
        </div>
      </aside>

      <!-- Основной контент -->
      <div class="col-lg-9 col-md-8">
        <header class="page-head">
          <div>
            <h1 class="page-title">Рецепты народов мира</h1>
            <p class="page-subtitle">
              Всего рецептов: <strong>{{ meta.total }}</strong>
              <span v-if="isDemo" class="text-muted"> (демонстрационный режим)</span>
            </p>
          </div>
        </header>

        <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>

        <LoaderSpinner v-if="loading && !recipes.length" text="Загружаем рецепты…" />

        <EmptyState
          v-else-if="!recipes.length && !loading"
          title="Пока нет рецептов"
          text="Станьте первым — добавьте свой рецепт на платформу."
        >
          <router-link to="/create" class="btn btn-primary">Добавить рецепт</router-link>
        </EmptyState>

        <div v-else class="row recipe-grid">
          <div v-for="recipe in recipes" :key="recipe.id" class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <RecipeCard :recipe="recipe" />
          </div>
        </div>

        <PaginationControls
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          @change="changePage"
        />
      </div>
    </div>
  </div>
</template>
