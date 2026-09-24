<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'discount')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount')->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'discount')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
