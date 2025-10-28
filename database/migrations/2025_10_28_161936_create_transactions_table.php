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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('amount')->unsigned();
            $table->string('reference')->nullable();
            $table->string('bank_transaction_id')->nullable();
            $table->string('image',2048)->nullable();
            $table->tinyInteger('manual')->default(0);
            $table->string('model_type')->nullable();
            $table->bigInteger("model_id")->nullable();
            $table->bigInteger("user_id")->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->string('message')->nullable();
            $table->enum('status',['pending', 'completed', 'failed', 'cancelled']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};