<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Заказ (покупка) рецепта.
 *
 * Номер заказа (`order_number`) используется для формирования
 * идентификатора webhook: purchase_id = md5(order_number).
 */
class Purchase extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'recipe_id',
        'total_amount',
        'status',
        'paid_at',
        'expires_at',
        'order_number',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** Пользователь, оформивший заказ. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Купленный рецепт (в том числе помеченный на удаление). */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class)->withTrashed();
    }

    /** Отметки о прохождении шагов по данному заказу. */
    public function stepCompletions(): HasMany
    {
        return $this->hasMany(StepCompletion::class);
    }

    /** Заказ оплачен. */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /** Заказ с ошибкой оплаты. */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /** Время на оплату истекло. */
    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isPast()
            && $this->status === self::STATUS_PENDING;
    }

    /** Идентификатор webhook — md5 от номера заказа. */
    public function paymentId(): string
    {
        return md5((string) $this->order_number);
    }

    /**
     * Человекочитаемый статус оплаты (для админ-панели).
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'оплачено',
            self::STATUS_FAILED => 'ошибка оплаты',
            self::STATUS_EXPIRED => 'истекло время на оплату',
            default => 'не оплачено',
        };
    }
}
