<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('size_id')->unique()->constrained()->onDelete('cascade');
                $table->integer('reserved')->default(0);
                $table->integer('quantity')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('sizes', 'stock')) {
            $sizes = DB::table('sizes')->select('id', 'stock', 'created_at', 'updated_at')->get();

            foreach ($sizes as $size) {
                DB::table('stocks')->insert([
                    'size_id' => $size->id,
                    'reserved' => 0,
                    'quantity' => (int) ($size->stock ?? 0),
                    'created_at' => $size->created_at ?? now(),
                    'updated_at' => $size->updated_at ?? now(),
                ]);
            }

            Schema::table('sizes', function (Blueprint $table) {
                $table->dropColumn('stock');
            });
        }

        if (Schema::hasColumn('products', 'stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('stock');
            });
        }

        // Pre-existing column used by OrderRepository but missing from original orders migration.
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'delivery_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('delivery_amount', 15, 2)->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('sizes', 'stock')) {
            Schema::table('sizes', function (Blueprint $table) {
                $table->integer('stock')->default(0)->after('code');
            });
        }

        if (Schema::hasTable('stocks')) {
            $stocks = DB::table('stocks')->select('size_id', 'quantity')->get();

            foreach ($stocks as $stock) {
                DB::table('sizes')
                    ->where('id', $stock->size_id)
                    ->update(['stock' => $stock->quantity]);
            }
        }

        if (! Schema::hasColumn('products', 'stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('stock')->default(0)->after('details');
            });
        }

        Schema::dropIfExists('stocks');
    }
};
