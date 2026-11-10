<script setup>
import { config } from '../config';

const year = config.app.year;
</script>

<template>
  <footer class="app-footer" id="footer">
    <div class="container">
      <div class="row footer-grid">
        <div class="col-lg-4 col-md-6 footer-col">
          <strong class="footer-brand">{{ config.app.name }}</strong>
          <p class="footer-text">
            Платформа рецептов народов России: ищите, публикуйте и открывайте блюда разных кухонь мира.
          </p>
        </div>

        <div class="col-lg-4 col-md-6 footer-col">
          <h3 class="footer-title">Навигация</h3>
          <ul class="footer-nav">
            <li><router-link to="/">Рецепты</router-link></li>
            <li><router-link to="/constructor">Конструктор</router-link></li>
            <li><router-link to="/my">Мои рецепты</router-link></li>
            <li><router-link to="/create">Создать рецепт</router-link></li>
            <li><router-link to="/login">Войти</router-link></li>
            <li><router-link to="/register">Регистрация</router-link></li>
          </ul>
        </div>

        <div class="col-lg-4 col-md-12 footer-col">
          <h3 class="footer-title">Контакты</h3>
          <ul class="footer-contacts">
            <li><a href="tel:+78007654321">8-800-765-43-21</a></li>
            <li><a href="mailto:info@kuhni-mira.ru">info@kuhni-mira.ru</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <span>© {{ year }} {{ config.app.name }}. Учебный проект чемпионата «Профессионалы».</span>
        <span class="footer-client">Client: {{ config.api.client }}</span>
      </div>
    </div>
  </footer>
</template>
