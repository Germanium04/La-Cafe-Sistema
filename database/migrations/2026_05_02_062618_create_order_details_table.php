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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id('order_detail_id');

            $table->foreignId('order_id')
                ->constrained(table: 'orders', column: 'order_id')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained(table: 'products', column: 'product_id')
                ->cascadeOnDelete();

            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->boolean('with_coffee')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
