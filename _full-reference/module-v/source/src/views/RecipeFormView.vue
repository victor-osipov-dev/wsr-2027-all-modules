<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseInput from '../components/BaseInput.vue';
import BaseTextarea from '../components/BaseTextarea.vue';
import BaseSelect from '../components/BaseSelect.vue';
import LoaderSpinner from '../components/LoaderSpinner.vue';
import { recipesApi } from '../api/recipes';
import { ingredientsApi } from '../api/ingredients';
import { parseApiError } from '../api/errors';
import { useForm } from '../composables/useForm';
import { useNotify } from '../composables/useNotify';
import { useAuth } from '../stores/auth';
import { pickCalories, pickCookingTime, pickIngredients, ingredientId, ingredientTitle, ingredientQuantity, pickOwnerId } from '../utils/recipe';

const route = useRoute();
const router = useRouter();
const { push } = useNotify();
const { currentUserId } = useAuth();

const isEdit = computed(() => Boolean(route.params.id));
const title = computed(() => (isEdit.value ? 'Редактирование рецепта' : 'Новый рецепт'));

const loading = ref(false);
const saving = ref(false);
const generalError = ref('');
const ingredientsCatalog = ref([]);
const selectedIngredients = ref([]);
const ingredientToAdd = ref('');
const imageFile = ref(null);
const imagePreview = ref('');
const imageInputKey = ref(0);

const { form, errors, setErrors, setError, clearErrors, fill } = useForm({
  title: '',
  description: '',
  calories: '',
  cooking_time: '',
});

const ingredientOptions = computed(() =>
  ingredientsCatalog.value.map((item) => ({
    value: String(ingredientId(item)),
    label: `${ingredientTitle(item)}${item.unit ? ` (${item.unit})` : ''}`,
  })),
);

const availableToAdd = computed(() =>
  ingredientOptions.value.filter(
    (option) => !selectedIngredients.value.some((item) => String(item.id) === String(option.value)),
  ),
);

const ACCEPTED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
const MAX_SIZE = 2 * 1024 * 1024;

/** Проверка изображения перед отправкой. */
const validateImage = (file) => {
  if (!file) return 'Загрузите изображение';
  if (!ACCEPTED_TYPES.includes(file.type)) return 'Допустимые форматы: jpg, jpeg, png, webp';
  if (file.size > MAX_SIZE) return 'Размер файла не должен превышать 2 МБ';
  return '';
};

const onImageChange = (event) => {
  const file = event.target.files && event.target.files[0];
  if (!file) {
    imageFile.value = null;
    imagePreview.value = '';
    return;
  }
  const error = validateImage(file);
  if (error) {
    setError('image', error);
    imageFile.value = null;
    imagePreview.value = '';
    event.target.value = '';
    return;
  }
  setError('image', '');
  imageFile.value = file;
  imagePreview.value = URL.createObjectURL(file);
};

const addIngredient = () => {
  if (!ingredientToAdd.value) return;
  const source = ingredientsCatalog.value.find((item) => String(ingredientId(item)) === String(ingredientToAdd.value));
  if (!source) return;
  selectedIngredients.value.push({
    id: ingredientId(source),
    title: ingredientTitle(source),
    unit: source.unit || '',
    quantity: '',
  });
  ingredientToAdd.value = '';
  setError('ingredients', '');
};

const removeIngredient = (id) => {
  selectedIngredients.value = selectedIngredients.value.filter((item) => String(item.id) !== String(id));
};

