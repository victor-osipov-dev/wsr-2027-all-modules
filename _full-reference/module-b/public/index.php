<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Определяем, находится ли приложение в режиме обслуживания...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Регистрируем автозагрузчик Composer...
require __DIR__.'/../vendor/autoload.php';

// Загружаем приложение и обрабатываем входящий запрос...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
