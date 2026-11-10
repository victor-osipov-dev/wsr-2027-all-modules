<?php

namespace App\Models;

class Favorite extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