/** Клиентская проверка обязательных полей. */
const validate = () => {
  const found = {};
  if (!String(form.title).trim()) found.title = 'Укажите название рецепта';
  if (!String(form.description).trim()) found.description = 'Добавьте описание рецепта';
  if (form.calories === '' || Number(form.calories) < 0 || Number.isNaN(Number(form.calories))) {
    found.calories = 'Укажите калорийность числом';
  }
  if (form.cooking_time === '' || Number(form.cooking_time) <= 0 || Number.isNaN(Number(form.cooking_time))) {
    found.cooking_time = 'Укажите время приготовления в минутах';
  }
  if (!selectedIngredients.value.length) found.ingredients = 'Выберите хотя бы один ингредиент';
  if (!isEdit.value) {
    const imageError = validateImage(imageFile.value);
    if (imageError) found.image = imageError;
  } else if (imageFile.value) {
    const imageError = validateImage(imageFile.value);
    if (imageError) found.image = imageError;
  }
  return found;
};

/** Собирает multipart/form-data для отправки на сервер. */
const buildFormData = () => {
  const data = new FormData();
  data.append('title', String(form.title).trim());
  data.append('description', String(form.description).trim());
  data.append('calories', String(form.calories));
  data.append('cooking_time', String(form.cooking_time));
  selectedIngredients.value.forEach((item) => {
    data.append('ingredients[]', String(item.id));
  });
  if (imageFile.value) {
    data.append('image', imageFile.value);
  }
  return data;
};

const submit = async () => {
  generalError.value = '';
  clearErrors();

  const localErrors = validate();
  if (Object.keys(localErrors).length) {
    setErrors(localErrors);
    return;
  }

  saving.value = true;
  try {
    const data = buildFormData();
    const result = isEdit.value
      ? await recipesApi.update(route.params.id, data)
      : await recipesApi.create(data);
    push({
      variant: 'success',
      title: isEdit.value ? 'Рецепт обновлён' : 'Рецепт создан',
      text: `«${result && result.title ? result.title : form.title}» сохранён.`,
    });
    await router.push({ name: 'my-recipes' });
  } catch (error) {
    const parsed = parseApiError(error);
    const normalized = { ...parsed.errors };
    if (normalized.cooking_time === undefined && normalized.time) normalized.cooking_time = normalized.time;
    if (Object.keys(normalized).length) {
      setErrors(normalized);
    } else {
      generalError.value = parsed.message;
    }
  } finally {
    saving.value = false;
  }
};

/** Загрузка данных для формы редактирования. */
const loadRecipe = async () => {
  try {
    const recipe = await recipesApi.detail(route.params.id);
    const ownerId = pickOwnerId(recipe);
    if (ownerId !== undefined && currentUserId.value !== null && String(ownerId) !== String(currentUserId.value)) {
      push({ variant: 'danger', title: 'Недоступно', text: 'Редактировать можно только свои рецепты.', timeout: 8000 });
      await router.push({ name: 'my-recipes' });
      return;
    }
    fill({
      title: recipe.title || '',
      description: recipe.description || '',
      calories: pickCalories(recipe) ?? '',
      cooking_time: pickCookingTime(recipe) ?? '',
    });
    selectedIngredients.value = pickIngredients(recipe).map((item) => ({
      id: ingredientId(item),
      title: ingredientTitle(item),
      unit: (item.pivot && item.pivot.unit) || item.unit || '',
      quantity: ingredientQuantity(item),
    }));
  } catch (error) {
    generalError.value = parseApiError(error).message;
  }
};

onMounted(async () => {
  loading.value = true;
  try {
    ingredientsCatalog.value = await ingredientsApi.list();
  } catch (error) {
    // Не критично: ингредиенты можно будет добавить позже.
    ingredientsCatalog.value = [];
  }
  if (isEdit.value) {
    await loadRecipe();
  }
  loading.value = false;
});
</script>

