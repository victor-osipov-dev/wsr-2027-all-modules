<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import RecipeCard from '../components/RecipeCard.vue';
import LoaderSpinner from '../components/LoaderSpinner.vue';
import EmptyState from '../components/EmptyState.vue';
import { recipesApi, ingredientsApi, normalizeList } from '../api/recipes';
import { parseApiError } from '../api/errors';
import { ingredientId, ingredientTitle, ingredientUnit } from '../utils/recipe';

const ingredients = ref([]);
const selectedIds = ref([]);
const recipes = ref([]);
const loading = ref(false);
const loadingCatalog = ref(false);
const errorMessage = ref('');
const dragOverZone = ref('');

// Текущий перетаскиваемый ингредиент.
const dragging = ref(null);
// Счётчик запросов для отсечения устаревших ответов.
let requestToken = 0;

/** Ингредиенты в алфавитном порядке (критерий: алфавитный порядок). */
const sortedIngredients = computed(() =>
  [...ingredients.value].sort((a, b) => String(ingredientTitle(a)).localeCompare(String(ingredientTitle(b)), 'ru')),
);

/** Доступные (ещё не выбранные) ингредиенты. */
const availableIngredients = computed(() =>
  sortedIngredients.value.filter((item) => !selectedIds.value.includes(String(ingredientId(item)))),
);

/** Выбранные ингредиенты в алфавитном порядке. */
const selectedIngredients = computed(() =>
  sortedIngredients.value.filter((item) => selectedIds.value.includes(String(ingredientId(item)))),
);

const selectionKey = computed(() => selectedIds.value.slice().sort().join(','));

/** Загружает справочник ингредиентов. */
const loadIngredients = async () => {
  loadingCatalog.value = true;
  try {
    ingredients.value = await ingredientsApi.list();
  } catch (error) {
    errorMessage.value = parseApiError(error).message;
    ingredients.value = [];
  } finally {
    loadingCatalog.value = false;
  }
};

/**
 * Поиск рецептов: если ингредиенты не выбраны — показываем все рецепты,
 * иначе запрашиваем рецепты, содержащие ВСЕ выбранные ингредиенты.
 */
const runSearch = async () => {
  const token = ++requestToken;
  loading.value = true;
  errorMessage.value = '';
  try {
    const payload = selectedIds.value.length
      ? await recipesApi.searchByIngredients(selectedIds.value, { per_page: 50 })
      : await recipesApi.list({ sort: 'created_at_desc', per_page: 50 });
    if (token !== requestToken) return;
    recipes.value = normalizeList(payload).items;
  } catch (error) {
    if (token !== requestToken) return;
    errorMessage.value = parseApiError(error).message;
    recipes.value = [];
  } finally {
    if (token === requestToken) loading.value = false;
  }
};

const addIngredient = (id) => {
  const key = String(id);
  if (!selectedIds.value.includes(key)) {
    selectedIds.value = [...selectedIds.value, key];
  }
};

const removeIngredient = (id) => {
  const key = String(id);
  selectedIds.value = selectedIds.value.filter((item) => item !== key);
};

/** Полная очистка выбранных ингредиентов. */
const clearSelection = () => {
  selectedIds.value = [];
};

// --- Drag & Drop (HTML5) ---
const onDragStart = (id, event) => {
  dragging.value = String(id);
  if (event && event.dataTransfer) {
    event.dataTransfer.setData('text/plain', String(id));
    event.dataTransfer.effectAllowed = 'move';
  }
};

const onDropToSelected = (event) => {
  dragOverZone.value = '';
  const id = (event && event.dataTransfer && event.dataTransfer.getData('text/plain')) || dragging.value;
  if (id) addIngredient(id);
  dragging.value = null;
};

const onDropToAvailable = (event) => {
  dragOverZone.value = '';
  const id = (event && event.dataTransfer && event.dataTransfer.getData('text/plain')) || dragging.value;
  if (id) removeIngredient(id);
  dragging.value = null;
};

const setDragZone = (zone) => {
  dragOverZone.value = zone;
};

// Изменение выбора ингредиентов повторно запускает поиск.
watch(selectionKey, () => {
  runSearch();
});

onMounted(async () => {
  await loadIngredients();
  await runSearch();
});
</script>

