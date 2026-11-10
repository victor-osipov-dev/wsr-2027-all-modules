<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import RecipeCard from '../components/RecipeCard.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import LoaderSpinner from '../components/LoaderSpinner.vue';
import EmptyState from '../components/EmptyState.vue';
import { recipesApi, normalizeList } from '../api/recipes';
import { parseApiError } from '../api/errors';
import { useNotify } from '../composables/useNotify';

const router = useRouter();
const { push } = useNotify();

const recipes = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const deleting = ref(false);
const modalVisible = ref(false);
const pendingRecipe = ref(null);

/** Загружает список собственных рецептов пользователя. */
const load = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const payload = await recipesApi.my();
    recipes.value = normalizeList(payload).items;
  } catch (error) {
    errorMessage.value = parseApiError(error).message;
    recipes.value = [];
  } finally {
    loading.value = false;
  }
};

const goEdit = (recipe) => {
  router.push({ name: 'recipe-edit', params: { id: recipe.id } });
};

/** Открывает модальное окно подтверждения удаления. */
const askRemove = (recipe) => {
  pendingRecipe.value = recipe;
  modalVisible.value = true;
};

const cancelRemove = () => {
  modalVisible.value = false;
  pendingRecipe.value = null;
};

/** Подтверждённое удаление: после успеха рецепт исчезает из списка. */
const confirmRemove = async () => {
  if (!pendingRecipe.value) return;
  deleting.value = true;
  try {
    await recipesApi.remove(pendingRecipe.value.id);
    recipes.value = recipes.value.filter((item) => String(item.id) !== String(pendingRecipe.value.id));
    push({ variant: 'success', title: 'Рецепт удалён', text: `«${pendingRecipe.value.title}» удалён из списка.` });
    modalVisible.value = false;
  } catch (error) {
    push({ variant: 'danger', title: 'Не удалось удалить', text: parseApiError(error).message, timeout: 8000 });
  } finally {
    deleting.value = false;
    pendingRecipe.value = null;
  }
};

onMounted(load);
</script>

<template>
  <div class="container page-container">
    <header class="page-head page-head--row">
      <div>
        <h1 class="page-title">Мои рецепты</h1>
        <p class="page-subtitle">Здесь собраны только ваши публикации.</p>
      </div>
      <router-link to="/create" class="btn btn-primary">Создать рецепт</router-link>
    </header>

    <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>

    <LoaderSpinner v-if="loading && !recipes.length" text="Загружаем ваши рецепты…" />

    <EmptyState
      v-else-if="!recipes.length && !loading"
      title="У вас пока нет рецептов"
      text="Добавьте первый рецепт, чтобы он появился в этом списке."
    >
      <router-link to="/create" class="btn btn-primary">Создать рецепт</router-link>
    </EmptyState>

    <div v-else class="row recipe-grid">
      <div v-for="recipe in recipes" :key="recipe.id" class="col-lg-4 col-md-6 col-sm-6 mb-4">
        <RecipeCard
          :recipe="recipe"
          show-actions
          show-date
          @edit="goEdit"
          @remove="askRemove"
        />
      </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <ConfirmModal
      :visible="modalVisible"
      title="Удаление рецепта"
      :text="pendingRecipe ? `Удалить рецепт «${pendingRecipe.title}»? Действие необратимо.` : ''"
      confirm-text="Удалить"
      :loading="deleting"
      @confirm="confirmRemove"
      @cancel="cancelRemove"
    />
  </div>
</template>
