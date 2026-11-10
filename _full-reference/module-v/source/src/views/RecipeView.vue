<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import LoaderSpinner from '../components/LoaderSpinner.vue';
import EmptyState from '../components/EmptyState.vue';
import { recipesApi } from '../api/recipes';
import { parseApiError } from '../api/errors';
import { pickImage, pickCalories, pickTimeLabel, pickIngredients, ingredientTitle, ingredientQuantity, ingredientUnit, pickOwnerId } from '../utils/recipe';
import { formatDate, formatDateTime, formatPrice } from '../utils/format';
import { useAuth } from '../stores/auth';

const route = useRoute();
const router = useRouter();
const { currentUserId } = useAuth();

const recipe = ref(null);
const loading = ref(false);
const errorMessage = ref('');
const imageFailed = ref(false);

const image = computed(() => pickImage(recipe.value));
const calories = computed(() => pickCalories(recipe.value));
const timeLabel = computed(() => pickTimeLabel(recipe.value));
const ingredients = computed(() => pickIngredients(recipe.value));
const steps = computed(() => (Array.isArray(recipe.value && recipe.value.steps) ? recipe.value.steps : []));
const createdLabel = computed(() => formatDateTime(recipe.value && recipe.value.created_at));

/** Разрешает редактирование только владельцу. */
const canEdit = computed(() => {
  if (!recipe.value) return false;
  const ownerId = pickOwnerId(recipe.value);
  if (ownerId === undefined || currentUserId.value === null || currentUserId.value === undefined) return true;
  return String(ownerId) === String(currentUserId.value);
});

const load = async () => {
  loading.value = true;
  errorMessage.value = '';
  recipe.value = null;
  imageFailed.value = false;
  try {
    recipe.value = await recipesApi.detail(route.params.id);
  } catch (error) {
    const parsed = parseApiError(error);
    errorMessage.value = parsed.status === 404 ? 'Рецепт не найден.' : parsed.message;
  } finally {
    loading.value = false;
  }
};

onMounted(load);
watch(() => route.params.id, () => {
  if (route.name === 'recipe') load();
});
</script>

<template>
  <div class="container page-container">
    <nav class="breadcrumbs" aria-label="Навигация">
      <router-link to="/">Рецепты</router-link>
      <span aria-hidden="true">/</span>
      <span>{{ recipe ? recipe.title : 'Рецепт' }}</span>
    </nav>

    <LoaderSpinner v-if="loading" text="Загружаем рецепт…" />

    <EmptyState
      v-else-if="errorMessage"
      icon="⚠️"
      title="Рецепт недоступен"
      :text="errorMessage"
    >
      <button type="button" class="btn btn-primary" @click="router.push({ name: 'recipes' })">К списку рецептов</button>
    </EmptyState>

    <article v-else-if="recipe" class="recipe-view">
      <div class="row">
        <div class="col-lg-7 col-md-12 mb-4">
          <div class="recipe-view__media">
            <img v-if="image && !imageFailed" :src="image" :alt="recipe.title" class="recipe-view__img" @error="imageFailed = true" />
            <span v-else class="recipe-view__placeholder" aria-hidden="true">🍲</span>
          </div>
        </div>

        <div class="col-lg-5 col-md-12">
          <h1 class="page-title">{{ recipe.title }}</h1>

          <div class="recipe-view__stats">
            <div class="stat">
              <span class="stat__label">Время приготовления</span>
              <span class="stat__value">{{ timeLabel }}</span>
            </div>
            <div class="stat">
              <span class="stat__label">Калорийность</span>
              <span class="stat__value">{{ calories ? `${calories} ккал` : 'не указана' }}</span>
            </div>
            <div v-if="recipe.price !== undefined && recipe.price !== null" class="stat">
              <span class="stat__label">Стоимость</span>
              <span class="stat__value">{{ formatPrice(recipe.price) }}</span>
            </div>
            <div v-if="recipe.difficulty" class="stat">
              <span class="stat__label">Сложность</span>
              <span class="stat__value">{{ recipe.difficulty }}</span>
            </div>
          </div>

          <p v-if="recipe.category && recipe.category.name" class="recipe-view__category">
            Категория: <strong>{{ recipe.category.name }}</strong>
          </p>

          <p class="recipe-view__date">Добавлен: {{ createdLabel }}</p>

          <div class="recipe-view__actions">
            <router-link v-if="canEdit" :to="{ name: 'recipe-edit', params: { id: recipe.id } }" class="btn btn-secondary">
              Редактировать
            </router-link>
            <router-link to="/" class="btn btn-outline-primary">К списку</router-link>
          </div>
        </div>
      </div>

      <section class="panel recipe-view__section">
        <h2 class="section-title">Описание</h2>
        <p class="recipe-view__description">{{ recipe.description }}</p>
      </section>

      <section class="panel recipe-view__section">
        <h2 class="section-title">Ингредиенты</h2>
        <ul v-if="ingredients.length" class="ingredients-list">
          <li v-for="(ingredient, index) in ingredients" :key="ingredient.id || index" class="ingredient-row">
            <span class="ingredient-row__title">{{ ingredientTitle(ingredient) }}</span>
            <span class="ingredient-row__qty">
              {{ ingredientQuantity(ingredient) }} {{ ingredientUnit(ingredient) }}
            </span>
          </li>
        </ul>
        <p v-else class="text-muted">Ингредиенты не указаны.</p>
      </section>

      <section v-if="steps.length" class="panel recipe-view__section">
        <h2 class="section-title">Шаги приготовления</h2>
        <ol class="steps-list">
          <li v-for="(step, index) in steps" :key="step.id || index" class="step-row">
            <strong>{{ step.title || `Шаг ${index + 1}` }}</strong>
            <p>{{ step.description }}</p>
          </li>
        </ol>
      </section>
    </article>
  </div>
</template>