<template>
  <div class="container page-container">
    <header class="page-head">
      <div>
        <h1 class="page-title">Конструктор рецептов</h1>
        <p class="page-subtitle">
          Перетащите ингредиенты в область «Выбрано» — найдём рецепты, в которых есть все выбранные ингредиенты.
        </p>
      </div>
    </header>

    <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>

    <LoaderSpinner v-if="loadingCatalog" text="Загружаем ингредиенты…" />

    <div v-else class="row constructor">
      <!-- Доступные ингредиенты -->
      <div class="col-lg-4 col-md-6 mb-4">
        <section
          class="panel constructor__zone"
          :class="{ 'constructor__zone--over': dragOverZone === 'available' }"
          @dragover.prevent="setDragZone('available')"
          @dragleave="setDragZone('')"
          @drop.prevent="onDropToAvailable"
        >
          <h2 class="panel-title-sm">Ингредиенты (A–Я)</h2>
          <p class="zone-hint">Перетащите в область справа. Сюда же можно вернуть лишний ингредиент.</p>

          <ul v-if="availableIngredients.length" class="ingredient-pool">
            <li
              v-for="item in availableIngredients"
              :key="ingredientId(item)"
              class="ingredient-pill"
              draggable="true"
              @dragstart="onDragStart(ingredientId(item), $event)"
              @dragend="dragging = null"
              @click="addIngredient(ingredientId(item))"
            >
              <span>{{ ingredientTitle(item) }}</span>
              <small v-if="ingredientUnit(item)" class="ingredient-pill__unit">{{ ingredientUnit(item) }}</small>
            </li>
          </ul>
          <p v-else class="text-muted">Все ингредиенты выбраны.</p>
        </section>
      </div>

      <!-- Выбранные ингредиенты (drop-zone) -->
      <div class="col-lg-4 col-md-6 mb-4">
        <section
          class="panel constructor__zone constructor__zone--target"
          :class="{ 'constructor__zone--over': dragOverZone === 'selected' }"
          @dragover.prevent="setDragZone('selected')"
          @dragleave="setDragZone('')"
          @drop.prevent="onDropToSelected"
        >
          <h2 class="panel-title-sm">Выбранные ингредиенты</h2>
          <p class="zone-hint">Перетащите сюда ингредиенты или кликните по ним слева.</p>

          <div class="drop-area" :class="{ 'drop-area--empty': !selectedIngredients.length }">
            <ul v-if="selectedIngredients.length" class="ingredient-selected">
              <li
                v-for="item in selectedIngredients"
                :key="ingredientId(item)"
                class="selected-chip"
                draggable="true"
                @dragstart="onDragStart(ingredientId(item), $event)"
                @dragend="dragging = null"
              >
                <span>{{ ingredientTitle(item) }}</span>
                <button type="button" class="selected-chip__remove" aria-label="Убрать" @click="removeIngredient(ingredientId(item))">
                  ×
                </button>
              </li>
            </ul>
            <p v-else class="drop-area__placeholder">Перетащите сюда ингредиенты</p>
          </div>

          <button
            v-if="selectedIngredients.length"
            type="button"
            class="btn btn-outline-primary w-100 mt-3"
            @click="clearSelection"
          >
            Очистить выбор
          </button>
        </section>
      </div>

      <!-- Памятка -->
      <div class="col-lg-4 col-md-12 mb-4">
        <section class="panel">
          <h2 class="panel-title-sm">Как это работает</h2>
          <ol class="howto-list">
            <li>Выберите ингредиенты методом перетаскивания.</li>
            <li>Список рецептов обновляется автоматически.</li>
            <li>В каждом результате есть все выбранные ингредиенты.</li>
            <li>Нажмите «Подробнее», чтобы открыть рецепт.</li>
          </ol>
        </section>
      </div>
    </div>

    <section class="constructor__results">
      <h2 class="section-title">
        {{ selectedIds.length ? `Найдено рецептов: ${recipes.length}` : 'Все рецепты' }}
      </h2>

      <LoaderSpinner v-if="loading" text="Ищем рецепты…" />

      <EmptyState
        v-else-if="!recipes.length"
        title="Рецепты не найдены"
        text="Попробуйте изменить набор ингредиентов."
      />

      <div v-else class="row recipe-grid">
        <div v-for="recipe in recipes" :key="recipe.id" class="col-lg-4 col-md-6 col-sm-6 mb-4">
          <RecipeCard :recipe="recipe" />
        </div>
      </div>
    </section>
  </div>
</template>
