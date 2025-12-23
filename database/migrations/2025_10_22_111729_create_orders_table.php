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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('discount_id')->nullable()->constrained('discounts');
            $table->string('description')->nullable();
            $table->integer('product_count');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'expired', 'paid', 'cancelled', 'shipped', 'delivered', 'returned', 'refunded', 'failed'])->default('pending');
            $table->tinyInteger('active')->default(1);
            $table->tinyInteger('vip')->default(0);
            $table->string('fullname')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('code')->unique();
            $table->decimal('profit', 15, 2)->default(0);
            $table->decimal('profit_rate', 15, 2)->default(0);
            $table->decimal('exchange_rate', 15, 2)->default(0);
            $table->date('expire_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};