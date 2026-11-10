<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Обработка вебхука платёжной системы.
 *
 * Тело запроса: { "purchase_id": "<md5(order_number)>", "status": "paid" }.
 * purchase_id сопоставляется с заказом через md5 от номера заказа.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $purchase = $this->purchaseService->findByPaymentId((string) $request->input('purchase_id', ''));

        if ($purchase === null) {
            return response()->json(['message' => 'Purchase not found'], 404);
        }

        // Заказ уже оплачен.
        if ($purchase->isPaid()) {
            return response()->json(['message' => 'Recipe already purchased'], 409);
        }

        // Ранее зафиксирована ошибка оплаты.
        if ($purchase->isFailed()) {
            return $this->paymentError();
        }

        // Проверка номера карты: валидна только 123456789.
        $card = $request->input('card');

        if ($card !== null && $card !== '' && ! $this->purchaseService->isValidCard((string) $card)) {
            $purchase->update(['status' => Purchase::STATUS_FAILED]);

            return $this->paymentError();
        }

        $status = strtolower((string) $request->input('status', ''));

        // Платёж отклонён платёжной системой.
        if (in_array($status, ['failed', 'error', 'declined', 'cancelled'], true)) {
            $purchase->update(['status' => Purchase::STATUS_FAILED]);

            return $this->paymentError();
        }

        // Истекло время на оплату (окно 1 минута).
        if ($purchase->expires_at !== null && $purchase->expires_at->isPast()) {
            $purchase->update(['status' => Purchase::STATUS_EXPIRED]);

            return response()->json(['message' => 'Time is left'], 419);
        }

        // Успешная оплата.
        if ($status === 'paid') {
            $purchase->update([
                'status' => Purchase::STATUS_PAID,
                'paid_at' => now(),
                'expires_at' => null,
            ]);

            return response()->json(['message' => 'Success'], 200);
        }

        // Неизвестный статус — считаем заказ оплаченным.
        return response()->json(['message' => 'Success'], 200);
    }

    /**
     * Стандартный ответ об ошибке оплаты.
     */
    private function paymentError(): JsonResponse
    {
        return response()->json([
            'message' => 'You cannot purchase an order if a payment error occurs',
        ], 408);
    }
}
