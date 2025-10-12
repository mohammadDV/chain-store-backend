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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('details')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('points')->default(0);
            $table->tinyInteger('rate')->default(0);
            $table->text('url')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('full_amount', 15, 2);
            $table->string('image', 2048)->nullable();
            $table->tinyInteger('active')->default(0);
            $table->enum('status',['pending', 'completed'])->default('pending'); // pending, completed
            $table->tinyInteger('vip')->default(0);
            $table->tinyInteger('priority')->default(0);
            $table->bigInteger('color_id')->unsigned()->index();
            $table->foreign('color_id')->references('id')->on('colors')->onDelete('cascade');
            $table->bigInteger('category_id')->unsigned()->index();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->bigInteger('barnd_id')->unsigned()->index();
            $table->foreign('barnd_id')->references('id')->on('brands')->onDelete('cascade');
            $table->bigInteger("user_id")->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
