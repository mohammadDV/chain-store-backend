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
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'expire_date')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dateTime('expire_date')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'expire_date')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->date('expire_date')->nullable()->change();
            });
        }
    }
};
