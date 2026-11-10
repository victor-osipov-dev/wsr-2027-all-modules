<?php

namespace App\Models;

class User extends \Illuminate\Foundation\Auth\User
{
    use \Laravel\Sanctum\HasApiTokens;
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
    public function isRegularUser(): bool
    {
        return 'user' === $this->role;
    }
}
