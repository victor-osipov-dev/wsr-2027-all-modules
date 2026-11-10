/**
 * Форматирование значений для вывода в интерфейсе.
 */

/** Форматирует время приготовления в человекочитаемый вид: «2 часа», «1 час 20 минут», «25 минут». */
export const formatCookingTime = (minutes) => {
  const value = Number(minutes);
  if (!Number.isFinite(value) || value <= 0) {
    return '—';
  }
  const hours = Math.floor(value / 60);
  const restMinutes = value % 60;
  const plural = (count, forms) => {
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) return forms[0];
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return forms[1];
    return forms[2];
  };
  const parts = [];
  if (hours > 0) parts.push(`${hours} ${plural(hours, ['час', 'часа', 'часов'])}`);
  if (restMinutes > 0) parts.push(`${restMinutes} ${plural(restMinutes, ['минута', 'минуты', 'минут'])}`);
  return parts.length ? parts.join(' ') : '0 минут';
};

/** Калорийность с единицей измерения. */
export const formatCalories = (value) => {
  const calories = Number(value);
  return Number.isFinite(calories) && calories > 0 ? `${calories} ккал` : 'не указана';
};

/** Дата в формате ДД.ММ.ГГГГ. */
export const formatDate = (value) => {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString('ru-RU');
};

/** Дата и время. */
export const formatDateTime = (value) => {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

/** Обрезает длинный текст до заданного количества символов. */
export const truncate = (text, length = 120) => {
  const value = String(text || '');
  return value.length > length ? `${value.slice(0, length).trimEnd()}…` : value;
};

/** Цена. */
export const formatPrice = (value) => {
  const price = Number(value);
  return Number.isFinite(price) && price > 0 ? `${price.toFixed(2)} ₽` : 'бесплатно';
};
