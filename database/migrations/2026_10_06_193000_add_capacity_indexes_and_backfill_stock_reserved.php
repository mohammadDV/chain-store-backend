<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'active'], 'orders_user_id_status_active_index');
            $table->index(['expire_date', 'status'], 'orders_expire_date_status_index');
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->index(['order_id', 'size_id'], 'order_product_order_id_size_id_index');
        });

        // Backfill reserved from pending order lines for stock-managed sizes (portable SQL).
        if (Schema::hasColumn('stocks', 'reserved')) {
            $totals = DB::table('order_product')
                ->join('orders', 'orders.id', '=', 'order_product.order_id')
                ->join('products', 'products.id', '=', 'order_product.product_id')
                ->join('brands', 'brands.id', '=', 'products.brand_id')
                ->where('orders.status', 'pending')
                ->where('orders.active', 1)
                ->where('brands.has_stock_management', 1)
                ->whereNotNull('order_product.size_id')
                ->groupBy('order_product.size_id')
                ->select('order_product.size_id', DB::raw('SUM(order_product.count) as total'))
                ->pluck('total', 'size_id');

            foreach ($totals as $sizeId => $total) {
                DB::table('stocks')
                    ->where('size_id', $sizeId)
                    ->update(['reserved' => (int) $total]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_status_active_index');
            $table->dropIndex('orders_expire_date_status_index');
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->dropIndex('order_product_order_id_size_id_index');
        });
    }
};
