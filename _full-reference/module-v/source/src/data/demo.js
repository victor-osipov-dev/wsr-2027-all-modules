/**
 * Демонстрационные данные.
 *
 * Используются ТОЛЬКО как аварийный фолбэк, когда сервер вообще не ответил по сети
 * (например, во время локальной разработки без поднятого API). Как только API отвечает,
 * все данные всегда берутся с сервера — в localStorage ничего, кроме токена, не хранится.
 */
import { config } from '../config';

export const demoIngredients = [
  { id: 1, title: 'Базилик', unit: 'г' },
  { id: 2, title: 'Говядина', unit: 'г' },
  { id: 3, title: 'Картофель', unit: 'кг' },
  { id: 4, title: 'Лук репчатый', unit: 'шт' },
  { id: 5, title: 'Морковь', unit: 'шт' },
  { id: 6, title: 'Мука', unit: 'г' },
  { id: 7, title: 'Помидоры', unit: 'г' },
  { id: 8, title: 'Свёкла', unit: 'шт' },
  { id: 9, title: 'Сыр твёрдый', unit: 'г' },
  { id: 10, title: 'Чеснок', unit: 'шт' },
  { id: 11, title: 'Яйцо', unit: 'шт' },
  { id: 12, title: 'Молоко', unit: 'л' },
].sort((a, b) => a.title.localeCompare(b.title, 'ru'));

const ingredient = (id, quantity = '') => {
  const source = demoIngredients.find((item) => item.id === id) || { id, title: 'Ингредиент', unit: '' };
  return { id: source.id, title: source.title, unit: source.unit, quantity, pivot: { quantity, unit: source.unit } };
};

export const demoRecipes = [
  {
    id: 101,
    title: 'Борщ классический',
    description: 'Наваристый борщ на говяжьем бульоне со свёклой, капустой и ароматными специями.',
    calories: 320,
    cooking_time: 120,
    formatted_time: '2 часа',
    image: '',
    created_at: '2026-03-13T10:00:00.000000Z',
    user_id: 1,
    ingredients: [ingredient(2, 500), ingredient(8, 2), ingredient(3, '0.4'), ingredient(4, 1), ingredient(5, 1)],
  },
  {
    id: 102,
    title: 'Паста с томатами и базиликом',
    description: 'Итальянская паста с сочными помидорами, чесноком и свежим базиликом.',
    calories: 480,
    cooking_time: 25,
    formatted_time: '25 минут',
    image: '',
    created_at: '2026-03-14T11:30:00.000000Z',
    user_id: 2,
    ingredients: [ingredient(7, 400), ingredient(10, 2), ingredient(1, 20), ingredient(9, 50)],
  },
  {
    id: 103,
    title: 'Драники картофельные',
    description: 'Хрустящие картофельные оладьи с золотистой корочкой, подаются со сметаной.',
    calories: 250,
    cooking_time: 40,
    formatted_time: '40 минут',
    image: '',
    created_at: '2026-03-15T09:15:00.000000Z',
    user_id: 1,
    ingredients: [ingredient(3, 1), ingredient(6, 60), ingredient(11, 2), ingredient(4, 1)],
  },
  {
    id: 104,
    title: 'Сырный суп',
    description: 'Нежный крем-суп с плавленым сыром и гренками из белого хлеба.',
    calories: 370,
    cooking_time: 45,
    formatted_time: '45 минут',
    image: '',
    created_at: '2026-03-16T18:45:00.000000Z',
    user_id: 3,
    ingredients: [ingredient(9, 200), ingredient(12, '0.5'), ingredient(3, '0.3'), ingredient(4, 1)],
  },
  {
    id: 105,
    title: 'Омлет с сыром',
    description: 'Пышный омлет с тёртым сыром — быстрый и сытный завтрак за пять минут.',
    calories: 210,
    cooking_time: 10,
    formatted_time: '10 минут',
    image: '',
    created_at: '2026-03-17T07:05:00.000000Z',
    user_id: 2,
    ingredients: [ingredient(11, 3), ingredient(12, '0.1'), ingredient(9, 40)],
  },
  {
    id: 106,
    title: 'Тушёная говядина с овощами',
    description: 'Мягкая говядина, томлённая с морковью, луком и ароматными травами.',
    calories: 540,
    cooking_time: 150,
    formatted_time: '2 часа 30 минут',
    image: '',
    created_at: '2026-03-18T13:20:00.000000Z',
    user_id: 1,
    ingredients: [ingredient(2, 700), ingredient(5, 2), ingredient(4, 2), ingredient(1, 15)],
  },
];

/** Флаг: активен ли режим демо-фолбэка. */
export const isDemoFallbackEnabled = () => config.api.demoFallback;

/** Упорядочивает набор демо-рецептов согласно параметру sort. */
export const sortDemoRecipes = (recipes, sort = '') => {
  const items = [...recipes];
  const comparators = {
    [config.sort.createdAsc]: (a, b) => new Date(a.created_at) - new Date(b.created_at),
    [config.sort.createdDesc]: (a, b) => new Date(b.created_at) - new Date(a.created_at),
    [config.sort.timeAsc]: (a, b) => a.cooking_time - b.cooking_time,
    [config.sort.timeDesc]: (a, b) => b.cooking_time - a.cooking_time,
    [config.sort.caloriesAsc]: (a, b) => a.calories - b.calories,
    [config.sort.caloriesDesc]: (a, b) => b.calories - a.calories,
  };
  return items.sort(comparators[sort] || comparators[config.sort.createdDesc]);
};
