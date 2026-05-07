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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');

            $table->foreignId('order_id')
                ->constrained(table: 'orders', column: 'order_id')
                ->cascadeOnDelete();

            $table->enum('payment_method', ['cash', 'gcash', 'maya']);
            $table->decimal('amount_paid', 10, 2);
            $table->dateTime('payment_date')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');

            $table->string('receipt_number')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
