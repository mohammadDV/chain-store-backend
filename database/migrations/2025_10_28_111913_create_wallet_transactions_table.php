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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal', 'transfer', 'purchase', 'refund']); // deposit, withdrawal, transfer
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IRR');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending'); // pending, completed, failed
            $table->string('reference')->unique(); // Unique transaction reference
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('walet_transactions');
    }
};
