import { config } from '../config';
import { formatCookingTime } from './format';

/** Возвращает первого кандидата, который не пустой. */
const firstDefined = (...values) => values.find((value) => value !== undefined && value !== null && value !== '');

/** Приводит относительный путь медиа к абсолютному URL с учётом базового адреса API. */
export const resolveMediaUrl = (path) => {
  if (!path) return '';
  const value = String(path);
  if (/^(https?:)?\/\//i.test(value) || value.startsWith('data:') || value.startsWith('blob:')) {
    return value;
  }
  const base = config.api.baseURL || '';
  if (/^https?:\/\//i.test(base)) {
    const origin = base.replace(/^(https?:\/\/[^/]+).*$/i, '$1');
    return value.startsWith('/') ? `${origin}${value}` : `${origin}/${value}`;
  }
  return value;
};

/** Изображение рецепта: поддержка разных вариантов именования поля. */
export const pickImage = (recipe) => {
  if (!recipe) return '';
  const images = Array.isArray(recipe.images) ? recipe.images : [];
  const defaultImage = images.find((image) => image && (image.is_default || image.is_default_image));
  const candidate = firstDefined(
    recipe.image,
    recipe.image_url,
    recipe.default_image,
    recipe.thumbnail,
    defaultImage && firstDefined(defaultImage.thumbnail, defaultImage.path, defaultImage.url),
    images[0] && firstDefined(images[0].thumbnail, images[0].path, images[0].url),
  );
  return resolveMediaUrl(candidate);
};

/** Калорийность рецепта. */
export const pickCalories = (recipe) =>
  firstDefined(recipe && recipe.calories, recipe && recipe.calorie_content, recipe && recipe.calories_count, recipe && recipe.kcal);

/** Время приготовления в минутах. */
export const pickCookingTime = (recipe) =>
  firstDefined(recipe && recipe.cooking_time, recipe && recipe.cooking_time_minutes, recipe && recipe.time);

/** Человекочитаемое время приготовления. */
export const pickTimeLabel = (recipe) => firstDefined(recipe && recipe.formatted_time, formatCookingTime(pickCookingTime(recipe)));

/** Список ингредиентов рецепта. */
export const pickIngredients = (recipe) => (Array.isArray(recipe && recipe.ingredients) ? recipe.ingredients : []);

/** Название ингредиента. */
export const ingredientTitle = (ingredient) => firstDefined(ingredient && ingredient.title, ingredient && ingredient.name, '') || '';

/** Идентификатор ингредиента. */
export const ingredientId = (ingredient) => firstDefined(ingredient && ingredient.id, ingredient && ingredient.ingredient_id, null);

/** Единица измерения ингредиента (с учётом pivot). */
export const ingredientUnit = (ingredient) =>
  firstDefined(
    ingredient && ingredient.pivot && ingredient.pivot.unit,
    ingredient && ingredient.unit,
    ingredient && ingredient.measure,
    '',
  ) || '';

/** Количество ингредиента (с учётом pivot). */
export const ingredientQuantity = (ingredient) =>
  firstDefined(
    ingredient && ingredient.pivot && ingredient.pivot.quantity,
    ingredient && ingredient.quantity,
    ingredient && ingredient.amount,
    '',
  ) || '';

/** Строка «2 шт · Свёкла» для вывода ингредиента. */
export const ingredientLabel = (ingredient) => {
  const quantity = ingredientQuantity(ingredient);
  const unit = ingredientUnit(ingredient);
  const title = ingredientTitle(ingredient);
  return `${[quantity, unit].filter(Boolean).join(' ')}${quantity || unit ? ' — ' : ''}${title}`.trim();
};

/** Идентификатор владельца рецепта. */
export const pickOwnerId = (recipe) =>
  firstDefined(recipe && recipe.user_id, recipe && recipe.owner_id, recipe && recipe.user && recipe.user.id);

/**
 * Проверяет, принадлежит ли рецепт пользователю.
 * Если сервер не передаёт владельца — не блокируем (права всё равно проверяет сервер).
 */
export const isOwnRecipe = (recipe, userId) => {
  const ownerId = pickOwnerId(recipe);
  if (ownerId === undefined || userId === undefined || userId === null) return true;
  return String(ownerId) === String(userId);
};
