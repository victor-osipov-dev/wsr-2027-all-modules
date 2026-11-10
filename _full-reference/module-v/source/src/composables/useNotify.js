import { reactive } from 'vue';

/**
 * Глобальные всплывающие уведомления (в т.ч. о новых рецептах по WebSocket).
 * Каждое уведомление автоматически скрывается через заданный интервал.
 */
const state = reactive({
  items: [],
});

let sequence = 0;

/** Планировщик автоскрытия, вынесенный отдельно для возможности отмены. */
const timers = new Map();

const scheduleRemoval = (id, timeout) => {
  if (!timeout) return;
  const timer = setTimeout(() => remove(id), timeout);
  timers.set(id, timer);
};

const remove = (id) => {
  const index = state.items.findIndex((item) => item.id === id);
  if (index !== -1) state.items.splice(index, 1);
  const timer = timers.get(id);
  if (timer) {
    clearTimeout(timer);
    timers.delete(id);
  }
};

const push = (notification) => {
  const id = ++sequence;
  const item = {
    id,
    variant: 'info',
    timeout: 6000,
    title: '',
    text: '',
    to: null,
    ...notification,
  };
  state.items.push(item);
  scheduleRemoval(id, item.timeout);
  return id;
};

const clear = () => {
  state.items.splice(0, state.items.length);
  timers.forEach((timer) => clearTimeout(timer));
  timers.clear();
};

export const useNotify = () => ({
  state,
  push,
  remove,
  clear,
});

export default useNotify;
