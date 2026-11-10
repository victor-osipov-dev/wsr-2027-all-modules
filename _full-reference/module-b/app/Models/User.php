<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Пользователь системы.
 *
 * Роль (`role`) разграничивает доступ: `admin` — управление админ-панелью,
 * `user` — доступ к пользовательским REST-маршрутам API.
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Пользователь является администратором. */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Пользователь имеет роль «Пользователь». */
    public function isRegularUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /** Рецепты, созданные пользователем. */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /** Записи избранного пользователя. */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /** Рецепты, добавленные пользователем в избранное. */
    public function favoriteRecipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'favorites')
            ->withTimestamps();
    }

    /** Покупки пользователя. */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
