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
        Schema::create('manual_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->enum('status', [
                'pending',
                'expired',
                'paid',
                'cancelled',
                'shipped',
                'delivered',
                'returned',
                'refunded',
                'failed',
            ])->default('pending');
            $table->integer('product_count')->default(1);
            $table->decimal('amount', 15, 2);
            $table->decimal('delivery_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('profit', 15, 2)->default(0);
            $table->decimal('profit_rate', 15, 2)->default(0);
            $table->decimal('exchange_rate', 15, 2)->default(0);
            $table->tinyInteger('active')->default(1);
            $table->tinyInteger('vip')->default(0);
            $table->dateTime('expire_date')->nullable();
            $table->string('product_name');
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->string('image', 2048)->nullable();
            $table->string('fullname')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('payment_receipt', 2048)->nullable();
            $table->string('refund_receipt', 2048)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_orders');
    }
};
