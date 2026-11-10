<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseRequest;
use App\Http\Resources\PurchaseListResource;
use App\Http\Resources\PurchaseDetailResource;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Покупка рецептов и просмотр заказов пользователя.
 */
class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {
    }

    /**
     * POST /recipe-api/purchase — покупка платного рецепта.
     */
    public function store(PurchaseRequest $request): JsonResponse
    {
        $recipe = Recipe::query()->find($request->integer('recipe_id'));

        if ($recipe === null) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }

        if ($recipe->isFree()) {
            return response()->json(['message' => 'This recipe is free'], 400);
        }

        $purchase = $this->purchaseService->create($recipe, $request->user());

        return response()->json([
            'purchase_id' => $purchase->getKey(),
            'status' => $purchase->status,
            'payment_link' => $this->paymentLink($purchase),
            'expires_at' => optional($purchase->expires_at)->toJSON(),
        ], 201);
    }

    /**
     * GET /recipe-api/purchases — список покупок пользователя.
     */
    public function index(Request $request): JsonResponse
    {
        $purchases = Purchase::query()
            ->where('user_id', $request->user()->getKey())
            ->with(['recipe.images'])
            ->latest()
            ->get();

        return response()->json(PurchaseListResource::collection($purchases)->resolve(), 200);
    }

    /**
     * GET /recipe-api/purchase/{id} — детали покупки с шагами рецепта.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $purchase = $this->findOwnedPurchase($request, $id);

        if ($purchase === null) {
            return response()->json(['message' => 'Purchase not found'], 404);
        }

        $purchase->load(['recipe.images', 'recipe.steps', 'stepCompletions']);

        return response()->json((new PurchaseDetailResource($purchase))->resolve(), 200);
    }

    /**
     * GET /recipe-api/purchase/{id}/progress — прогресс прохождения шагов.
     */
    public function progress(Request $request, int $id): JsonResponse
    {
        $purchase = $this->findOwnedPurchase($request, $id);

        if ($purchase === null) {
            return response()->json(['message' => 'Purchase not found'], 404);
        }

        $purchase->load(['recipe.steps', 'stepCompletions']);

        $completions = $purchase->stepCompletions->keyBy('step_id');

        $steps = $purchase->recipe->steps->map(function ($step) use ($completions) {
            $completion = $completions->get($step->id);

            return [
                'id' => $step->id,
                'title' => $step->title,
                'order' => (int) $step->order,
                'completed' => $completion !== null && $completion->completed_at !== null,
                'completed_at' => optional($completion?->completed_at)->toJSON(),
            ];
        })->values();

        return response()->json([
            'purchase_id' => $purchase->getKey(),
            'recipe_id' => $purchase->recipe->id,
            'recipe_title' => $purchase->recipe->title,
            'total_steps' => $steps->count(),
            'completed_steps' => $steps->where('completed', true)->count(),
            'steps' => $steps,
        ], 200);
    }

    /**
     * Формирует ссылку на оплату с адресом webhook.
     */
    private function paymentLink(Purchase $purchase): string
    {
        $webhook = (string) (env('PAYMENT_WEBHOOK_URL') ?: route('api.webhook'));

        return url('pay').'?order_id='.$purchase->order_number.'&url='.urlencode($webhook);
    }

    /**
     * Находит заказ, принадлежащий текущему пользователю.
     */
    private function findOwnedPurchase(Request $request, int $id): ?Purchase
    {
        return Purchase::query()
            ->where('user_id', $request->user()->getKey())
            ->find($id);
    }
}
