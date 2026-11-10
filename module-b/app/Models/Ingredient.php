<?php

namespace App\Models;

class Ingredient extends \Illuminate\Database\Eloquent\Model
{
    public const UNITS = ['шт', 'л', 'кг', 'мг', 'ст.л.'];
    protected $guarded = [];
}
