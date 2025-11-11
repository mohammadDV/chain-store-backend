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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->tinyInteger('status')->default(0);
            $table->text('description')->nullable();
            $table->string('image', 2048)->nullable();
            $table->bigInteger('parent_id')->unsigned()->index()->default(0);
            $table->tinyInteger('priority')->unsigned()->index()->default(0);
            $table->foreignId('brand_id')->constrained('brands');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