<template>
  <div class="container page-container">
    <nav class="breadcrumbs" aria-label="Навигация">
      <router-link to="/">Рецепты</router-link>
      <span aria-hidden="true">/</span>
      <router-link :to="{ name: 'my-recipes' }">Мои рецепты</router-link>
      <span aria-hidden="true">/</span>
      <span>{{ isEdit ? 'Редактирование' : 'Новый рецепт' }}</span>
    </nav>

    <header class="page-head">
      <h1 class="page-title">{{ title }}</h1>
      <p class="page-subtitle">Заполните поля и сохраните рецепт. Все поля валидируются на сервере.</p>
    </header>

    <div v-if="generalError" class="alert alert-danger" role="alert">{{ generalError }}</div>

    <LoaderSpinner v-if="loading" text="Подготовка формы…" />

    <form v-else class="row" novalidate @submit.prevent="submit">
      <div class="col-lg-7 col-md-12">
        <div class="panel">
          <h2 class="panel-title-sm">Основное</h2>

          <BaseInput
            id="recipe-title"
            v-model="form.title"
            label="Название рецепта"
            type="text"
            placeholder="Например: Борщ классический"
            required
            :error="errors.title"
          />

          <BaseTextarea
            id="recipe-description"
            v-model="form.description"
            label="Описание"
            placeholder="Кратко опишите блюдо, особенности приготовления…"
            required
            :rows="5"
            :error="errors.description"
          />

          <div class="row">
            <div class="col-md-6">
              <BaseInput
                id="recipe-calories"
                v-model="form.calories"
                label="Калорийность, ккал"
                type="number"
                min="0"
                placeholder="например, 320"
                required
                :error="errors.calories"
              />
            </div>
            <div class="col-md-6">
              <BaseInput
                id="recipe-time"
                v-model="form.cooking_time"
                label="Время приготовления, минут"
                type="number"
                min="1"
                placeholder="например, 120"
                required
                :error="errors.cooking_time"
              />
            </div>
          </div>
        </div>

        <div class="panel">
          <h2 class="panel-title-sm">Ингредиенты</h2>

          <div class="ingredient-add">
            <BaseSelect
              id="recipe-ingredient-select"
              v-model="ingredientToAdd"
              label=""
              placeholder="Выберите ингредиент"
              :options="availableToAdd"
              :error="errors.ingredients"
            />
            <button type="button" class="btn btn-secondary" :disabled="!ingredientToAdd" @click="addIngredient">
              Добавить
            </button>
          </div>

          <ul v-if="selectedIngredients.length" class="ingredient-chips">
            <li v-for="item in selectedIngredients" :key="item.id" class="ingredient-chip">
              <span class="ingredient-chip__title">{{ item.title }}</span>
              <input
                v-model="item.quantity"
                type="text"
                class="ingredient-chip__qty"
                :placeholder="item.unit || 'кол-во'"
                :aria-label="`Количество: ${item.title}`"
              />
              <button type="button" class="ingredient-chip__remove" aria-label="Убрать ингредиент" @click="removeIngredient(item.id)">
                ×
              </button>
            </li>
          </ul>
          <p v-else class="text-muted">Ингредиенты не добавлены.</p>
        </div>
      </div>

      <div class="col-lg-5 col-md-12">
        <div class="panel sticky-side">
          <h2 class="panel-title-sm">Изображение</h2>

          <div class="image-drop">
            <img v-if="imagePreview" :src="imagePreview" alt="Предпросмотр изображения" class="image-drop__preview" />
            <span v-else class="image-drop__placeholder" aria-hidden="true">🖼️</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="recipe-image">Файл изображения</label>
            <input
              :key="imageInputKey"
              id="recipe-image"
              type="file"
              class="form-control"
              :class="{ 'is-invalid': Boolean(errors.image) }"
              accept="image/jpeg,image/png,image/webp"
              @change="onImageChange"
            />
            <div v-if="errors.image" class="invalid-feedback d-block">{{ errors.image }}</div>
            <div v-else class="form-text">
              jpg, jpeg, png, webp — не более 2 МБ{{ isEdit ? '. Оставьте пустым, чтобы не менять.' : '' }}
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 btn-lg" :disabled="saving">
            {{ saving ? 'Сохранение…' : isEdit ? 'Сохранить изменения' : 'Опубликовать рецепт' }}
          </button>

          <router-link to="/my" class="btn btn-outline-primary w-100 mt-3">Отмена</router-link>
        </div>
      </div>
    </form>
  </div>
</template>
