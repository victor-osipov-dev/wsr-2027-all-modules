<?php

namespace App\Models;

class Purchase extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2', 'paid_at' => 'datetime', 'expires_at' => 'datetime'];
    }
    public function recipe()
    {
        return $this->belongsTo(Recipe::class)->withTrashed();
    }
}
