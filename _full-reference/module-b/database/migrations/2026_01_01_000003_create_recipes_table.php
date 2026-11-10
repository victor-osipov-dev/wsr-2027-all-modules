<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Рецепты. Мягкое удаление через deleted_at.
     */
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 20);
            $table->string('description', 255);
            $table->unsignedInteger('cooking_time');
            $table->unsignedInteger('calories')->nullable();
            $table->enum('difficulty', ['легко', 'средне', 'сложно']);
            $table->decimal('price', 10, 2)->nullable()->default(0);
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['difficulty', 'cooking_time']);
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
