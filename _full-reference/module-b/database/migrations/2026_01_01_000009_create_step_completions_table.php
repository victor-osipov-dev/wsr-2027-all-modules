<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Прогресс прохождения шагов рецепта в рамках заказа.
     */
    public function up(): void
    {
        Schema::create('step_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('step_id')->constrained('steps')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['purchase_id', 'step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_completions');
    }
};
