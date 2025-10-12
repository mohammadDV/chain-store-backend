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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('pre_title')->nullable();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('summary')->nullable();
            $table->text('content');
            $table->text('image')->nullable();
            $table->text('thumbnail')->nullable();
            $table->text('slide')->nullable();
            $table->bigInteger("user_id")->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger("video_id")->nullable();
            $table->tinyInteger('status')->default(0);
            $table->bigInteger('view')->default(0);
            $table->tinyInteger('type')->default(0); // 0 = normal | 1 = video
            $table->tinyInteger('special')->default(0); // 0 = normal | 1 = as slider
            $table->text('video')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};