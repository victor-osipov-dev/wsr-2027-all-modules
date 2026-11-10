<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Просмотр заказов пользователей.
 *
 * Удаление доступно только для заказов со статусами «ошибка оплаты»
 * и «истекло время на оплату».
 */
class OrderController extends Controller
{
    /**
     * Список заказов с пагинацией по 5 записей.
     */
    public function index(): View
    {
        $orders = Purchase::query()
            ->with(['user', 'recipe'])
            ->latest()
            ->paginate(5);

        return view('admin.orders.index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Удаление заказа (только для разрешённых статусов).
     */
    public function destroy(Purchase $order): RedirectResponse
    {
        $deletable = [Purchase::STATUS_FAILED, Purchase::STATUS_EXPIRED];

        if (! in_array($order->status, $deletable, true)) {
            return back()->with('error', 'Удалить заказ можно только со статусом «ошибка оплаты» или «истекло время на оплату».');
        }

        $order->delete();

        return back()->with('success', 'Заказ удалён.');
    }
}
