import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import './bootstrap.min.css';

// Точка входа SPA: подключаем шаблон Bootstrap, роутер и корневой компонент.
createApp(App).use(router).mount('#app');
