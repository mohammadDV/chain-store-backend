<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('delivery_amount', 15, 2)
                ->default(0)
                ->after('payment_gateway_enabled');
            $table->decimal('limit_delivery_amount', 15, 2)
                ->default(0)
                ->after('delivery_amount');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['delivery_amount', 'limit_delivery_amount']);
        });
    }
};
