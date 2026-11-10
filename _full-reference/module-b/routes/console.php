<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Консольные команды
|--------------------------------------------------------------------------
| Здесь можно регистрировать замыкания-команды artisan.
| Пример: команда перегенерации миниатюр:
|
| Artisan::command('recipes:thumbnails', function () {
|     \App\Models\Recipe::with('images')->get()
|         ->each(fn ($recipe) => app(\App\Services\ImageService::class)->regenerateThumbnails($recipe));
| })->purpose('Перегенерировать миниатюры и водяные знаки всех рецептов');
*/
