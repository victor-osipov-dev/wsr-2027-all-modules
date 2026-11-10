<script setup>
import { computed, ref, watch } from 'vue';
import { pickImage, pickCalories, pickTimeLabel } from '../utils/recipe';
import { formatDate, truncate } from '../utils/format';

const props = defineProps({
  recipe: { type: Object, required: true },
  showActions: { type: Boolean, default: false },
  showDate: { type: Boolean, default: false },
});

defineEmits(['edit', 'remove']);

const imageFailed = ref(false);
const image = computed(() => pickImage(props.recipe));
const calories = computed(() => pickCalories(props.recipe));
const timeLabel = computed(() => pickTimeLabel(props.recipe));
const description = computed(() => truncate(props.recipe.description, 130));
const createdAt = computed(() => formatDate(props.recipe.created_at));

const caloriesText = computed(() =>
  calories.value === undefined || calories.value === null || calories.value === '' ? 'н/д' : `${calories.value} ккал`,
);

// Сбрасываем состояние ошибки изображения при смене рецепта.
watch(image, () => {
  imageFailed.value = false;
});
</script>

<template>
  <article class="recipe-card h-100">
    <router-link :to="{ name: 'recipe', params: { id: recipe.id } }" class="recipe-card__media">
      <img
        v-if="image && !imageFailed"
        :src="image"
        :alt="recipe.title"
        loading="lazy"
        class="recipe-card__img"
        @error="imageFailed = true"
      />
      <span v-else class="recipe-card__placeholder" aria-hidden="true">🍲</span>
    </router-link>

    <div class="recipe-card__body">
      <div class="recipe-card__meta">
        <span class="badge bg-prim">{{ timeLabel }}</span>
        <span class="badge bg-soft">🔥 {{ caloriesText }}</span>
        <span v-if="showDate && createdAt !== '—'" class="badge bg-soft">{{ createdAt }}</span>
      </div>

      <h3 class="recipe-card__title">
        <router-link :to="{ name: 'recipe', params: { id: recipe.id } }">{{ recipe.title }}</router-link>
      </h3>

      <p class="recipe-card__text">{{ description }}</p>

      <div class="recipe-card__footer">
        <router-link :to="{ name: 'recipe', params: { id: recipe.id } }" class="btn btn-outline-primary btn-sm">
          Подробнее
        </router-link>

        <div v-if="showActions" class="recipe-card__actions">
          <button type="button" class="btn btn-secondary btn-sm" @click="$emit('edit', recipe)">Редактировать</button>
          <button type="button" class="btn btn-danger btn-sm" @click="$emit('remove', recipe)">Удалить</button>
        </div>
      </div>
    </div>
  </article>
</template>
