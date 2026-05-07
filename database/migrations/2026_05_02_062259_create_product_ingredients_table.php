<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained(table: 'products', column: 'product_id')
                ->cascadeOnDelete();

            $table->foreignId('ingredient_id')
                ->constrained(table: 'ingredients', column: 'ingredient_id')
                ->cascadeOnDelete();

            $table->integer('quantity_used');

            $table->unique(['product_id', 'ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
