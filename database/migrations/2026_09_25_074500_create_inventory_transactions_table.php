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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('size_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('source')->default('system');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->integer('quantity_change');
            $table->integer('previous_quantity');
            $table->integer('resulting_quantity');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('source');
            $table->index('user_id');
            $table->index(['product_id', 'size_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
