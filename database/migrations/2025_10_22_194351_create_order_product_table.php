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
        Schema::create('order_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('count');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'expired', 'paid', 'cancelled', 'shipped', 'delivered', 'returned', 'refunded', 'failed'])->default('pending');
            $table->foreignId('color_id')->nullable()->constrained('colors')->onDelete('set null');
            $table->foreignId('size_id')->nullable()->constrained('sizes')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_color');
    }
};
