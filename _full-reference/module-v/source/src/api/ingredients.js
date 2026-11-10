/**
 * Тонкая обёртка над API ингредиентов.
 * Реализация сосредоточена в recipes.js, чтобы не дублировать работу с http-клиентом.
 */
export { ingredientsApi, ingredientsApi as default } from './recipes';
