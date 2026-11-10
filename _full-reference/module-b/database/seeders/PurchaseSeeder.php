<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Демонстрационные заказы пользователя со всеми статусами оплаты.
 */
class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'user@champ2026.lan')->first();

        if ($user === null) {
            return;
        }

        /** @var array<int, array{0: string, 1: string, 2: float}> $orders */
        $orders = [
            ['paid', 'Плов', 350.00],
            ['paid', 'Медовик', 500.00],
            ['pending', 'Цезарь', 180.00],
            ['failed', 'Голубцы', 250.00],
            ['expired', 'Плов', 350.00],
            ['paid', 'Голубцы', 250.00],
            ['failed', 'Медовик', 500.00],
            ['expired', 'Цезарь', 180.00],
        ];

        foreach ($orders as $index => [$status, $title, $amount]) {
            $recipe = Recipe::query()->withTrashed()->where('title', $title)->first();

            if ($recipe === null) {
                continue;
            }

            $createdAt = now()->subDays(8 - $index)->subHours($index);

            $purchase = Purchase::query()->create([
                'user_id' => $user->getKey(),
                'recipe_id' => $recipe->getKey(),
                'total_amount' => $amount,
                'status' => $status,
                'paid_at' => $status === Purchase::STATUS_PAID ? $createdAt->copy()->addMinutes(1) : null,
                'expires_at' => match ($status) {
                    Purchase::STATUS_PAID => null,
                    Purchase::STATUS_PENDING => now()->addMinutes(1),
                    default => $createdAt->copy()->addMinutes(1),
                },
                'order_number' => $this->uniqueOrderNumber(),
            ]);

            $purchase->created_at = $createdAt;
            $purchase->updated_at = $createdAt;
            $purchase->save();

            // Для первого оплаченного заказа отметим первые два шага пройденными.
            if ($index === 0) {
                $recipe->steps()->orderBy('order')->take(2)->get()->each(function ($step) use ($purchase) {
                    $purchase->stepCompletions()->create([
                        'step_id' => $step->getKey(),
                        'completed_at' => now(),
                    ]);
                });
            }
        }
    }

    /**
     * Уникальный номер заказа.
     */
    private function uniqueOrderNumber(): string
    {
        do {
            $number = (string) random_int(100000, 9999999);
        } while (Purchase::query()->where('order_number', $number)->exists());

        return $number;
    }
}
