import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// Сборка SPA «Кухни мира»: результат — в папку dist, публикуется в корень хоста.
export default defineConfig({
    plugins: [vue()],
    base: '/',
});
