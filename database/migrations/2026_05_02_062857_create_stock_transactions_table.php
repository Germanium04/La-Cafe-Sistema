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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id('transaction_id');

            $table->foreignId('ingredient_id')
                ->constrained(table: 'ingredients', column: 'ingredient_id')
                ->cascadeOnDelete();

            // The staff member who submitted the transaction
            $table->foreignId('user_id')
                ->constrained(table: 'users', column: 'id')
                ->cascadeOnDelete();

            $table->enum('transaction_type', ['IN', 'OUT']);

            // ─── Quantity (two representations) ───────────────────────────
            // quantity         → converted value in base unit (what actually hits stock_level)
            //                    e.g. staff enters 2 kg → stored as 2000 (grams)
            // entered_quantity → exactly what the staff typed, kept for the audit log
            // entered_unit     → the unit the staff selected (e.g. 'kg')
            $table->integer('quantity');
            $table->integer('entered_quantity');
            $table->string('entered_unit');

            // ─── Reason & notes ────────────────────────────────────────────
            // reason → predefined dropdown choice (required)
            // notes  → optional free-text for extra context
            $table->string('reason');
            $table->text('notes')->nullable();

            // ─── Approval workflow ─────────────────────────────────────────
            // status          → pending (just submitted) | approved | rejected
            // approved_by     → FK to users; which admin acted on this
            // approved_at     → when the admin approved or rejected
            // rejection_note  → admin's reason if rejected; shown to staff
            //
            // IMPORTANT: stock_level on ingredients must only be updated
            // when status changes to 'approved' — NOT on submission.
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained(table: 'users', column: 'id')
                ->nullOnDelete();

            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_note')->nullable();

            $table->dateTime('transaction_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};