<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes to commonly queried columns to improve performance
     * when dealing with large product datasets in Filament.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Index for status filtering (commonly used in filters)
            $table->index('status', 'products_status_index');

            // Index for active filtering (commonly used in filters)
            $table->index('active', 'products_active_index');

            // Index for created_at sorting (default sort column)
            $table->index('created_at', 'products_created_at_index');

            // Index for title searching (full-text search would be better, but this helps)
            $table->index('title', 'products_title_index');

            // Composite index for common filter combinations
            $table->index(['status', 'active', 'created_at'], 'products_status_active_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_index');
            $table->dropIndex('products_active_index');
            $table->dropIndex('products_created_at_index');
            $table->dropIndex('products_title_index');
            $table->dropIndex('products_status_active_created_index');
        });
    }
};