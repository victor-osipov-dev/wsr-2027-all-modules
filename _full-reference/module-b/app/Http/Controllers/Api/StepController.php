<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StepCompleteRequest;
use App\Models\Purchase;
use App\Models\Step;
use App\Models\StepCompletion;
use Illuminate\Http\JsonResponse;

/**
 * Сохранение прогресса прохождения шагов рецепта.
 */
class StepController extends Controller
{
    /**
     * POST /recipe-api/steps/{stepId}/complete
     *
     * Отмечает шаг пройденным или снимает отметку. Требует, чтобы рецепт был
     * куплен (есть оплаченный заказ), иначе — 403.
     */
    public function complete(StepCompleteRequest $request, int $stepId): JsonResponse
    {
        $step = Step::query()->find($stepId);

        if ($step === null) {
            return response()->json(['message' => 'Step not found'], 404);
        }

        $recipeId = $request->integer('recipe_id');

        $purchase = Purchase::query()
            ->where('user_id', $request->user()->getKey())
            ->where('recipe_id', $recipeId)
            ->where('status', Purchase::STATUS_PAID)
            ->latest()
            ->first();

        if ($purchase === null) {
            return response()->json(['message' => 'Forbidden for you'], 403);
        }

        $completed = $request->boolean('completed');

        if ($completed) {
            StepCompletion::updateOrCreate(
                ['purchase_id' => $purchase->getKey(), 'step_id' => $step->getKey()],
                ['completed_at' => now()],
            );
            $message = 'Step marked as completed';
        } else {
            StepCompletion::query()
                ->where('purchase_id', $purchase->getKey())
                ->where('step_id', $step->getKey())
                ->delete();
            $message = 'Step marked as not completed';
        }

        $completion = StepCompletion::query()
            ->where('purchase_id', $purchase->getKey())
            ->where('step_id', $step->getKey())
            ->first();

        return response()->json([
            'success' => true,
            'message' => $message,
            'step' => [
                'id' => $step->getKey(),
                'title' => $step->title,
                'completed' => $completed,
                'completed_at' => optional($completion?->completed_at)->toJSON(),
            ],
        ], 200);
    }
}
