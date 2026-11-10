import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// Локальная копия Bootstrap 5 (без CDN) и собственные стили приложения.
import './assets/css/bootstrap.min.css';
import './assets/css/app.css';

const app = createApp(App);
app.use(router);

// Глобальный обработчик ошибок: предотвращает «немой» падение и не засоряет консоль стеком.
app.config.errorHandler = (error) => {
  // Служебное сообщение оставляем кратким.
  // eslint-disable-next-line no-console
  console.warn('[app]', error && error.message ? error.message : error);
};

app.mount('#app');
