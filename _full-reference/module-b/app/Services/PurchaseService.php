<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Бизнес-логика покупки рецептов и обработки вебхука платёжной системы.
 *
 * purchase_id вебхука формируется как md5 от номера заказа (order_number).
 */
class PurchaseService
{
    /** Время на оплату заказа (минуты). */
    public const PAYMENT_WINDOW_MINUTES = 1;

    /** Единственный валидный номер карты (тестовый платёжный шлюз). */
    public const VALID_CARD = '123456789';

    /**
     * Создаёт новый заказ для платного рецепта.
     */
    public function create(Recipe $recipe, User $user): Purchase
    {
        return Purchase::create([
            'user_id' => $user->getKey(),
            'recipe_id' => $recipe->getKey(),
            'total_amount' => $recipe->price,
            'status' => Purchase::STATUS_PENDING,
            'expires_at' => now()->addMinutes(self::PAYMENT_WINDOW_MINUTES),
            'order_number' => $this->generateOrderNumber(),
        ]);
    }

    /**
     * Находит заказ по идентификатору webhook (md5 от номера заказа).
     */
    public function findByPaymentId(string $paymentId): ?Purchase
    {
        $paymentId = strtolower(trim($paymentId));

        if ($paymentId === '') {
            return null;
        }

        $purchase = null;

        if ($this->databaseSupportsMd5()) {
            $purchase = Purchase::query()
                ->whereNotNull('order_number')
                ->whereRaw('LOWER(MD5(order_number)) = ?', [$paymentId])
                ->first();
        } else {
            // Универсальный запасной вариант (например, SQLite в тестах).
            $purchase = Purchase::query()
                ->whereNotNull('order_number')
                ->get()
                ->first(fn (Purchase $candidate) => md5((string) $candidate->order_number) === $paymentId);
        }

        if ($purchase !== null) {
            return $purchase;
        }

        // Запасной вариант: клиент прислал числовой идентификатор заказа.
        if (preg_match('/^\d{1,10}$/', $paymentId) === 1) {
            return Purchase::query()->find((int) $paymentId);
        }

        return null;
    }

    /**
     * Проверяет валидность номера карты.
     */
    public function isValidCard(?string $card): bool
    {
        return $card === null || $card === '' || $card === self::VALID_CARD;
    }

    /**
     * Генерирует уникальный номер заказа.
     */
    public function generateOrderNumber(): string
    {
        do {
            $number = (string) random_int(100000, 9999999);
        } while (Purchase::query()->where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Определяет, поддерживает ли БД функцию MD5().
     */
    private function databaseSupportsMd5(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
