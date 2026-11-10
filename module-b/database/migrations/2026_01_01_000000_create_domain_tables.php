<?php
return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        \Illuminate\Support\Facades\Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->rememberToken();
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('categories', function ($table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('ingredients', function ($table) {
            $table->id();
            $table->string('title', 20)->unique();
            $table->enum('unit', ['шт', 'л', 'кг', 'мг', 'ст.л.']);
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('recipes', function ($table) {
            $table->id();
            $table->string('title', 20);
            $table->string('description', 255);
            $table->unsignedInteger('cooking_time');
            $table->unsignedInteger('calories')->nullable();
            $table->enum('difficulty', ['легко', 'средне', 'сложно']);
            $table->decimal('price', 10, 2)->default(0);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        \Illuminate\Support\Facades\Schema::create('recipe_ingredient', function ($table) {
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->enum('unit', ['шт', 'л', 'кг', 'мг', 'ст.л.'])->default('шт');
            $table->unique(['recipe_id', 'ingredient_id']);
        });
        \Illuminate\Support\Facades\Schema::create('recipe_images', function ($table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('steps', function ($table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->string('title', 40);
            $table->string('description', 255);
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('favorites', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'recipe_id']);
        });
        \Illuminate\Support\Facades\Schema::create('purchases', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('order_number')->unique();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        \Illuminate\Support\Facades\Schema::dropAllTables();
    }
};
