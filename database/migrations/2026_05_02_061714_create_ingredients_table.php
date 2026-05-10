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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id('ingredient_id');
            $table->string('ingredient_name');

            // ─── Stock level (always stored in base unit) ──────────────────
            $table->integer('stock_level')->default(0);

            // ─── Unit system ───────────────────────────────────────────────
            // unit       → the base unit admin declared (e.g. 'grams', 'ml', 'pcs')
            // unit_group → the family it belongs to, controls what staff can pick
            //              'weight'  → allows: g, kg
            //              'volume'  → allows: ml, L
            //              'piece'   → allows: pcs only
            $table->string('unit');
            $table->enum('unit_group', ['weight', 'volume', 'piece'])->default('piece');

            // ─── Min / Max thresholds (in base unit) ───────────────────────
            // min_stock → triggers LOW warning when stock_level falls at or below this
            // max_stock → optional ceiling; null means no upper limit set
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};